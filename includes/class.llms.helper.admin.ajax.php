<?php
/**
 * Admin AJAX Functions
 *
 * @package 	LifterLMS Helper
 * @category 	Core
 * @author 		codeBOX
 *
 * @since  1.0.0
 */

// Restrict direct access
if ( ! defined( 'ABSPATH' ) ) { exit; }

class LLMS_Helper_Admin_Ajax
{


	/**
	 * Constructor
	 *
	 * Register all ajax functions used by the plugin
	 */
	public function __construct()
	{

		add_action( 'wp_ajax_llms_helper_dismiss_notice', array( $this, 'dismiss_notice' ) );

	}


	/**
	 * Verify Ajax Referrer via Helper Nonce
	 *
	 * Will DIE when nonce doesn't match!
	 *
	 * @return void
	 */
	public function verify()
	{

		check_ajax_referer( LLMS_HELPER_NONCE, 'nonce' );

	}



	/**
	 * Allows user to dismiss error notifications that they don't want to resolve or see anymore
	 * @return json
	 */
	public function dismiss_notice()
	{


		$slug = llms_clean( stripslashes( $_POST['slug'] ) );

		$success = false;

		if( $slug ) {

		 	if( LLMS_Helper_Admin_Notices::remove_notice( $slug ) ) {

		 		$success = true;

		 	}

		}

		header( 'Content-Type: application/json' );
		echo json_encode( array( 'success' => $success ) );
		wp_die();

	}

}
return new LLMS_Helper_Admin_Ajax();
?>
