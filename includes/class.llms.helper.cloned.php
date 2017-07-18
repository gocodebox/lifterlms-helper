<?php
/**
 * Automatically attempt to activate already activated add-ons
 * during clones
 * @since    2.5.0
 * @version  2.5.0
 */

// Restrict direct access
if ( ! defined( 'ABSPATH' ) ) { exit; }

class LLMS_Helper_Cloned {

	/**
	 * Constructor
	 * @since    2.5.0
	 * @version  2.5.0
	 */
	public function __construct() {

		add_action( 'llms_site_clone_detected', array( $this, 'handle_clone' ) );

	}

	/**
	 * Attempt to automatically activate already activated add-ons when cloning
	 * If the key cannot be activated all activation related data will be removed
	 * Called when LifterLMS core detects a cloned site
	 * @return   void
	 * @since    2.5.0
	 * @version  2.5.0
	 */
	public function handle_clone() {

		// get all potential products
		$products = array();
		foreach ( llms_helper_get_products() as $type => $slugs ) {
			$products = array_merge( $products, $slugs );
		}


		// create an slug=>key array of all activated addons with keys stored
		$keys = array();
		foreach ( $products as $product ) {

			$product = llms_helper_get_extension_slug( $product );

			// if not activated, unset the stored data
			$activated = get_option( $product . '_is_activated', 'no' );
			$key = get_option( $product . '_activation_key', '' );

			// if no key or not activated unset the stored data
			if ( ! $key || 'yes' !== $activated ) {
				llms_helper_clear_product_activation_data( $product );
				continue;
			}

			$keys[ $product ] = $key;

		}

		// if we have any keys, activate them on the new url
		if ( $keys ) {

			$api = new LLMS_Helper_Api();
			$res = $api->activate( $keys );

			// remove activation data for anything that didn't succeed
			foreach ( $res['activations'] as $product_slug => $data ) {
				if ( ! isset( $data['status'] ) || 'success' !== $data['status'] ) {
					llms_helper_clear_product_activation_data( $product_slug );
				}
			}

		}

	}

}

return new LLMS_Helper_Cloned();
