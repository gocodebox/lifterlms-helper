<?php
/**
 * Filters for LifterLMS & LaunchPad admin options for license key saving & validation
 *
 * @package 	LifterLMS Helper
 * @category 	Core
 * @author 		codeBOX
 *
 * @since    1.0.0
 * @version  2.5.0
 * @todo  this class should be removed in the future when users are fully transitioned to 3.2.2+
 */

// Restrict direct access
if ( ! defined( 'ABSPATH' ) ) { exit; }

class LLMS_Helper_Admin_Settings {

	private $activation_url = 'https://lifterlms.com/llms-api/activate';
	// private $activation_url = 'https://lifterlms.com.dev/llms-api/activate';

	/**
	 * Constructor
	 * @version  2.4.0
	 */
	public function __construct() {

		// only display these fields for LifterLMS 3.2.1 and lower
		if ( function_exists( 'LLMS' ) && version_compare( LLMS()->version, '3.2.0', '<' ) ) {

			// output field for LLMS Plugins
			add_action( 'lifterlms_admin_field_llms_license_key', array( $this, 'extensions_output_field' ), 10, 5 );

			// output field for LaunchPad theme(s)
			add_action( 'launchpad_output_field_llms_license_key', array( $this, 'launchpad_output_field' ), 10, 5 );

			add_action( 'lifterlms_update_option_llms_license_key', array( $this, 'save_field' ), 777, 5 );
			add_action( 'launchpad_save_field_llms_license_key', array( $this, 'save_field' ), 777, 5 );

		}

	}


	/**
	 * Post to LifterLMS API to attempt extension activation
	 * @param  string $license license key to attempt activation with
	 * @param  string $product name of the product we're trying to activate
	 * @return array           associative array containing a message and a success boolean
	 */
	private function activate( $license, $product )
	{

		$r = array(
			'message' => __( 'An unknown error occurred during activation. Please try again.', 'lifterlms-helper' ),
			'success' => false
		);

		// attempt to activate
		$res = wp_remote_post( $this->activation_url, array(
			'sslverify' => false, // for local testing only
			'body' => array(
				'license' => $license,
				'url'     => get_site_url(),
				'product' => $product,
			)
		) );

		if ( is_wp_error( $res ) ) {

			$r['message'] = $res->get_error_message();

		} else {

			if( $res['response']['code'] === 200 ) {

				$r = json_decode( $res['body'], true );

			}

		}

		return $r;

	}




	/**
	 * Output the HTML for the License Key Field
	 * @param  array  $field             array of field data
	 * @param  string $value             option value stored in the database or default as defined by $field if none found
	 * @param  string $description       field description HTML
	 * @param  string $tooltip           field tooltip HTML
	 * @param  array  $custom_attributes array of custom attributes formatted as HTML strings
	 * @return void
	 */
	public function extensions_output_field( $field = array(), $value = '', $description = '', $tooltip = '', $custom_attributes = array() )
	{

		?><tr valign="top">
			<th>
				<label for="<?php echo esc_attr( $field['id'] ); ?>"><?php echo esc_html( $field['title'] ); ?></label>
				<?php echo $tooltip; ?>
			</th>
		    <td class="forminp forminp-text forminp-<?php echo sanitize_title( $field['type'] ) ?>">
		    	<input
		    		name="<?php echo esc_attr( $field['id'] ); ?>"
		    		id="<?php echo esc_attr( $field['id'] ); ?>"
		    		type="text"
		    		style="<?php echo esc_attr( $field['css'] ); ?>"
		    		value="<?php echo esc_attr( $value ); ?>"
		    		class="<?php echo esc_attr( $field['class'] ); ?>"
		    		<?php echo implode( ' ', $custom_attributes ); ?>
		    		/>
					<?php if( get_option( llms_helper_get_extension_slug( $field['extension'] ) . '_is_activated', 'no' ) == 'yes' ): ?>
						<span class="llms-helper-activation-status"><span class="dashicons dashicons-yes"></span> <em>Activated</em></span>
					<?php else: ?>
						<span class="llms-helper-activation-status"><span class="dashicons dashicons-warning"></span> <em>Not activated</em></span>
					<?php endif; ?><br>
		    		<?php echo $description; ?>
		    </td>
		</tr><?php

	}


	public function launchpad_output_field( $field ) {

		// $value = ( isset( $_POST[ $field['id'] ] ) ) ? sanitize_text_field( $_POST[ $field['id'] ] ) : '';

		$value = get_option( $field['id'],  $field['default'] );

		?>
		<tr valign="top">
		    <th>
		        <label for="<?php echo esc_attr($field['id']); ?>"><?php echo esc_html($field['title']); ?></label>
		        <?php //echo $tooltip; ?>
		    </th>
		    <td class="forminp forminp-<?php echo sanitize_title($field['type']) ?>">
		        <input
		            name="<?php echo esc_attr($field['id']); ?>"
		            id="<?php echo esc_attr($field['id']); ?>"
		            type="text"
		            value="<?php echo esc_attr($value); ?>"
		            class="launchpad-field"
		            />
					<?php if( get_option( llms_helper_get_extension_slug( $field['extension'] ) . '_is_activated', 'no' ) == 'yes' ): ?>
						<span class="llms-helper-activation-status"><span class="dashicons dashicons-yes"></span> <em>Activated</em></span>
					<?php else: ?>
						<span class="llms-helper-activation-status"><span class="dashicons dashicons-warning"></span> <em>Not activated</em></span>
					<?php endif; ?>
					<span class="launchpad-field-desc"><?php echo $field['desc']; ?></span>
		    </td>
		</tr>
		<?php
	}

	public function save_field( $field )
	{

		$value = isset( $_POST[ $field['id'] ] ) ?  llms_clean( stripslashes( $_POST[ $field['id'] ] ) ) : $field['default'];

		$saved_value = get_option( $field['id'],  $field['default'] );

		$activated = get_option( llms_helper_get_extension_slug( $field['extension'] ) . '_is_activated', 'no' );

		// save new value to db
		update_option( $field['id'], $value );

		// value has changed OR plugin is not active
		if( $value !== $saved_value || $activated !== 'yes' ) {

			// if the value is not empty, attempt activation
			if( !empty( $value ) ) {

				$r = $this->activate( $value, basename( $field['extension'], '.php' ) );

				// setup additional options
				if( $r['success'] && isset( $r['update_key'] ) ) {

					$activated = 'yes';
					$key = $r['update_key'];

				} else {

					$activated = 'no';
					$key = '';

				}

				// update key for updates
				update_option( llms_helper_get_extension_slug( $field['extension'] ) . '_update_key', $key );

				// show activation status near the license key box
				update_option( llms_helper_get_extension_slug( $field['extension'] ) . '_is_activated', $activated );

				// add to the array of notices to be displayed
				LLMS_Helper_Admin_Notices::add_notice( $field['extension'], $r );

				/**
				 * @todo  there has to be a better way to do this b/c this isn't right
				 */
				LLMS_Helper_Admin_Notices::output_notices();

			}

			// if value is empty we need to clear the update key and deactivate
			else {

				// update key for updates
				update_option( llms_helper_get_extension_slug( $field['extension'] ) . '_update_key', '' );

				// show activation status near the license key box
				update_option( llms_helper_get_extension_slug( $field['extension'] ) . '_is_activated', '' );

			}

			// if the key has changed, clear cached transients so the key will be included in the next update request
			llms_helper_clear_transiets();

		}

	}

}
return new LLMS_Helper_Admin_Settings();
?>
