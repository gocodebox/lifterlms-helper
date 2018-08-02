<?php
/**
 * Helper functions
 * @since    2.2.0
 * @version  3.0.0
 */
defined( 'ABSPATH' ) || exit;

/**
 * Retrieve the LLMS_Helper_Options singleton
 * @return   obj
 * @since    3.0.0
 * @version  3.0.0
 */
function llms_helper_options() {
	return LLMS_Helper_Options::instance();
}

/**
 * Retrieve an array of addons that are available via currently active License Keys
 * @param    bool     $installable_only   if true, only includes installable addons
 *                                        if false, includes non-installable addons (like bundles)
 * @return   array
 * @since    3.0.0
 * @version  3.0.0
 */
function llms_helper_get_available_add_ons( $installable_only = true ) {

	$ids = array();
	foreach ( llms_helper_options()->get_license_keys() as $key ) {
		if ( 1 == $key['status'] ) {
			$ids = array_merge( $ids, $key['addons'] );
		}
		if ( false === $installable_only ) {
			$ids[] = $key['product_id'];
		}
	}

	return array_unique( $ids );

}
