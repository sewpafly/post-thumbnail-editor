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

		if ( 'client' !== $_REQUEST['pte-action'] ) {
			header( 'Content-Type: application/json' );
		}

		/**
		 * Return if id is invalid.
		 *
		 * @since 3.0.0
		 * @param int     $id     The post id
		 */
		if ( isset($_REQUEST['id']) && ! apply_filters( 'pte_api_assert_valid_id', false, $_REQUEST['id'] ) ) {
			$this->error(__("Permission denied", 'post-thumbnail-editor'));
		}


		switch ($_REQUEST['pte-action'])
		{
		case "client":
			$this->display_client();
			break;
		case "get-metadata":
			$this->get_metadata( $_REQUEST );
			break;
		case "resize-thumbnails":
			$this->resize_thumbnails( $_REQUEST );
			break;
		case "confirm-images":
			$this->confirm_images( $_REQUEST );
			break;
		case "delete-images":
			$this->delete_images( $_REQUEST );
			break;
		case "change-options":
			$this->change_options();
			break;
		default:
			$this->error( 'invalid API call' );
		}
		wp_die();
	}

	/**
	 * Display the HTML client
	 *
	 * @return void
	 */
	public function display_client()
	{
		print apply_filters('pte_client_launch', '');
	}

	/**
	 * Get the basic image information
	 *
	 * @since 3.0.0
	 *
	 * @param array    $request  The request array, allows GET or POST to be
	 *                           passed in as needed
	 * @return void
	 */
	public function get_metadata( $request )
	{
		$id = $this->validateInt( $request['id'] );
		if ( $id === false ) {
			return $this->error( __("Invalid id", 'post-thumbnail-editor'));
		}

		$metadata = array(
			'id' => $id,
			'url' => wp_get_attachment_url( $id ),
			'thumbnails' => apply_filters( 'pte_api_get_thumbnails', array(), $id )
		);
		return $this->message( $metadata );
	}

	/**
	 * Validate the the given parameter is an integer.  Return false if not
	 * numeric
	 *
	 * @since 3.0.0
	 *
	 * @param ???  $param  The potential int
	 * @return int or false
	 */
	public function validateInt( $param )
	{
		return is_numeric ( $param ) ? intval( $param ) : false;
	}

	/**
	 * Validate the keys exist in array
	 *
	 * @since 3.0.0
	 *
	 * @param array   $array   The array to validate
	 * @param array   $keys    The keys to validate in the $array
	 *
	 * @return true if all keys are in the array else an offending key
	 */
	private function validateKeys ( $array, $keys ) {

		if ( ! is_array( $keys ) ) {
			$keys = array( $keys );
		}

		foreach ( $keys as $key ) {
			if ( ! isset( $array[$key] ) ) {
				return $key;
			}
		}

		return true;
	}

	/**
	 * Resize image
	 *
	 * Resize a given image
	 *
	 * @since 3.0.0
	 *
	 * @param array    $request  The request array, allows GET or POST to be
	 *                           passed in as needed
	 */
	public function resize_thumbnails ( $request ) {

		if ( true !== $msg = $this->validateKeys( $request, array( 'id', 'w', 'h', 'x', 'y', 'sizes' ) ) ) {
			return $this->error( "Missing input: {$msg}" );
		}

		$id = $this->validateInt( $request['id'] );
		$w  = $this->validateInt( $request['w'] );
		$h  = $this->validateInt( $request['h'] );
		$x  = $this->validateInt( $request['x'] );
		$y  = $this->validateInt( $request['y'] );

		if ( $id === false || $w === false || $h === false || $x === false || $y === false ) {
			return $this->error( "Invalid resize parameter: 'id:{$id} w:{$w} h:{$h} x:{$x} y:{$y}'" );
		}

		$save = isset( $request['save'] ) && strtolower( $request['save'] ) === "true";

		// TODO: uncomment this out...
		// Check nonce
		//if ( !check_ajax_referer( "pte-resize-{$id}", 'pte-nonce', false ) ){
		//    return $this->error( "CSRF Check failed" );
		//}

		// Get the sizes to process
		$sizes = $request['sizes'];
		if ( ! is_array( $sizes ) ){
			$sizes = explode( ",", $sizes );
		}

		/**
		 * Resize the thumbnails and return the thumbnail instances
		 *
		 * Return an array of thumbnail objects (size + url information)
		 *
		 * @since 3.0.0
		 *
		 * @param  callback   $filter   filter results with this filter callback
		 * @param int     $id    The post/attachment id to modify
		 * @param int     $w     The width of the crop
		 * @param int     $h     The height of the crop
		 * @param int     $x     The left position of the crop
		 * @param int     $y     The upper position of the crop
		 * @param array   $sizes An array of the thumbnails to modify
		 *
		 * @return array PTE_Thumbnail instances
		 */
		$thumbnails = apply_filters( 'pte_api_resize_thumbnails', array(), $id, $w, $h, $x, $y, $sizes );

		$this->message( $thumbnails );

	}

    /*
	 * Confirm images by moving them into the correct folders and updating the
	 * file metadata in the wordpress database
	 *
	 * @since 3.0.0
	 *
	 * @param array    $request  The request array, allows GET or POST to be
	 *                           passed in as needed
	 */
	public function confirm_images ( $request ) {

		// TODO: Uncomment
		// Check nonce
		//if ( ! check_ajax_referer( "pte-{$id}", 'pte-nonce', false ) ){
		//    return $this->error( __( "CSRF Check failed" ), 'post-thumbnail-editor );
		//}

		if ( true !== $msg = $this->validateKeys( $request, array( 'id', 'files' ) ) ) {
			return $this->error( "Missing input: {$msg}" );
		}

		if ( false === $id = $this->validateInt( $request['id'] ) ) {
			return $this->error( "Invalid id:{$id}'" );
		}

		// Get the sizes to process
		$files = $request['files'];
		if ( ! is_array( $files ) ){
			return $this->error( "Invalid files:{$files}'" );
		}

		/**
		 * Confirm the thumbnails and return the thumbnail instances
		 *
		 * Return an array of thumbnail objects (size + url information)
		 *
		 * @since 3.0.0
		 *
		 * @param  callback   $filter   filter results with this filter callback
		 * @param int         $id       The post/attachment id to modify
		 * @param array       $files    An array of the thumbnails to modify
		 *
		 * @return array PTE_Thumbnail instances
		 */
		$thumbnails = apply_filters( 'pte_api_confirm_images', array(), $id, $files );

		return $this->message( $thumbnails );

	}

	/**
	 * Delete images associated with an ID
	 *
	 * @since 1.0
	 *
	 * @param int    $id    The ID of the temporary images to delete
	 *
	 * @return status message
	 */
	public function delete_images ( $request ) {

		// TODO: uncomment
		// Check nonce
		//if ( !check_ajax_referer( "pte-delete-{$id}", 'pte-nonce', false ) ){
		//    return $this->error( __( "CSRF Check failed", 'post-thumbnail-editor' ) );
		//}

		$id = isset( $request['id'] )
			? $this->validateInt( $request['id'] )
			: false;

		if ( $id === false ) {
			return $this->error( __( "Invalid id:{$id}", 'post-thumbnail-editor' ) );
		}

		$tmp_dir = apply_filters( 'pte_options_get', null, 'tmp_dir' )
			. $id
			. DIRECTORY_SEPARATOR;

		try {

			$status = apply_filters( 'pte_api_delete_dir', null, $tmp_dir );

			return $this->message( array( 'success' => sprintf(
				__( 'Successfully deleted directory: %s', 'post-thumbnail-editor'),
				$tmp_dir
			) ) );

		}
		catch (Exception $e) {

			return $this->error( sprintf(
				__( 'Error deleting directory: %s', 'post-thumbnail-editor'),
				$tmp_dir
			));

		}

	}

	/**
	 * Take the object passed in, JSON encode it and print out then return
	 *
	 * @since 3.0.0
	 *
	 * @param mixed $output  The objects to output in JSON
	 *
	 * @return void
	 */
	public function message ( $output ) {

		print( json_encode( $output ) );
		return null;

	}

	/**
	 * Print a JSON error message and die
	 *
	 * @since 3.0.0
	 *
	 * @return void
	 */
	public function error( $message ) {

		return $this->message( array( 'error' => $message ) );

	}

	/**
	 * Check for undeclared functions
	 *
	 * @since 3.0.0
	 *
	 * @return void
	 */
	public function __call ( $name, $arguments ) {

		return $this->error( 'undefined function call' );

	}

}
