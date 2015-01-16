<?php
/*
 * TODO: add helper functions to get various links to different functions
 */

require_once(PTE_PLUGINPATH . 'php/log.php');

function pte_require_json() {
	if ( function_exists( 'ob_start' ) ){
		ob_start();
	}
}

/*
 * This is used to output JSON
 * - Calling this should return all the way up the chain...
 */
function pte_json_encode($mixed = null){
	$logger = PteLogger::singleton();
	$options = pte_get_options();
	$logs['error'] = array();
	$logs['log'] = array();

	// If a buffer was started this will check for any residual output
	// and add to the existing errors.
	if ( function_exists( 'ob_get_flush' ) ){
		$buffer = ob_get_clean();
		if ( isset( $buffer ) && strlen( $buffer ) > 0 ){
			$logger->warn( "Buffered output: {$buffer}" );
		}
	}

	if ( $logger->get_log_count( PteLogMessage::$ERROR ) > 0 
		|| $logger->get_log_count( PteLogMessage::$WARN ) > 0 )
	{
		$logs['error'] = $logger->get_logs( PteLogMessage::$ERROR | PteLogMessage::$WARN );
	}
	//if ( $logger->get_log_count( PteLogMessage::$WARN ) > 0 ){
	//   $logs['warn'] = $logger->get_logs( PteLogMessage::$WARN );
	//}
	if ( $options['pte_debug'] ){
		$logs['log'] = $logger->get_logs();
	}

	if ( ! function_exists('json_encode') ){
		$logs['error'][] = "json_encode not available, upgrade your php";
		$messages = implode( "\r\n", $logs['error'] );
		die("{\"error\":\"{$messages}\"}");
	}

	if ( ! isset($mixed) ){
		$mixed = array();
	}
	else if ( ! is_array( $mixed ) ){
		$mixed = array('noarray' => $mixed);
	}


	if ( count( $logs['error'] )+count( $logs['log'] ) > 0 ){
		$mixed = array_merge_recursive( $mixed, $logs );
	}

	print( json_encode($mixed) );
	return true;
}

/*
 * pte_json_error - Calling this should return all the way up the chain...
 */
function pte_json_error( $error ){
	$logger = PteLogger::singleton();
	$logger->error( $error );
	return pte_json_encode();
}

/*
 * pte_filter_sizes
 *
 * This is used by the get_sizes functions to determine which sizes to
 * reduce to
 */
function pte_filter_sizes( $element ){
	global $pte_sizes;
	$options = pte_get_options();
	// Check if the element is in the pte_sizes array
	if ( ( is_array( $pte_sizes ) && !in_array( $element, $pte_sizes ) )
		or ( in_array( $element, $options['pte_hidden_sizes'] ) )
	){
		return false;
	}
	return true;
}

/*
 * pte_get_alternate_sizes
 *
 * Creates an array of each thumbnail size and the corresponding data:
 *   * height
 *   * width
 *   * crop boolean
 *   * display name
 *
 * Thanks to the ajax_thumbnail_rebuild plugin
 */
function pte_get_alternate_sizes($filter=true){
	//Put in some code to check if it's already been called...
	global $_wp_additional_image_sizes, $pte_gas;

	$size_names = apply_filters( 'image_size_names_choose', array(
		'thumbnail' => __( 'Thumbnail' ),
		'medium'    => __( 'Medium' ),
		'large'     => __( 'Large' ),
		'full'      => __( 'Full Size' )
	) );

	if ( !isset($pte_gas) ){
		$pte_gas = array();
		$sizes = array();

		// Some times we don't want the filter to run (in admin for example)
		if ($filter){
			$sizes = array_filter( get_intermediate_image_sizes(), 'pte_filter_sizes' );
		}
		else{
			$sizes = get_intermediate_image_sizes();
		}

		foreach ($sizes as $s){
			if ( isset( $_wp_additional_image_sizes[$s]['width'] ) ) // For theme-added sizes
				$width = intval( $_wp_additional_image_sizes[$s]['width'] );
			else                                                     // For default sizes set in options
				$width = get_option( "{$s}_size_w" );

			if ( isset( $_wp_additional_image_sizes[$s]['height'] ) ) // For theme-added sizes
				$height = intval( $_wp_additional_image_sizes[$s]['height'] );
			else                                                      // For default sizes set in options
				$height = get_option( "{$s}_size_h" );

			if ( isset( $_wp_additional_image_sizes[$s]['crop'] ) ) // For theme-added sizes
				$crop = intval( $_wp_additional_image_sizes[$s]['crop'] );
			else                                                      // For default sizes set in options
				$crop = get_option( "{$s}_crop" );

			$pte_gas[$s] = array(
				'width'  => $width,
				'height' => $height,
				'crop'   => $crop
			);

			// If the display name is set for this size, add this information
			if (isset($size_names[$s])) {
				$pte_gas[$s]['display_name'] = $size_names[$s];
			}
		}
	}
	return $pte_gas;
}

/*
 * pte_get_image_data
 *
 * Gets specific data for a given image (id) at a given size (size)
 * Optionally can return the JSON value or PHP array
 */
function pte_get_image_data( $id, $size, $size_data ){
	$logger = PteLogger::singleton();

	$fullsizepath = get_attached_file( $id );
	$path_information = image_get_intermediate_size($id, $size);

	if ( $path_information && 
		@file_exists( dirname( $fullsizepath ) . DIRECTORY_SEPARATOR . $path_information['file'] )
	){
		return $path_information;
	}

	// We don't really care how it gets generated, just that it is...
	// see ajax-thumbnail-rebuild plugin for inspiration
	if ( FALSE !== $fullsizepath && @file_exists($fullsizepath) ) {
		// Create the image and update the wordpress metadata
		$resized = image_make_intermediate_size( $fullsizepath, 
			$size_data['width'], 
			$size_data['height'],
			$size_data['crop']
		);
		if ($resized){
			$metadata = wp_get_attachment_metadata($id, true);
			$metadata['sizes'][$size] = $resized;
			wp_update_attachment_metadata( $id, $metadata);
		}
	}

	// Finish how we started
	$path_information = image_get_intermediate_size($id, $size);
	if ($path_information){
		return $path_information;
	}
	else {
		$logger->warn( "Couldn't find or generate metadata for image: {$id}-{$size}" );
	}
	return false;
}

/*
 * pte_get_all_alternate_size_information
 *
 * Gets all pertinent data describing the alternate sizes
 */
function pte_get_all_alternate_size_information( $id ){
	$sizes = pte_get_alternate_sizes();
	foreach ( $sizes as $size => &$info ){
		if ( $info['crop'] )
			$info['crop'] = true;
		else
			$info['crop'] = false;
		$info['current'] = pte_get_image_data( $id, $size, $info );
	}
	return $sizes;
}

/*
 * pte_body
 *
 * Returns the base HTML needed to display and transform the inages
 *
 * Requires post id as $_GET['id']
 */
function pte_body( $id ){
	ob_start();

	$logger = PteLogger::singleton();
	$options = pte_get_options();

	// Get the information needed for image preview 
	//   (See wp-admin/includes/image-edit.php)
	$nonce = wp_create_nonce("image_editor-$id");
	$meta = wp_get_attachment_metadata($id, true);

	if ( !is_array($meta) || empty( $meta['width'] ) || empty( $meta['height'] ) ){
		$logger->error( 
			sprintf( __( "Invalid meta data for POST #%d: %s" )
				, $id
				, print_r( $meta, true ) 
			) 
		);
		$logger->error( __( "Please contact support", PTE_DOMAIN ) );
	}

	PteLogger::debug( "PTE-VERSION: " . PTE_VERSION .
		"\nUSER-AGENT:  " . $_SERVER['HTTP_USER_AGENT'] .
		"\nWORDPRESS:   " . $GLOBALS['wp_version'] );

	// Generate an image and put into the ptetmp directory
	if (false === $editor_image = pte_generate_working_image($id)) {
		$editor_image = sprintf("%s?action=pte_imgedit_preview&amp;_ajax_nonce=%s&amp;postid=%d&amp;rand=%d",
			admin_url('admin-ajax.php'),
			$nonce,
			$id,
			rand(1,99999)
		);
	}

	require( PTE_PLUGINPATH . "html/pte.php" );
	return ob_get_clean();
}

function pte_generate_working_image($id)
{
	$options = pte_get_options();
	if (false == $options['pte_imgedit_disk'])
		return false;

	// SETS PTE_TMP_DIR and PTE_TMP_URL
	extract( pte_tmp_dir() );

	$original_file = _load_image_to_edit_path( $id );
	$size = $options['pte_imgedit_max_size'];

	$editor = wp_get_image_editor( $original_file );
	$finfo    = pathinfo( $original_file );
	$basename = sprintf("%s-%s.%s", $id, $size, $finfo['extension']);
	$file     = sprintf("%s%s", $PTE_TMP_DIR, $basename );
	$url      = sprintf("%s%s", $PTE_TMP_URL, $basename );

	if ( file_exists( $file ) )
		return $url;

	PteLogger::debug("\nGENERATING WORKING IMAGE:\n  [{$file}]\n  [{$url}]");

	// Resize the image and check the results
	$results = $editor->resize($size,$size);
	if ( is_wp_error( $results ) ) {
		PteLogger::error( $results );
		return false;
	}

	// Save the image
	if ( is_wp_error( $editor->save( $file ) ) ) {
		PteLogger::error( "Unable to save the generated image falling back to ajax-ed image" );
		return false;
	}
	return $url;
}

function pte_check_int( $int ){
	$logger = PteLogger::singleton();
	if (! is_numeric( $int ) ){
		$logger->warn( "PARAM not numeric: '{$int}'" );
		return false;
	}
	return $int;
}

/*
 * Get Destination width & height
 * ==============================
 * When the crop isn't set:
 *    the size information for the biggest dimension is accurate, 
 *    but the other dimension is wrong
 */
function pte_get_width_height( $size_information, $w, $h ){
	$logger = PteLogger::singleton();
	if ( $size_information['crop'] == 1 ){
		$logger->debug("GETwidthheightCROPPED");
		$dst_w = $size_information['width'];
		$dst_h = $size_information['height'];
	}
	// Crop isn't set so the height / width should be based on the smallest side
	// or check if the post_thumbnail has a 0 for a side.
	else {
		$use_width = false;
		if ( $w > $h ) $use_width = true;
		if ( $size_information['height'] == 0 ) $use_width = true;
		// This case appeared because theme twentytwelve made a thumbnail 'post-thumbnail'
		// with 624x9999, no crop... The images it created were huge...
		if ( $size_information['width'] < $size_information['height'] ) $use_width = true;
		if ( $size_information['width'] == 0 ) $use_width = false;

		$logger->debug("GETwidthheightPARAMS\nWIDTH: $w\nHEIGHT: $h\nUSE_WIDTH: " . print_r($use_width, true));
		if ( $use_width ){
			$dst_w = $size_information['width'];
			$dst_h = round( ($dst_w/$w) * $h, 0);
		}
		else {
			$dst_h = $size_information['height'];
			$dst_w = round( ($dst_h/$h) * $w, 0);
		}
	}

	// Sanity Check
	if ( $dst_h == 0 || $dst_w == 0 ){
		$logger->error( "Invalid derived dimensions: ${dst_w} x ${dst_h}" );
	}
	return compact( "dst_w", "dst_h" );
}

/*
 * ================
 * Get the filename
 * ================
 * See image_resize function in wp-includes/media.php to follow the same conventions
 *  - Check if the file exists
 *
 * Using the cache buster is a good idea because:
 *  * we shouldn't overwrite old images that have been placed into posts
 *  * keeps problems from occuring when I try to debug and people think picture
 *    didn't save, when it's just a caching issue
 *
 * @param $file the original file
 * @param $w width of cropped image
 * @param $h height of the cropped image
 * @param $transparent if the cropped image is transparent
 */
function pte_generate_filename( $file, $w, $h, $transparent=false){
	$options      = pte_get_options();
	$info         = pathinfo( $file );
	$ext          = (false !== $transparent) ? 'png' : $info['extension'];
	$name         = wp_basename( $file, ".$ext" );
	$suffix       = "{$w}x{$h}";

	if ( $options['cache_buster'] ){
		$cache_buster = time();
		return sprintf( "%s-%s-%s.%s",
			$name,
			$suffix,
			$cache_buster,
			$ext );
	}
	//print_r( compact( "file", "info", "ext", "name", "suffix" ) );
	return "{$name}-{$suffix}.{$ext}";
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
	$logger = PteLogger::singleton();
	global $pte_sizes;

	// Require JSON output
	pte_require_json();

	$id = intval( $_GET['id'] );
	$w  = pte_check_int( $_GET['w'] );
	$h  = pte_check_int( $_GET['h'] );
	$x  = pte_check_int( $_GET['x'] );
	$y  = pte_check_int( $_GET['y'] );
	$save = isset( $_GET['save'] ) && ( strtolower( $_GET['save'] ) === "true" );

	if ( pte_check_id( $id ) === false
		|| $w === false
		|| $h === false
		|| $x === false
		|| $y === false
	) {
		return pte_json_error( "ResizeImages initialization failed: '{$id}-{$w}-{$h}-{$x}-{$y}'" );
	}

	// Check nonce
	if ( !check_ajax_referer( "pte-resize-{$id}", 'pte-nonce', false ) ){
		return pte_json_error( "CSRF Check failed" );
	}

	// Get the sizes to process
	$pte_sizes      = $_GET['pte-sizes'];
	if ( !is_array( $pte_sizes ) ){
		$logger->debug( "Converting pte_sizes to array" );
		$pte_sizes = explode( ",", $pte_sizes );
	}
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

function pte_image_editors( $editor_array ){
	require_once( PTE_PLUGINPATH . 'php/class-pte-image-editor-gd.php' );
	require_once( PTE_PLUGINPATH . 'php/class-pte-image-editor-imagick.php' );
	array_unshift( $editor_array, 'PTE_Image_Editor_Imagick', 'PTE_Image_Editor_GD' );
	return $editor_array;
}

/*
 * pte_confirm_images
 *
 * Take an array of image sizes, an ID and a nonce and then move the confirmed images
 * to the official position and update metadata...
 *
 * Clean up and return error/success information...
 */
function pte_confirm_images($immediate = false){
	global $pte_sizes;
	$logger = PteLogger::singleton();

	// Require JSON output
	pte_require_json();

	$id = (int) $_GET['id'];
	if ( pte_check_id( $id ) === false ){
		return pte_json_error( "ID invalid: {$id}" );
	}

	// Check nonce
	if ( !check_ajax_referer( "pte-{$id}", 'pte-nonce', false ) ){
		return pte_json_error( "CSRF Check failed" );
	}

	// Get the available sizes
	if ( is_array( $_GET['pte-confirm'] ) ){
		$pte_sizes = array_keys( $_GET['pte-confirm'] );
		$sizes = pte_get_all_alternate_size_information( $id );
		$logger->debug( "pte_get_all_alternate_size_information returned: "
			. print_r( $sizes, true ) );
	}
	else {
		return pte_json_error( "Invalid Parameters: can't find sizes" );
	}
	// === END INITIALIZATION ================================

	// Foreach size:
	//    Move good image
	//    Update metadata
	//    Delete old image
	// Remove PTE/$id directory

	// SETS PTE_TMP_DIR and PTE_TMP_URL
	extract( pte_tmp_dir() );

	foreach ( $sizes as $size => $data ){
		// Make sure we're only moving our files
		$good_file = $PTE_TMP_DIR
			. $id . DIRECTORY_SEPARATOR
			. basename( $_GET['pte-confirm'][$size] );

		if ( ! ( isset( $good_file ) && file_exists( $good_file ) ) ){
			return pte_json_error("FILE is invalid: {$good_file}");
		}

		$dir = dirname( get_attached_file( $id ) );
		$new_file = $dir
			. DIRECTORY_SEPARATOR
			. basename( $good_file );
		if ( isset( $data['current']['file'] ) ){
			$old_file = $dir
				. DIRECTORY_SEPARATOR
				. $data['current']['file'];
		}

		// Delete/unlink old file
		if ( isset( $old_file ) )
		{
			$logger->debug( "Deleting old thumbnail: {$old_file}" );
			@unlink( apply_filters( 'wp_delete_file', $old_file ) );
		}

		// Move good image
		$logger->debug( "Moving '{$good_file}' to '{$new_file}'" );
		wp_mkdir_p( dirname( $new_file ) );
		rename( $good_file, $new_file );

		// Update metadata
		$image_dimensions  = @getimagesize( $new_file );
		list( $w, $h, $type ) = $image_dimensions;
		//print("IMAGE DIMENSIONS...");
		//print_r( $image_dimensions );
		$metadata = wp_get_attachment_metadata( $id, true );
		$metadata['sizes'][$size] = array( 
			'file' => basename( $new_file ),
			'width' => $w,
			'height' => $h 
		);
		$logger->debug( "Updating '{$size}' metadata: " . print_r( $metadata['sizes'][$size], true ) );
		wp_update_attachment_metadata( $id, $metadata);
	}
	// Delete tmpdir
	//pte_rmdir( $PTE_TMP_DIR );
	return pte_json_encode( array( 
		'thumbnails' => pte_get_all_alternate_size_information( $id ),
		'immediate' => $immediate
	) );
}

function pte_rmdir( $dir ){
	$logger = PteLogger::singleton();
	if ( !is_dir( $dir ) || !preg_match( "/ptetmp/", $dir ) ){
		$logger->warn("Tried to delete invalid directory: {$dir}");
		return;
	}
	foreach ( scandir( $dir ) as $file ){
		if ( "." == $file || ".." == $file ) continue;
		$full_path_to_file = $dir . DIRECTORY_SEPARATOR . $file;
		$logger->debug("DELETING: {$full_path_to_file}");
		unlink( $full_path_to_file );
	}
	rmdir( $dir );
}

function pte_delete_images()
{
	// Require JSON output
	pte_require_json();

	$id = (int) $_GET['id'];
	if ( pte_check_id( $id ) === false ){
		return pte_json_error( "ID invalid: {$id}" );
	}
	// Check nonce
	if ( !check_ajax_referer( "pte-delete-{$id}", 'pte-nonce', false ) ){
		return pte_json_error( "CSRF Check failed" );
	}

	// SETS PTE_TMP_DIR and PTE_TMP_URL
	extract( pte_tmp_dir() );
	$PTE_TMP_DIR = $PTE_TMP_DIR . $id . DIRECTORY_SEPARATOR;

	// Delete tmpdir
	PteLogger::debug( "Deleting [{$PTE_TMP_DIR}]" );
	pte_rmdir( $PTE_TMP_DIR );
	return pte_json_encode( array( "success" => "Yay!" ) );
}

function pte_get_jpeg_quality($quality){
	$options = pte_get_options();
	$jpeg_compression = $options['pte_jpeg_compression'];
	if ( isset( $_GET['pte-jpeg-compression'] ) ) {
		$tmp_jpeg = intval( $_GET['pte-jpeg-compression'] );
		if ( 0 <= $tmp_jpeg && $tmp_jpeg <= 100 ){
			$jpeg_compression = $tmp_jpeg;
		}
	}
	PteLogger::debug( "COMPRESSION: " . $jpeg_compression );
	return $jpeg_compression;
}

/**
 * Sending output to an iframe
 */
function pte_init_iframe() {
	global $title, $pte_iframe;
	$pte_iframe = true;

	// Provide the base framework/HTML for the editor.
	require_once( ABSPATH . WPINC . '/script-loader.php' );
	// Check the input parameters and create the HTML
	pte_edit_setup();

	print( "<!DOCTYPE html>\n<html><head><title>${title}</title>\n" );

	print_head_scripts();
	print_admin_styles();

	print( '</head><body class="wp-core-ui pte-iframe">' );
	// Simply echo the created HTML
	pte_edit_page();
	wp_print_footer_scripts();
	print( '</body></html>' );
}

/**
 * Are we adding borders
 *
 * @param $src_w The width of the src image
 * @param $src_h The height of the src image
 * @param $dst_w The width of the dst image
 * @param $dst_h The height of the dst image
 */
function pte_is_crop_border_enabled( $src_w, $src_h, $dst_w, $dst_h ) {
	$src_ar = $src_w / $src_h;
	$dst_ar = $dst_w / $dst_h;
	return ( isset( $_REQUEST['pte-fit-crop-color'] ) && abs( $src_ar - $dst_ar ) > 0.01 );
}

/**
 * Is the border transparent
 */
function pte_is_crop_border_opaque() {
	return ( preg_match( "/^#[a-fA-F0-9]{6}$/", $_REQUEST['pte-fit-crop-color'] ) );
}
