<?php

/**
 * The service-specific functionality of the plugin.
 *
 * @link       http://sewpafly.github.io/post-thumbnail-editor
 * @since      3.0.0
 *
 * @package    Post_Thumbnail_Editor
 * @subpackage Post_Thumbnail_Editor/service
 */

class PTE_Service {

	/**
	 * The ID of this plugin.
	 *
	 * @since    3.0.0
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    3.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    3.0.0
	 * @param      string    $plugin_name       The name of this plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version = $version;

	}

	/**
	 * Handle the API calls
	 *
	 * @since 3.0.0
	 */
	public function api_handler(){
		header( 'Content-Type: application/json' );

		switch ($_REQUEST['pte-action'])
		{
		case "client":
			$this->display_client();
			break;
		case "resize-images":
			$this->resize_images();
			break;
		case "confirm-images":
			$this->confirm_images();
			break;
		case "delete-images":
			$this->delete_images();
			break;
		case "get-thumbnail-info":
			$this->get_thumbnail_info();
			break;
		case "change-options":
			$this->change_options();
			break;
		}
		wp_die();
	}

	/**
	 * Print thumbnail information
	 *
	 * @return void
	 */
	public function get_thumbnail_info () {

		/**
		 * Get the size information
		 *
		 * Return an array of thumbnail objects describing the size information
		 *
		 * @since 3.0.0
		 * @param  callback   $filter   filter results with this filter callback
		 */
		$thumbnails = apply_filters( 'pte_api_get_sizes', array() );
		print ( json_encode( $thumbnails ) );

	}
	
	/**
	 * Check for undeclared functions
	 *
	 * @return void
	 */
	public function __call ( $name, $arguments ) {

		print( json_encode( array(
			'error' => 'undefined function call'
		)));

	}
	
}
