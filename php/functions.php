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
 *
 * Thanks to the ajax_thumbnail_rebuild plugin
 */
function pte_get_alternate_sizes($filter=true){
	//Put in some code to check if it's already been called...
	global $_wp_additional_image_sizes, $pte_gas;
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
		$info['current'] = pte_get_image_data( $id, $size, $info );
	}
	return $sizes;
}

/*
 * pte_test
 *
 * Outputs the test HTML (and loads normal interface in an iframe)
 *
 * Requires post id as $_GET['id']
 */
function pte_test(){
	$id = pte_check_id((int) $_GET['id']);
	if ( $id ){
		$testurl = admin_url('admin-ajax.php') . "?action=pte_ajax&pte-action=launch&id=${id}";
		require( PTE_PLUGINPATH . "html/test.php" );
	}
	return;
}

/*
 * pte_launch
 *
 * Outputs the base HTML needed to display and transform the inages
 *
 * Requires post id as $_GET['id']
 */
function pte_launch(){
	$logger = PteLogger::singleton();
	$options = pte_get_options();
	if ( $options['pte_debug'] ) {
		wp_enqueue_script( 'pte'
			, PTE_PLUGINURL . 'js/pte.full.js'
			, array('jquery','imgareaselect')
			, PTE_VERSION
		);
		wp_enqueue_script( 'jquery-json'
			, PTE_PLUGINURL . 'apps/jquery.json-2.2.min.js'
			, array('jquery')
			, '2.2'
		);
		wp_enqueue_style( 'pte'
			, PTE_PLUGINURL . 'css/pte.css'
			, array('imgareaselect')
			, PTE_VERSION
		);
	}
	else { // Minified versions
		wp_enqueue_script( 'pte'
			, PTE_PLUGINURL . 'js/pte.full.min.js'
			, array('jquery','imgareaselect')
			, PTE_VERSION
		);
		wp_enqueue_style( 'pte'
			, PTE_PLUGINURL . 'css/pte.min.css'
			, array('imgareaselect')
			, PTE_VERSION
		);
	}
	wp_localize_script('pte'
		, 'objectL10n'
		, array( 'pastebin_create_error' => __( 'Sorry, there was a problem trying to send to pastebin', PTE_DOMAIN )
			, 'pastebin_url' => __( 'PASTEBIN URL:', PTE_DOMAIN )
			, 'aspect_ratio_disabled' => __( 'Disabling aspect ratio', PTE_DOMAIN )
			, 'crop_submit_data_error' => __( 'Error parsing selection information', PTE_DOMAIN )
		)
	);

	$id = pte_check_id((int) $_GET['id']);

	$size_information = pte_get_all_alternate_size_information( $id );

	// Get the information needed for image preview 
	//   (See wp-admin/includes/image-edit.php)
	$nonce = wp_create_nonce("image_editor-$id");
	$meta = wp_get_attachment_metadata($id, true);

	if ( is_array($meta) && isset( $meta['width'] ) ){
		$big = max( $meta['width'], $meta['height'] );
	}
	else {
		$logger->error( 
			sprintf( __( "Invalid meta data for POST #%d: %s" )
				, $id
				, print_r( $meta, true ) 
			) 
		);
		$logger->error( __( "Please contact support", PTE_DOMAIN ) );
	}

	$sizer = $big > 400 ? 400 / $big : 1;
	$sizer = sprintf( "%.8F", $sizer );
	$logger->debug( "USER-AGENT: " . $_SERVER['HTTP_USER_AGENT'] );
	$logger->debug( "WORDPRESS: " . $GLOBALS['wp_version'] );
	$logger->debug( "SIZER: ${sizer}" );
	$logger->debug( "META: " . print_r( $meta, true ) );

	require( PTE_PLUGINPATH . "html/pte.php" );
}

function pte_check_id( $id ){
	$logger = PteLogger::singleton();
	if ( !$post =& get_post( $id ) ){
		$logger->warn( "Invalid id: {$id}" );
		return false;
	}
	if ( !current_user_can( 'edit_post', $id ) ){
		$logger->warn( "User does not have permission to edit this item" );
		return false;
	}
	return $id;
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
	if ( $size_information['crop'] == 1 ){
		$dst_w = $size_information['width'];
		$dst_h = $size_information['height'];
	}
	// Crop isn't set so the height / width should be based on the biggest side
	else if ($w > $h){
		$dst_w = $size_information['width'];
		$dst_h = round( ($dst_w/$w) * $h, 0);
	}
	else {
		$dst_h = $size_information['height'];
		$dst_w = round( ($dst_h/$h) * $w, 0);
	}
	$return = compact( "dst_w", "dst_h" );
	return $return;
}

/*
 * ================
 * Get the filename
 * ================
 * See image_resize function in wp-includes/media.php to follow the same conventions
 *  - Check if the file exists
 */
function pte_generate_filename( $file, $w, $h ){
	$info   = pathinfo( $file );
	$ext    = $info['extension'];
	$name   = wp_basename( $file, ".$ext" );
	$suffix = "{$w}x{$h}";
	//print_r( compact( "file", "info", "ext", "name", "suffix" ) );
	return "{$name}-{$suffix}.{$ext}";
}

/*
 * pte_create_image
 */
function pte_create_image($original_image, $type,
	$dst_x, $dst_y, $dst_w, $dst_h,
	$src_x, $src_y, $src_w, $src_h )
{
	$logger = PteLogger::singleton();
	$logger->debug( print_r( compact( 'type', 'dst_x', 'dst_y', 'dst_w', 'dst_h',
		'src_x','src_y','src_w','src_h' ), true ) );

	$new_image = wp_imagecreatetruecolor( $dst_w, $dst_h );

	imagecopyresampled( $new_image, $original_image,
		$dst_x, $dst_y, $src_x, $src_y,
		$dst_w, $dst_h, $src_w, $src_h 
	);

	// convert from full colors to index colors, like original PNG.
	if ( IMAGETYPE_PNG == $type && 
		function_exists('imageistruecolor') && 
		!imageistruecolor( $original_image ) 
		){
			imagetruecolortopalette( $newimage, false, imagecolorstotal( $original_image ) );
		}

	return $new_image;
}

function pte_write_image( $image, $orig_type, $destfilename ){
	$logger = PteLogger::singleton();

	$dir = dirname( $destfilename );
	if ( ! is_dir( $dir ) ){
		if ( ! mkdir( $dir, 0777, true ) ){
			$logger->warn("Error creating directory: {$dir}");
		}
	}

	if ( IMAGETYPE_GIF == $orig_type ) {
		if ( !imagegif( $image, $destfilename ) ){
			$logger->error("Resize path invalid");
			return false;
		}
	} 
	elseif ( IMAGETYPE_PNG == $orig_type ) {
		if ( !imagepng( $image, $destfilename ) ){
			$logger->error("Resize path invalid");
			return false;
		}
	} 
	else {
		// all other formats are converted to jpg
		if ( !imagejpeg( $image, $destfilename, 90) ){
			$logger->error("Resize path invalid: " . $destfilename);
			return false;
		}
	}

	imagedestroy( $image );

	// Set correct file permissions
	$stat = stat( dirname( $destfilename ));
	$perms = $stat['mode'] & 0000666; //same permissions as parent folder, strip off the executable bits
	@ chmod( $destfilename, $perms );

	return true;
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

	$id = pte_check_id( $_GET['id'] );
	$w  = pte_check_int( $_GET['w'] );
	$h  = pte_check_int( $_GET['h'] );
	$x  = pte_check_int( $_GET['x'] );
	$y  = pte_check_int( $_GET['y'] );

	if ( $id === false
		|| $w === false
		|| $h === false
		|| $x === false
		|| $y === false
	){
		return pte_json_error( "ResizeImages initialization failed: '{$id}-{$w}-{$h}-{$x}-{$y}'" );
	}

	// Get the sizes to process
	$pte_sizes      = $_GET['pte-sizes'];
	$sizes          = pte_get_all_alternate_size_information( $id );

	// The following information is common to all sizes
	// *** common-info
	$dst_x          = 0;
	$dst_y          = 0;
	$original_file  = get_attached_file( $id );
	$original_image = wp_load_image( $original_file );
	$original_size  = @getimagesize( $original_file );
	$uploads 	    = wp_upload_dir();
	$PTE_TMP_DIR    = $uploads['basedir'] . DIRECTORY_SEPARATOR . "ptetmp" . DIRECTORY_SEPARATOR;
	$PTE_TMP_URL    = $uploads['baseurl'] . "/ptetmp/";

	if ( !$original_size ){
		return pte_json_error("Could not read image size");
	}

	$logger->debug( "BASE FILE DIMENSIONS/INFO: " . print_r( $original_size, true ) );
	list( $orig_w, $orig_h, $orig_type ) = $original_size;
	// *** End common-info

	foreach ( $sizes as $size => $data ){
		// Get all the data needed to run image_create
		//
		//	$dst_w, $dst_h 
		extract( pte_get_width_height( $data, $w, $h ) );
		//
		// Set the directory
		$basename = pte_generate_filename( $original_file, $dst_w, $dst_h );
		// Set the file and URL's - defines set in pte_ajax
		$tmpfile  = "{$PTE_TMP_DIR}{$id}" . DIRECTORY_SEPARATOR . "{$basename}";
		$tmpurl   = "{$PTE_TMP_URL}{$id}/{$basename}";

		// === CREATE IMAGE ===================
		$image = pte_create_image( $original_image, $orig_type,
			$dst_x, $dst_y, $dst_w, $dst_h,
			$x,     $y,     $w,     $h );

		if ( ! isset( $image ) ){
			$logger->error( "Error creating image: {$size}" );
			continue;
		}

		if ( ! pte_write_image( $image, $orig_type, $tmpfile ) ){
			$logger->error( "Error writing image: {$size} to '{$tmpfile}'" );
			continue;
		}
		// === END CREATE IMAGE ===============

		// URL: wp_upload_dir => base_url/subdir + /basename of $tmpfile
		// This is for the output
		$thumbnails[$size]['url'] = $tmpurl;
		$thumbnails[$size]['file'] = basename( $tmpfile );
	}

	// we don't need the original in memory anymore
	imagedestroy( $original_image );

	// Did you process anything?
	if ( count( $thumbnails ) < 1 ){
		return pte_json_error("No images processed");
	}

	return pte_json_encode( array( 
		'thumbnails'        => $thumbnails,
		'pte-nonce'         => wp_create_nonce( "pte-{$id}" ),
		'pte-delete-nonce'  => wp_create_nonce( "pte-delete-{$id}" )
	) );
}

/*
 * pte_confirm_images
 *
 * Take an array of image sizes, an ID and a nonce and then move the confirmed images
 * to the official position and update metadata...
 *
 * Clean up and return error/success information...
 */
function pte_confirm_images(){
	global $pte_sizes;
	$logger = PteLogger::singleton();

	// Require JSON output
	pte_require_json();

	$id = pte_check_id( (int) $_GET['id'] );
	if ( $id === false ){
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
	$uploads = wp_upload_dir();
	$PTE_TMP_DIR = $uploads['basedir'] . DIRECTORY_SEPARATOR . "ptetmp" . DIRECTORY_SEPARATOR . $id;
	foreach ( $sizes as $size => $data ){
		// Make sure we're only moving our files
		$good_file = $PTE_TMP_DIR
			. DIRECTORY_SEPARATOR
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
		if ( isset( $old_file ) 
			&& file_exists( $old_file ) )
		{
			$logger->debug( "Deleting old thumbnail: {$old_file}" );
			unlink( $old_file );
		}

		// Move good image
		$logger->debug( "Moving '{$good_file}' to '{$new_file}'" );
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
	pte_rmdir( $PTE_TMP_DIR );
	return pte_json_encode( array( 'success' => "Yay!" ) );
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

	$id = pte_check_id( (int) $_GET['id'] );
	if ( $id === false ){
		return pte_json_error( "ID invalid: {$id}" );
	}
	// Check nonce
	if ( !check_ajax_referer( "pte-delete-{$id}", 'pte-nonce', false ) ){
		return pte_json_error( "CSRF Check failed" );
	}
	$uploads = wp_upload_dir();
	$PTE_TMP_DIR = $uploads['basedir'] . DIRECTORY_SEPARATOR . "ptetmp" . DIRECTORY_SEPARATOR . $id;
	// Delete tmpdir
	pte_rmdir( $PTE_TMP_DIR );
	return pte_json_encode( array( "success" => "Yay!" ) );
}

?>
