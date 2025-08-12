<?php

namespace LighterLMS\Admin;

class Admin
{
	public function __construct()
	{
		add_action('admin_menu', [$this, 'admin_menu'], 9);

		add_filter('menu_order', [$this, 'menu_order']);
	}

	public function admin_menu()
	{
		global $menu, $admin_page_hooks;

		if (current_user_can('edit_posts')) {
			$menu[] = ['', 'read', 'separator-lighter', '', 'wp-menu-separator lighter'];
		}

		add_menu_page(
			__('Lighter LMS', 'lighterlms'),
			__('Lighter LMS', 'lighterlms'),
			'edit_posts',
			'lighter-lms',
			[$this, 'course_list'],
			'dashicons-book-alt',
			38
		);
	}

	public function course_list()
	{
?>
		<h1>Hello World</h1>
<?php
	}

	public function menu_order($menu)
	{
		$lighter_order = [];

		$lighter_seperator = array_search('separator-lighter', $menu, true);

		foreach ($menu as $index => $item) {
			if ('lighter-lms' === $item) {
				$lighter_order[] = 'separator-lighter';
				$lighter_order[] = $item;
				unset($menu[$lighter_seperator]);
			} elseif (! in_array($item, ['separator-lighter'], true)) {
				$lighter_order[] = $item;
			}
		}

		return $lighter_order;
	}
}
