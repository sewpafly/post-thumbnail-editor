<?php

/**
 * The Post Thumbnail Editor API
 *
 * @link       http://sewpafly.github.io/post-thumbnail-editor
 * @since      3.0.0
 *
 * @package    Post_Thumbnail_Editor
 * @subpackage Post_Thumbnail_Editor/includes
 */

class PTE_Api extends PTE_Hooker{

	/**
	 * The cached thumbnails
	 *
	 * @since    3.0.0
	 * @access   private
	 * @var      array    $thumbnails    The wordpress thumbnail information
	 */
	private $thumbnails;

	/**
	 * Assert that the current user has access to the given image/post
	 *
	 * @since 3.0.0
	 * @param int    $id    The Post ID of the image you want to check
	 * @return true if valid
	 */
	public function assert_valid_id ( $id ) {

		if ( !$post = get_post( $id ) ) {
			return false;
		}
		$user_can = current_user_can( 'edit_post', $id )
			|| current_user_can( 'pte-edit', $id );

		/**
		 * Check for user capability.
		 *
		 * Return if the user should be able to view the id
		 *
		 * @since 2.0.0
		 * @param int      $id      The post id
		 */
		$user_can = apply_filters( 'pte-capability-check', $user_can, $id );

		if ( $user_can ) return true;

		return false;

	}

	/**
	 * Hook to retrieve the size information from wordpress
	 *
	 * @since 3.0.0
	 * @param mixed    $sizes   The list of Thumbnail objects
	 * @param string   $filter  if present, (not 'none') apply the filter above
	 * @return mixed   $sizes   The list of Thumbnail objects
	 */
	public function get_sizes ( $filter = null ) {

		if ( ! isset( $this->thumbnails ) ) {
			$this->thumbnails = PTE_Thumbnail::get_all();
		}

		if ( is_null( $filter ) ) {
			return $this->thumbnails;
		}

		return array_filter( $this->thumbnails, array( $this, 'filter_sizes' ) );

	}
	
	/**
	 * Filter callback for get_sizes
	 *
	 * @param PTE_Thumbnail    $element   The thumbnail to check
	 * @return true   if the $element should be filtered
	 */
	private function filter_sizes ( $element ) {

		/**
		 * Check for the hidden sizes option
		 *
		 * Return the thumbnail information that the user should be able to
		 * modify.
		 *
		 * @since 2.0.0
		 * @param array    $sizes   The sizes the user can see
		 * @param string   $label   The option to get
		 */
		$filtered_sizes = apply_filters( 'pte_options_get', array(), 'pte_hidden_sizes');
		if ( is_array( $filtered_sizes ) && in_array( $element->label, $filtered_sizes ) ) {
			return true;
		}
		return false;

	}
	
	/**
	 * Resize Images
	 *
	 * Given the appropriate parameters resize the given images, and place in a
	 * temporary directory (or save in place with the right option)
	 *
	 * @since 0.1
	 * 
	 * @param int     $id    The post/attachment id to modify
	 * @param int     $w     The width of the crop
	 * @param int     $h     The height of the crop
	 * @param int     $x     The left position of the crop
	 * @param int     $y     The upper position of the crop
	 * @param array   $sizes An array of the thumbnails to modify
	 * @param string  $save  Save without confirmation
	 *
	 * @return array of PTE_ActualThumbnail
	 */
	public function resize_thumbnails ( $id, $w, $h, $x, $y, $sizes, $save ) {
		var_dump( compact( 'id', 'w', 'h', 'x', 'y', 'sizes', 'save' ) );
	}
	
/*
 * resize_images
 *
 * Take an array of sizes along with the associated resize data (w/h/x/y) 
 * and save the images to a temp directory
 * 
 * OUTPUT: JSON object 'size: url'
 */
function pte_resize_images(){

	$sizes          = pte_get_all_alternate_size_information( $id );

	// The following information is common to all sizes
	// *** common-info
	$original_file  = _load_image_to_edit_path( $id );
	$original_size  = @getimagesize( $original_file );

	// SETS $PTE_TMP_DIR and $PTE_TMP_URL
	extract( pte_tmp_dir() );
	$thumbnails     = array();

	if ( !$original_size ){
		return pte_json_error("Could not read image size");
	}

	$logger->debug( "BASE FILE DIMENSIONS/INFO: " . print_r( $original_size, true ) );
	list( $orig_w, $orig_h, $orig_type ) = $original_size;
	// *** End common-info

	// So this never interrupts the jpeg_quality anywhere else
	add_filter('jpeg_quality', 'pte_get_jpeg_quality');
	add_filter('wp_editor_set_quality', 'pte_get_jpeg_quality');

	foreach ( $sizes as $size => $data ){
		// Get all the data needed to run image_create
		//
		//	$dst_w, $dst_h 
		extract( pte_get_width_height( $data, $w, $h ) );
		$logger->debug( "WIDTHxHEIGHT: $dst_w x $dst_h" );

		// Set the cropped filename
		$transparent = pte_is_crop_border_enabled($w, $h, $dst_w, $dst_h)
			&& !pte_is_crop_border_opaque();
		$basename = pte_generate_filename( $original_file, $dst_w, $dst_h, $transparent );
		$tmpfile  = "{$PTE_TMP_DIR}{$id}" . DIRECTORY_SEPARATOR . "{$basename}";

		// === CREATE IMAGE ===================
		// This function is in wp-includes/media.php
		// We've added a filter to return our own editor which extends the wordpress one.
		add_filter( 'wp_image_editors', 'pte_image_editors' );
		$editor = wp_get_image_editor( $original_file );
		if ( is_a( $editor, "WP_Image_Editor_Imagick" ) ) $logger->debug( "EDITOR: ImageMagick" );
		if ( is_a( $editor, "WP_Image_Editor_GD" ) ) $logger->debug( "EDITOR: GD" );
		$crop_results = $editor->crop($x, $y, $w, $h, $dst_w, $dst_h); 

		if ( is_wp_error( $crop_results ) ){
			$logger->error( "Error creating image: {$size}" );
			continue;
		}

		// The directory containing the original file may no longer exist when
		// using a replication plugin.
		wp_mkdir_p( dirname( $tmpfile ) );

		$tmpfile = dirname( $tmpfile ) . '/' . wp_unique_filename( dirname( $tmpfile ), basename( $tmpfile ) );
		$tmpurl   = "{$PTE_TMP_URL}{$id}/" . basename( $tmpfile );

		if ( is_wp_error( $editor->save( $tmpfile ) ) ){
			$logger->error( "Error writing image: {$size} to '{$tmpfile}'" );
			continue;
		}
		// === END CREATE IMAGE ===============

		// URL: wp_upload_dir => base_url/subdir + /basename of $tmpfile
		// This is for the output
		$thumbnails[$size]['url'] = $tmpurl;
		$thumbnails[$size]['file'] = basename( $tmpfile );
	}

	// Did you process anything?
	if ( count( $thumbnails ) < 1 ){
		return pte_json_error("No images processed");
	}

	$ptenonce = wp_create_nonce( "pte-{$id}" );

	// If save -- return pte_confirm_images
	if ( $save ){
		function create_pte_confirm($thumbnail){
			return $thumbnail['file'];
		}
		$_REQUEST['pte-nonce'] = $ptenonce;
		$_GET['pte-confirm'] = array_map('create_pte_confirm', $thumbnails);
		$logger->debug( "CONFIRM:" );
		$logger->debug( print_r( $_GET, true ) );
		return pte_confirm_images(true);
	}

	return pte_json_encode( array( 
		'thumbnails'        => $thumbnails,
		'pte-nonce'         => $ptenonce,
		'pte-delete-nonce'  => wp_create_nonce( "pte-delete-{$id}" )
	) );
}
}
