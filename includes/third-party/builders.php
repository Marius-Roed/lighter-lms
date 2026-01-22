<?php

namespace LighterLMS\Third_Party;

class Builders
{
	private static $_builders = [
		"fl-builder" => [
			"name" => ["Beaver Builder", "Beaver Builder Plugin (Lite Version)"],
			"slug" => "beaver-builder",
			"foreground" => "#00000000",
			"background" => "#FEAF52",
		],
		"brizy" => [
			"name" => ["Brizy"],
			"slug" => "brizy",
			"foreground" => "#00000000",
			"background" => "#0E0736",
		],
		"breakdance/plugin.php" => [
			"name" => ["Breakdance"],
			"slug" => "breakdance",
			"foreground" => "#000000",
			"background" => "#FFC514",
		],
		"classic-editor" => [
			"name" => ["Classic Editor"],
			"slug" => "classic-editor",
			"foreground" => "#25719B",
			"background" => "#F0F0F1",
		],
		"cornerstone-page-builder" => [
			"name" => ["Cornerstone Page Builder"],
			"slug" => "cornerstone",
			"foreground" => "#00000000",
			"background" => "#00000000",
		],
		"divi" => [
			"name" => ["Divi Builder"],
			"slug" => "divi",
			"foreground" => "#FFFFFFF",
			"background" => "#00000000",
		],
		"elementor" => [
			"name" => ["Elementor", "Elementor Pro"],
			"slug" => "elementor",
			"foreground" => "#F0F0F1",
			"background" => "#92003B",
		],
		"gutenberg" => [
			"name" => ["Gutenberg"],
			"slug" => "gutenberg",
			"foreground" => "#1E1E1E",
			"background" => "#F0F0F1",
		],
		"fusion-builder" => [
			"name" => ["Fusion Builder"],
			"slug" => "fusion",
			"foreground" => "#FFFFFF",
			"background" => "#50B3C4",
		],
		"flatsome-Builder" => [
			"name" => ["Flatsome UX Builder"],
			"slug" => "flatsome",
			"foreground" => "#00000000",
			"background" => "#00000000",
		],
		"kingcomposer" => [
			"name" => ["KingComposer"],
			"slug" => "kingcomposer",
			"foreground" => "#00000000",
			"background" => "#00000000",
		],
		"live-composer" => [
			"name" => ["Live Composer"],
			"slug" => "live-composer",
			"foreground" => "#00000000",
			"background" => "#2EDCE7",
		],
		"layers" => [
			"name" => ["Layers WP"],
			"slug" => "layers",
			"foreground" => "#00000000",
			"background" => "#00000000",
		],
		"motopress-content-editor" => [
			"name" => ["MotoPress Content Editor"],
			"slug" => "motopress",
			"foreground" => "#00000000",
			"background" => "#00000000",
		],
		"oxygen-builder" => [
			"name" => ["Oxygen Builder"],
			"slug" => "oxygen",
			"foreground" => "#FFFFFF",
			"background" => "#000000",
		],
		"siteorigin-page-builder" => [
			"name" => ["SiteOrigin Page Builder"],
			"slug" => "siteorigin",
			"foreground" => "#00000000",
			"background" => "#00000000",
		],
		"ultimate-addons-for-gutenberg" => [
			"name" => ["Spectra"],
			"slug" => "spectra",
			"foreground" => "#F0F0F1",
			"background" => "#5733FF",
		],
		"thrive-architect" => [
			"name" => ["Thrive Architect"],
			"slug" => "thrive",
			"foreground" => "#00000000",
			"background" => "#00000000",
		],
		"visual-composer-website-builder" => [
			"name" => ["Visual Composer Website Builder"],
			"slug" => "visual-composer",
			"foreground" => "#00000000",
			"background" => "#00000000",
		],
		"wpbakery-page-builder" => [
			"name" => ["WPBakery Page Builder"],
			"slug" => "wpbakery",
			"foreground" => "#00000000",
			"background" => "#00000000",
		],
		"yellow-pencil" => [
			"name" => ["Yellow Pencil"],
			"slug" => "yellow",
			"foreground" => "#00000000",
			"background" => "#00000000",
		],
	];

	/**
	 * Get page builders
	 *
	 * Gets all the active page builder plugin on the site. Accessible properties
	 * are "all", "name", "slug", "foreground" and "background". Defaults to "name".
	 * 
	 * @param string $property
	 * @return array
	 */
	public static function get_builders($property = "name")
	{
		$plugins = get_option('active_plugins');
		$builders = [];

		foreach ($plugins as $plugin) {
			$slug = substr(
				$plugin,
				strpos($plugin, '/') + 1,
				strpos($plugin, '.') - strpos($plugin, '/') - 1
			);
			if (isset(self::$_builders[$slug])) {
				$builders[] = self::$_builders[$slug];
			} elseif (isset(self::$_builders[$plugin])) {
				$builders[] = self::$_builders[$plugin];
			}
		}

		$props = [];
		if ($property == "all") {
			return $builders;
		} else if (in_array($property, array_keys(self::$_builders['Classic Editor']))) {
			$props = array_column($builders, $property);
		} else {
			$props = array_column($builders, 'name');
		}

		return $props;
	}
}
