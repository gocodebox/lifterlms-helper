<?php
/**
 * Test LLMS_Helper_Keys class
 *
 * @package LifterLMS_Helper/Tests
 *
 * @group main
 *
 * @since [version]
 * @version [version]
 */
class LLMS_Helper_Test_Keys extends LLMS_Helper_Unit_Test_Case {

	/**
	 * Test activate_keys() to sanitize and parse acceptable types of input data.
	 *
	 * @since [version]
	 *
	 * @return void
	 */
	public function test_activate_keys_sanitize_and_parse() {

		$handler = function ( $res, $args, $url ) {
			$this->assertEquals( array( 1, 2 ), $args['body']['keys'] );
			return new WP_Error( 'mock', 'Mock' );
		};

		add_filter( 'pre_http_request', $handler, 10, 3 );

		// Array is parsed and duplicates are removed.
		LLMS_Helper_Keys::activate_keys( array( 1, 2, 2 ) );

		// String with one key per line & extra white space trimmed.
		LLMS_Helper_Keys::activate_keys( "1 \n 2 \n1" );

		remove_filter( 'pre_http_request', $handler, 10, 3 );

	}

}
