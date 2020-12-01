<?php
defined( 'ABSPATH' ) || exit;

/**
 * Handle status beta tab
 *
 * @since    3.0.0
 * @version  3.0.0
 */
class LLMS_Helper_Betas {

	/**
	 * Constructor
	 *
	 * @return   void
	 * @since    3.0.0
	 * @version  3.0.0
	 */
	public function __construct() {

		add_filter( 'llms_admin_page_status_tabs', array( $this, 'add_tab' ) );

		add_action( 'llms_before_admin_page_status', array( $this, 'output_tab' ) );

		add_action( 'admin_init', array( $this, 'handle_form_submit' ) );

	}

	/**
	 * Add the tab to the nav
	 *
	 * @param    array $tabs  existing tabs
	 * @return   array
	 * @since    3.0.0
	 * @version  3.0.0
	 */
	public function add_tab( $tabs ) {
		return llms_assoc_array_insert( $tabs, 'tools', 'betas', __( 'Beta Testing', 'lifterlms-helper' ) );
	}

	/**
	 * Handle channel subscription saves
	 *
	 * @return   void
	 * @since    3.0.0
	 * @version  3.0.0
	 */
	public function handle_form_submit() {

		if ( ! llms_verify_nonce( '_llms_beta_sub_nonce', 'llms_save_channel_subscriptions' ) ) {
			return;
		}

		foreach ( $_POST['llms_channel_subscriptions'] as $id => $channel ) {

			$addon = llms_get_add_on( $id );
			if ( 'channel' !== $addon->get_channel_subscription() ) {
				$addon->subscribe_to_channel( sanitize_text_field( $channel ) );
			}
		}

	}

	/**
	 * Output content for the beta testing screen
	 *
	 * @return   void
	 * @since    3.0.0
	 * @version  3.0.0
	 */
	public function output_tab( $curr_tab ) {

		if ( 'betas' !== $curr_tab ) {
			return;
		}

		$addons = llms_helper_get_available_add_ons();
		array_unshift( $addons, 'lifterlms-com-lifterlms', 'lifterlms-com-lifterlms-helper' );
		include 'views/beta-testing.php';

	}

}
return new LLMS_Helper_Betas();
