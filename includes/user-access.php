<?php

namespace LighterLMS;

use DateTime;

class User_Access
{
	private string $owned_courses = '_lighter_owned_courses';
	private string $course_progress = '_lighter_course_progress';

	protected \WP_User $user;
	protected array $owned = [];

	/**
	 * @param int|\WP_User|null $user_id The ID of the user. Will default to current logged in user or 0 for guests.
	 */
	public function __construct($user = null)
	{
		$this->user = isset($user) ? (is_int($user) ? new \WP_User($user) : $user) : wp_get_current_user();
		$owned = get_user_meta($this->user->ID, $this->owned_courses, true);
		$this->owned = $owned ? json_decode($owned, true) : [];
	}

	/**
	 * Grant course access
	 *
	 * Grants the user access to a course.
	 *
	 * @param int $course_id The ID of the course to grant access to.
	 * @param string $access_type Which type of access to give the user. Accepts "full" | "drip" | "partial".
	 * @param array $unlock The custom lessons which should be granted access to. Only used when `$access_type` is "partial"
	 * @param DateTime $start_date The start date.
	 * @param string $drip_interval The drip interval.
	 * @param DateTime $expires When access should expire.
	 */
	public function grant_course_access($course_id, $access_type = 'full', $unlock = [], $start_date = null, $drip_interval = null, $expires = null)
	{
		if (!$this->user->ID) {
			return;
		}
		$lesson_query = new Lessons(['parent' => $course_id]);
		$lessons = array_map(fn($post) => $post->ID, $lesson_query->get_lessons());
		$exists = false;
		foreach ($this->owned as &$entry) {
			if ($entry['course_id'] == $course_id) {
				$exists = true;
				$entry['access_type'] = $access_type;
				break;
			}
		}

		if (!$exists) {
			$this->owned[] = [
				'course_id' => $course_id,
				'access_type' => $access_type,
				'start_date' => $start_date ?: current_time('mysql'),
				'drip_interval' => $drip_interval,
				'expires' => $expires,
			];
		}
		update_user_meta($this->user->ID, $this->owned_courses, wp_json_encode($this->owned));

		$progress = get_user_meta($this->user->ID, $this->course_progress, true);
		$progress = $progress ? json_decode($progress, true) : [];
		if (!isset($progress[$course_id])) {
			// NOTE: Should probably have a $unlocked_lessons param
			switch ($access_type) {
				case 'full':
					$unlocked_lessons = $lessons;
					break;
				case 'drip':
					$unlocked_lessons = count($lessons[0]) > 2 ? array_slice($lessons[0], 0, 2) : $lessons[0];
					break;
				case 'partial':
					$unlocked_lessons = $unlock;
					break;
				default:
					$unlocked_lessons = $lessons;
					break;
			}
			$progress[$course_id] = [
				'max_unlocked_lesson' => 0,
				'unlocked_lessons' => $unlocked_lessons,
				'completed_lessons' => [],
				'completion_date' => null
			];
			update_user_meta($this->user->ID, $this->course_progress, wp_json_encode($progress));
		}
		delete_transient('lighter_lms_access_check_' . $this->user->ID . '_*');
	}

	/**
	 * Revoke course access
	 *
	 * Revokes the users access to a specific course
	 * NOTE: This leaves the progress on the course, should the user get access again at a later point.
	 *
	 * @param int $course_id The id of the course to revoke
	 */
	public function revoke_course_access($course_id)
	{
		if (!$this->user->ID) {
			return;
		}

		$exists = false;
		foreach ($this->owned as &$entry) {
			if ($entry['course_id'] == $course_id) {
				$exists = true;
				$entry['access_type'] = 'revoked';
				break;
			}
		}

		if (!$exists) {
			error_log(
				sprintf(
					"Lighter LMS: Tried to revoke course access for user (%d) on course they don\'t own: %d (%s)",
					$this->user->ID,
					$course_id,
					get_the_title($course_id)
				)
			);
			return;
		}

		unset($this->owned[$course_id]);

		update_user_meta($this->user->ID, $this->owned_courses, wp_json_encode($this->owned));

		delete_transient('lighter_lms_access_check_' . $this->user->ID . '_*');
	}

	/**
	 * Check user course access
	 *
	 * Checks whether the user has access to a course. Will default to global $post.
	 *
	 * @param int|\WP_Post|null $course_id The ID or Post object of the course to check the access of. Will default to the global $post if none is provided
	 *
	 * @return bool Whether the user has access to the specified course.
	 */
	public function check_course_access($course_id = null)
	{
		if (!$this->user->ID || !is_user_logged_in()) return false;

		if ($this->user->has_cap('manage_options')) return true;

		$post = get_post($course_id);
		$cache_key = 'lighter_lms_access_check_' . $this->user->ID . '_' . $post->ID;
		$has_access = get_transient($cache_key);
		if (false !== $has_access) return $has_access;

		foreach ($this->owned as $entry) {
			if ($entry['course_id'] == $post->ID) {
				if ($entry['expires'] && current_time('timestamp') > strtotime($entry['expires'])) {
					return false;
				} elseif ($entry['access_type'] == 'revoked') {
					return false;
				}
				$has_access = true;
				break;
			}
		}

		if (isset($has_access)) {
			set_transient($cache_key, $has_access, HOUR_IN_SECONDS);
			return $has_access;
		}
		set_transient($cache_key, false, HOUR_IN_SECONDS);
		return false;
	}

	/**
	 * Check user lesson access
	 *
	 * Checks the users access of a courses lesson.
	 *
	 * @param int $lesson_id
	 * @param int $course_id
	 *
	 * @return bool Whether the user has access
	 */
	public function check_lesson_access($lesson_id, $course_id)
	{
		if ($this->user->has_cap('manage_options')) return true;

		$has_course_access = $this->check_course_access($course_id);

		if (!$has_course_access) return false;

		$progress = get_user_meta($this->user->ID, $this->course_progress, true);
		$progress = $progress ? json_decode($progress, true) : [];

		if (!isset($progress[$course_id])) return false;

		if (in_array($lesson_id, $progress[$course_id]['unlocked_lessons'])) return true;

		return false;
	}

	public function get_owned($course = null)
	{
		$owned = $this->owned;

		if ($course) {
			$course = get_post($course);
			$key = array_key_first(array_filter($owned, fn($access) => $access['course_id'] == $course->ID) ?? []);
			if (!empty($owned)) {
				$owned = $owned[$key];
			}
		}

		return $owned;
	}

	public function get_progress($course = null)
	{
		$progress = get_user_meta($this->user->ID, $this->course_progress, true);
		$progress = $progress ? json_decode($progress, true) : [];

		if ($course) {
			$course = get_post($course);
			$progress = $progress[$course->ID];
		}

		return $progress;
	}

	/**
	 * Marks a lesson as completed.
	 *
	 * @param \WP_Post|int $course
	 * @param \WP_Post|int $lesson
	 *
	 * @return int 1 for success 0 for failure.
	 */
	public function complete_lesson($course, $lesson)
	{
		$course = get_post($course);
		$lesson = get_post($lesson);

		// TODO: Check user owns course and lesson.

		$progress = get_user_meta($this->user->ID, $this->course_progress, true);
		$progress = $progress ? json_decode($progress, true) : [];

		$completed = $progress[$course->ID]['completed_lessons'] ?: [];
		$completed[] = $lesson->ID;
		$completed = array_unique($completed, SORT_NUMERIC);
		$progress[$course->ID]['completed_lessons'] = $completed;

		if (!update_user_meta($this->user->ID, $this->course_progress, wp_json_encode($progress))) {
			return 0;
		}

		return 1;
	}
}
