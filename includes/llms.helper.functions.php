<?php

// Restrict direct access
if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * Get an extension "slug" from it's __FILE__
 * @param  string  $extension_file  plugin's __FILE__
 * @return string                   plugin slug
 */
function llms_helper_get_extension_slug( $extension_file ) {

	return basename( $extension_file, '.php' );

}

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
?>
