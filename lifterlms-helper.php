<?php
/**
* Plugin Name: LifterLMS Helper
* Plugin URI: https://lifterlms.com/
* Description: Assists premium LifterLMS theme and plugin updates
* Version: 0.1.0
* Author: codeBOX
* Author URI: http://gocodebox.com
*
*
* @package 		LifterLMS
* @category 	Core
* @author 		codeBOX
*/

// Restrict direct access
if ( ! defined( 'ABSPATH' ) ) exit;

if( !class_exists( 'LLMS_Helper' ) ):

class LLMS_Helper
{

	/**
	 * Array of plugins to update via the helper
	 * @var array
	 */
	public $plugins = array();

	/**
	 * Constructor, get things started!
	 *
	 * @return void
	 */
	public function __construct()
	{

		add_action( 'admin_head', function() {

			echo '<style type="text/css">.xdebug-var-dump { padding: 15px; position: relative; background: white; z-index: 9234234; };</style>';

		} );

		// Define class constants
		$this->define_constants();

		add_action( 'plugins_loaded', array( $this, 'init') );

	}


	/**
	 * Inititalize the Plugin
	 * @return void
	 */
	public function init()
	{
		// only load plugin if LifterLMS class exists.
		if ( class_exists( 'LifterLMS') ) {

			// include necessary classes
			$this->includes();

			// enqueue
			add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ) );

			$nonce = wp_create_nonce( '3lcYCG8cgGsYidpWjN196sUA1Nxig8R7' );
			define( 'LLMS_HELPER_NONCE', $nonce );

			// build array of plugins to use helper to update
			$this->plugins = apply_filters( 'lifterlms_helper_plugins_to_update', array() );

			// get release information and add our plugin to the update object if available
			add_filter( 'pre_set_site_transient_update_plugins', array( $this, 'pre_set_transient' ), 20, 1 );

			// handle lightbox data
			add_filter( 'plugins_api', array( $this, 'plugins_api' ), 20, 3 );

			// check license key
			add_filter( 'upgrader_package_options', array( $this, 'upgrader_package_authorization' ), 10, 1 );

			// return a WP error if previous filter returns an error
			add_filter( 'upgrader_pre_download', array( $this, 'upgrader_pre_download' ), 7, 2 );

		}

		// LifterLMS doesn't exist, deactivate and warn
		else {

			add_action( 'admin_init', array( $this, 'deactivate' ) );
      		add_action( 'admin_notices', array( $this, 'deactivate_notice' ) );

		}

	}


	/**
	 * Enqueue Scripts & Styles
	 * @return void
	 */
	public function admin_enqueue_scripts()
	{

		wp_enqueue_script( 'llms-helper-admin', plugin_dir_url( __FILE__ ) . '/assets/admin/js/llms-helper.js', array( 'jquery' ), NULL, true );

		wp_enqueue_style( 'llms-helper-admin', plugin_dir_url( __FILE__ ) . 'assets/admin/css/llms-helper.css' );

	}

	/**
	 * Deactivate the plugin (if user has sufficient privileges)
	 *
	 * @return void
	 */
	public function deactivate()
	{

		deactivate_plugins( plugin_basename( __FILE__ ) );

	}


	/**
	 * Notify admins that they can't activate without LifterLMS
	 *
	 * @return void
	 */
	public function deactivate_notice()
	{

		echo '<div class="error"><p><strong>LifterLMS Helper</strong> cannot function without <strong>LifterLMS</strong>! LifterLMS Helper has been deactivated. Please activate LifterLMS and try again.</p></div>';

		// remove the query param if user is on the plugin page
		if ( isset( $_GET['activate'] ) ) {
			unset( $_GET['activate'] );
		}

	}


	/**
	 * Define constants for plugin
	 *
	 * @return void
	 */
	private function define_constants()
	{

		// LLMS ConvertKit Plugin File
		if ( ! defined( 'LLMS_HELPER_PLUGIN_FILE' ) ) {
			define( 'LLMS_HELPER_PLUGIN_FILE', __FILE__ );
		}

		// LLMS Convert Kit Plugin Directory
		if ( ! defined( 'LLMS_HELPER_PLUGIN_DIR' ) ) {
			define( 'LLMS_HELPER_PLUGIN_DIR', WP_PLUGIN_DIR . "/" . plugin_basename( dirname(__FILE__) ) . '/');
		}

	}


	/**
	 * Include all clasess required by the plugin
	 * @return void
	 */
	private function includes()
	{

		if( is_admin() ) {

			require_once 'includes/llms.helper.functions.php';

			require_once 'includes/class.llms.helper.admin.settings.php';
			require_once 'includes/class.llms.helper.admin.notices.php';
			require_once 'includes/class.llms.helper.admin.ajax.php';
			require_once 'includes/class.llms.helper.updater.php';

		}

	}


	/**
	 * Output lightbox information for our custom plugins
	 * @param  mixed  $result response object
	 * @param  string $action api call action
	 * @param  obj    $args   object of arguments
	 * @return obj
	 */
	public function plugins_api( $result, $action, $args )
	{

		// skip other actions
		if( $action != 'plugin_information' ) {
			return $result;
		}

		// check to see if this is one of our plugins
		foreach( $this->plugins as $plugin )
		{

			$p = new LLMS_Helper_Updater( $plugin );
			if( $args->slug === $p->plugin_basename )
			{

				// override result with our info
				$result = $p->get_lightbox_data();

			}

		}

		// var_dump( $result, $action, $args );

		return $result;

	}


	/**
	 * Determine if there's an update available for our plugins
	 * @param  obj $transient  transient object
	 * @return obj
	 */
	public function pre_set_transient( $transient )
	{

		// start updater for all plugins that need updates
		foreach( $this->plugins as $plugin ) {

			$p = new LLMS_Helper_Updater( $plugin );
			$latest = $p->get_latest_version_data();

			// if latest is greater than current, we want to update
			$update = ( isset( $latest['version'] ) ) ? version_compare( $latest['version'], $p->plugin_data['Version'], '>' ) : false;

			// if we need an update, load the data into the transient object
			if( $update ) {

				$transient->response[$p->plugin_slug] = $p->get_transient_object( $latest['version'] );

			}

		}

		return $transient;

	}


	/**
	 * Check License key before serving an update and set the real download URL for the package
	 * @return array
	 */
	public function upgrader_package_authorization( $options )
	{

		// only check auth on lifterlms plugins
		if( strpos( $options['package'], '//lifterlms.com' )  ) {

			$r = wp_remote_post( $options['package'], array(
				'sslverify' => false, // dev
			) );

			if( $r['response']['code'] === 200 ) {

				$body = json_decode( $r['body'], true );

				if( !empty( $body['zip'] ) ) {

					$options['package'] = $body['zip'];

				} else {

					$options['package'] = 'llms-error';

				}

			} else {

				$options['package'] = 'llms-error';

			}

		}

		return $options;

	}

	/**
	 * Called after package installation complete
	 *
	 * moves directory to the correct name and reactivates if the plugin was active
	 *
	 * @param bool  $response   Install response.
	 * @param array $hook_extra Extra arguments passed to hooked filters.
	 * @param array $result     Installation result data.
	 */
	public function post_install( $response, $hook_extra, $result ) {

		var_dump( $response, $hook_extra, $result );

		// // Get plugin info
		// $this->get_plugin_data();

		// // Remember if our plugin was previously activated
		// $was_activated = is_plugin_active( $this->plugin_slug );

		// // Since we are hosted in GitHub, our plugin folder would have a dirname of
		// // reponame-tagname change it to our original one:
		// global $wp_filesystem;
		// $plugin_dir = WP_PLUGIN_DIR . DIRECTORY_SEPARATOR . dirname( $this->plugin_slug );
		// $wp_filesystem->move( $result['destination'], $plugin_dir );
		// $result['destination'] = $plugin_dir;

		// // reactivate the plugin if it was active previously
		// if( $was_activated )
		// 	$activate = activate_plugin( $this->slug );

		return $result;

	}


	/**
	 * Runs immediately prior to downloading the package
	 *
	 * We've replaced the URL with an internal error code and should display that message, otherwise, we have a good download link
	 *
	 * @param  bool   $response true
	 * @param  string $url      package download url
	 * @return mixed            true or WP error if we've encountered an auth error
	 */
	public function upgrader_pre_download( $response, $url )
	{

		if( $url == 'llms-error' ) {

			return new WP_Error( 'error', 'Invalid Activation Key', array() );

		}

		return $response;

	}


}

return new LLMS_Helper();

endif;