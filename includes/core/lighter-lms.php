<?php

namespace LighterLMS\Core;

use LighterLMS\Admin\Admin;
use LighterLMS\API\API;
use LighterLMS\Assets;
use LighterLMS\Admin\Settings;
use LighterLMS\Course_Post;
use LighterLMS\Lesson_Post;

class Lighter_LMS
{
	private static $_instance = null;

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

	/**
	 * Plugin slug
	 *
	 * @var string;
	 */
	public $slug = 'lighter-lms';

	/**
	 * Remote repo url
	 *
	 * @var string;
	 */
	private $_remote_url = "https://api.github.com/repos/Marius-Roed/lighter-lms";

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
		add_action('admin_post_save_lighter_lms_settings', [Settings::class, 'save']);

		$this->_course = new Course_Post();
		$this->_lesson = new Lesson_Post();
		if (is_admin()) {
			$this->admin = new Admin();
		}
		$this->_assets = new Assets();
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
		add_filter('plugins_api', [$this, 'get_plugin_info'], 10, 3);
	}

	public function check_update($transient)
	{
		if (empty($transient->checked)) {
			return $transient;
		}

		$plugin_file = $this->slug . '/' . $this->slug . '.php';

		if (! function_exists('get_plugin_data')) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		$installed_ver = get_plugin_data(WP_PLUGIN_DIR . '/' . $plugin_file, false, false)['Version'] ?? LIGHTER_LMS_VERSION;

		$c_key = 'lighter_lms_update_check';
		$cached = get_transient($c_key);
		if (false !== $cached && isset($cached['last_check']) && $cached['last_check'] > (current_time('timestamp') - 0.5 * HOUR_IN_SECONDS)) {
			if (version_compare($cached['remote_version'], $installed_ver, '>')) {
				$transient->response[$plugin_file] = $cached['update_data'];
			}

			return $transient;
		}

		$releases = wp_remote_get($this->_remote_url . '/releases', [
			'timeout' => 10,
			'sslverify' => true,
			'headers' => [
				'User-Agent' => 'WordPress/' . get_bloginfo('version') . '; ' . home_url(),
			],
		]);

		if (is_wp_error($releases)) {
			error_log('LighterLMS update check: API Error - ' . $releases->get_error_message());
			return $transient;
		}

		$releases_code = wp_remote_retrieve_response_code($releases);
		if ($releases_code !== 200) {
			error_log('LighterLMS update check: Non-200 response (' . $releases_code . ')');
			return $transient;
		}

		$releases_body = wp_remote_retrieve_body($releases);
		$releases_data = json_decode($releases_body, true);

		$latest = $releases_data[0];

		if (!isset($latest['tag_name']) || !isset($latest['assets']) || empty($latest['assets'])) {
			error_log('LighterLMS update check: Invalid GitHub response - no tag or assets');
			return $transient;
		}

		$remote_version = preg_replace('/^v/', '', $latest['tag_name']);

		$package_url = null;
		foreach ($latest['assets'] as $asset) {
			if (
				strpos($asset['name'], 'lighterlms') !== false &&
				strpos($asset['name'], $remote_version) !== false &&
				pathinfo($asset['name'], PATHINFO_EXTENSION) === 'zip' &&
				$asset['content_type'] === 'application/zip'
			) {

				$package_url = $asset['browser_download_url'];
				break;
			}
		}

		if (!$package_url) {
			error_log('LighterLMS update check: No valid or matching zip found in release');
			return $transient;
		}

		if (version_compare($remote_version, $installed_ver, '>')) {
			$update_data = (object)[
				'slug' => $this->slug,
				'new_version' => $remote_version,
				'url' => 'https://github.com/marius-roed/lighter-lms',
				'package' => $package_url,
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
			], 0.5 * HOUR_IN_SECONDS);
		} else {
			delete_transient($c_key);
		}

		return $transient;
	}

	public function get_plugin_info($result, $action, $args)
	{
		if ($action !== 'plugin_information' || empty($args->slug) || $args->slug !== 'lighter-lms') {
			return $result;
		}

		$c_key = 'lighter_lms_info';
		$c_info = get_transient($c_key);

		if (false === $c_info) {
			$releases = wp_remote_get($this->_remote_url . '/releases', [
				'timeout' => 10,
				'sslverify' => true,
				'headers' => [
					'User-Agent' => 'WordPress/' . get_bloginfo('version') . '; ' . home_url(),
				],
			]);

			if (is_wp_error($releases) || wp_remote_retrieve_response_code($releases) !== 200) {
				return $result;
			}

			$releases_body = wp_remote_retrieve_body($releases);
			$releases_data = json_decode($releases_body, true);

			if (empty($releases_data)) {
				return $result;
			}

			$latest = $releases_data[0];

			if (!isset($latest['tag_name'])) {
				return $result;
			}

			$c_info = [
				'tag_name' => $latest['tag_name'],
				'body' => $latest['body'] ?? 'No release notes available.',
				'published_at' => $latest['published_at'] ?? date('c'),
				'author' => $latest['author']['login'] ?? 'Unknown',
			];

			set_transient($c_key, $c_info, 0.5 * HOUR_IN_SECONDS);
		}

		$remote_version = preg_replace('/^v/', '', $c_info['tag_name']);

		$sections = $this->_parse_changelog($c_info['body']);

		$plugin_info = (object) [
			'name' => 'Lighter LMS',
			'slug' => $this->slug,
			'version' => $remote_version,
			'author' => '<a href="https://github.com/' . $c_info['author'] . '">' . $c_info['author'] . '</a>',
			'author_profile' => 'https://github.com/' . $c_info['author'],
			'requires' => '5.3',
			'requires_php' => '8.2',
			'tested' => '6.8.3',
			'requires_wp' => '5.3',
			'requires_wp_php' => '8.2',
			'rating' => 5,
			'ratings' => [],
			'num_ratings' => 0,
			'downloaded' => 0,
			'active_installs' => 0,
			'last_updated' => $c_info['published_at'],
			'homepage' => 'https://github.com/Marius-Roed/lighter-lms',
			'description' => 'Lighter LMS is a lightweight LMS plugin for WordPress.',
			'short_description' => 'Lightweight LMS plugin for WordPress.',
			'sections' => $sections, // "description", "changelog", "installation", etc.
			'download_link' => '', // Not used for external plugins, but include for completeness
			'is_commercial' => false,
			'type' => 'plugin',
			'external' => true,
		];

		return $plugin_info;
	}

	private function _parse_changelog($body)
	{
		$sections = [
			'description' => 'Lighter LMS is a lightweight LMS plugin for WordPress.',
			'changelog' => $body,
			'installation' => 'Extract and upload the lighter-lms folder to /wp-content/plugins/. Activate in WordPress.',
		];

		if (preg_match_all('/^## (.+)$/m', $body, $headers, PREG_OFFSET_CAPTURE)) {
			foreach ($headers[1] as $index => $header) {
				$section_name = strtolower(str_replace(' ', '_', trim($header[0])));
				$section_name = preg_replace('/[^a-z0-9_]/', '', $section_name);

				$start = $headers[0][$index][1] + strlen($headers[0][$index][0]) + 1;
				$end = isset($headers[0][$index + 1]) ? $headers[0][$index + 1][1] : strlen($body);
				$content = substr($body, $start, $end - $start);

				if (!empty(trim($content))) {
					$sections[$section_name] = wp_kses_post(trim($content));
				}
			}
		}

		return $sections;
	}
}
