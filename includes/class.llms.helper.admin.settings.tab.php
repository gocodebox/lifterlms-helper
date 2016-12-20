<?php
/**
 * Filters for LifterLMS & LaunchPad admin options for license key saving & validation
 *
 * @package 	LifterLMS Helper
 * @category 	Core
 * @author 		codeBOX
 *
 * @since    2.4.0
 * @version  2.4.0
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

class LLMS_Helper_Admin_Settings_Tab extends LLMS_Settings_Page {

	// private $api_url = 'https://lifterlms.com/wp-json/llms-api/v2';
	private $api_url = 'https://lifterlms.com.dev/wp-json/llms-api/v2';

	/**
	 * Allow settings page to determine if a rewrite flush is required
	 * @var      boolean
	 */
	protected $flush = false;

	/**
	 * Constructor
	 * @since    2.4.0
	 * @version  2.4.0
	 */
	public function __construct() {

		$this->id    = 'helper';
		$this->label = __( 'Helper', 'lifterlms-helper' );

		add_filter( 'lifterlms_settings_tabs_array', array( $this, 'add_settings_page' ), 20 );
		add_action( 'lifterlms_settings_' . $this->id, array( $this, 'output' ) );
		add_action( 'lifterlms_settings_save_' . $this->id, array( $this, 'save' ) );

	}

	/**
	 * Simple api wrapper
	 * Responds with false if there was an error or the parsed success body as an array
	 * @param    string     $method  api method to call
	 * @param    array      $data    array of data to send to the api
	 * @return   false|array
	 * @since    2.4.0
	 * @version  2.4.0
	 */
	private function call( $method, $data = array() ) {

		// attempt to activate
		$res = wp_remote_post( $this->api_url . '/' . $method, array(
			'body' => json_encode( $data ),
			'headers' => array(
				'Content-type' => 'application/json',
			),
			'sslverify' => false, // for local testing only
		) );

		if ( is_wp_error( $res ) ) {

			LLMS_Admin_Settings::set_error( $res->get_error_message() );
			return false;

		} else {

			if ( $res['response']['code'] === 200 ) {

				return json_decode( $res['body'], true );

			} else {

				LLMS_Admin_Settings::set_error( __( 'An unknown error occurred, please try again.', 'lifterlms-helper' ) );
				return false;

			}

		}

	}

	/**
	 * Compile, validate, sanitize posted data and send to activation api
	 * @return   void
	 * @since    2.4.0
	 * @version  2.4.0
	 */
	private function do_activations() {

		$data = array(
			'activations' => array(),
		);

		// setup one array of themes and plugins
		$submitted = array();
		if ( isset( $_POST['llms_keys']['plugins'] ) ) {
			$submitted = array_merge( $submitted, $_POST['llms_keys']['plugins'] );
		}
		if ( isset( $_POST['llms_keys']['themes'] ) ) {
			$submitted = array_merge( $submitted, $_POST['llms_keys']['themes'] );
		}

		foreach ( $submitted as $slug => $key ) {

			// don't attempt to activate if no key submitted
			if ( empty( $key ) ) {
				continue;
			}

			$key = sanitize_text_field( $key );

			$product = llms_helper_get_extension_slug( $slug );

			$data['activations'][] = array(
				'key' => $key,
				'product' => $product,
				'url' => get_site_url(),
			);

			// store the submitted key
			update_option( $product . '_activation_key', $key );

		}

		$res = $this->call( 'activate', $data );

		if ( ! $res ) {
			return;
		}

		// store response, show html functions can access and display\ response message
		$this->activations = $res['activations'];

		// loop through results and store keys for each depending on the status
		foreach( $res['activations'] as $slug => $a ) {

			if ( 'success' === $a['status'] && $a['update_key'] ) {

				$ukey = sanitize_text_field( $a['update_key'] );
				$active = 'yes';

			} else {

				$ukey = '';
				$active = 'no';

			}

			// save update key
			update_option( $slug . '_update_key', $ukey );
			// mark the add-on as active
			update_option( $slug . '_is_activated', $active );

		}

	}

	/**
	 * Deactivate a single product via API
	 * @return   void
	 * @since    2.4.0
	 * @version  2.4.0
	 */
	private function do_deactivation() {

		$product = sanitize_text_field( $_POST['llms_deactivate'] );

		// get data to pass to API
		$license_key = get_option( $product . '_activation_key', '' );
		$update_key  = get_option( $product . '_update_key', '' );

		// clear all saved data
		update_option( $product . '_activation_key', '' );
		update_option( $product . '_update_key', '' );
		update_option( $product . '_is_activated', 'no' );

		// call the api
		$res = $this->call( 'deactivate', array(
			'license_key' => $license_key,
			'update_key' => $update_key,
			'product' => $product,
			'url' => get_site_url(),
		) );

		if ( ! $res ) {
			return;
		}

		$this->deactivation = $res;

	}

	/**
	 * Retrieve array of settings fields
	 * @return   array
	 * @since    2.4.0
	 * @version  2.4.0
	 */
	public function get_settings() {

		$fields = array();

		$fields[] = array(
			'class' => 'top',
			'id' => 'helper_options_start',
			'type' => 'sectionstart',
		);

		$fields[] = array(
			'title' => __( 'LifterLMS Helper', 'lifterlms-helper' ),
			'type' => 'title',
			'id' => 'helper_options',
		);

		$fields[] = array(
			'desc' => __( 'Activate your LifterLMS Add-ons with lifterlms.com so you can update them automatically.', 'lifterlms-helper' ),
			'title' => __( 'Add-on Activation', 'lifterlms-helper' ),
			'type' => 'subtitle',
		);

		$fields[] = array(
			'type' => 'custom-html',
			'value' => $this->get_table_html(),
		);

		$fields[] = array(
			'id' => 'helper_options_end',
			'type' => 'sectionend',
		);

		return apply_filters( 'lifterlms_helper_settings', $fields );

	}

	/**
	 * Get the full HTML for the table
	 * @return   string
	 * @since    2.4.0
	 * @version  2.4.0
	 */
	private function get_table_html() {

		$html = '
			<table class="wp-list-table widefat fixed llms-helper-table">
				<thead>' . $this->get_table_header_row_html() . '</thead>
				<tbody>
		';

		$i = 1;

		$products = llms_helper_get_products();
		foreach ( $products['plugins'] as $plugin ) {

			// skip the helper because it doesn't require a key
			if ( 'lifterlms-helper/lifterlms-helper.php' === $plugin ) {
				continue;
			}

			$p = new LLMS_Helper_Plugin_Updater( $plugin );
			$data = $p->plugin_data;
			$html .= $this->get_table_row_html( array(
				'stripe' => ( 1 === $i % 2 ) ? ' stripe' : '',
				'id' => $plugin,
				'field_name' => 'llms_keys[plugins][' . $plugin . ']',
				'key' => $p->activation_key,
				'name' => $data['Name'],
				'url' => $data['PluginURI'],
				'version' => $data['Version'],
			) );

			$i++;

		}
		foreach ( $products['themes'] as $theme ) {

			$t = new LLMS_Helper_Theme_Updater( $theme );
			$data = $t->theme_data;
			$html .= $this->get_table_row_html( array(
				'stripe' => ( 1 === $i % 2 ) ? ' stripe' : '',
				'id' => $theme,
				'field_name' => 'llms_keys[themes][' . $theme . ']',
				'key' => $t->activation_key,
				'name' => $data->get( 'Name' ),
				'url' => $data->get( 'ThemeURI' ),
				'version' => $data->get( 'Version' ),
			) );

			$i++;

		}

		$html .= '
				</tbody>
				<tfoot>' . $this->get_table_header_row_html() . '</tfoot>
			</table>
		';

		return $html;

	}

	/**
	 * Get header row html
	 * @return   string
	 * @since    2.4.0
	 * @version  2.4.0
	 */
	private function get_table_header_row_html() {
		return '<tr>
			<th class="llms-helper-name">' . __( 'Add-on', 'lifterlms-helper' ) . '</th>
			<th class="llms-helper-version">' . __( 'Version', 'lifterlms-helper' ) . '</th>
			<th class="llms-helper-key">' . __( 'Key', 'lifterlms-helper' ) . '</th>
			<th class="llms-helper-actions">' . __( 'Actions', 'lifterlms-helper' ) . '</th>
		</tr>';
	}

	/**
	 * Get the HTML for a single product in the table
	 * @param    array     $data  array of product data
	 * @return   string
	 * @since    2.4.0
	 * @version  2.4.0
	 */
	private function get_table_row_html( $data ) {

		$msg = false;
		$type = 'regular';

		$id = llms_helper_get_extension_slug( $data['id'] );
		if ( isset( $this->activations ) ) {
			if ( isset( $this->activations[ $id ] ) ) {
				$msg = $this->activations[ $id ]['message'];
				$url = '/(http|https|ftp|ftps)\:\/\/[a-zA-Z0-9\-\.]+\.[a-zA-Z]{2,3}(\/\S*)?/';
				$msg = preg_replace( $url, '<a href="$0" target="_blank">$0</a>', $msg );
				$type = $this->activations[ $id ]['status'];
			}
		} elseif ( isset( $this->deactivation ) ) {
			if ( isset( $this->deactivation['product'] ) && $id === $this->deactivation['product'] ) {
				$msg = $this->deactivation['message'];
				$type = $this->deactivation['status'];
			}
		}

		$activated = get_option( $id . '_is_activated', 'no' );

		$html = '<tr class="type-' . $type . $data['stripe'] . '">';

			$html .= '<td class="llms-helper-name"><a href="' . esc_url( $data['url'] ) . '" target="_blank">' . $data['name'] . '</a></td>';
			$html .= '<td class="llms-helper-version">' . $data['version'] . '</td>';
			$html .= '<td class="llms-helper-key">';
				if ( 'yes' === $activated ) {
					$html .= '<pre>' . $data['key'] . '</pre>';
					$html .= '<span class="llms-helper-activation-status"><span class="dashicons dashicons-yes"></span></span>';
				} else {
					$html .= '<input class="regular-text code" name="' . $data['field_name'] . '" type="text" value="' . $data['key'] . '">';
				}

			$html .'</td>';
			$html .= '<td class="llms-helper-actions">';
				if ( 'yes' === $activated ) {
					$html .= '<button class="llms-button-danger small" name="llms_deactivate" type="submit" value="' . $id . '">' . __( 'Deactivate', 'lifterlms-helper' ) . '</button>';
				}
			$html .= '</td>';

			if ( $msg ) {
				$html .= '<tr class="message' . $data['stripe'] . '"><td colspan="4"><div class="notice inline notice-' . $type .' notice-alt"><p>' . $msg . '</p></td></tr>';
			}

		$html .= '</tr>';
		return $html;
	}

	/**
	 * Save actions
	 * Routes to actual functions depending on submit button used
	 * @return   void
	 * @since    2.4.0
	 * @version  2.4.0
	 */
	public function save() {

		if ( ! empty( $_POST['llms_deactivate'] ) ) {
			$this->do_deactivation();
		} elseif ( ! empty( $_POST['llms_keys'] ) && is_array( $_POST['llms_keys'] ) ) {
			$this->do_activations();
		}



	}

}

return new LLMS_Helper_Admin_Settings_Tab();
