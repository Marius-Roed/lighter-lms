<?php

namespace LighterLMS\Third_Party;

class Builders {

	private static $_builders = array(
		'fl-builder'                      => array(
			'name'       => array( 'Beaver Builder', 'Beaver Builder Plugin (Lite Version)' ),
			'slug'       => 'beaver-builder',
			'foreground' => '#00000000',
			'background' => '#FEAF52',
		),
		'brizy'                           => array(
			'name'       => array( 'Brizy' ),
			'slug'       => 'brizy',
			'foreground' => '#00000000',
			'background' => '#0E0736',
		),
		'breakdance/plugin.php'           => array(
			'name'       => array( 'Breakdance' ),
			'slug'       => 'breakdance',
			'foreground' => '#000000',
			'background' => '#FFC514',
		),
		'classic-editor'                  => array(
			'name'       => array( 'Classic Editor' ),
			'slug'       => 'classic-editor',
			'foreground' => '#25719B',
			'background' => '#F0F0F1',
		),
		'cornerstone-page-builder'        => array(
			'name'       => array( 'Cornerstone Page Builder' ),
			'slug'       => 'cornerstone',
			'foreground' => '#00000000',
			'background' => '#00000000',
		),
		'divi'                            => array(
			'name'       => array( 'Divi Builder' ),
			'slug'       => 'divi',
			'foreground' => '#FFFFFFF',
			'background' => '#00000000',
		),
		'elementor'                       => array(
			'name'       => array( 'Elementor', 'Elementor Pro' ),
			'slug'       => 'elementor',
			'foreground' => '#F0F0F1',
			'background' => '#92003B',
		),
		'gutenberg'                       => array(
			'name'       => array( 'Gutenberg' ),
			'slug'       => 'gutenberg',
			'foreground' => '#1E1E1E',
			'background' => '#F0F0F1',
		),
		'fusion-builder'                  => array(
			'name'       => array( 'Fusion Builder' ),
			'slug'       => 'fusion',
			'foreground' => '#FFFFFF',
			'background' => '#50B3C4',
		),
		'flatsome-Builder'                => array(
			'name'       => array( 'Flatsome UX Builder' ),
			'slug'       => 'flatsome',
			'foreground' => '#00000000',
			'background' => '#00000000',
		),
		'kingcomposer'                    => array(
			'name'       => array( 'KingComposer' ),
			'slug'       => 'kingcomposer',
			'foreground' => '#00000000',
			'background' => '#00000000',
		),
		'live-composer'                   => array(
			'name'       => array( 'Live Composer' ),
			'slug'       => 'live-composer',
			'foreground' => '#00000000',
			'background' => '#2EDCE7',
		),
		'layers'                          => array(
			'name'       => array( 'Layers WP' ),
			'slug'       => 'layers',
			'foreground' => '#00000000',
			'background' => '#00000000',
		),
		'motopress-content-editor'        => array(
			'name'       => array( 'MotoPress Content Editor' ),
			'slug'       => 'motopress',
			'foreground' => '#00000000',
			'background' => '#00000000',
		),
		'oxygen-builder'                  => array(
			'name'       => array( 'Oxygen Builder' ),
			'slug'       => 'oxygen',
			'foreground' => '#FFFFFF',
			'background' => '#000000',
		),
		'siteorigin-page-builder'         => array(
			'name'       => array( 'SiteOrigin Page Builder' ),
			'slug'       => 'siteorigin',
			'foreground' => '#00000000',
			'background' => '#00000000',
		),
		'ultimate-addons-for-gutenberg'   => array(
			'name'       => array( 'Spectra' ),
			'slug'       => 'spectra',
			'foreground' => '#F0F0F1',
			'background' => '#5733FF',
		),
		'thrive-architect'                => array(
			'name'       => array( 'Thrive Architect' ),
			'slug'       => 'thrive',
			'foreground' => '#00000000',
			'background' => '#00000000',
		),
		'visual-composer-website-builder' => array(
			'name'       => array( 'Visual Composer Website Builder' ),
			'slug'       => 'visual-composer',
			'foreground' => '#00000000',
			'background' => '#00000000',
		),
		'wpbakery-page-builder'           => array(
			'name'       => array( 'WPBakery Page Builder' ),
			'slug'       => 'wpbakery',
			'foreground' => '#00000000',
			'background' => '#00000000',
		),
		'yellow-pencil'                   => array(
			'name'       => array( 'Yellow Pencil' ),
			'slug'       => 'yellow',
			'foreground' => '#00000000',
			'background' => '#00000000',
		),
	);

	/**
	 * Get page builders
	 *
	 * Gets all the active page builder plugin on the site. Accessible properties
	 * are "all", "name", "slug", "foreground" and "background". Defaults to "name".
	 *
	 * @param string $property
	 * @return array
	 */
	public static function get_builders( $property = 'name' ) {
		$plugins  = get_option( 'active_plugins' );
		$builders = array();

		foreach ( $plugins as $plugin ) {
			$slug = substr(
				$plugin,
				strpos( $plugin, '/' ) + 1,
				strpos( $plugin, '.' ) - strpos( $plugin, '/' ) - 1
			);
			if ( isset( self::$_builders[ $slug ] ) ) {
				$builders[] = self::$_builders[ $slug ];
			} elseif ( isset( self::$_builders[ $plugin ] ) ) {
				$builders[] = self::$_builders[ $plugin ];
			}
		}

		$props = array();
		if ( $property == 'all' ) {
			return $builders;
		} elseif ( in_array( $property, array_keys( self::$_builders['Classic Editor'] ) ) ) {
			$props = array_column( $builders, $property );
		} else {
			$props = array_column( $builders, 'name' );
		}

		return $props;
	}
}
