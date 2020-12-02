<?php
/**
 * LifterLMS Helper Unit Test Case Bootstrap
 *
 * @package LifterLMS_Helper/Tests
 *
 * @since 3.2.0
 * @version 3.2.0
 */
class LLMS_Helper_Unit_Test_Case extends LLMS_Unit_Test_Case {

	/**
	 * Retrieve license keys to use for testing from environment vars.
	 *
	 * @since 3.2.0
	 *
	 * @return void
	 */
	public function get_test_keys() {

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

}
