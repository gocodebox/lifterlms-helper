<?php
/**
 * Updater functions for Themes
 *
 * @since  1.1.0
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
	 * @since  1.1.0
	 */
	public $theme_data;

	/**
	 * Theme stylesheet
	 * The theme "stylesheet" directory, eg "lifterlms-{theme name}/"
	 * @var  string
	 *
	 * @since  1.1.0
	 */
	public $theme_stylesheet;


	/**
	 * Theme slug
	 * @var string
	 *
	 * @since  1.1.0
	 */
	public $theme_slug;

	/**
	 * Update Key
	 * @var string
	 *
	 * @since  1.1.0
	 */
	private $update_key;


	/**
	 * Constructor
	 * @param string   $theme   path from the WP_PLUGIN_DIR
	 *
	 * @since  1.1.0
	 */
	function __construct( $theme ) {

		$this->theme_stylesheet = $theme;
		$this->get_theme_data();

	}


	/**
	 * Get basic information about the theme
	 * @return null
	 *
	 * @since  1.1.0
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
	 * Output data to display in the lightbox when the "View Version {$version} details" link is clicked on the plugins screen
	 * @todo  possibly add download link
	 * @todo  add banners
	 * @todo  figure out why the "Plugin Name" isn't displaying
	 * @return obj
	 */
	public function get_lightbox_data()
	{

		$data = $this->get_latest_version_data();

		$r = new stdClass();
		$r->last_updated = $data['release_date'];
		$r->slug = $this->plugin_slug;
		$r->plugin_name  = $this->plugin_data['Name'];
		$r->version = $data['version'];
		$r->author = $this->plugin_data['AuthorName'];
		$r->homepage = $this->plugin_data['PluginURI'];
		$r->name = $this->plugin_data['Name'];

		// Create tabs in the lightbox
		$r->sections = array(
			'description' => $this->plugin_data['Description'],
			'changelog' => $data['notes'],
		);


		return $r;

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
	 * Handle moving the plugin to it's intended directory after plugin installation
	 * @param  array $install_result   install data array
	 * @return array
	 */
	public function post_install( $install_result )
	{

		// is the plugin currently active? we'll re-activate later if it is
		$active = is_plugin_active( $this->plugin_slug );

		global $wp_filesystem;

		// where we want the plugin
		$dir = WP_PLUGIN_DIR . DIRECTORY_SEPARATOR . dirname( $this->plugin_slug );

		// move it
		$wp_filesystem->move( $install_result['destination'], $dir );

		// update the install_result array
		$install_result['destination'] = $dir;

		// reactivate the plugin if it was active previously
		if( $active ) {

			activate_plugin( $this->slug );

		}

		// return the update data
		return $install_result;

	}

}
?>
