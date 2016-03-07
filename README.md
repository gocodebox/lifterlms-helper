LifterLMS Helper
================

### [Changelog](CHANGELOG.md)

### Activation

This plugin adds a new admin field type that can be used by LifterLMS admin settings arrays.

When a settings array is saved and a license key field is included in the posted values (and the field value is not empty), this plugin will automatically attempt activation with LifterLMS activation API.

If activation is successful, the provided update key will be stored and a success notification will be output using WordPress admin notices.

If activation failed (for any reason) an error message will be output using WordPress admin notices.

An error notice will persist on the admin panel (for users who can manage plugins) until the notice is dismissed.

A success notice will not persist.

The helper will only re-try activation if the value of a license key field changes.

If the value is cleared, the update key will be cleared and the extension will enter a "deactivated" state.

### Automatic Updates

Regardless of whether or not an extension is activated, the Helper will automatically query the LifterLMS Updates API to determine if updates for an extension are available.

If a user attempts to retrieve an update when their extension is inactive, they will receive an error notice explaining they must activate the plugin in order to receive the update.

Therefore, update information will be served regardless of extension state, but downloads will only be served to active licenses.

Additionally, the LifterLMS Update API will only serve updates when a license is active. The status of a license is determined during an update request (when the user clicks the "update" button) so if a license expires, the download will not actually be served and the user will receive an error during the update attempt.

### Ensuring the Extension can receive updates

In order to determine what extensions are eligible for automatic updates, this plugin queries static data from a LifterLMS Cloudfront domain. The static data is json object of plugins and themes that can receive updates via the Helper.

Your extension must be added to our static data, to do so, please contact Thomas Levy.

### Adding a LifterLMS License Key Activation Field to a premium LifterLMS Extension

To add a license key field and take advantage of the API simply add an array to a LifterLMS settings array using any of the available settings filters.

For example, if we wanted an license key field for a payment gateway extension, we would do the following:

	<?php
	add_filter( 'lifterlms_gateway_settings', function( $settings ) {

		$settings[] = array(
			'title'     => __( 'Activation Key', 'lifterlms-extension-domain' ), // add your extensions text domain only
			'desc' 		=> __( 'Required for support and automated plugin updates. Located on your <a href="https://lifterlms.com/my-account/" target="_blank">LifterLMS Account Settings page</a>.', 'lifterlms-extension-domain' ), // add your extensions text domain only

			'id' 		=> 'lifterlms_extension_name_activation_key', // this should follow the convention "lifterlms_{$extension_name}_activation_key"

			'type' 		=> 'llms_license_key', // don't change this!
			'default'	=> '', // blank by default!
			'extension' => LLMS_EXTENSION_NAME_PLUGIN_FILE, // this should be the full path to your extension's main plugin file, it does not have to be a constant but probably you're defining a constant anyway so use it
		);

		// your other plugin settings fields should go here...

		return $settings;

	}, 10, 1);

You will not have to do anything else, the helper will handle the activation / deactivation on form submission.

