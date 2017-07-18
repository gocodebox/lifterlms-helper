<?php
/**
 * Plugin Updating Related functions
 *
 * @since    2.0.0
 * @version  2.5.0
 */

// Restrict direct access
if ( ! defined( 'ABSPATH' ) ) { exit; }

class LLMS_Helper_Plugin_Updater extends LLMS_Helper_Updater {

	/**
	 * Plugin Data
	 * Array of plugin data from WordPress get_plugin_data() function
	 * @var array
	 */
	public $plugin_data;

	/**
	 * Plugin Basename
	 * Actual plugin slug, eg "lifterlms-{extension_name}"
	 * @var  string
	 */
	public $plugin_basename;

	/**
	 * Plugin Slug
	 * result of WordPress plugin_basename() function
	 * this will be directory + main filename path
	 * @var string
	 */
	public $plugin_slug;

	/**
	 * __FILE__ of the plugin file
	 * @var string
	 */
	private $plugin_file;


	/**
	 * Constructor
	 * @param string   $plugin_file   path from the WP_PLUGIN_DIR
	 * @version  2.4.0
	 */
	function __construct( $plugin_file ) {

		$this->plugin_file = WP_PLUGIN_DIR . DIRECTORY_SEPARATOR . $plugin_file;

		$this->get_plugin_data();

		$this->activation_key = get_option( $this->plugin_basename . '_activation_key', '' );

	}


	/**
	 * Get Some basic information about the plugin from WordPress
	 * @return null
	 */
	private function get_plugin_data()
	{

		$this->plugin_data = get_plugin_data( $this->plugin_file );
		$this->plugin_slug = plugin_basename( $this->plugin_file );

		$this->plugin_basename = basename( $this->plugin_slug, '.php' );

		$this->update_key = get_option( $this->plugin_basename . '_update_key', false );

	}



	/**
	 * Retrive the latest version from the LLMS Api
	 * @return mixed     string or false
	 */
	public function get_latest_version_data( $slug = null )
	{

		$slug = ( $slug ) ? $slug : $this->plugin_basename;

		return parent::get_latest_version_data( $slug );

	}


	/**
	 * Output data to display in the lightbox when the "View Version {$version} details" link is clicked on the plugins screen
	 * @todo  possibly add download link
	 * @todo  add banners
	 * @todo  figure out why the "Plugin Name" isn't displaying
	 * @return obj
	 * @version  2.1.0
	 */
	public function get_lightbox_data( )
	{

		if ( ! class_exists( 'Parsedown' ) ) {
			require LLMS_HELPER_PLUGIN_DIR . 'includes/lib/parsedown.php';
		}

		$Parsedown = new Parsedown();

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
			'changelog' => $Parsedown->text( $data['notes'] ),
			'description' => $this->plugin_data['Description'],
		);

		return $r;

	}


	/**
	 * Get an object of data to load into the plugin transient object when an update is available
	 * @param  string $version new version number
	 * @return object
	 */
	public function get_transient_data( $version )
	{

		$package = $this->api_url . '/download';

		$args = array(
			'slug'       => $this->plugin_basename,
			'url'        => get_site_url(),
		);

		// helper will not have an update key
		// other plugins will fail without an update key when they actually attempt to request an update
		if ( $this->update_key ) {

			$args['update_key'] = $this->update_key;

		}

		$package = add_query_arg( $args, $package );

		// create an object of plugin data
		$obj = new stdClass();

		$obj->slug        = $this->plugin_basename;
		$obj->plugin      = $this->plugin_slug;
		$obj->new_version = $version;
		$obj->url         = $this->plugin_data["PluginURI"];
		$obj->package     = $package;

		return $obj;

	}


	/**
	 * Handle moving the plugin to it's intended directory after plugin installation
	 * @param    array $install_result   install data array
	 * @return   array
	 * @since    1.0.0
	 * @version  2.5.0
	 */
	public function post_install( $install_result ) {

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

			activate_plugin( $this->plugin_slug );

		}

		// return the update data
		return $install_result;

	}

}
?>
