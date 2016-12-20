<?php
/**
 * Theme update related functions
 *
 * @package 	LifterLMS Helper
 * @category 	Core
 * @author 		codeBOX
 *
 * @since  2.0.0
 * @version  2.2.0
 */

// Restrict direct access
if ( ! defined( 'ABSPATH' ) ) { exit; }

class LLMS_Helper_Theme_Updater extends LLMS_Helper_Updater
{

	/**
	 * Theme Data
	 * Array of theme data from WordPress wp_get_theme() function
	 * @var array
	 *
	 * @since  2.0.0
	 */
	public $theme_data;

	/**
	 * Theme stylesheet
	 * The theme "stylesheet" directory, eg "lifterlms-{theme name}/"
	 * @var  string
	 *
	 * @since  2.0.0
	 */
	public $theme_stylesheet;


	/**
	 * Theme slug
	 * @var string
	 *
	 * @since  2.0.0
	 */
	public $theme_slug;


	/**
	 * Constructor
	 * @param string   $theme   path from the WP_PLUGIN_DIR
	 *
	 * @since    2.0.0
	 * @version  2.4.0
	 */
	function __construct( $theme ) {

		$this->theme_stylesheet = $theme;
		$this->get_theme_data();
		$this->activation_key = get_option( $this->theme_slug . '_activation_key', '' );

	}


	/**
	 * Get basic information about the theme
	 * @return null
	 *
	 * @since  2.0.0
	 */
	private function get_theme_data() {

		$this->theme_slug = str_replace( '/', '', $this->theme_stylesheet );
		$this->theme_data = wp_get_theme( $this->theme_stylesheet );
		$this->update_key = get_option( $this->theme_slug . '_update_key', false );

	}


	/**
	 * Call the parent version data method with params
	 * @return mixed     string or false
	 */
	public function get_latest_version_data( $slug = null ) {

		$slug = ( $slug ) ? $slug : $this->theme_slug;

		return parent::get_latest_version_data( $slug );

	}


	/**
	 * Get an array of data to load into the theme transient object when an update is available
	 * @param  string $version new version number
	 * @return object
	 */
	public function get_transient_data( $version ) {

		$package = $this->api_url . '/download';

		$args = array(
			'slug'       => $this->theme_slug,
			'url'        => get_site_url(),
		);

		// free themes will not have an update key
		// other themes will fail without an update key when they actually attempt to request an update
		if( $this->update_key ) {

			$args['update_key'] = $this->update_key;

		}

		$package = add_query_arg( $args, $package );

		// create an object of plugin data
		return array(
			'theme' => $this->theme_slug,
			'new_version' => $version,
			'url' => $this->theme_data->get( 'ThemeURI' ),
			'package' => $package,
		);

	}


	/**
	 * Handle moving the theme to it's intended directory after theme installation
	 * @param  array $install_result   install data array
	 * @return array
	 * @since  2.0.0
	 * @version  2.2.0
	 */
	public function post_install( $install_result ) {

		// get the current theme
		$theme = wp_get_theme();

		// is the plugin currently active? we'll re-activate later if it is
		$active = ( $this->theme_stylesheet === $theme->get_stylesheet() ) ? true : false;

		// var_dump( $this, $install_result, $theme, $theme->get_stylesheet() );

		global $wp_filesystem;

		// where we want the plugin
		$dir = $install_result['local_destination'] . DIRECTORY_SEPARATOR . $this->theme_stylesheet;

		// move it
		$wp_filesystem->move( $install_result['destination'], $dir );

		// update the install_result array
		$install_result['destination'] = $dir;
		$install_result['destination_name'] = $this->theme_stylesheet;

		// reactivate the plugin if it was active previously
		if ( $active ) {

			add_action( 'switch_theme', array( $this, 'switch_theme' ), 10, 3 );

		}

		// return the update data
		return $install_result;

	}

	/**
	 * If the theme was active it'll be automatically switched back to the theme
	 * but it'll have the wrong name because our directory information out of GH is messed up
	 * catch the error here and run switch_theme() with the correct information, hopefully
	 *
	 * @param    string     $new_name   name of the theme
	 * @param    obj        $new_theme  instance of WP_Theme for the new theme (the one that doesn't actually exist...)
	 * @param    obj        $old_theme  instance of WP_Theme for the old theme (the one that should stay active)
	 * @return   void
	 * @since    2.2.0
	 * @version  2.2.0
	 */
	public function switch_theme( $new_name, $new_theme, $old_theme ) {

		$err = $new_theme->errors();

		if ( $err && 'theme_not_found' === $err->get_error_code() ) {

			switch_theme( $this->theme_stylesheet );

		}
	}

}
?>
