<?php

namespace LighterLMS\Third_Party;

class Stores
{
	private static $_stores = [
		"woocommerce" => [
			"name" => ["woocommerce"],
			"slug" => "woocommerce",
			"foreground" => "#FFFFFF",
			"background" => "#873EFF",
		],
	];

	/**
	 * Get connected stores
	 *
	 * Gets all the active store plugins on the site. Accessible properties
	 * are "all", "name", "slug", "foreground" and "background". Defaults to "name".
	 * 
	 * @param string $property
	 * @return array
	 */
	public static function get_stores($property = "name")
	{
		$plugins = get_option('active_plugins');
		$stores = [];

		foreach ($plugins as $plugin) {
			$slug = substr(
				$plugin,
				strpos($plugin, '/') + 1,
				strpos($plugin, '.') - strpos($plugin, '/') - 1
			);
			if (isset(self::$_stores[$slug])) {
				$stores[] = self::$_stores[$slug];
			} elseif (isset(self::$_stores[$plugin])) {
				$stores[] = self::$_stores[$plugin];
			}
		}

		$props = [];
		if ($property == "all") {
			return $stores;
		} else if (in_array($property, array_keys(self::$_stores['woocommerce']))) {
			$props = array_column($stores, $property);
		} else {
			$props = array_column($stores, 'name');
		}

		return $props;
	}
}
