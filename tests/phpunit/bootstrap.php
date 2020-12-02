<?php
/**
 * Testing Bootstrap
 *
 * @package LifterLMS_Helper/Tests
 *
 * @since [version]
 * @version [version]
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

}

return new LLMS_Helper_Tests_Bootstrap();
