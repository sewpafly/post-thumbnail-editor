<?php

/**
 * Shortcuts for lazy loading API, Options, and Client classes
 *
 * @link       http://sewpafly.github.io/post-thumbnail-editor
 * @since      3.0.0
 *
 * @package    Post_Thumbnail_Editor
 * @subpackage Post_Thumbnail_Editor/includes
 */

class PTE {

	/**
	 * The canonical instance of this class
	 *
	 * @since    3.0.0
	 * @access   private
	 * @var      string    $instance    The canonical instance
	 */
	private static $instance;

	/**
	 * The plugin client
	 *
	 * @since    3.0.0
	 * @access   private
	 * @var      object    $client     The plugin client.
	 */
	private $client;

	/**
	 * Return a handle to the PTE client
	 * 
	 * @since 3.0.0
	 */
	private	function _client()
	{

		if ( ! isset( $this->client ) ) {
			require_once plugin_dir_path( dirname( __FILE__ ) ) . 'client/class-pte-client.php';
			$this->client = new PTE_Client();
		}

		return $this->client;

	}

	/**
	 * Static singleton
	 *
	 * @since 3.0.0
	 */
	public static function singleton()
	{
		if (!isset(self::$instance)) {
			$className = __CLASS__;
			self::$instance = new $className();
		}
		return self::$instance;
	}

	/**
	 * Static client accessor
	 * 
	 * @since 3.0.0
	 */
	public static function client() {

		return self::singleton()->_client();

	}
}
