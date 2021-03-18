<?php
/**
 * Processes structures that adhere to the theme.json schema V0.
 *
 * @package gutenberg
 */

/**
 * Class that encapsulates the processing of
 * structures that adhere to the theme.json V0.
 */
class WP_Theme_JSON_Schema_V0 {

	/**
	 * Data schema of each block within a theme.json.
	 *
	 * Example:
	 *
	 * {
	 *   'block-one': {
	 *     'styles': {
	 *       'color': {
	 *         'background': 'color'
	 *       }
	 *     },
	 *     'settings': {
	 *       'color': {
	 *         'custom': true
	 *       }
	 *     }
	 *   },
	 *   'block-two': {
	 *     'styles': {
	 *       'color': {
	 *         'link': 'color'
	 *       }
	 *     }
	 *   }
	 * }
	 */
	const SCHEMA = array(
		'customTemplates' => null,
		'templateParts'   => null,
		'styles'          => array(
			'border'     => array(
				'radius' => null,
				'color'  => null,
				'style'  => null,
				'width'  => null,
			),
			'color'      => array(
				'background' => null,
				'gradient'   => null,
				'link'       => null,
				'text'       => null,
			),
			'spacing'    => array(
				'padding' => array(
					'top'    => null,
					'right'  => null,
					'bottom' => null,
					'left'   => null,
				),
			),
			'typography' => array(
				'fontFamily'     => null,
				'fontSize'       => null,
				'fontStyle'      => null,
				'fontWeight'     => null,
				'lineHeight'     => null,
				'textDecoration' => null,
				'textTransform'  => null,
			),
		),
		'settings'        => array(
			'border'     => array(
				'customRadius' => null,
				'customColor'  => null,
				'customStyle'  => null,
				'customWidth'  => null,
			),
			'color'      => array(
				'custom'         => null,
				'customGradient' => null,
				'gradients'      => null,
				'link'           => null,
				'palette'        => null,
			),
			'spacing'    => array(
				'customPadding' => null,
				'units'         => null,
			),
			'typography' => array(
				'customFontSize'        => null,
				'customLineHeight'      => null,
				'dropCap'               => null,
				'fontFamilies'          => null,
				'fontSizes'             => null,
				'customFontStyle'       => null,
				'customFontWeight'      => null,
				'customTextDecorations' => null,
				'customTextTransforms'  => null,
			),
			'custom'     => null,
			'layout'     => null,
		),
	);

	/**
	 * Constructor.
	 *
	 * @param array $theme_json A structure that follows the theme.json schema.
	 */
	public static function sanitize( $input = array(), $block_metadata ) {
		$output = array();

		if ( ! is_array( $input ) ) {
			return $output;
		}

		// Remove top-level keys that aren't present in the schema.
		$output = array_intersect_key( $input, self::SCHEMA );

		foreach ( array( 'settings', 'styles' ) as $subtree ) {
			// Remove settings & styles subtrees if they aren't arrays.
			if ( isset( $output[ $subtree ] ) && ! is_array( $output[ $subtree ] ) ) {
				unset( $output[ $subtree ] );
			}

			// Remove block selectors subtrees declared within settings & styles if that aren't registered.
			if ( isset( $output[ $subtree ] ) ) {
				$output[ $subtree ] = array_intersect_key( $output[ $subtree ], $block_metadata );
			}
		}

		foreach ( $block_metadata as $block_selector => $metadata ) {
			if ( isset( $output['styles'][ $block_selector ] ) ) {
				// Remove the block selector subtree if it's not an array.
				if ( ! is_array( $output['styles'][ $block_selector ] ) ) {
					unset( $output['styles'][ $block_selector ] );
					continue;
				}

				$styles_schema                       = self::SCHEMA['styles'];
				$output['styles'][ $block_selector ] = self::remove_keys_not_in_schema(
					$output['styles'][ $block_selector ],
					$styles_schema
				);

				// Remove the block selector subtree if it is empty after having processed it.
				if ( empty( $output['styles'][ $block_selector ] ) ) {
					unset( $output['styles'][ $block_selector ] );
				}
			}

			if ( isset( $output['settings'][ $block_selector ] ) ) {
				// Remove the block selector subtree if it's not an array.
				if ( ! is_array( $output['settings'][ $block_selector ] ) ) {
					unset( $output['settings'][ $block_selector ] );
					continue;
				}

				// Remove the properties that aren't present in the schema.
				$output['settings'][ $block_selector ] = self::remove_keys_not_in_schema(
					$output['settings'][ $block_selector ],
					self::SCHEMA['settings']
				);

				// Remove the block selector subtree if it is empty after having processed it.
				if ( empty( $output['settings'][ $block_selector ] ) ) {
					unset( $output['settings'][ $block_selector ] );
				}
			}
		}

		// Remove the settings & styles subtrees if they're empty after having processed them.
		foreach ( array( 'settings', 'styles' ) as $subtree ) {
			if ( empty( $output[ $subtree ] ) ) {
				unset( $output[ $subtree ] );
			}
		}

		return $output;
	}

	/**
	 * Given a tree, removes the keys that are not present in the schema.
	 *
	 * It is recursive and modifies the input in-place.
	 *
	 * @param array $tree Input to process.
	 * @param array $schema Schema to adhere to.
	 *
	 * @return array Returns the modified $tree.
	 */
	private static function remove_keys_not_in_schema( $tree, $schema ) {
		$tree = array_intersect_key( $tree, $schema );

		foreach ( $schema as $key => $data ) {
			if ( is_array( $schema[ $key ] ) && isset( $tree[ $key ] ) ) {
				$tree[ $key ] = self::remove_keys_not_in_schema( $tree[ $key ], $schema[ $key ] );

				if ( empty( $tree[ $key ] ) ) {
					unset( $tree[ $key ] );
				}
			}
		}

		return $tree;
	}

	public static function to_v1( $old, $version ) {
		// Copy everything.
		$new = $old;

		// Overwrite the things that change.
		$new['settings'] = self::process_settings( $old['settings'] );
		$new['styles']   = self::process_styles( $old['styles'] );
		$new['version']  = $version;

		return $new;
	}

	private static function process_settings( $settings ) {
		$new = array();

		// Styles within defaults become top-level.
		if ( isset( $settings[ WP_Theme_JSON::ALL_BLOCKS_NAME] ) ) {
			$new = $settings[ WP_Theme_JSON::ALL_BLOCKS_NAME ];
			unset( $settings[ WP_Theme_JSON::ALL_BLOCKS_NAME ] );
		}

		// Styles within root become top-level and overwrite the defaults.
		if ( isset( $settings[ WP_Theme_JSON::ROOT_BLOCK_NAME ] ) ) {
			$new = array_replace_recursive( $new, $settings[ WP_Theme_JSON::ROOT_BLOCK_NAME ] );

			// The array_replace_recursive algorithm merges at the leaf level.
			// This means that when a leaf value is an array,
			// the incoming array won't replace the existing,
			// but the numeric indexes are used for replacement.
			//
			// These are the cases that have array values at the leaf levels:
			//
			// Color presets: palette & gradients.
			if ( isset( $settings[ WP_Theme_JSON::ROOT_BLOCK_NAME ]['color']['palette'] ) ) {
				$new['color']['palette'] = $settings[ WP_Theme_JSON::ROOT_BLOCK_NAME ]['color']['palette'];
			}
			if ( isset( $settings[ WP_Theme_JSON::ROOT_BLOCK_NAME ]['color']['gradients'] ) ) {
				$new['color']['gradients'] = $settings[ WP_Theme_JSON::ROOT_BLOCK_NAME ]['color']['gradients'];
			}
			// Spacing: units.
			if ( isset( $settings[ WP_Theme_JSON::ROOT_BLOCK_NAME ]['spacing']['units'] ) ) {
				$new['spacing']['units'] = $settings[ WP_Theme_JSON::ROOT_BLOCK_NAME ]['spacing']['units'];
			}
			// Typography presets: fontSizes & fontFamilies.
			if ( isset( $settings[ WP_Theme_JSON::ROOT_BLOCK_NAME ]['typography']['fontSizes'] ) ) {
				$new['typography']['fontSizes'] = $settings[ WP_Theme_JSON::ROOT_BLOCK_NAME ]['typography']['fontSizes'];
			}
			if ( isset( $settings[ WP_Theme_JSON::ROOT_BLOCK_NAME ]['typography']['fontFamilies'] ) ) {
				$new['typography']['fontFamilies'] = $settings[ WP_Theme_JSON::ROOT_BLOCK_NAME ]['typography']['fontFamilies'];
			}
			// Custom section.
			if ( isset( $settings[ WP_Theme_JSON::ROOT_BLOCK_NAME ]['custom'] ) ) {
				$new['custom'] = $settings[ WP_Theme_JSON::ROOT_BLOCK_NAME ]['custom'];
			}

			unset( $settings[ WP_Theme_JSON::ROOT_BLOCK_NAME ] );
		}

		// It only contains block's data, copy over.
		if ( ! empty( $settings ) ) {
			$new['blocks'] = $settings;
		}

		return $new;
	}

	private static function process_styles( $styles ) {
		$new = array();

		// Styles within root become top-level.
		if ( isset( $styles[ WP_Theme_JSON::ROOT_BLOCK_NAME ] ) ) {
			$new = $styles[ WP_Theme_JSON::ROOT_BLOCK_NAME ];
			unset( $styles[ WP_Theme_JSON::ROOT_BLOCK_NAME ] );

			// Transform root.styles.color.link into elements.link.color.text.
			if ( isset( $new['color']['link'] ) ) {
				$new['elements']['link']['color']['text'] = $new['color']['link'];
				unset( $new['color']['link'] );
			}
		}

		// It only contains block's data, copy over.
		if ( ! empty( $styles ) ) {
			$new['blocks'] = $styles;
			foreach( $new['blocks'] as $block_name => $metadata ) {
				// Transform root.styles.color.link into elements.link.color.text.
				if ( isset( $metadata['color']['link'] ) ) {
					$new['blocks'][ $block_name ]['elements']['link']['color']['text'] = $metadata['color']['link'];
					unset( $new['blocks'][ $block_name ]['color']['link'] );
				}
			}
		}

		return $new;
	}
}
