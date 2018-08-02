<?php
defined( 'ABSPATH' ) || exit;

/**
 * Actions and LifterLMS.com API interactions related to plugin and theme updates for LifterLMS premium add-ons
 * @since    3.0.0
 * @version  3.0.0
 */
class LLMS_Helper_Upgrader {

	protected static $_instance = null;

	/**
	 * Main Instance of LLMS_Helper_Upgrader
	 * Ensures only one instance of LifterLMS is loaded or can be loaded.
	 * @return   LLMS_Helper_Upgrader - Main instanceg
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
	 * Constructor
	 * @since    3.0.0
	 * @version  3.0.0
	 */
	private function __construct() {

		// setup a llms add-on plugin info
		add_filter( 'plugins_api', array( $this, 'plugins_api' ), 10, 3 );

		// authenticate and get a real download link during add-on upgrade attempts
		add_filter( 'upgrader_package_options', array( $this, 'upgrader_package_options' ) );

		// add llms add-on info to list of available updates
		add_filter( 'pre_set_site_transient_update_themes', array( $this, 'pre_set_site_transient_update_things' ) );
		add_filter( 'pre_set_site_transient_update_plugins', array( $this, 'pre_set_site_transient_update_things' ) );

		$products = llms_get_add_ons();
		foreach ( (array) $products['items'] as $product ) {

			if ( 'plugin' === $product['type'] && $product['update_file'] ) {
				add_action( "in_plugin_update_message-{$product['update_file']}", array( $this, 'in_plugin_update_message' ), 10, 2 );
			}
		}

	}

	/**
	 * Install an add-on from LifterLMS.com
	 * @param    string|obj     $addon_or_id   ID for the add-on or an instance of the LLMS_Add_On
	 * @param    string         $action        installation type [install|update]
	 * @return   WP_Error|true
	 * @since    3.0.0
	 * @version  3.0.0
	 */
	public function install_addon( $addon_or_id, $action = 'install' ) {

		// setup the addon
		$addon = is_a( $addon_or_id, 'LLMS_Add_On' ) ? $addon_or_id : llms_get_add_on( $addon_or_id );
		if ( ! $addon ) {
			return new WP_Error( 'invalid_addon', __( 'Invalid add-on ID.', 'lifterlms-helper' ) );
		}

		if ( ! in_array( $action, array( 'install', 'update' ) ) ) {
			return new WP_Error( 'invalid_action', __( 'Invalid action.', 'lifterlms-helper' ) );
		}

		if ( ! $addon->is_installable() ) {
			return new WP_Error( 'not_installable', __( 'Add-on cannot be installable.', 'lifterlms-helper' ) );
		}

		// make sure it's not already installed
		if ( 'install' === $action && $addon->is_installed() ) {
			/* Translators: %s = Add-on name */
			return new WP_Error( 'installed', sprintf( __( '%s is already installed', 'lifterlms-helper' ), $addon->get( 'title' ) ) );
		}

		// get download info via llms.com api
		$dl_info = $addon->get_download_info();
		if ( is_wp_error( $dl_info ) ) {
			return $dl_info;
		}
		if ( ! isset( $dl_info['data']['url'] ) ) {
			return new WP_Error( 'no_url', __( 'An error occured while attempting to retrieve add-on download information. Please try again.', 'lifterlms-helper' ) );
		}

		require_once ABSPATH . 'wp-admin/includes/file.php';
		include_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
		WP_Filesystem();

		$skin = new Automatic_Upgrader_Skin();

		if ( 'plugin' === $addon->get_type() ) {

			$upgrader = new Plugin_Upgrader( $skin );

		} elseif ( 'theme' === $addon->get_type() ) {

			$upgrader = new Theme_Upgrader( $skin );

		} else {

			return new WP_Error( 'inconceivable', __( 'The requested action is not possible.', 'lifterlms-helper' ) );

		}

		if ( 'install' === $action ) {
			remove_filter( 'upgrader_package_options', array( $this, 'upgrader_package_options' ) );
			$result = $upgrader->install( $dl_info['data']['url'] );
			add_filter( 'upgrader_package_options', array( $this, 'upgrader_package_options' ) );
		} elseif ( 'update' === $action ) {
			$result = $upgrader->upgrade( $addon->get( 'update_file' ) );
		}

		if ( is_wp_error( $result ) ) {
			return $result;
		} elseif ( is_wp_error( $skin->result ) ) {
			return $skin->result;
		} elseif ( is_null( $result ) ) {
			return new WP_Error( 'filesystem', __( 'Unable to connect to the filesystem. Please confirm your credentials.', 'lifterlms-helper' ) );
		}

		return true;

	}

	/**
	 * Output additional information on plugins update screen when updates are available
	 * for an unlicensed addon
	 * @param    array     $plugin_data  array of plugin data
	 * @param    array     $res          response data
	 * @return   void
	 * @since    3.0.0
	 * @version  3.0.0
	 */
	public function in_plugin_update_message( $plugin_data, $res ) {

		if ( empty( $plugin_data['package'] ) ) {

			echo '<style>p.llms-msg:before { content: ""; }</style>';

			echo '<p class="llms-msg"><strong>';
			_e( 'Your LifterLMS add-on is currently unlicensed and cannot be updated!', 'lifterlms-helper' );
			echo '</strong></p>';

			echo '<p class="llms-msg">';
			/* Translators: %1$s = Opening anchor tag; %2$s = Closing anchor tag */
			printf( __( 'If you already have a license, you can activate it on the %1$sadd-ons management screen%2$s.', 'lifterlms-helper' ), '<a href="#">', '</a>' );
			echo '</p>';

			echo '<p class="llms-msg">';
			/* Translators: %s = URI to licensing FAQ */
			printf( __( 'Learn more about LifterLMS add-on licensing at %s.', 'lifterlms-helper' ), make_clickable( 'https://lifterlms.com/#' ) );
			echo '</p><p style="display:none;">';

		}

	}

	/**
	 * Filter API calls to get plugin information and replace it with data from LifterLMS.com API for our addons
	 * @param    bool       $response  false (denotes API call should be made to wp.org for plugin info)
	 * @param    string     $action    name of the API action
	 * @param    obj        $args      additional API call args
	 * @return   false|obj
	 * @since    3.0.0
	 * @version  3.0.0
	 */
	public function plugins_api( $response, $action = '', $args = null ) {

		if ( 'plugin_information' !== $action ) {
			return $response;
		}

		if ( empty( $args->slug ) ) {
			return $response;
		}

		$core = false;

		if ( 'lifterlms' === $args->slug ) {
			remove_filter( 'plugins_api', array( $this, 'plugins_api' ), 10, 3 );
			$args->slug = 'lifterlms-com-lifterlms';
			$core = true;
		}

		if ( 0 !== strpos( $args->slug, 'lifterlms-com-' ) ) {
			return $response;
		}

		$response = $this->set_plugins_api( $args->slug, true );

		if ( $core ) {
			add_filter( 'plugins_api', array( $this, 'plugins_api' ), 10, 3 );
		}

		return $response;

	}

	/**
	 * Handle setting the site transient for plugin updates
	 * @param    obj     $value  transient value
	 * @return   obj
	 * @since    3.0.0
	 * @version  3.0.0
	 */
	public function pre_set_site_transient_update_things( $value ) {

		if ( empty( $value ) ) {
			return $value;
		}

		$which = current_filter();
		if ( 'pre_set_site_transient_update_plugins' === $which ) {
			$type = 'plugin';
		} elseif ( 'pre_set_site_transient_update_themes' === $which ) {
			$type = 'theme';
		} else {
			return $value;
		}

		$all_products = llms_get_add_ons( false );

		foreach ( $all_products['items'] as $addon_data ) {

			$addon = llms_get_add_on( $addon_data );

			if ( ! $addon->is_installable() || ! $addon->is_installed() ) {
				continue;
			}

			if ( $type !== $addon->get_type() ) {
				continue;
			}

			$file = $addon->get( 'update_file' );

			if ( 'plugin' === $type ) {

				if ( 'lifterlms-com-lifterlms' === $addon->get( 'id' ) ) {
					if ( 'stable' === $addon->get_channel_subscription() || ! $addon->get( 'version_beta' ) ) {
						continue;
					}
				}

				$item = (object) $this->set_plugins_api( $addon->get( 'id' ), false );

			} elseif ( 'theme' === $type ) {

				$item = array(
					'theme' => $file,
					'new_version' => $addon->get_latest_version(),
					'url' => $addon->get_permalink(),
					'package' => true,
				);
			}

			if ( $addon->has_available_update() ) {

				$value->response[ $file ] = $item;
				unset( $value->no_update[ $file ] );

			} else {

				$value->no_update[ $file ] = $item;
				unset( $value->response[ $file ] );

			}
		}

		return $value;

	}

	/**
	 * Setup an object of addon data for use when requesting plugin information normally acquired from wp.org
	 * @param    string     $id  addon id
	 * @return   object
	 * @since    3.0.0
	 * @version  3.0.0
	 */
	private function set_plugins_api( $id, $include_sections = true ) {

		$addon = llms_get_add_on( $id );

		if ( 'lifterlms-com-lifterlms' === $id && false !== strpos( $addon->get_latest_version(), 'beta' ) ) {

			require_once ABSPATH . 'wp-admin/includes/plugin-install.php';
			$item = plugins_api( 'plugin_information', array(
				'slug' => 'lifterlms',
				'fields' => array(
					'banners' => true,
					'icons' => true,
				),
			) );
			$item->version = $addon->get_latest_version();
			$item->new_version = $addon->get_latest_version();
			$item->package = true;

			unset( $item->versions );

			$item->sections['changelog'] = $this->get_changelog_for_api( $addon );

			return $item;

		}

		$item = array(
			'name' => $addon->get( 'title' ),
			'slug' => $id,
			'version' => $addon->get_latest_version(),
			'new_version' => $addon->get_latest_version(),
			'author' => '<a href="https://lifterlms.com/">' . $addon->get( 'author' )['name'] . '</a>',
			'author_profile' => $addon->get( 'author' )['link'],
			'requires' => $addon->get( 'version_wp' ),
			'tested' => '',
			'requires_php' => $addon->get( 'version_php' ),
			'compatibility' => '',
			'homepage' => $addon->get( 'permalink' ),
			'download_link' => '',
			'package' => $addon->is_licensed() ? true : '',
			'banners' => array(
				'low' => $addon->get( 'image' ),
			),
		);

		if ( $include_sections ) {

			$item['sections'] = array(
				'description' => $addon->get( 'description' ),
				'changelog' => $this->get_changelog_for_api( $addon ),
			);

		}

		return (object) $item;

	}

	/**
	 * Retrieve the changelog for an addon
	 * @param    obj     $addon  LLMS_Add_On
	 * @return   string
	 * @since    3.0.0
	 * @version  3.0.0
	 */
	private function get_changelog_for_api( $addon ) {

		$changelog = file_get_contents( $addon->get( 'changelog' ) );
		preg_match( '#<body[^>]*>(.*?)</body>#si', $changelog, $changelog );
		// css on h2 is intended for plugin title in header image but causes huge gap on changelog
		return str_replace( array( '<h2 id="', '</h2>' ), array( '<h3 id="', '</h3>' ), $changelog[1] );

	}

	/**
	 * Get a real package download url for a LifterLMS add-on
	 * This is called immediately prior to package upgrades
	 * @param    [type]     $options  [description]
	 * @return   [type]
	 * @since    3.0.0
	 * @version  3.0.0
	 */
	public function upgrader_package_options( $options ) {

		if ( ! isset( $options['hook_extra'] ) ) {
			return $options;
		}

		if ( isset( $options['hook_extra']['plugin'] ) ) {
			$file = $options['hook_extra']['plugin'];
		} elseif ( isset( $options['hook_extra']['theme'] ) ) {
			$file = $options['hook_extra']['theme'];
		} else {
			return $options;
		}

		$addon = llms_get_add_on( $file, 'update_file' );
		if ( ! $addon || ! $addon->is_installable() || ! $addon->is_licensed() ) {
			return $options;
		}

		$info = $addon->get_download_info();
		if ( is_wp_error( $info ) || ! isset( $info['data'] ) || ! isset( $info['data']['url'] ) ) {
			return $options;
		}

		$options['package'] = $info['data']['url'];

		return $options;

	}

}
