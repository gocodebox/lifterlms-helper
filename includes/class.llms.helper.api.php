<?php
/**
 * Simple API calls to LifterLMS.com activation server
 * @since    2.5.0
 * @version  2.5.0
 */

// Restrict direct access
if ( ! defined( 'ABSPATH' ) ) { exit; }

class LLMS_Helper_API {

	private $api_url = 'https://lifterlms.com/wp-json/llms-api/v2';
	// private $api_url = 'https://lifterlms.com.dev/wp-json/llms-api/v2';

	/**
	 * Constructor
	 * @since    2.5.0
	 * @version  2.5.0
	 */
	public function __construct() {}

	/**
	 * Activate Product(s)
	 * @param    array     $products  associative array of product => key
	 * @return   array
	 * @since    2.5.0
	 * @version  2.5.0
	 */
	public function activate( $products ) {

		$data = array(
			'activations' => array(),
		);

		foreach ( $products as $slug => $key ) {

			// don't attempt to activate if no key submitted
			if ( empty( $key ) ) {
				continue;
			}

			$key = sanitize_text_field( $key );

			$product = llms_helper_get_extension_slug( $slug );

			$data['activations'][] = array(
				'key' => $key,
				'product' => $product,
				'url' => get_site_url(),
			);

			// store the submitted key
			update_option( $product . '_activation_key', $key );

		}

		$res = $this->call( 'activate', $data );

		if ( $res ) {

			// loop through results and store keys for each depending on the status
			foreach( $res['activations'] as $slug => $a ) {

				if ( 'success' === $a['status'] && $a['update_key'] ) {

					$ukey = sanitize_text_field( $a['update_key'] );
					$active = 'yes';

				} else {

					$ukey = '';
					$active = 'no';

				}

				// save update key
				update_option( $slug . '_update_key', $ukey );
				// mark the add-on as active
				update_option( $slug . '_is_activated', $active );

			}

		}


		return $res;

	}

	/**
	 * Deactivate product(s)
	 * @param    array     $products  indexed array of product slugs
	 * @return   array
	 * @since    2.5.0
	 * @version  2.5.0
	 */
	public function deactivate( $products ) {

		$data = array(
			'deactivations' => array(),
		);

		$url = get_site_url();

		foreach ( $products as $product ) {

			$product = llms_helper_get_extension_slug( $product );

			// get data to pass to API
			$license_key = get_option( $product . '_activation_key', '' );
			$update_key  = get_option( $product . '_update_key', '' );

			if ( $license_key && $update_key ) {

				$data['deactivations'][] = array(
					'license_key' => $license_key,
					'update_key' => $update_key,
					'product' => $product,
					'url' => $url,
				);

			}

		}

		$res = $this->call( 'deactivate_bulk', $data );

		if ( $res ) {

			// clear stored activation-related data
			foreach( $res['deactivations'] as $product => $data ) {

				if ( 'success' === $data['status'] ) {

					// clear all saved data
					llms_helper_clear_product_activation_data( $product );

				}

			}

		}

		return $res;

	}

	/**
	 * Simple api wrapper
	 * Responds with false if there was an error or the parsed success body as an array
	 * @param    string     $method  api method to call
	 * @param    array      $data    array of data to send to the api
	 * @return   false|array
	 * @since    2.5.0
	 * @version  2.5.0
	 */
	private function call( $method, $data = array() ) {

		// attempt to activate
		$res = wp_remote_post( $this->api_url . '/' . $method, array(
			'body' => json_encode( $data ),
			'headers' => array(
				'Content-type' => 'application/json',
			),
			// 'sslverify' => false, // for local testing only
		) );

		if ( is_wp_error( $res ) ) {

			LLMS_Admin_Settings::set_error( $res->get_error_message() );
			return false;

		} else {

			if ( $res['response']['code'] === 200 ) {

				return json_decode( $res['body'], true );

			} else {

				LLMS_Admin_Settings::set_error( __( 'An unknown error occurred, please try again.', 'lifterlms-helper' ) );
				return false;

			}

		}

	}

}
