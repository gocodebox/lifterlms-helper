<?php
/**
 * Abstract Updater class extended by theme and plugin updater classes
 *
 * @package 	LifterLMS Helper
 * @category 	Core
 * @author 		codeBOX
 *
 * @since  2.0.0
 */

// Restrict direct access
if ( ! defined( 'ABSPATH' ) ) { exit; }

abstract class LLMS_Helper_Updater {

	/**
	 * URL to query for release info
	 * @var string
	 *
	 * @since  1.1.0
	 */
	protected $api_url = 'https://lifterlms.com/llms-api';
	// protected $api_url = 'https://lifterlms.com.dev/llms-api';

	/**
	 * Update Key
	 * @var string
	 *
	 * @since  1.1.0
	 */
	protected $update_key;

	/**
	 * Constructor
	 *
	 * Defined by concrete class
	 *
	 * @param mixed $data
	 *
	 * @since  1.1.0
	 */
	abstract public function __construct( $data );

	/**
	 * Retrieve an object of data that can be passed into the transient object for WP to parse for updating
	 * @param string $version  new version number (eg: 1.0.1)
	 */
	abstract public function get_transient_data( $version );

	/**
	 * Post Install actions run after downloading the package
	 * @param  array $data   array of install data
	 */
	abstract public function post_install( $data );

	/**
	 * Retrive the latest version from the LLMS Api
	 * @return mixed     string or false
	 *
	 * @since  1.1.0
	 */
	public function get_latest_version_data( $slug ) {

		$result = wp_remote_post( $this->api_url . '/get_release_info', array(
			'body' => array(
				'slug'    => $slug,
			),
			'sslverify' => false,
		) );

		if ( ! is_wp_error( $result ) ) {

			$r = json_decode( $result['body'], true );

			if( !empty( $r['success'] ) && isset( $r['version'] ) ) {

				return $r;

			}

		}

		return false;

	}

}
