<?php
defined( 'ABSPATH' ) || exit;

/**
 * Get & Set Helper options
 * @since    3.0.0
 * @version  3.0.0
 */
class LLMS_Helper_Options {

	/**
	 * Singleton instance
	 * @var  null
	 */
	protected static $_instance = null;

	/**
	 * Main Instance of LifterLMS
	 * Ensures only one instance of LifterLMS is loaded or can be loaded.
	 * @return   LLMS_Helper_Options - Main instance
	 * @since    3.0.0
	 * @version  3.0.0
	 */
	public static function instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}
		return self::$_instance;
	}

	/*
		                     /$$                       /$$
		                    |__/                      | $$
		  /$$$$$$   /$$$$$$  /$$ /$$    /$$ /$$$$$$  /$$$$$$    /$$$$$$
		 /$$__  $$ /$$__  $$| $$|  $$  /$$/|____  $$|_  $$_/   /$$__  $$
		| $$  \ $$| $$  \__/| $$ \  $$/$$/  /$$$$$$$  | $$    | $$$$$$$$
		| $$  | $$| $$      | $$  \  $$$/  /$$__  $$  | $$ /$$| $$_____/
		| $$$$$$$/| $$      | $$   \  $/  |  $$$$$$$  |  $$$$/|  $$$$$$$
		| $$____/ |__/      |__/    \_/    \_______/   \___/   \_______/
		| $$
		| $$
		|__/
	*/

	/**
	 * Retrive a single option
	 * @param    string     $key      option name
	 * @param    mixed      $default  default option value if option isn't already set
	 * @return   mixed
	 * @since    3.0.0
	 * @version  3.0.0
	 */
	private function get_option( $key, $default = '' ) {

		$options = $this->get_options();

		if ( isset( $options[ $key ] ) ) {
			return $options[ $key ];
		}

		return $default;

	}

	/**
	 * Retrieve all upgrader options array
	 * @return   array
	 * @since    3.0.0
	 * @version  3.0.0
	 */
	private function get_options() {
		return get_option( 'llms_helper_options', array() );
	}

	/**
	 * Update the value of an option
	 * @param    string     $key  option name
	 * @param    mixed      $val  option value
	 * @return   boolean          True if option value has changed, false if not or if update failed.
	 * @since    3.0.0
	 * @version  3.0.0
	 */
	private function set_option( $key, $val ) {

		$options = $this->get_options();
		$options[ $key ] = $val;
		return update_option( 'llms_helper_options', $options, false );

	}

	/*
		                     /$$       /$$ /$$
		                    | $$      | $$|__/
		  /$$$$$$  /$$   /$$| $$$$$$$ | $$ /$$  /$$$$$$$
		 /$$__  $$| $$  | $$| $$__  $$| $$| $$ /$$_____/
		| $$  \ $$| $$  | $$| $$  \ $$| $$| $$| $$
		| $$  | $$| $$  | $$| $$  | $$| $$| $$| $$
		| $$$$$$$/|  $$$$$$/| $$$$$$$/| $$| $$|  $$$$$$$
		| $$____/  \______/ |_______/ |__/|__/ \_______/
		| $$
		| $$
		|__/
	*/

	/**
	 * Get info about addon channel subscriptions
	 * @return   array
	 * @since    3.0.0
	 * @version  3.0.0
	 */
	public function get_channels() {
		return $this->get_option( 'channels', array() );
	}

	/**
	 * Set info about addon channel subscriptions
	 * @param    array     $channels  array of channel information
	 * @return   boolean              True if option value has changed, false if not or if update failed.
	 * @since    3.0.0
	 * @version  3.0.0
	 */
	public function set_channels( $channels ) {
		return $this->set_option( 'channels', $channels );
	}

	/**
	 * Retrieve a timestamp for the last time the keys check cron was run
	 * @return   int
	 * @since    3.0.0
	 * @version  3.0.0
	 */
	public function get_last_keys_cron_check() {
		return $this->get_option( 'last_keys_cron_check', 0 );
	}

	/**
	 * Set the last cron check time
	 * @param    int     $time  timestamp
	 * @return   boolean        True if option value has changed, false if not or if update failed.
	 * @since    3.0.0
	 * @version  3.0.0
	 */
	public function set_last_keys_cron_check( $time ) {
		return $this->set_option( 'last_keys_cron_check', $time );
	}

	/**
	 * Retrieve saved license key data
	 * @return   array
	 * @since    3.0.0
	 * @version  3.0.0
	 */
	public function get_license_keys() {
		return $this->get_option( 'license_keys', array() );
	}

	/**
	 * Update saved license key data
	 * @param    array     $keys  key data to save
	 * @return   boolean          True if option value has changed, false if not or if update failed.
	 * @since    3.0.0
	 * @version  3.0.0
	 */
	public function set_license_keys( $keys ) {
		return $this->set_option( 'license_keys', $keys );
	}

}
