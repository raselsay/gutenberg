<?php

class WP_Theme_JSON_Schema_V1 {

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

	public static function sanitize( $input, $block_metadata ) {
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

			// Remove invalid keys according to the schema of the subtree:
			// - settings: top-level keys in schema + 'blocks'
			// - styles: top-level keys in schema + 'blocks' + 'elements'
			$schema_for_subtree = array_merge( self::SCHEMA[ $subtree ], array( 'blocks' => null ) );
			if ( 'styles' === $subtree ) {
				$schema_for_subtree = array_merge( $schema_for_subtree, array( 'elements' => null ) );
			}
			$output[ $subtree ] = self::remove_keys_not_in_schema( $output[ $subtree ], $schema_for_subtree );

			// Remove block selectors subtrees declared within settings & styles if that aren't registered.
			if ( isset( $output[ $subtree ]['blocks'] ) ) {
				$output[ $subtree ]['blocks'] = array_intersect_key( $output[ $subtree ]['blocks'], $block_metadata );
			}
		}

		// Iterate over blocks.
		foreach ( $block_metadata as $block_selector => $metadata ) {
			if ( isset( $output['styles']['blocks'][ $block_selector ] ) ) {
				// Remove the block selector subtree if it's not an array.
				if ( ! is_array( $output['styles']['blocks'][ $block_selector ] ) ) {
					unset( $output['styles']['blocks'][ $block_selector ] );
					continue;
				}

				$output['styles']['blocks'][ $block_selector ] = self::remove_keys_not_in_schema(
					$output['styles']['blocks'][ $block_selector ],
					array_merge( self::SCHEMA['styles'], array( 'elements' => null) )
				);

				// Remove the block selector subtree if it is empty after having processed it.
				if ( empty( $output['styles']['blocks'][ $block_selector ] ) ) {
					unset( $output['styles']['blocks'][ $block_selector ] );
				}
			}

			if ( isset( $output['settings']['blocks'][ $block_selector ] ) ) {
				// Remove the block selector subtree if it's not an array.
				if ( ! is_array( $output['settings']['blocks'][ $block_selector ] ) ) {
					unset( $output['settings']['blocks'][ $block_selector ] );
					continue;
				}

				// Remove the properties that aren't present in the schema.
				$output['settings']['blocks'][ $block_selector ] = self::remove_keys_not_in_schema(
					$output['settings']['blocks'][ $block_selector ],
					self::SCHEMA['settings']
				);

				// Remove the block selector subtree if it is empty after having processed it.
				if ( empty( $output['settings']['blocks'][ $block_selector ] ) ) {
					unset( $output['settings']['blocks'][ $block_selector ] );
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

}