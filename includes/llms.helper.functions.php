<?php

// Restrict direct access
if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * Get an extension "slug" from it's __FILE__
 * @param  string  $extension_file  plugin's __FILE__
 * @return string                   plugin slug
 */
function llms_helper_get_extension_slug( $extension_file )
{

	return basename( $extension_file, '.php' );

}
?>
