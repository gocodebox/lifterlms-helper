<?php
/**
 * Test LLMS_Helper_Keys class
 *
 * @package LifterLMS_Helper/Tests
 *
 * @group keys
 *
 * @since 3.2.0
 */
class LLMS_Helper_Test_Keys extends LLMS_Helper_Unit_Test_Case {

	/**
	 * Test activate_keys() and deactivate_keys() with real active keys
	 *
	 * @since [version]
	 *
	 * @return void
	 */
	public function test_activate_deactivate_add_remove_keys_real_active_key() {

		$key = $this->get_test_key( 'STRIPE' );

		$activation_res = LLMS_Helper_Keys::activate_keys( $key );
		$activation     = $activation_res['data']['activations'][0];

		$this->assertEquals( array(), $activation_res['data']['errors'] );
		$this->assertEquals( 'lifterlms-com-stripe-extension', $activation['id'] );
		$this->assertEquals( $key, $activation['license_key'] );
		$this->assertEquals( 'TEST', $activation['type'] );
		$this->assertEquals( 1, $activation['status'] );
		$this->assertEquals( wp_parse_url( get_site_url(), PHP_URL_HOST ), $activation['url'] );
		$this->assertEquals( array( 'lifterlms-com-stripe-extension' ), $activation['addons'] );

		$this->assertNotEmpty( $activation['update_key'] );

		// Store the key so we can remove it later.
		$this->assertTrue( LLMS_Helper_Keys::add_license_key( $activation ) );

		// Make sure option is stored properly.
		$options = llms_helper_options()->get_license_keys();
		$expect  = array(
			'product_id'  => $activation['id'],
			'status'      => 1,
			'license_key' => $key,
			'update_key'  => $activation['update_key'],
			'addons'      => $activation['addons'],
		);
		$this->assertEquals( $expect, $options[ $key ] );

		// Test deactivation.
		$deactivation_res = LLMS_Helper_Keys::deactivate_keys( array( $key ) );
		$deactivation     = $deactivation_res['data']['deactivations'][0];

		$this->assertEquals( array(), $deactivation_res['data']['errors'] );
		$this->assertEquals( 'lifterlms-com-stripe-extension', $activation['id'] );
		$this->assertEquals( $key, $deactivation['license_key'] );
		$this->assertEquals( 'TEST', $deactivation['type'] );
		$this->assertEquals( 0, $deactivation['status'] );
		$this->assertEquals( wp_parse_url( get_site_url(), PHP_URL_HOST ), $deactivation['url'] );
		$this->assertEquals( array(), $deactivation['addons'] );

		$this->assertNotEmpty( $deactivation['update_key'] );

		// Remove the key.
		$this->assertTrue( LLMS_Helper_Keys::remove_license_key( $key ) );
		$this->assertEquals( array(),  llms_helper_options()->get_license_keys() );

	}

	/**
	 * Test activate_keys() and deactivate_keys() with real inactive keys
	 *
	 * @since [version]
	 *
	 * @return void
	 */
	public function test_activate_keys_real_inactive_key() {

		$key = $this->get_test_key( 'INACTIVE' );
		$ret = LLMS_Helper_Keys::activate_keys( $key );

		$expect = "\"{$key}\" is not an active license key. The current status is \"Cancelled\". Visit your account dashboard at https://lifterlms.com/my-account to renew the license key.";
		$this->assertEquals( array( $expect ), $ret['data']['errors'] );
		$this->assertEquals( array(), $ret['data']['activations'] );

	}

	/**
	 * Test activate_keys() and deactivate_keys() with an invalid key
	 *
	 * @since [version]
	 *
	 * @return void
	 */
	public function test_activate_keys_real_fake_key() {

		$key = 'fake-key';
		$ret = LLMS_Helper_Keys::activate_keys( $key );

		$expect = "\"{$key}\" is not a valid license key. Please ensure your license key is correct and try again.";
		$this->assertEquals( array( $expect ), $ret['data']['errors'] );
		$this->assertEquals( array(), $ret['data']['activations'] );

	}

	/**
	 * Test activate_keys() to sanitize and parse acceptable types of input data.
	 *
	 * @since 3.2.0
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
