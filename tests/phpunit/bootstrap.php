<?php
/**
 * Testing Bootstrap
 *
 * @package LifterLMS_Helper/Tests
 *
 * @since 3.2.0
 * @version 3.2.0
 */

require_once './vendor/lifterlms/lifterlms-tests/bootstrap.php';

class LLMS_Helper_Tests_Bootstrap extends LLMS_Tests_Bootstrap {

	/**
	 * __FILE__ reference, should be defined in the extending class
	 *
	 * @var string
	 */
	public $file = __FILE__;

	/**
	 * Name of the testing suite
	 *
	 * @var string
	 */
	public $suite_name = 'LifterLMS Helper';

	/**
	 * Main PHP File for the plugin
	 *
	 * @var string
	 */
	public $plugin_main = 'lifterlms-helper.php';

	/**
	 * Load the plugin and the LifterLMS core.
	 *
	 * @since 3.4.0
	 *
	 * @return void
	 */
	public function load() {

		// Disable the helper library so this version is tested.
		add_filter( 'llms_included_libs', function( $libs ) {
			$libs['helper']['test'] = false;
			return $libs;
		} );

		parent::load();

	}

	/**
	 * Runs immediately after $this->install()
	 *
	 * @since 3.2.1
	 *
	 * @return void
	 */
	public function install_after() {

		parent::install_after();

		update_option( 'siteurl', 'http://llms-helper-testing.test' );

		require_once LLMS_PLUGIN_DIR . 'includes/admin/llms.functions.admin.php';

	}

}

return new LLMS_Helper_Tests_Bootstrap();
