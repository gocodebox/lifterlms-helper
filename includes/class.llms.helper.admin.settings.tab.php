<?php
/**
 * Filters for LifterLMS & LaunchPad admin options for license key saving & validation
 *
 * @since    2.4.0
 * @version  2.5.0
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

class LLMS_Helper_Admin_Settings_Tab extends LLMS_Settings_Page {

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

		$this->id    = 'licenses';
		$this->label = __( 'Licenses', 'lifterlms-helper' );

		add_filter( 'lifterlms_settings_tabs_array', array( $this, 'add_settings_page' ), 20 );
		add_action( 'lifterlms_settings_' . $this->id, array( $this, 'output' ) );
		add_action( 'lifterlms_settings_save_' . $this->id, array( $this, 'save' ) );

		add_filter( 'llms_admin_settings_submit_button_text', array( $this, 'submit_button_text'), 10, 2 );

	}



	/**
	 * Compile, validate, sanitize posted data and send to activation api
	 * @return   void
	 * @since    2.4.0
	 * @version  2.5.0
	 */
	private function do_activations() {

		// setup one array of themes and plugins
		$submitted = array();
		if ( isset( $_POST['llms_keys']['plugins'] ) ) {
			$submitted = array_merge( $submitted, $_POST['llms_keys']['plugins'] );
		}
		if ( isset( $_POST['llms_keys']['themes'] ) ) {
			$submitted = array_merge( $submitted, $_POST['llms_keys']['themes'] );
		}

		$api = new LLMS_Helper_API();
		$res = $api->activate( $submitted );

		// store response, show html functions can access and display response message
		$this->activations = $res['activations'];

	}

	/**
	 * Deactivate a single product via API
	 * @return   void
	 * @since    2.4.0
	 * @version  2.5.0
	 */
	private function do_deactivation() {

		$products = array();

		if ( isset( $_POST['llms_deactivate'] ) ) {

			$products[] = sanitize_text_field( $_POST['llms_deactivate'] );

		} elseif ( isset( $_POST['llms_bulk'] ) ) {

			// setup one array of themes and plugins
			if ( isset( $_POST['llms_bulk']['plugins'] ) ) {
				$products = array_merge( $products, array_keys( $_POST['llms_bulk']['plugins'] ) );
			}
			if ( isset( $_POST['llms_bulk']['themes'] ) ) {
				$products = array_merge( $products, array_keys( $_POST['llms_bulk']['themes'] ) );
			}
		}

		$api = new LLMS_Helper_API();
		$res = $api->deactivate( $products );

		if ( ! $res ) {
			return;
		}

		$this->deactivations = $res['deactivations'];

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
			'desc' => sprintf(
						__( 'Activate your LifterLMS Add-ons for automatic updates. Login to your %1$sLifterLMS.com Account%2$s to locate your keys and manage your activations from the cloud.', 'lifterlms-helper' ),
						'<a href="https://lifterlms.com/my-account/" target="_blank">',
						'</a>'
					),
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
	 * Get the HTML for the bulk actions selector/submitter
	 * @return   string
	 * @since    2.5.0
	 * @version  2.5.0
	 */
	private function get_bulk_actions_html() {

		return '<div class="llms-helper-bulk-actions">
			<select name="llms_bulk_action" id="llms-helper-bulk-actions-select" style="width:auto;">
				<option value="">' . esc_html__( 'Bulk Actions', 'lifterlms-helper' ) . '</option>
				<option value="deactivate">' . esc_html__( 'Deactivate', 'lifterlms-helper' ) . '</option>
			</select>
			<button class="llms-button-secondary small" name="llms_bulk_action_submit" type="submit">' . esc_html__( 'Submit', 'lifterlms-helper' ) . '</button>
		</div>';

	}

	/**
	 * Get the full HTML for the table
	 * @return   string
	 * @since    2.4.0
	 * @version  2.5.0
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
				'field_name_cb' => 'llms_bulk[plugins][' . $plugin . ']',
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
				'field_name_cb' => 'llms_bulk[themes][' . $theme . ']',
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

		$html .= $this->get_bulk_actions_html();

		return $html;

	}

	/**
	 * Get header row html
	 * @return   string
	 * @since    2.4.0
	 * @version  2.5.0
	 */
	private function get_table_header_row_html() {
		return '<tr>
			<th class="llms-helper-bulk"><input class="llms-helper-bulk-cb-all" type="checkbox"></th>
			<th class="llms-helper-name">' . __( 'Add-on', 'lifterlms-helper' ) . '</th>
			<th class="llms-helper-version">' . __( 'Version', 'lifterlms-helper' ) . '</th>
			<th class="llms-helper-key">' . __( 'License Key', 'lifterlms-helper' ) . '</th>
			<th class="llms-helper-actions">' . __( 'Actions', 'lifterlms-helper' ) . '</th>
		</tr>';
	}

	/**
	 * Get the HTML for a single product in the table
	 * @param    array     $data  array of product data
	 * @return   string
	 * @since    2.4.0
	 * @version  2.5.0
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
		} elseif ( isset( $this->deactivations ) ) {
			if ( isset( $this->deactivations[ $id ] ) ) {
				$msg = $this->deactivations[ $id ]['message'];
				$type = $this->deactivations[ $id ]['status'];
			}
		}

		$activated = get_option( $id . '_is_activated', 'no' );

		$html = '<tr class="type-' . $type . $data['stripe'] . '">';

			$html .= '<td class="llms-helper-bulk"><input class="llms-helper-bulk-cb" name="' . $data['field_name_cb'] . '" type="checkbox" value="yes"></td>';
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
				$html .= '<tr class="message' . $data['stripe'] . '"><td colspan="5"><div class="notice inline notice-' . $type .' notice-alt"><p>' . $msg . '</p></td></tr>';
			}

		$html .= '</tr>';
		return $html;
	}

	/**
	 * Save actions
	 * Routes to actual functions depending on submit button used
	 * @return   void
	 * @since    2.4.0
	 * @version  2.5.0
	 */
	public function save() {

		if ( ! empty( $_POST['llms_deactivate'] ) ) {
			$this->do_deactivation();
		} elseif ( isset( $_POST['llms_bulk_action_submit'] ) ) {
			if ( ! empty( $_POST['llms_bulk_action'] ) ) {
				if ( 'deactivate' === $_POST['llms_bulk_action'] ) {
					$this->do_deactivation();
				}
			}
		} elseif ( ! empty( $_POST['llms_keys'] ) && is_array( $_POST['llms_keys'] ) ) {
			$this->do_activations();
		}

		llms_helper_clear_transiets();

	}

	public function submit_button_text( $text, $tab ) {

		if ( 'licenses' === $tab ) {

			$text = __( 'Activate Add-Ons', 'lifterlms-helper' );

		}

		return $text;

	}

}

return new LLMS_Helper_Admin_Settings_Tab();
