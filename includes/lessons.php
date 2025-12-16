<?php

namespace LighterLMS;

use wpdb;

class Lessons
{
	/** @var wpdb */
	protected $db;

	/** @var string */
	protected $table;

	/** @var int The lesson ID */
	protected $lesson;

	/** @var int The parent ID */
	protected $parent;

	/** @var int The topic ID */
	protected $topic;

	/** @var array The given arguments */
	protected $args;

	public function __construct($args = [], $db = null)
	{
		global $wpdb;

		$this->db = $db ?: $wpdb;
		$this->table = $this->db->prefix . 'lighter_lessons';

		$this->lesson = isset($args['lesson']) && (int)$args['lesson'] > 0 ? (int)$args['lesson'] : null;
		$this->parent = isset($args['parent']) && (int)$args['parent'] > 0 ? (int)$args['parent'] : null;
		$this->topic = isset($args['topic']) && (int)$args['topic'] > 0 ? (int)$args['topic'] : null;

		$this->args = $args;
	}

	public function install()
	{
		$charset_collate = $this->db->get_charset_collate();

		$sql = "CREATE TABLE {$this->table} (
		  ID bigint(20) unsigned NOT NULL AUTO_INCREMENT,
		  lesson_id bigint(20) unsigned NOT NULL,
		  parent_id bigint(20) unsigned NULL,
		  topic_id bigint(20) unsigned NULL,
		  PRIMARY KEY (ID),
		  KEY idx_parent_id (parent_id),
		  KEY idx_lesson_parent (lesson_id, parent_id),
		  KEY idx_topic_parent (topic_id, parent_id),
		  UNIQUE KEY unique_lesson_parent_topic (lesson_id, parent_id, topic_id),
		  FOREIGN KEY (lesson_id) REFERENCES {$this->db->posts}(ID) ON DELETE CASCADE ON UPDATE CASCADE,
		  FOREIGN KEY (parent_id) REFERENCES {$this->db->posts}(ID) ON DELETE CASCADE ON UPDATE CASCADE,
		  FOREIGN KEY (topic_id) REFERENCES {$this->db->prefix}lighter_topics(ID) ON DELETE CASCADE ON UPDATE CASCADE
		) $charset_collate";

		require_once ABSPATH . '/wp-admin/includes/upgrade.php';
		dbDelta($sql);
	}

	/**
	 * @param string $join
	 * @param \WP_Query $query
	 */
	public function db_join($join, $query)
	{
		if ($query->is_main_query() || !in_array($query->get('post_type'), lighter_lms()->post_types)) {
			return $join;
		}

		$vars = $query->query_vars;

		if (isset($vars['lesson_parent']) && intval($vars['lesson_parent'])) {
			$join .= " INNER JOIN {$this->table} AS ll ON {$this->db->posts}.ID = ll.parent_id AND ll.lesson_id = " . intval($vars['lesson_parent']);
			return $join;
		}

		if (isset($vars['lighter_course']) && intval($vars['lighter_course'])) {
			$join .= " INNER JOIN {$this->table} AS ll ON {$this->db->posts}.ID = ll.lesson_id AND ll.parent_id = " . intval($vars['lighter_course']);
			$join .= " INNER JOIN {$this->db->postmeta} AS meta_sort ON {$this->db->posts}.ID = meta_sort.post_id AND meta_sort.meta_key = '_lighter_sort_order'";
			$join .= " INNER JOIN {$this->db->postmeta} AS meta_topic ON {$this->db->posts}.ID = meta_topic.post_id AND meta_topic.meta_key = '_lighter_parent_topic'";
			$join .= " INNER JOIN {$this->db->prefix}lighter_topics AS topic ON topic.topic_key = meta_topic.meta_value AND topic.post_id = " . intval($vars['lighter_course']);
			return $join;
		}

		return $join;
	}

	public function db_orderby($orderby, $query)
	{
		if ($query->is_main_query() || !in_array($query->get('post_type'), lighter_lms()->post_types)) {
			return $orderby;
		}

		$vars = $query->query_vars;

		if (isset($vars['lighter_course']) && intval($vars['lighter_course'])) {
			$orderby = " topic.sort_order ASC, CAST(meta_sort.meta_value AS UNSIGNED) ASC";
		}

		return $orderby;
	}

	/**
	 * Get lesson(s)
	 *
	 * Uses the given args to get a(ll) lesson(s) by topic key or, if not supplied, by parent ID.
	 * Currently supplying only a lesson ID will just return the lesson Post object.
	 *
	 * @param {Array_A} $args - Alternative args to the class properties
	 *
	 * @return \WP_Post|array
	 */
	public function get_lessons($args = [])
	{
		$defaults = [
			'topic' => $this->topic ?? false,
			'parent' => $this->parent ?? false,
			'lesson' => $this->lesson ?? false,
			'status' => $this->args['status'] ?? null,
		];

		$args = wp_parse_args($args, $defaults);

		if ($args['topic']) {
			// TODO: Query by topic key.
			if (is_numeric($args['topic'])) {
				$lessons = $this->db->get_results(
					$this->db->prepare(
						'SELECT %1$s.* FROM %2$s INNER JOIN %3$s ON %4$s.ID = %5$s.lesson_id WHERE %6$s.topic_id = %7$s',
						$this->db->posts,
						$this->db->posts,
						$this->table,
						$this->db->posts,
						$this->table,
						$this->table,
						$args['topic']
					)
				);
				return $lessons;
			}
		}

		if ($args['parent']) {
			// TODO: Query by parent ID.
			$args = [
				'post_type' => lighter_lms()->lesson_post_type,
				'post_status' => $args['status'] ?? 'publish',
				'lighter_course' => $args['parent'],
				'numberposts' => -1,
				'suppress_filters' => false,
			];

			return get_posts($args);
		}

		if ($args['lesson']) {
			return get_post($this->lesson);
			// TODO: Query by lesson ID.
		}

		return [];
	}

	/**
	 * Save a lesson.
	 *
	 * Saves a lesson to the Lighter Lesson DB table with given information.
	 */
	public function save()
	{
		if ($this->db->query($this->db->prepare("SELECT * FROM {$this->table} WHERE parent_id = {$this->parent} AND lesson_id = {$this->lesson}"))) {
			return $this->db->update(
				$this->table,
				[
					'topic_id' => $this->topic
				],
				[
					'parent_id' => $this->parent,
					'lesson_id' => $this->lesson,
				],
				['%d'],
			);
		}
		$this->db->insert(
			$this->table,
			[
				'lesson_id' => $this->lesson,
				'parent_id' => $this->parent,
				'topic_id' => $this->topic
			],
			[
				'%d',
				'%d',
				'%d'
			],
		);
	}
}
