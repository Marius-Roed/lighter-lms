<?php

namespace LighterLMS\Admin;

use LighterLMS\Lessons;
use LighterLMS\User_Access;

class Admin
{
	public function __construct()
	{
		global $wpdb;
		add_action('admin_enqueue_scripts', [$this, 'admin_app']);
		add_action('admin_menu', [$this, 'admin_menu'], 9);
		add_action('current_screen', [$this, 'current_screen']);
		add_action('admin_init', [$this, 'admin_init']);
		add_action('admin_post_lighter_complete_lesson', [$this, 'complete_lesson']);

		add_filter('menu_order', [$this, 'menu_order']);
		add_filter('admin_body_class', [$this, 'dialog_editor']);
		add_filter('screen_options_show_screen', [$this, 'remove_options'], 10, 2);
	}

	public function dialog_editor($classes)
	{
		$in_dialog = $_GET['in_dialog'] ?? false;
		if ($in_dialog) {
			$classes .= ' dia-editor ';
		}

		return $classes;
	}

	public function admin_menu()
	{
		global $menu, $submenu;

		if (current_user_can('edit_posts')) {
			$menu[] = ['', 'read', 'separator-lighter', '', 'wp-menu-separator lighter'];
		}

		add_menu_page(
			__('Lighter LMS', 'lighterlms'),
			__('Lighter LMS', 'lighterlms'),
			'edit_posts',
			lighter_lms()->admin_url,
			[$this, 'app'],
			'dashicons-book-alt',
			38
		);

		add_submenu_page(
			lighter_lms()->admin_url,
			__('Settings'),
			__('Settings'),
			'edit_others_posts',
			'lighter-lms-settings',
			[$this, 'settings'],
		);
	}

	public function admin_init()
	{
		$per_page = isset($_GET['limit']) ? (int) $_GET['limit'] : 20;
		$per_page = max(1, min(100, $per_page));
		add_filter("edit_" . lighter_lms()->course_post_type . "_per_page", fn() => $per_page);
		add_filter("edit_" . lighter_lms()->lesson_post_type . "_per_page", fn() => $per_page);
	}

	public function app()
	{
?>
		<div id="lighter-lms-mount" data-screen="<?= esc_attr(get_current_screen()->id) ?>"></div>
<?php
	}

	public function settings()
	{
		Settings::render();
	}

	/**
	 * adds content for current screen
	 *
	 * @param \WP_Screen $screen The current screen object
	 */
	public function current_screen($screen)
	{
		$screen_id = strpos($screen->id, 'edit-') !== false ? substr($screen->id, 5) : $screen->id;
		if (str_contains($screen_id, 'lighter-lms') || in_array($screen_id, lighter_lms()->post_types)) {
			add_action('in_admin_header', [$this, 'in_admin_header']);

			add_filter('admin_body_class', [$this, 'admin_body_class']);
		}
	}

	/**
	 * removes the screen options from the course table list
	 * 
	 * @param bool $show
	 * @param \WP_Screen $srceen
	 */
	public function remove_options($show, $screen)
	{
		if (
			$screen->id === 'edit-' . lighter_lms()->course_post_type ||
			$screen->id === 'edit-' . lighter_lms()->lesson_post_type
		) {
			$show = false;
		}
		return $show;
	}

	public function admin_body_class($classNames)
	{
		if (strpos($classNames, 'lighter') === false) {
			$classNames .= ' lighter';
		}

		return $classNames;
	}

	public function in_admin_header()
	{
		$screen = get_current_screen();

		if (isset($screen->base) && 'post' === $screen->base) {
			$this->in_cpt_header();
		}

		if (isset($screen->base) && ('edit' === $screen->base && in_array($screen->post_type, lighter_lms()->post_types))) {
			$this->show_header();
		}
	}

	public function in_cpt_header()
	{
		lighter_view('form-top', ['admin' => true]);
	}

	public function show_header()
	{
		lighter_view('post-list-header', ['admin' => true]);
	}

	public function menu_order($menu)
	{
		global $submenu;

		$lighter_order = [];
		$temp = null;

		$lighter_seperator = array_search('separator-lighter', $menu, true);

		foreach ($menu as $index => $item) {
			if (lighter_lms()->admin_url === $item) {
				$lighter_order[] = 'separator-lighter';
				$lighter_order[] = $item;
				unset($menu[$lighter_seperator]);

				foreach ($submenu[$item] as $idx => $sub) {
					if ('lighter-lms-settings' === $sub[2]) {
						$temp = [$idx, $sub];
						break;
					}
				}

				if ($temp) {
					list($idx, $tab) = $temp;
					$submenu[$item][] = $tab;
					unset($submenu[$item][$idx]);
				}
			} elseif (! in_array($item, ['separator-lighter'], true)) {
				$lighter_order[] = $item;
			}
		}

		return $lighter_order;
	}

	public function admin_app($hook_suffix)
	{
		wp_enqueue_style('lighter-dia-editor', LIGHTER_LMS_URL . 'assets/css/dialog-editor.css', [], LIGHTER_LMS_VERSION);
		wp_enqueue_style('lighter-lms-admin', LIGHTER_LMS_URL . 'assets/css/admin.css', [], LIGHTER_LMS_VERSION, false);

		$handle = 'lighter-lms';

		$screen_map = [
			'toplevel_page_lighter-lms' => [
				'entry' => 'dashboard',
				'dev' => 'src/screens/dashboard/main.js',
			],
			'edit-lighter_courses' => [
				'entry' => 'pages',
				'dev' => 'src/screens/pages/main.js',
			],
			'edit-lighter_lessons' => [
				'entry' => 'pages',
				'dev' => 'src/screens/pages/main.js',
			],
			'lighter_courses' => [
				'entry' => 'courses',
				'dev' => 'src/screens/courses/main.js',
			],
			'lighter_lessons' => [
				'entry' => 'lessons',
				'dev' => 'src/screens/lessons/main.js',
			],
			// 'lighter-lms-settings' => [
			//	'entry' => 'settings',
			//	'dev' => 'src/screens/settings/main.js',
			//]
		];

		$screen_id = function_exists('get_current_screen') ? get_current_screen()->id : $hook_suffix;

		if (! isset($screen_map[$screen_id])) {
			return;
		}

		// wp_enqueue_script('lighterlms-object', LIGHTER_LMS_URL . 'assets/js/lighterlms.js', [], LIGHTER_LMS_VERSION, true);
		wp_enqueue_script('lighterlms-hooks', LIGHTER_LMS_URL . 'assets/dist/lighterlms-hooks.js', [], LIGHTER_LMS_VERSION, true);
		wp_enqueue_script('wp-tinymce');
		wp_enqueue_editor();
		wp_enqueue_media();

		$lighter_lms = [
			'restUrl' => esc_url(rest_url('lighterlms/v1/')),
			'nonce' => wp_create_nonce('wp_rest'),
			'machineId' => apply_filters('lighterlms_use_machine_id', 0)
		];

		$lighter_lms = apply_filters('lighter_admin_object', $lighter_lms, $screen_id);

		wp_localize_script('lighterlms-hooks', 'LighterLMS', $lighter_lms);

		$entry_key = $screen_map[$screen_id]['entry'];
		$entry_dev = $screen_map[$screen_id]['dev'];

		if ($screen_map[$screen_id]['entry'] === 'pages') {
			wp_add_inline_script('lighterlms-hooks', 'var lighterCourses = ' . lighter_postlist_js_obj(lighter_lms()->course_post_type), 'before');
		}

		if (lighter_lms()->development) {
			$local_server = 'http://localhost:5173/';
			wp_enqueue_script_module('vite-client', $local_server . '@vite/client', [], null, true);
			wp_enqueue_script_module($handle . '-' . $entry_key, $local_server . $entry_dev, ['vite-client'], null, true);
		} else {
			$manifest_path = LIGHTER_LMS_PATH . '/assets/dist/.vite/manifest.json';
			$manifest      = json_decode(file_get_contents($manifest_path), true);

			// Entry is keyed by its file path used in 'input' (vite resolves to names)
			// Vite manifest keys match the resolved filenames like 'js/[name].js'
			// Use the name to look up the entry record:
			$entry_js = 'js/lighter-' . $entry_key . '.js';

			if (empty($manifest[$entry_js])) {
				// Fallback: try to find by name
				foreach ($manifest as $k => $info) {
					if (isset($info['isEntry']) && $info['isEntry'] && str_contains($k, $entry_key)) {
						$entry_js = $k;
						break;
					}
				}
			}

			if (empty($manifest[$entry_js])) {
				return;
			}

			$entry_info = $manifest[$entry_js];

			// Enqueue JS
			wp_enqueue_script_module(
				$handle . '-' . $entry_key,
				LIGHTER_LMS_URL . '/assets/dist/' . $entry_info['file'],
				array(),
				null,
				true
			);

			// Enqueue vendor chunk if present and not inlined
			if (! empty($entry_info['imports'])) {
				foreach ($entry_info['imports'] as $import_key) {
					if (! empty($manifest[$import_key]['file'])) {
						wp_enqueue_script_module(
							$handle . '-' . $entry_key . '-import-' . md5($import_key),
							LIGHTER_LMS_URL . '/assets/dist/' . $manifest[$import_key]['file'],
							array(),
							null,
							true
						);
					}
				}
			}

			// Enqueue CSS for this entry
			if (! empty($entry_info['css'])) {
				foreach ($entry_info['css'] as $css_file) {
					wp_enqueue_style(
						$handle . '-' . $entry_key,
						LIGHTER_LMS_URL . '/assets/dist/' . $css_file,
						array(),
						null
					);
				}
			}
		}
	}

	public function complete_lesson()
	{
		$user = new User_Access();
		$course_id = intval($_POST['course_id'] ?? 0);
		$lesson_id = intval($_POST['lesson_id'] ?? 0);
		if (!$user->check_course_access($course_id) || !$user->check_lesson_access($lesson_id, $course_id)) {
			wp_die('Unauthorized access', '', ['response' => 403]);
		}

		$nonce = sanitize_text_field($_POST['lighter_lesson_nonce'] ?? '');
		if (!wp_verify_nonce($nonce, 'complete_lesson')) {
			wp_die('Security fail. Nonce failure', '', ['response' => 403]);
		}

		if (get_post_type($lesson_id) !== lighter_lms()->lesson_post_type) {
			wp_die('Invalid lesson id.', '', ['response' => 403]);
		}

		$user->complete_lesson($course_id, $lesson_id);

		$lessons = new Lessons(['parent' => $course_id]);
		$lessons = $lessons->get_lessons();

		$next_lesson = null;
		$next = false;

		foreach ($lessons as $lesson) {
			if ($next) {
				$next_lesson = $lesson;
				break;
			}
			if ($lesson->ID == $lesson_id) $next = true;
		}

		if ($next_lesson && $user->check_lesson_access($next_lesson, $course_id)) {
			$slug = get_post($course_id)->post_name;
			if ($slug) {
				wp_redirect($slug . '?lesson=' . $next_lesson->post_name);
				exit;
			}
		}
		wp_redirect(wp_get_referer());
		exit;
	}
}
