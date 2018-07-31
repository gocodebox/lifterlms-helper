<?php
/**
 * Plugin Name: LifterLMS Helper
 * Plugin URI: https://lifterlms.com/
 * Description: Assists premium LifterLMS theme and plugin updates
 * Version: 3.0.0-beta.1
 * Author: Thomas Patrick Levy, codeBOX LLC
 * Author URI: http://gocodebox.com
 * Text Domain: lifterlms-helper
 * Domain Path: /i18n
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl-3.0.html
 * LifterLMS Minimum Version: 3.22.0
 */
defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'LifterLMS_Helper' ) ) :
final class LifterLMS_Helper {

	/**
	 * Current Plugin Version
	 * @var  string
	 */
	public $version = '3.0.0-beta.1';

	/**
	 * Singleton instance reference
	 * @var  null
	 */
	protected static $_instance = null;

	/**
	 * Instance of the LLMS_Helper_Upgrader class
	 * use/retrieve via LLMS_Helper()->upgrader()
	 * @var  null
	 */
	private $upgrader = null;

	/**
	 * Main Instance of LifterLMS_Helper
	 * Ensures only one instance of LifterLMS is loaded or can be loaded.
	 * @return   LLMS_AddOn_Upgrader - Main instanceg
	 * @since    3.0.0
	 * @version  3.0.0
	 */
	public static function instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}
		return self::$_instance;
	}

	/**
	 * Constructor, get things started!
	 * @return   void
	 * @since    1.0.0
	 * @version  1.0.0
	 */
	private function __construct() {

		// Define class constants
		$this->define_constants();

		add_action( 'init', array( $this, 'load_textdomain' ), 0 );
		add_action( 'plugins_loaded', array( $this, 'init' ) );

	}

	/**
	 * Inititalize the Plugin
	 * @return    void
	 * @since     1.0.0
	 * @version   1.0.0
	 */
	public function init() {

		if ( class_exists( 'LifterLMS' ) ) {

			$this->includes();
			$this->crons();

			if ( is_admin() ) {
				$this->upgrader = LLMS_Helper_Upgrader::instance();
			}

		}

	}

	/**
	 * Schedule and handle cron functions
	 * @return   void
	 * @since    3.0.0
	 * @version  3.0.0
	 */
	private function crons() {

		add_action( 'llms_helper_check_license_keys', array( 'LLMS_Helper_Keys', 'check_keys' ) );

		if ( ! wp_next_scheduled( 'llms_helper_check_license_keys' ) ) {
			wp_schedule_event( time(), 'daily', 'llms_helper_check_license_keys' );
		}

	}

	/**
	 * Define constants for plugin
	 * @return void
	 * @since 1.0.0
	 */
	private function define_constants() {

		if ( ! defined( 'LLMS_HELPER_PLUGIN_FILE' ) ) {
			define( 'LLMS_HELPER_PLUGIN_FILE', __FILE__ );
		}

		if ( ! defined( 'LLMS_HELPER_PLUGIN_DIR' ) ) {
			define( 'LLMS_HELPER_PLUGIN_DIR', WP_PLUGIN_DIR . "/" . plugin_basename( dirname(__FILE__) ) . '/');
		}

		if ( ! defined( 'LLMS_HELPER_PLUGIN_URL' ) ) {
			define( 'LLMS_HELPER_PLUGIN_URL', trailingslashit( plugin_dir_url( __FILE__ ) ) );
		}

		if ( ! defined( 'LLMS_HELPER_VERSION' ) ) {
			define( 'LLMS_HELPER_VERSION', $this->version );
		}

	}

	/**
	 * Include all clasess required by the plugin
	 * @return void
	 * @since    1.0.0
	 * @version  3.0.0
	 */
	private function includes() {

		require_once LLMS_HELPER_PLUGIN_DIR . 'includes/class-llms-helper-admin-add-ons.php';
		require_once LLMS_HELPER_PLUGIN_DIR . 'includes/class-llms-helper-assets.php';
		require_once LLMS_HELPER_PLUGIN_DIR . 'includes/class-llms-helper-betas.php';
		require_once LLMS_HELPER_PLUGIN_DIR . 'includes/class-llms-helper-cloned.php';
		require_once LLMS_HELPER_PLUGIN_DIR . 'includes/class-llms-helper-install.php';
		require_once LLMS_HELPER_PLUGIN_DIR . 'includes/class-llms-helper-keys.php';
		require_once LLMS_HELPER_PLUGIN_DIR . 'includes/class-llms-helper-options.php';
		require_once LLMS_HELPER_PLUGIN_DIR . 'includes/class-llms-helper-upgrader.php';

		require_once LLMS_HELPER_PLUGIN_DIR . 'includes/functions-llms-helper.php';

		require_once LLMS_HELPER_PLUGIN_DIR . 'includes/model-llms-helper-add-on.php';

	}

	/**
	 * Load l10n files
	 * The first loaded file takes priority
	 *
	 * Files can be found in the following order:
	 * 		WP_LANG_DIR/lifterlms/lifterlms-helper-LOCALE.mo (safe directory will never be automatically overwritten)
	 * 		WP_LANG_DIR/plugins/lifterlms-helper-LOCALE.mo (unsafe directory, may be automatically updated)
	 *
	 * @return   void
	 * @since    2.5.0
	 * @version  2.5.0
	 */
	public function load_textdomain() {

		// load locale
		$locale = apply_filters( 'plugin_locale', get_locale(), 'lifterlms-helper' );

		// load a lifterlms specific locale file if one exists
		load_textdomain( 'lifterlms-helper', WP_LANG_DIR . '/lifterlms/lifterlms-helper-' . $locale . '.mo' );

		// load localization files
		load_plugin_textdomain( 'lifterlms-helper', false, dirname( plugin_basename( __FILE__ ) ) . '/i18n' );

	}

	/**
	 * Return the singleton instance of the LLMS_Helper_Upgader
	 * @return   obj
	 * @since    3.0.0
	 * @version  3.0.0
	 */
	public function upgrader() {
		return $this->upgrader;
	}

}
endif;

/**
 * Returns the main instance of the LifterLMS Helper
 * @return LifterLMS
 */
function LLMS_Helper() {
	return LifterLMS_Helper::instance();
}
return LLMS_Helper();
