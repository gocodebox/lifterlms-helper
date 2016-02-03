<?php
class LLMS_Helper_Updater
{

	/**
	 * Is the Plugin currently activated via LifterLMS.com?
	 * @var boolean
	 */
	private $activated = false;

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
	 * Update Key
	 * @var string
	 */
	private $update_key;


	/**
	 * URL to query for release info
	 * @var string
	 */
	private $api_url = 'https://lifterlms.com.dev/llms-api';


	/**
	 * Constructor
	 * @param string   $plugin_file   __FILE__ of the plugin
	 */
	function __construct( $plugin_file )
	{

		$this->plugin_file = $plugin_file;

		$this->get_plugin_data();

		// make sure the plugin is activated and an update key exists before proceeding
		if( $this->activated === 'yes' && $this->update_key )
		{

			// add_filter( 'upgrader_post_install', array( $this, 'post_install' ), 10, 3 );

		}

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

		$this->activated =  get_option( $this->plugin_basename . '_is_activated', 'no' );
		$this->update_key = get_option( $this->plugin_basename . '_update_key', false );

	}



	/**
	 * Retrive the latest version from the LLMS Api
	 * @return mixed     string or false
	 */
	public function get_latest_version_data()
	{

		$result = wp_remote_post( $this->api_url . '/get_release_info', array(
			'body' => array(
				'slug'    => $this->plugin_basename,
			),
			'sslverify' => false,
		) );

		if( !is_wp_error( $result ) )
		{

			$r = json_decode( $result['body'], true );

			if( !empty( $r['success'] ) && isset( $r['version'] ) ) {

				return $r;

			}

		}

		return false;

	}


	/**
	 * Output data to display in the lightbox when the "View Version {$version} details" link is clicked on the plugins screen
	 * @todo  possibly add download link
	 * @todo  add banners
	 * @todo  figure out why the "Plugin Name" isn't displaying
	 * @return obj
	 */
	public function get_lightbox_data( )
	{

		$data = $this->get_latest_version_data();

		$r = new stdClass();
		$r->last_updated = $data['release_date'];
		$r->slug = $this->plugin_slug;
		$r->plugin_name  = $this->plugin_data['Name'];
		$r->version = $data['version'];
		$r->author = $this->plugin_data['AuthorName'];
		$r->homepage = $this->plugin_data['PluginURI'];

		// Create tabs in the lightbox
		$r->sections = array(
			'description' => $this->plugin_data['Description'],
			'changelog' => $data['notes'],
		);


		return $r;

	}


	/**
	 * Get an object of data to load into the plugin transient object when an update is available
	 * @param  string $version new version number
	 * @return object
	 */
	public function get_transient_object( $version )
	{

		$package = $this->api_url . '/download';

		$package = add_query_arg(
			array(
				'slug'       => $this->plugin_basename,
				'update_key' => $this->update_key,
				'url'        => get_site_url(),
			),
			$package
		);

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
	 * Retrive latest release information from Github
	 * @return null
	 */
	private function get_repo_release_info() {

		// if we've already called github, kill this function
		if( !empty( $this->github_api_result ) )
			return;

		// setup the url to call for plugin data & key validation
		$url = 'https://lifterlms.com.dev/';

		$url = add_query_arg(
			array(
 				'plugin'         => basename( $this->plugin_slug, '.php' ),
 				'request_update' => 1,
				'update_key'     => $this->update_key,
				'url'            => get_site_url(),
				'version'        => $this->plugin_data['Version']
			),
			$url
		);

		$this->api_result =  wp_remote_retrieve_body( wp_remote_get( $url, array(
			'sslverify' => false
		) ) );

		echo $this->api_result; die();

		// $url = 'https://api.github.com/repos/' . self::USERNAME . '/' . $this->github_repo . '/releases/?access_token=' . self::ACCESS_TOKEN;

		// get release information
		// $this->github_api_result = wp_remote_retrieve_body( wp_remote_get( $url ) );
		// if ( !empty( $this->github_api_result ) ) {
		// 	$this->github_api_result = @json_decode( $this->github_api_result );
		// }

		// // we only want the latest release
		// if( is_array( $this->github_api_result ) ) {
		// 	$this->github_api_result = $this->github_api_result[0];
		// }

	}







	/**
	 * Check if an update is required and add the data to the update transient if it is
	 * @param obj $transient  object of plugin information for plugins that need updates
	 */
	public function set_transient( $transient )
	{

		// If we have checked the plugin data before, don't re-check
		if ( empty( $transient->checked ) ) {
			return $transient;
		}

		return $transient;

		// Get plugin & GitHub release information
		// $this->get_plugin_data();
		// $this->get_repo_release_info();

		// return $transient;

		// Check the versions if we need to do an update
		$do_update = version_compare( $this->github_api_result->tag_name, $transient->checked[ $this->plugin_slug ] );

		// If an update is required, load the data into the plugin transient
		if ( $do_update ) {

			$package = $this->github_api_result->zipball_url;

			// Include the access token for private GitHub repos
			$package = add_query_arg( array( "access_token" => self::ACCESS_TOKEN ), $package );

			// create an object of plugin data
			$obj = new stdClass();
			$obj->slug = $this->plugin_slug;
			$obj->new_version = $this->github_api_result->tag_name;
			$obj->url = $this->plugin_data["PluginURI"];
			$obj->package = $package;

			// load it into the transient
			$transient->response[$this->plugin_slug] = $obj;
		}

		return $transient;

	}


	/**
	 * Add plugin version information to display plugin details in the lightbox
	 * @param [type] $false    [description]
	 * @param [type] $action   [description]
	 * @param [type] $response [description]
	 */
	public function set_plugin_info(  ) {



		// Get plugin & GitHub release information
		$this->get_plugin_data();
		$this->get_repo_release_info();

		// If nothing is found, do nothing
		if ( empty( $response->slug ) || $response->slug != $this->plugin_slug )
			return false;

		// Add our plugin information
		$response->last_updated = $this->github_api_result->published_at;
		$response->slug = $this->plugin_slug;
		$response->plugin_name  = $this->plugin_data["Name"];
		$response->version = $this->github_api_result->tag_name;
		$response->author = $this->plugin_data["AuthorName"];
		$response->homepage = $this->plugin_data["PluginURI"];

		// This is our release download zip file
		$download_link = $this->github_api_result->zipball_url;

		// Include the access token for private GitHub repos
		$download_link = add_query_arg( array( "access_token" => $this->accessToken ), $download_link );
		$response->download_link = $download_link;

		// Create tabs in the lightbox
		$response->sections = array(
			'description' => $this->plugin_data["Description"],
			'changelog' => $this->github_api_result->body
		);

		// // Gets the required version of WP if available
		// $matches = null;
		// preg_match( "/requires:\s([\d\.]+)/i", $this->githubAPIResult->body, $matches );
		// if ( ! empty( $matches ) ) {
		//     if ( is_array( $matches ) ) {
		//         if ( count( $matches ) > 1 ) {
		//             $response->requires = $matches[1];
		//         }
		//     }
		// }

		// // Gets the tested version of WP if available
		// $matches = null;
		// preg_match( "/tested:\s([\d\.]+)/i", $this->githubAPIResult->body, $matches );
		// if ( ! empty( $matches ) ) {
		//     if ( is_array( $matches ) ) {
		//         if ( count( $matches ) > 1 ) {
		//             $response->tested = $matches[1];
		//         }
		//     }
		// }

		return $response;

	}

	/**
	 * Called after installation complete, moves directory to the correct name and reactivates if the plugin was active
	 * @param  [type] $true       [description]
	 * @param  [type] $hook_extra [description]
	 * @param  [type] $result     [description]
	 * @return [type]             [description]
	 */
	public function post_install( $true, $hook_extra, $result ) {

		// Get plugin info
		$this->get_plugin_data();

		// Remember if our plugin was previously activated
		$was_activated = is_plugin_active( $this->plugin_slug );

		// Since we are hosted in GitHub, our plugin folder would have a dirname of
		// reponame-tagname change it to our original one:
		global $wp_filesystem;
		$plugin_dir = WP_PLUGIN_DIR . DIRECTORY_SEPARATOR . dirname( $this->plugin_slug );
		$wp_filesystem->move( $result['destination'], $plugin_dir );
		$result['destination'] = $plugin_dir;

		// reactivate the plugin if it was active previously
		if( $was_activated )
			$activate = activate_plugin( $this->slug );

		return $result;

	}

}
?>