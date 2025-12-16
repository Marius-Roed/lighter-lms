<?php

namespace LighterLMS\Admin;

use LighterLMS\User_Access;

class Settings
{
	/** @var string|bool $tab The current tab */
	protected static $tab;

	public function __construct()
	{
		self::$tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : false;
	}

	public static function render()
	{
		self::$tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : false;

		lighter_view('settings', ['admin' => true, 'tab' => self::$tab]);
	}

	public static function save()
	{
		if (! isset($_POST['lighter_lms']) || ! wp_verify_nonce($_POST['lighter_lms'], 'lighter_lms_settings')) {
			wp_die('Nonce failure. Try again.');
		}

		if (! current_user_can('manage_options')) {
			wp_die('You do not have permission to do this');
		}

		$to_update = [];

		$editor = $_POST['default-editor'] ?? '';
		$courses = $_POST['courses'] ?? [];
		$users = $_POST['users'] ?? [];

		if ($editor) {
			$to_update['lighter_lms_default_builder'] = $editor;
		}

		if (empty($to_update)) {
			wp_redirect(add_query_arg(['error' => 'true', 'message' => 'Value cannot be empty'], admin_url(lighter_lms()->admin_page_path . '-settings')));
			exit;
		}

		if ($courses && empty($users)) {
			wp_redirect(add_query_arg(['error' => 'true', 'message' => 'Cannot give access to 0 users'], admin_url(lighter_lms()->admin_page_path . '-settings')));
			exit;
		}

		foreach ($to_update as $opt => $val) {
			update_option($opt, $val);
		}

		foreach ($users as $user) {
			$user_acc = new User_Access((int)$user);
			foreach ($courses as $course => $lessons) {
				$user_acc->grant_course_access($course, "partial", array_map('intval', explode(",", $lessons)));
			}
		}

		wp_redirect(add_query_arg('updated', 'true', wp_get_referer()));
		exit;
	}
}
