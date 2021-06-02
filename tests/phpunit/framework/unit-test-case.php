<?php
/**
 * LifterLMS Helper Unit Test Case Bootstrap
 *
 * @package LifterLMS_Helper/Tests
 *
 * @since 3.2.0
 */
class LLMS_Helper_Unit_Test_Case extends LLMS_Unit_Test_Case {

	/**
	 * Teardown the test case
	 *
	 * @since [version]
	 *
	 * @return void
	 */
	public function tearDown() {

		parent::tearDown();
		delete_option( 'llms_helper_options' );

	}

	/**
	 * Retrieve license keys to use for testing from environment vars.
	 *
	 * @since 3.2.0
	 * @since [version] Only run api integration tests when explicitly specified through environment vars.
	 *
	 * @return void
	 */
	public function get_test_keys() {

		/**
		 * Skip test unless API integration tests are explicitly specified.
		 *
		 * This is used by Travis to only run API integrations tests on a single build
		 * to prevent unnecessary load on the API server.
		 *
		 * We'll run the API tests when running code coverage too so that we get "credit"
		 * for the integration tests.
		 */
		if ( ! getenv( 'LLMS_COM_API_INTEGRATION_TESTS' ) && ! getenv( 'RUN_CODE_COVERAGE' ) ) {
			$this->markTestSkipped( 'Integration tests skipped in this environment.' );
		}

		$keys = array();

		foreach ( array( 'INFINITY', 'UNIVERSE', 'STRIPE', 'INACTIVE' ) as $val ) {

			$key = getenv( "LLMS_HELPER_TEST_KEY_{$val}" );

			if ( $key ) {
				$keys[ $val ] = $key;
			}

		}

		// No keys found, skip the test.
		if ( ! $keys ) {
			$this->markTestSkipped( 'No license keys available.' );
		}

		return $keys;

	}

	/**
	 * Retrieve a single test key by its test ID
	 *
	 * @since [version]
	 *
	 * @param string $key Test key ID. See `get_test_keys()` for available key ids.
	 * @return strinng|boolaen The license key or `false`.
	 */
	public function get_test_key( $key ) {
		$keys = $this->get_test_keys();
		return isset( $keys[ $key ] ) ? $keys[ $key ] : false;
	}

	/**
	 * Activate a test key using the test key id
	 *
	 * @since [version]
	 *
	 * @param string $key Test key ID. See `get_test_keys()` for available key ids.
	 * @return array
	 */
	public function activate_key( $key ) {
		$key = $this->get_test_key( $key );
		$api = LLMS_Helper_Keys::activate_keys( $key );
		LLMS_Helper_Keys::add_license_key( $api['data']['activations'][0] );
		return llms_helper_options()->get_license_keys()[ $key ];
	}

}
