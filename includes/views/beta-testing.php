<?php
/**
 * View for displaying the Beta Testing tab on the "Status" screen
 * @since    3.0.0
 * @version  3.0.0
 */
defined( 'ABSPATH' ) || exit;
?>
<form action="" class="llms-beta-main" method="POST">

	<aside class="llms-beta-aside">

		<h1><?php _e( 'Beta Testing Warnings and FAQs', 'lifterlms-helper' ); ?></h1>

		<h3><?php _e( 'Always test with caution!', 'lifterlms-helper' ); ?></h3>
		<p><strong><?php _e( 'Beta releases may not be stable. We may not be able to fix issues caused by using a beta release. We urge you to only use beta versions in testing environments!', 'lifterlms-helper' ); ?></strong></p>
		<p><?php _e( 'Subscribing to the <em>beta channel</em> for LifterLMS or any available add-ons will allow you to automatically update to the latest beta release for the given plugin or theme.', 'lifterlms-helper' ); ?></p>
		<p><?php _e( 'When no beta versions are available, automatic updates will be to the latest stable version of the plugin or theme.', 'lifterlms-helper' ); ?></p>

		<h3><?php _e( 'Rolling back and restoring data', 'lifterlms-helper' ); ?></h3>
		<p><?php _e( 'You can switch back to the latest stable version by unsubscribing from the <em>beta channel</em>. <strong>Doing so will not restore your database</strong>. If you require a database restoration you must do so manually after the stable codebase is reinstalled.', 'lifterlms-helper' ); ?>

		<h3><?php _e( 'Reporting bugs and contributing', 'lifterlms-helper' ); ?></h3>
		<p><?php printf( __( 'We welcome contributions of all kinds, review our contribution guidelines on %1$sGitHub%2$s to get started.', 'lifterlms-helper' ), '<a href="https://github.com/gocodebox/lifterlms/blob/master/.github/CONTRIBUTING.md">', '</a>' ); ?></p>
		<p><?php printf( __( 'If you encounter a bug while beta testing, please report it at %s.', 'lifterlms-helper' ), make_clickable( 'https://github.com/gocodebox/lifterlms/issues' ) ); ?></p>

		<h3><?php _e( 'Still have questions?', 'lifterlms-helper' ); ?></h3>
		<p><?php printf( __( "Check out our Guide to Beta Testing at %s.", 'lifterlms-helper' ), make_clickable( 'https://lifterlms.com/beta-testing' ) ); ?></p>

	</aside>

	<table class="llms-table zebra text-left size-large llms-beta-table">
		<thead>
			<tr>
				<th><?php _e( 'Name', 'lifterlms-helper' ); ?></th>
				<th><?php _e( 'Channel', 'lifterlms-helper' ); ?></th>
				<th><?php _e( 'Installed Version', 'lifterlms-helper' ); ?></th>
				<th><?php _e( 'Beta Version', 'lifterlms-helper' ); ?></th>
			</tr>
		</thead>
		<tbody>
		<?php foreach ( $addons as $addon ) :
			$addon = llms_get_add_on( $addon ); ?>
			<tr>
				<td><?php echo $addon->get( 'title' ); ?></td>
				<td>
					<select name="llms_channel_subscriptions[<?php echo $addon->get( 'id' ); ?>]">
						<option value="stable" <?php selected( 'stable', $addon->get_channel_subscription() ); ?>><?php _e( 'Stable', 'lifterlms-helper' ); ?></option>
						<option value="beta" <?php selected( 'beta', $addon->get_channel_subscription() ); ?>><?php _e( 'Beta', 'lifterlms-helper' ); ?></option>
					</select>
				</td>
				<td><?php echo $addon->get_installed_version(); ?></td>
				<td><?php echo $addon->get( 'version_beta' ) ? $addon->get( 'version_beta' ) : __( 'N/A', 'lifterlms-helper' ); ?></td>
			</tr>
		<?php endforeach; ?>
		</tbody>
		<tfoot>
			<tr>
				<th colspan="4"><button class="llms-button-primary" id="llms-channel-submit" type="submit"><?php _e( 'Save & Update', 'lifterlms-helper' ); ?></button></th>
			</tr>
		</tfoot>
	</table>

	<script>
		document.getElementById( 'llms-channel-submit' ).onclick = function( e ) {
			if ( ! window.confirm( "<?php esc_attr_e( 'Are you sure you want to enable or disable beta testing for these plugins and themes?', 'lifterlms-helper' ); ?>" ) ) {
				e.preventDefault();
			}
		}
	</script>

	<?php wp_nonce_field( 'llms_save_channel_subscriptions', '_llms_beta_sub_nonce' ); ?>

</form>
<?php
