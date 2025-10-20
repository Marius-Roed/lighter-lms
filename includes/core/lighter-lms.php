<?php

namespace LighterLMS\Core;

use LighterLMS\Admin\Admin;
use LighterLMS\API\API;
use LighterLMS\Assets;
use LighterLMS\Post_Types;

class Lighter_LMS
{
	private static $_instance = null;

	/**
	 * Internal post types object
	 *
	 * @var object
	 */
	private $_post_types;

	/**
	 * Course class object
	 *
	 * @var object
	 */
	private $_course;

	/**
	 * Lesson class object
	 *
	 * @var object
	 */
	private $_lesson;

	/**
	 * Admin class object
	 * 
	 * @var object
	 */
	public $admin;

	/**
	 * All registered assets
	 *
	 * @var object
	 */
	private $_assets;

	/**
	 * Api class object
	 *
	 * @var object
	 */
	private $_api;

	public static function get_instance()
	{
		if (null == self::$_instance) {
			self::$_instance = new self();
		}

		return self::$_instance;
	}

	private function __construct()
	{
		$this->includes();

		do_action('lighter_lms_before_load');

		add_action('init', [$this, 'init_update_checker'], 5);

		// $this->_course = new Course();
		// $this->_lesson = new Lesson();
		$this->admin = new Admin();
		$this->_assets = new Assets();
		$this->_post_types = new Post_Types();
		$this->_api = new API();
	}

	/**
	 * Inlcude any file not loaded by the autoloader
	 */
	public function includes()
	{
		include LIGHTER_LMS_PATH . 'includes/lighter-lms-functions.php';
	}

	public function init_update_checker()
	{
		if (! is_admin() && !defined('DOING_CRON')) {
			return;
		}

		add_filter('pre_set_site_transient_update_plugins', [$this, 'check_update']);
	}

	public function check_update($transient)
	{
		if (empty($transient->checked)) {
			return $transient;
		}

		$plugin_slug = 'lighter-lms';
		$plugin_file = $plugin_slug . '/' . $plugin_slug . '.php';

		if (! function_exists('get_plugin_data')) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		$installed_ver = get_plugin_data(WP_PLUGIN_DIR . '/' . $plugin_file, false, false)['Version'] ?? LIGHTER_LMS_VERSION;

		$c_key = 'lighter_lms_update_check';
		$cached = get_transient($c_key);
		if (false !== $cached && isset($cached['last_check']) && $cached['last_check'] > (current_time('timestamp') - 2 * HOUR_IN_SECONDS)) {
			if (version_compare($cached['remote_version'], $installed_ver, '>')) {
				$transient->response[$plugin_file] = $cached['update_data'];
			}

			return $transient;
		}

		$remote_url = "https://api.github.com/repos/Marius-Roed/lighter-lms/releases/latest";

		$resp = wp_remote_get($remote_url, [
			'timeout' => 10,
			'sslverify' => true,
			'headers' => [
				'User-Agent' => 'WordPress/' . get_bloginfo('version') . '; ' . home_url(),
			],
		]);

		if (is_wp_error($resp)) {
			error_log('LighterLMS update check: API Error - ' . $resp->get_error_message());
			return $transient;
		}

		$resp_code = wp_remote_retrieve_response_code($resp);
		if ($resp_code !== 200) {
			error_log('LighterLMS update check: Non-200 response (' . $resp_code . ')');
			return $transient;
		}

		$resp_body = wp_remote_retrieve_body($resp);
		$resp_data = json_decode($resp_body, true);

		if (!isset($resp_data['tag_name']) || !isset($resp_data['zipball_url'])) {
			error_log('LighterLMS update check: Invalid GitHub response');
			return $transient;
		}

		$remote_version = preg_replace('/^v/', '', $resp_data['tag_name']);

		if (version_compare($remote_version, $installed_ver, '>')) {
			$update_data = (object)[
				'slug' => $plugin_slug,
				'new_version' => $remote_version,
				'url' => 'https://github.com/marius-roed/lighter-lms',
				'package' => $resp_data['zipball_url'],
				'tested' => '6.8.3',
				'requires' => '5.3',
				'requires_php' => '8.2',
				'icons' => [],
			];

			$transient->response[$plugin_file] = $update_data;

			set_transient($c_key, [
				'remote_version' => $remote_version,
				'update_data' => $update_data,
				'last_check' => current_time('timestamp'),
			], 2 * HOUR_IN_SECONDS);
		} else {
			delete_transient($c_key);
		}

		return $transient;
	}
}
