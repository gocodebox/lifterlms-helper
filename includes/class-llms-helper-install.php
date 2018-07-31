<?php
defined( 'ABSPATH' ) || exit;

/**
 * Plugin installation
 * @since   [version]
 * @version [version]
 */
class LLMS_Helper_Install {

	/**
	 * Initialize the install class
	 * Hooks all actions
	 * @return   void
	 * @since    [version]
	 * @version  [version]
	 */
	public static function init() {
		add_action( 'init', array( __CLASS__, 'check_version' ), 5 );
	}

	/**
	 * Checks the current LLMS version and runs installer if required
	 * @return   void
	 * @since    [version]
	 * @version  [version]
	 */
	public static function check_version() {
		if ( ! defined( 'IFRAME_REQUEST' ) && get_option( 'llms_helper_version' ) !== LLMS_Helper()->version ) {
			self::install();
			do_action( 'llms_helper_updated' );
		}
	}

	/**
	 * Core install function
	 * @return  void
	 * @since   [version]
	 * @version [version]
	 */
	public static function install() {

		if ( ! is_blog_installed() ) {
			return;
		}

		do_action( 'llms_helper_before_install' );

		if ( ! get_option( 'llms_helper_version', '' ) ) {

			self::_migrate_300();

		}

		self::update_version();

		do_action( 'llms_helper_after_install' );
	}

	/**
	 * Update the LifterLMS version record to the latest version
	 * @param  string $version version number
	 * @return void
	 * @since    [version]
	 * @version  [version]
	 */
	public static function update_version( $version = null ) {
		delete_option( 'llms_helper_version' );
		add_option( 'llms_helper_version', is_null( $version ) ? LLMS_Helper()->version : $version  );
	}

	/**
	 * Migrate to version 3.0.0
	 * @return   [type]
	 * @since    [version]
	 * @version  [version]
	 */
	private static function _migrate_300() {

		$text = '<p><strong>' . __( 'Welcome to the LifterLMS Helper', 'lifterlms' ) . '</strong></p>';
		$text .= '<p>' . __( 'This plugin allows your website to interact with your subscriptions at LifterLMS.com to ensure your add-ons stay up to date.', 'lifterlms' ) . '</p>';
		$text .= '<p>' . sprintf( __( 'You can activate your add-ons from the %1$sAdd-Ons & More%2$s screen.', 'lifterlms' ), '<a href="' . admin_url( 'admin.php?page=llms-add-ons' ) . '">', '</a>' ) . '</p>';

		$keys = array();
		foreach ( llms_helper_get_available_add_ons() as $id ) {

			$addon = llms_get_add_on( $id );

			$option_name = sprintf( '%s_activation_key', $addon->get( 'slug' ) );

			$key = get_option( $option_name );
			if ( $key ) {
				$keys[] = get_option( $option_name );
			}

			delete_option( $option_name );
			delete_option( sprintf( '%s_update_key', $addon->get( 'slug' ) ) );

		}

		$res = LLMS_Helper_Keys::activate_keys( $keys );

		if ( ! is_wp_error( $res ) ) {

			$data = $res['data'];
			if ( isset( $data['activations'] ) ) {

				$text .= '<p>' . sprintf( _n( '%d license has been automatically migrated from the previous version of the LifterLMS Helper', '%d licenses have been automatically migrated from the previous version of the LifterLMS Helper.', count( $data['activations'] ), 'lifterlms' ), count( $data['activations'] ) ) . ':</p>';

				foreach ( $data['activations'] as $activation ) {
					LLMS_Helper_Keys::add_license_key( $activation );
					$text .= '<p><em>' . $activation['license_key'] . '</em></p>';
				}

			}

		}

		LLMS_Admin_Notices::flash_notice( $text, 'info' );

		// clean up legacy options
		$remove = array(
			'lifterlms_stripe_activation_key',
			'lifterlms_paypal_activation_key',
			'lifterlms_gravityforms_activation_key',
			'lifterlms_mailchimp_activation_key',
			'llms_helper_key_migration',
		);

		foreach ( $remove as $opt ) {
			delete_option( $opt );
		}

	}

}

LLMS_Helper_Install::init();
