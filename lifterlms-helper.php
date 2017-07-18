<?php
/**
 * Plugin Name: LifterLMS Helper
 * Plugin URI: https://lifterlms.com/
 * Description: Assists premium LifterLMS theme and plugin updates
 * Version: 2.5.0
 * Author: Thomas Patrick Levy, codeBOX LLC
 * Author URI: http://gocodebox.com
 * Text Domain: lifterlms-helper
 * Domain Path: /i18n
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl-3.0.html
 * LifterLMS Minimum Version: 3.0.0
 */

// Restrict direct access
if ( ! defined( 'ABSPATH' ) ) { exit; }

if( ! class_exists( 'LLMS_Helper' ) ):

final class LLMS_Helper {

	/**
	 * Current Plugin Version
	 * @var  string
	 */
	public $version = '2.5.0';

	/**
	 * Array of plugins to update via the helper
	 * @var array
	 *
	 * @since 1.0.0
	 */
	private $plugins = array();

	/**
	 * Array of themes to update via the helper
	 * @var array
	 *
	 * @since 1.0.0
	 */
	private $themes = array();


	/**
	 * URL to query for release info
	 * @var string
	 * @since  2.3.0
	 */
	protected $api_url = 'https://lifterlms.com/llms-api';

	/**
	 * Constructor, get things started!
	 *
	 * @return void
	 *
	 * @since 1.0.0
	 */
	public function __construct() {

		// Define class constants
		$this->define_constants();

		add_action( 'init', array( $this, 'load_textdomain' ), 0 );

		add_action( 'plugins_loaded', array( $this, 'init') );

	}

	/**
	 * Inititalize the Plugin
	 *
	 * @return void
	 *
	 * @since 1.0.0
	 */
	public function init() {

		// migrate pre 2.4 keys to the new structure
		if ( ! get_option( 'llms_helper_key_migration', '' ) ) {
			$this->migrate_keys();
		}

		// only load plugin if LifterLMS class exists.
		if ( class_exists( 'LifterLMS') ) {

			// include necessary classes
			$this->includes();

			add_filter( 'lifterlms_get_settings_pages', array( $this, 'add_license_tab' ), 777 );

			// get products that can be updated by this plugin
			$this->get_products();

			// enqueue
			add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ) );

			$nonce = wp_create_nonce( '3lcYCG8cgGsYidpWjN196sUA1Nxig8R7' );
			define( 'LLMS_HELPER_NONCE', $nonce );

			// get release information and add our plugin to the update object if available
			add_filter( 'pre_set_site_transient_update_plugins', array( $this, 'pre_set_transient' ), 20, 1 );
			add_filter( 'pre_set_site_transient_update_themes', array( $this, 'pre_set_transient' ), 20, 1 );

			// handle lightbox data
			add_filter( 'plugins_api', array( $this, 'handle_lightbox' ), 20, 3 );

			// check license key
			add_filter( 'upgrader_package_options', array( $this, 'upgrader_package_authorization' ), 10, 1 );

			// return a WP error if previous filter returns an error
			add_filter( 'upgrader_pre_download', array( $this, 'upgrader_pre_download' ), 7, 3 );

			// move & rename dir after installation
			add_filter( 'upgrader_post_install', array( $this, 'upgrader_post_install' ), 10, 3 );

		}

		// LifterLMS doesn't exist, deactivate and warn
		else {

			add_action( 'admin_init', array( $this, 'deactivate' ) );
      		add_action( 'admin_notices', array( $this, 'deactivate_notice' ) );

		}

	}


	public function add_license_tab( $tabs ) {
		$tabs[] = include 'includes/class.llms.helper.admin.settings.tab.php';
		return $tabs;
	}

	/**
	 * Enqueue Scripts & Styles
	 * @return void
	 * @since    1.0.0
	 * @version  2.5.0
	 */
	public function admin_enqueue_scripts() {

		$min = ( ! defined( 'WP_DEBUG' ) || WP_DEBUG == false ) ? '.min' : '';
		wp_enqueue_script( 'llms-helper-admin', plugin_dir_url( __FILE__ ) . '/assets/js/admin/llms-helper' . $min . '.js', array( 'jquery' ), $this->version, true );
		wp_enqueue_style( 'llms-helper-admin', plugin_dir_url( __FILE__ ) . 'assets/css/admin/llms-helper' . $min . '.css', array(), $this->version );

	}

	/**
	 * Deactivate the plugin (if user has sufficient privileges)
	 *
	 * @return void
	 *
	 * @since 1.0.0
	 */
	public function deactivate() {

		deactivate_plugins( plugin_basename( __FILE__ ) );

	}


	/**
	 * Notify admins that they can't activate without LifterLMS
	 *
	 * @return void
	 *
	 * @since 1.0.0
	 */
	public function deactivate_notice() {

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
	 *
	 * @since 1.0.0
	 */
	private function define_constants() {

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
	 * Setup plugins and themes that can be updated by this plugin
	 *
	 * @return void
	 *
	 * @since 1.0.0
	 */
	public function get_products() {

		$products = llms_helper_get_products();

		if ( $products ) {

			$this->themes = $products['themes'];
			$this->plugins = $products['plugins'];

		}

	}


	/**
	 * Include all clasess required by the plugin
	 * @return void
	 * @since    1.0.0
	 * @version  2.5.0
	 */
	private function includes() {

		require_once 'includes/llms.helper.functions.php';

		require_once 'includes/class.llms.helper.api.php';

		require_once 'includes/class.llms.helper.cloned.php';
		require_once 'includes/class.llms.helper.admin.settings.php';
		require_once 'includes/class.llms.helper.admin.notices.php';
		require_once 'includes/class.llms.helper.admin.ajax.php';
		require_once 'includes/abstract.llms.helper.updater.php';
		require_once 'includes/class.llms.helper.theme.updater.php';
		require_once 'includes/class.llms.helper.plugin.updater.php';

	}


	/**
	 * Determine if a package is in our array of themes or plugin
	 *
	 * @param  string $package slug, __FILE__, or basename of package
	 * @return bool / string
	 *
	 * @since 2.0.0
	 * @version  2.1.0
	 */
	function in_helper_array( $package, $type ) {

		// ensure we allow lifterlms itself to run through .org
		if ( 'lifterlms' !== $package ) {

			$type .= 's'; // add an s because our arrays our plural

			if ( isset ( $this->$type ) ) {

				foreach ( $this->$type as $p ) {

					if ( strpos( $p, $package ) !== false ) {

						return $p;

					}

				}

			}

		}

		return false;

	}


	/**
	 * Output lightbox information for our custom plugins & themes
	 *
	 * @param  mixed  $result response object
	 * @param  string $action api call action
	 * @param  obj    $args   object of arguments
	 * @return obj
	 *
	 * @since  1.0.0
	 */
	public function handle_lightbox( $result, $action, $args ) {

		// skip other actions
		if( $action != 'plugin_information' ) {
			return $result;
		}

		$slug = $this->in_helper_array( $args->slug, 'plugin' );
		if( $slug ) {

			$p = new LLMS_Helper_Plugin_Updater( $slug );
			// override result with our info
			$result = $p->get_lightbox_data();

		}

		return $result;

	}

	/**
	 * Load l10n files
	 * The first loaded file takes priority
	 *
	 * Files can be found in the following order:
	 * 		WP_LANG_DIR/lifterlms/lifterlms-helper-LOCALE.mo (safe directory will never be automatically overwritten)
	 * 		WP_LANG_DIR/plugins/lifterlms-helper-LOCALE.mo (unsafe directory, may be automatically updated)
	 * 		wp-content/plugins/lifterlms-helper/i18n/lifterlms-helper-LOCALE.mo (unsafe directory, will be automatically overwritten)
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
	 * Update activation key options to follow new naming convention
	 * @return   void
	 * @since    2.4.0
	 * @version  2.4.0
	 */
	private function migrate_keys() {

		global $wpdb;

		$options = array(
			'lifterlms_convertkit_activation_key' => 'lifterlms-convertkit_activation_key',
			'lifterlms_paypal_activation_key' => 'lifterlms-gateway-paypal_activation_key',
			'lifterlms_gravityforms_activation_key' => 'lifterlms-integration-gravity-forms_activation_key',
			'lifterlms_woocommerce_activation_key' => 'lifterlms-integration-woocommerce_activation_key',
			'lifterlms_mailchimp_activation_key' => 'lifterlms-mailchimp_activation_key',
			'lifterlms_stripe_activation_key' => 'lifterlms-stripe_activation_key',
			'lifterlms_xapi_activation_key' => 'lifterlms-integration-xapi',
		);

		foreach( $options as $old => $new ) {
			$q = $wpdb->update(
				$wpdb->options,
				array( 'option_name' => $new ),
				array( 'option_name' => $old )
			);
		}

		update_option( 'llms_helper_key_migration', 'yes' );
	}

	/**
	 * Determine if there's an update available for our plugins
	 *
	 * @param   obj $transient  transient object
	 * @return  obj
	 *
	 * @since   1.0.0
	 * @version 2.4.2
	 */
	public function pre_set_transient( $transient ) {

		// store our results in a transient so we don't make multiple API calls
		// during the many times this thing can get updated during an update check
		$llms_transient = str_replace( 'pre_set_site_transient', 'llms_helper', current_filter() ); // llms_helper_update_themes OR llms_helper_update_plugins
		$response = get_site_transient( $llms_transient );

		// no transient, check our themes or plugins
		if ( ! $response ) {

			$response = array();
			$slugs = array();

			if ( 'pre_set_site_transient_update_themes' === current_filter() ) {

				foreach ( $this->themes as $theme ) {
					// only check for installed plugins
					if( ! file_exists( WP_CONTENT_DIR . get_theme_roots() . DIRECTORY_SEPARATOR . $theme ) ) {
						unset( $this->themes[ $theme ] );
						continue;
					}

					$slugs[] = llms_helper_get_extension_slug( $theme );

				}

				if ( $slugs ) {

					$result = wp_remote_post( $this->api_url . '/get_release_info', array(
						'body' => array(
							'slug' => $slugs,
						),
						'sslverify' => false,
					) );

					if ( is_wp_error( $result ) ) {
						return $transient;
					}

					$r = json_decode( $result['body'], true );

					if ( ! empty ( $r['success'] ) && ! empty ( $r['packages'] ) ) {

						foreach ( $this->themes as $theme ) {

							if ( isset( $r['packages'][ llms_helper_get_extension_slug( $theme ) ] ) ) {

								$t = new LLMS_Helper_Theme_Updater( $theme );
								$latest = $r['packages'][ llms_helper_get_extension_slug( $theme ) ];

								// if latest is greater than current, we want to update
								$update = ( isset( $latest['version'] ) ) ? version_compare( $latest['version'], $t->theme_data['Version'], '>' ) : false;

								// if we need an update, load the data into the transient object
								if( $update ) {

									$response[$t->theme_slug] = $t->get_transient_data( $latest['version'] );

								}

							}


						}

					}

				}

			} elseif ( 'pre_set_site_transient_update_plugins' === current_filter() ) {

				// start updater for all plugins that need updates
				foreach ( $this->plugins as $plugin ) {

					// only check for installed plugins
					if( ! file_exists( WP_PLUGIN_DIR . DIRECTORY_SEPARATOR . $plugin ) ) {
						unset( $this->plugins[ $plugin ] );
						continue;
					}

					$slugs[] = llms_helper_get_extension_slug( $plugin );

				}

				if ( $slugs ) {

					$result = wp_remote_post( $this->api_url . '/get_release_info', array(
						'body' => array(
							'slug' => $slugs,
						),
						'sslverify' => false,
					) );

					if ( is_wp_error( $result ) ) {
						return $transient;
					}

					$r = json_decode( $result['body'], true );

					if ( ! empty ( $r['success'] ) && ! empty ( $r['packages'] ) ) {

						foreach ( $this->plugins as $plugin ) {

							if ( isset( $r['packages'][ llms_helper_get_extension_slug( $plugin ) ] ) ) {

								$p = new LLMS_Helper_Plugin_Updater( $plugin );
								$latest = $r['packages'][ llms_helper_get_extension_slug( $plugin ) ];

								// if latest is greater than current, we want to update
								$update = ( isset( $latest['version'] ) ) ? version_compare( $latest['version'], $p->plugin_data['Version'], '>' ) : false;

								// if we need an update, load the data into the transient object
								if( $update ) {

									$response[ $p->plugin_slug ] = $p->get_transient_data( $latest['version'] );

								}

							}


						}

					}

				}

			}


			// save the transient so we don't check again for at least 5 mins
			set_site_transient( $llms_transient, $response, MINUTE_IN_SECONDS * 5 );

		}

		// add our response to the transient
		if ( isset( $transient->response ) ) {

			$transient->response = array_merge( $transient->response, $response );

		}

		// return
		return $transient;

	}


	/**
	 * Check License key before serving an update and set the real download URL for the package
	 *
	 * @return array
	 *
	 * @since    1.0.0
	 * @version  2.4.2
	 */
	public function upgrader_package_authorization( $options ) {

		// only check auth on lifterlms plugins
		if( strpos( $options['package'], '//lifterlms.com' )  ) {

			$r = wp_remote_post( $options['package'], array(
				// 'sslverify' => false, // dev
			) );

			if ( is_wp_error( $r ) ) {

				$options['package'] = array(
					'LLMS-ERROR' => true,
					'data' => $r,
				);

				$options['package']['code'] = 'LLMS-PU-005';
				$options['package']['message'] = 'There was an error connecting to the LifterLMS upgrade servers. If this problem persists, please contact LifterLMS Support at https://lifterlms.com/';
				return $options;
			}

			$body = json_decode( $r['body'], true );

			if( $r['response']['code'] === 200 ) {

				// success
				if( !empty( $body['zip'] ) ) {

					$options['package'] = $body['zip'];
					return $options;

				}

				// error of some kind
				else {

					$error = true;

					$options['package'] = array(
						'LLMS-ERROR' => true,
						'data' => $r,
					);

					if ( ! isset( $body['message'] ) ) {

						$options['package']['code'] = 'LLMS-PU-002';
						$options['package']['message'] = 'An unkown error occurred during license key validation, please try again. If this problem persists, please contact LifterLMS Support at https://lifterlms.com/';

					} else {

						$options['package']['code'] = 'LLMS-PU-001';
						$options['package']['message'] = $body['message'];

					}

				}

			}

			// response code was not 200
			else {

				$error = true;

				$options['package'] = array(
					'LLMS-ERROR' => true,
					'data' => $r,
				);

				if( isset( $body['message'] ) ) {

					$options['package']['code'] = 'LLMS-PU-003';
					$options['package']['message'] = $body['message'];

				}
				// something else
				else {

					$options['package']['code'] = 'LLMS-PU-004';
					$options['package']['message'] = 'An unkown error occurred during license key validation, please try again. If this problem persists, please contact LifterLMS Support at https://lifterlms.com/';

				}

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
	 * @return  void
	 *
	 * @since  1.0.0
	 * @version  2.4.3
	 */
	public function upgrader_post_install( $response, $hook_extra, $result ) {

		$type = '';
		$package = '';

		if( isset( $hook_extra['theme'] ) ) {

			$type = 'theme';
			$package = $hook_extra['theme'];

		} elseif ( isset( $hook_extra['plugin'] ) ) {

			$type = 'plugin';
			$package = $hook_extra['plugin'];

		}

		$class = 'LLMS_Helper_' . ucwords( $type ) . '_Updater';

		// only run post install on our packages
		if( $this->in_helper_array( $package, $type ) ) {

			$p = new $class( $hook_extra[ $type ] );
			$result = $p->post_install( $result );

			// clear the cached data so that the update appears to have taken
			llms_helper_clear_transiets();

		}

		return $result;

	}


	/**
	 * Runs immediately prior to downloading the package
	 *
	 * We've replaced the URL with an internal error code and should display that message, otherwise, we have a good download link
	 *
	 * @param  bool   $response true
	 * @param  string $url      package download url
	 *
	 * @return mixed            true or WP error if we've encountered an auth error
	 *
	 * @since  1.0.0
	 */
	public function upgrader_pre_download( $response, $url, $upgrader ) {

		// $url will be an error with an "LLMS-ERROR" key if we're hijacking this to pass key errors
		if( is_array( $url ) && isset( $url['LLMS-ERROR'] ) ) {

			/**
			 * Show the error message during ajax plugin update attempts
			 * This isn't lovely
			 * @shame http://i.giphy.com/c2YyNySJ1CbFC.gif
			 */
			global $llms_helper_error;
			$llms_helper_error = $url;

			add_filter( 'gettext', function( $translated, $domain, $text ) {

				if( 'Plugin update failed.' === $translated ) {

					global $llms_helper_error;

					$translated .= ' ' . $llms_helper_error['message'] . ' ' . __( 'Reference Code:', 'lifterlms-helper' ) . ' ' . $llms_helper_error['code'];

				}

				return $translated;

			}, 20, 3 );

			return new WP_Error( $url['code'], $url['message'], $url );

		}

		// otherwise just return the response
		return $response;

	}


}

return new LLMS_Helper();

endif;
