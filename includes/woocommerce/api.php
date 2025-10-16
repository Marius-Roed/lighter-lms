<?php

namespace LighterLMS\WooCommerce;

class API
{
	private $namespace = 'lighterlms/v1';

	public function __construct()
	{
		add_action('rest_api_init', [$this, 'lighter_wc_product']);
	}

	public function lighter_wc_product()
	{
		register_rest_route($this->namespace, '/wc', [
			'methods' => 'GET',
			'callback' => [$this, 'search_products'],
			'permission_callback' => fn() => current_user_can('edit_posts'),
		]);
	}

	public function search_products($req)
	{
		return;
	}
}
