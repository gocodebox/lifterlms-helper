<?php

// Restrict direct access
if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * Clears transients related to plugin & theme updates
 * @return   void
 * @since    2.2.0
 * @version  2.2.0
 */
function llms_helper_clear_transiets() {

	delete_site_transient( 'update_plugins' );
	delete_site_transient( 'update_themes' );
	delete_site_transient( 'llms_helper_update_themes' );
	delete_site_transient( 'llms_helper_update_plugins' );

}

/**
 * Clear the options related to a product's activation
 * @param    string     $product  product name (eg lifterlms-stripe, lifterlms-gateway-paypal, etc...)
 * @return   void
 * @since    2.5.0
 * @version  2.5.0
 */
function llms_helper_clear_product_activation_data( $product ) {

	update_option( $product . '_activation_key', '' );
	update_option( $product . '_update_key', '' );
	update_option( $product . '_is_activated', 'no' );

}

/**
 * Get an extension "slug" from it's __FILE__
 * @param  string  $extension_file  plugin's __FILE__
 * @return string                   plugin slug
 */
function llms_helper_get_extension_slug( $extension_file ) {

	return basename( $extension_file, '.php' );

}

/**
 * Retrieves a list of products eligible for updates from the helper
 * Stores the results in local cache for 48 hours to cut down
 * on external api requests
 * @return   array
 * @since    2.4.0
 * @version  2.4.1
 */
function llms_helper_get_products() {

	$products = get_transient( 'lifterlms-helper-products' );

	// nothing saved, retrieve them from the remote list
	if ( ! $products ) {

		$r = wp_remote_get( 'http://d34dpc7391qduo.cloudfront.net/helper-products.min.json' );

		if ( ! is_wp_error( $r ) ) {

			if ( $r['response']['code'] == 200 ) {

				$products = json_decode( $r['body'], true );

				set_transient( 'lifterlms-helper-products', $products, DAY_IN_SECONDS * 2 );

			}

		}

	}

	// ensure the return is in an acceptable format
	if ( ! is_array( $products ) ) {

		$products = array(
			'plugins' => array(),
			'themes' => array(),
		);

	} else {

		if ( ! isset( $products['plugins'] ) ) {
			$products['plugins'] = array();
		}

		if ( ! isset( $products['themes'] ) ) {
			$products['themes'] = array();
		}

	}

	// filter down the arrays that we work with installed products
	foreach ( $products['plugins'] as $key => $plugin ) {
		if ( ! file_exists( WP_PLUGIN_DIR . DIRECTORY_SEPARATOR . $plugin ) ) {
			unset( $products['plugins'][ $key ] );
		}
	}

	foreach( $products['themes'] as $key => $theme ) {
		if ( ! file_exists( WP_CONTENT_DIR . get_theme_roots() . DIRECTORY_SEPARATOR . $theme ) ) {
			unset( $products['themes'][ $key ] );
		}
	}

	return $products;

}
