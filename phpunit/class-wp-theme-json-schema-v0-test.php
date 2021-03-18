<?php

/**
 * Test WP_Theme_JSON class.
 *
 * @package Gutenberg
 */

class WP_Theme_JSON_Schema_V0_Test extends WP_UnitTestCase {

	function test_schema_sanitization_subtree_is_removed_if_key_invalid() {
		$theme_json = new WP_Theme_JSON(
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
			)
		);
		$result     = $theme_json->get_raw_data();

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

		$this->assertEqualSetsWithIndex( $expected, $result );
	}

	function test_schema_sanitization_subtree_is_removed_if_not_array() {
		$root_name  = WP_Theme_JSON::ROOT_BLOCK_NAME;
		$theme_json = new WP_Theme_JSON(
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
			)
		);

		$actual   = $theme_json->get_raw_data();
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
		$theme_json = new WP_Theme_JSON(
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
			)
		);
		$result     = $theme_json->get_raw_data();

		$expected = array(
			'styles' => array(
				$root_name => array(
					'color' => array(
						'link' => 'blue',
					),
				),
			),
		);

		$this->assertEqualSetsWithIndex( $expected, $result );
	}

}
