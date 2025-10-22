<?php

namespace LighterLMS\Admin;

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

		$editor = isset($_POST['default-editor']) ? $_POST['default-editor'] : '';

		if ($editor) {
			$to_update['lighter_lms_default_builder'] = $editor;
		}

		if (empty($to_update)) {
			wp_redirect(add_query_arg(['error' => true, 'message' => 'Value cannot be empty'], wp_get_referer()));
		}

		foreach ($to_update as $opt => $val) {
			update_option($opt, $val);
		}

		wp_redirect(add_query_arg('updated', 'true', wp_get_referer()));
		exit;
	}
}
