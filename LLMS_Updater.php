<?php
class LLMS_Updater
{

	/**
	 * Is the Plugin currently activated via LifterLMS.com?
	 * @var boolean
	 */
	private $activated = false;

	/**
	 * Cache API call results from Github
	 */
	private $github_api_result;

	/**
	 * Plugin Data
	 * @var ??
	 */
	private $plugin_data;

	/**
	 * Plugin Slug
	 * @var string
	 */
	private $plugin_slug;

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
	 * Constructor
	 * @param string   $plugin_file   __FILE__ of the plugin
	 */
	function __construct( $plugin_file )
	{

		$this->plugin_file = $plugin_file;

		add_action( 'admin_init', array( $this, 'maybe_init' ), 1 );

	}


	/**
	 * Add additional actions if plugin is activated with LifterLMS.com and has a saved update key
	 * @return null
	 */
	public function maybe_init()
	{

		$this->get_plugin_data();

		// make sure the plugin is activated and an update key exists before proceeding
		if( $this->activated === 'yes' && $this->update_key )
		{

			// get plugin information from github
			// called by WordPress before checking for plugin updates and after it gets results
			add_filter( 'pre_set_site_transient_update_plugins', array( $this, 'set_transient' ) );

			add_filter( 'plugins_api', array( $this, 'set_plugin_info' ), 10, 3 );

			add_filter( 'upgrader_post_install', array( $this, 'post_install' ), 10, 3 );

		}

	}











	/**
	 * Get Some basic information about the plugin from WordPress
	 * @return null
	 */
	private function get_plugin_data()
	{

		$this->activated = get_option( 'lifterlms_is_activated', '' );

		$this->plugin_data = get_plugin_data( $this->plugin_file );

		$this->plugin_slug = plugin_basename( $this->plugin_file );

		$this->update_key = get_option( 'lifterlms_update_key', '' );

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
		if ( empty( $transient->checked ) )
			return $transient;

		// Get plugin & GitHub release information
		$this->get_plugin_data();
		$this->get_repo_release_info();

		return;

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
	public function set_plugin_info( $false, $action, $response ) {

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