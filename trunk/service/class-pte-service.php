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

		switch ($_REQUEST['pte-action'])
		{
		case "client":
			PTE::client()->displayIframe();
			break;
		case "resize-images":
			PTE::api()->resize_images();
			break;
		case "confirm-images":
			PTE::api()->confirm_images();
			break;
		case "delete-images":
			PTE::api()->delete_images();
			break;
		case "get-thumbnail-info":
			PTE::api()->get_thumbnail_info();
			break;
		case "change-options":
			PTE::options()->update_user_options();
			break;
		}
		wp_die();
	}

}
