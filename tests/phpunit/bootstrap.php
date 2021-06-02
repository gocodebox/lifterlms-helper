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
	 * Runs immediately after $this->install()
	 *
	 * @since [version]
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
