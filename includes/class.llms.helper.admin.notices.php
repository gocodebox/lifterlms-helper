<?php
/**
 * Output Admin Notices
 * @since     1.0.0
 * @version   2.5.0
 */

// Restrict direct access
if ( ! defined( 'ABSPATH' ) ) { exit; }

class LLMS_Helper_Admin_Notices {

	public static $option_name = 'lifterlms_helper_admin_notices';

	public function __construct() {

		// don't output messages when saving options
		if( $_SERVER['REQUEST_METHOD'] == 'POST' ) return;
			add_action( 'admin_notices', 'LLMS_Helper_Admin_Notices::output_notices', 777 );

	}

	/**
	 * Add a notice to the array of active notices
	 * @param string $extension_file __FILE__ of the extension
	 */
	public static function add_notice( $extension_file, $data )
	{

		$notices = self::get_notices();

		$notices[ $extension_file ] = $data;

		// update the option
		return update_option( self::$option_name, $notices );

	}

	/**
	 * Remove a notice from the array of active notices by key
	 * @param string $extension_file __FILE__ of the extension
	 * @return bool
	 */
	public static function remove_notice( $extension_file )
	{

		$notices = self::get_notices();

		unset( $notices[ $extension_file ] );

		return update_option( self::$option_name, $notices );

	}


	/**
	 * Get all active notice slugs
	 * @return array     array of extension slugs
	 */
	public static function get_notices()
	{

		return get_option( self::$option_name, array() );

	}


	public static function output_notices()
	{

		// only display this to users who can actually mess with plugins
		if( !current_user_can( 'install_plugins' ) ) return;

		$notices = self::get_notices();

		foreach( $notices as $extension_file => $notice )
		{

			// get data
			$data = get_plugin_data( $extension_file );

			if ( ! empty( $data['Name'] ) ) {

				$name = $data['Name'];

			} else {

				$data = wp_get_theme( $extension_file );
				$name = $data->get( 'Name' );

			}

			// // setup vars for success
			if( $notice['success'] ) {

				$class = 'updated';
				$type = '';
				$dismiss = '';

				$notice['message'] .= __( 'activation successful!', 'lifterlms-helper' );

				// immediately success notices after displaying them
				self::remove_notice( $extension_file );

			}
			// vars for error
			else {

				$class = 'error';
				$type = ' ' . __( 'Error', 'lifterlms-helper' );
				$dismiss = '<a class="llms-helper-dismiss" data-nonce="' . LLMS_HELPER_NONCE . '" data-slug="' . $extension_file . '" href="#" id="lifterlms-helper-dismiss-notice"><span class="dashicons dashicons-no"></span></a>';

				if( isset( $notice['reference_code'] ) ) {
					switch( $notice['reference_code'] )
					{

						// add a link to the integrations page
						case 'LLMS-SA-001':
							$notice['message'] .= ' ' . __( 'Visit <a href="' . admin_url( 'admin.php?page=llms-settings&tab=integrations' ) . '">LifterLMS Integrations</a> to try again.', 'lifterlms-helper' );
						break;

						// do nothing
						case 'LLMS-SA-002': // inactive license
						case 'LLMS-SA-003': // max activations
						case 'LLMS-SA-004': // server error
						break;

					}
				}

			}


			echo '<div class="' . $class . '"><p>';
			echo '<strong>' . $name . '' . $type . ': </strong>';
			echo $notice['message'];
			echo $dismiss;
			echo '</p></div>';

		}

	}

}

return new LLMS_Helper_Admin_Notices();
