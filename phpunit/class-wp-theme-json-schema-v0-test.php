<?php

/**
 * Test WP_Theme_JSON class.
 *
 * @package Gutenberg
 */

class WP_Theme_JSON_Schema_V0_Test extends WP_UnitTestCase {

	function test_schema_sanitization_subtree_is_removed_if_key_invalid() {
		$actual = WP_Theme_JSON_Schema_V0::sanitize(
			array(
				'invalid/key' => 'content',
				'styles'      => array(
					'invalid/key' => array(
						'color' => array(
							'custom' => 'false',
						),
					),
					'core/group'  => array(
						'invalid/key' => array(
							'custom'     => false,
							'background' => 'red',
						),
						'color'       => array(
							'invalid/key' => true,
							'background'  => 'red',
						),
						'spacing'     => array(
							'padding' => array(
								'invalid/key' => false,
								'top'         => '10px',
							),
						),
					),
				),
			),
			array(
				'core/group' => array()
			)
		);

		$expected = array(
			'styles' => array(
				'core/group' => array(
					'color'   => array(
						'background' => 'red',
					),
					'spacing' => array(
						'padding' => array(
							'top' => '10px',
						),
					),
				),
			),
		);

		$this->assertEqualSetsWithIndex( $expected, $actual );
	}

	function test_schema_sanitization_subtree_is_removed_if_not_array() {
		$root_name  = WP_Theme_JSON::ROOT_BLOCK_NAME;
		$actual     = WP_Theme_JSON_Schema_V0::sanitize(
			array(
				'settings' => 'invalid/not/array',
				'styles'   => array(
					$root_name       => 'invalid/not/array',
					'core/paragraph' => array(
						'invalid/not/array' => false,
					),
					'core/group'     => array(
						'invalid/not/array' => false,
						'color'             => array(
							'link' => 'pink',
						),
						'typography'        => array(
							'invalid/key' => false,
						),
						'spacing'           => array(
							'padding' => array(
								'invalid/key' => '10px',
							),
						),
					),
				),
			),
			array(
				'core/paragraph' => array(),
				'core/group'     => array(),
			)
		);

		$expected = array(
			'styles' => array(
				'core/group' => array(
					'color' => array(
						'link' => 'pink',
					),
				),
			),
		);

		$this->assertEqualSetsWithIndex( $expected, $actual );
	}

	function test_schema_sanitization_subtree_is_removed_if_empty() {
		$root_name  = WP_Theme_JSON::ROOT_BLOCK_NAME;
		$actual     = WP_Theme_JSON_Schema_V0::sanitize(
			array(
				'settings' => array(
					'invalid/key' => array(
						'color' => array(
							'custom' => false,
						),
					),
					$root_name    => array(
						'invalid/key' => false,
					),
				),
				'styles'   => array(
					$root_name => array(
						'color'      => array(
							'link' => 'blue',
						),
						'typography' => array(
							'invalid/key' => false,
						),
						'spacing'    => array(
							'padding' => array(
								'invalid/key' => '10px',
							),
						),
					),
				),
			),
			array(
				$root_name => array(),
			)
		);

		$expected = array(
			'styles' => array(
				$root_name => array(
					'color' => array(
						'link' => 'blue',
					),
				),
			),
		);

		$this->assertEqualSetsWithIndex( $expected, $actual );
	}

	function test_schema_to_v1() {
		$root_name  = WP_Theme_JSON::ROOT_BLOCK_NAME;
		$theme_json = new WP_Theme_JSON(
			array(
				'invalid/key' => 'content',
				'settings'    => array(
					'defaults'       => array(
						'color' => array(
							'palette' => array(
								array(
									"name"  => "Black",
									"slug"  => "black",
									"color" => "#000000"
								),
								array(
									"name"  => "White",
									"slug"  => "white",
									"color" => "#ffffff"
								),
								array(
									"name"  => "Pale Pink",
									"slug"  => "pale-pink",
									"color" => "#f78da7"
								),
								array(
									"name"  => "Vivid Red",
									"slug"  => "vivid-red",
									"color" => "#cf2e2e"
								),
							),
							'custom'  => false,
							'link'    => false,
						),
					),
					'root'           => array(
						'color'  => array(
							'palette' => array(
								array(
									"name"  => "Pale Pink",
									"slug"  => "pale-pink",
									"color" => "#f78da7"
								),
								array(
									"name"  => "Vivid Red",
									"slug"  => "vivid-red",
									"color" => "#cf2e2e"
								),
							),
							'link'    => true,
						),
						'border' => array(
							'customRadius' => false,
						),
					),
					'core/paragraph' => array(
						'typography' => array(
							'dropCap' => false,
						),
					),
				),
				'styles'      => array(
					'invalid/key' => array(
						'color' => array(
							'custom' => 'false',
						),
					),
					$root_name   => array(
						'color' => array(
							'background' => 'purple',
							'link'       => 'red',
						),
					),
					'core/group' => array(
						'invalid/key' => array(
							'custom'     => false,
							'background' => 'red',
						),
						'color'       => array(
							'invalid/key' => true,
							'background'  => 'red',
							'link'        => 'yellow',
						),
						'spacing'     => array(
							'padding' => array(
								'invalid/key' => false,
								'top'         => '10px',
							),
						),
					),
				),
			)
		);

		$actual = $theme_json->get_raw_data();

		$expected = array(
			'version'  => 1,
			'settings' => array(
				'color'  => array(
					'palette' => array(
						array(
							"name"  => "Pale Pink",
							"slug"  => "pale-pink",
							"color" => "#f78da7"
						),
						array(
							"name"  => "Vivid Red",
							"slug"  => "vivid-red",
							"color" => "#cf2e2e"
						),
					),
					'custom' => false,
					'link'   => true,
				),
				'border' => array(
					'customRadius' => false,
				),
				'blocks' => array(
					'core/paragraph' => array(
						'typography' => array(
							'dropCap' => false,
						),
					),
				),
			),
			'styles'   => array(
				'color' => array(
					'background' => 'purple',
				),
				'blocks' => array(
					'core/group'  => array(
						'color'       => array(
							'background'  => 'red',
						),
						'spacing'     => array(
							'padding' => array(
								'top'         => '10px',
							),
						),
						'elements' => array(
							'link' => array(
								'color' => array(
									'text' => 'yellow',
								),
							),
						),
					),
				),
				'elements' => array(
					'link' => array(
						'color' => array(
							'text' => 'red'
						)
					)
				)
			),
		);

		$this->assertEqualSetsWithIndex( $expected, $actual );
	}
}
