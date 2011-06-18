<?php

/*
 * This is used to output JSON
 */
function pte_json_encode($mixed = null){
	global $pte_errors;
   if ( ! function_exists('json_encode') ){
      pte_add_error( "json_encode not available, upgrade your php" );
		$messages = implode( "\r\n", $pte_errors );
		die("{\"error\":\"{$messages}\"}");
   }

	if ( ! isset($mixed) ){
		$mixed = array();
	}
	else if ( ! is_array( $mixed ) ){
		$mixed = array($mixed);
	}

	if ( count( $pte_errors ) > 0 ){
		$mixed = array_merge_recursive( $mixed, array( 'error' => $pte_errors ) );
	}

	die( json_encode($mixed) );
}

/*
 * pte_add_error
 */
function pte_add_error($error){
	global $pte_errors;
	if ( ! isset( $pte_errors ) || ! is_array( $pte_errors ) ){
		$pte_errors = array();
	}

	if ( !in_array( $error, $pte_errors ) ){
		if ( is_string( $error ) ){
			$pte_errors[] = $error;
		}
		else if ( is_array( $error ) ){
			$pte_errors = array_merge( $error, $pte_errors );
		}
	}
}

/*
 * pte_filter_sizes
 *
 * This is used by the get_sizes functions to determine which sizes to
 * reduce to
 */
function pte_filter_sizes( $element ){
	global $pte_sizes;
	if ( is_array( $pte_sizes ) && !in_array( $element, $pte_sizes )
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
function pte_get_alternate_sizes(){
	//Put in some code to check if it's already been called...
   global $_wp_additional_image_sizes, $pte_gas;
	if ( !isset($pte_gas) ){
		$pte_gas = array();
		foreach (array_filter( get_intermediate_image_sizes(), 'pte_filter_sizes' ) as $s){
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
 * pte_generate_attachment
 *
 * If the path_information is invalid or missing then we
 * have to generate it using this function
 */
function pte_generate_attachment($id, $size){
	// Check inputs
   if ( !is_numeric( $id ) ) {
		$pte_current_errors[] = "Parameter id: '{$id}' is not numeric";
	}

   $size_information = pte_get_alternate_sizes();
   if (! array_key_exists( $size, $size_information ) ){
      $pte_current_errors[] = "Invalid size: {$size}";
   }

	if ( isset( $pte_current_errors ) ){
		pte_add_error( $pte_current_errors );
		return false;
	}

   // We don't really care how it gets generated, just that it is...
   // see ajax-thumbnail-rebuild plugin for inspiration
   if ( FALSE !== $fullsizepath && @file_exists($fullsizepath) ) {
      // Create the image and update the wordpress metadata
      $resized = image_make_intermediate_size( $fullsizepath, 
         $size_information[$size]['width'], 
         $size_information[$size]['height'],
         $size_information[$size]['crop']
      );
      if ($resized){
         $metadata = wp_get_attachment_metadata($id);
         $metadata['sizes'][$size] = $resized;
         wp_update_attachment_metadata( $id, $metadata);
      }
   }
	return true;
}

/*
 * pte_get_image_data
 *
 * Gets specific data for a given image (id) at a given size (size)
 * Optionally can return the JSON value or PHP array
 */
function pte_get_image_data( $id, $size ){

   if ( !is_numeric( $id ) ) {
		pte_add_error( "Parameter 'id' is not numeric" );
		return;
	}

   $fullsizepath = get_attached_file( $id );
   $path_information = image_get_intermediate_size($id, $size);

   if ( $path_information && 
      @file_exists(dirname($fullsizepath)."/".$path_information['file']))
   {
		return $path_information;
   }

	pte_generate_attachment($id, $size);

   // Finish how we started
   $path_information = image_get_intermediate_size($id, $size);
   if ($path_information){
		return $path_information;
   }
   else {
      pte_add_error( "Couldn't find or generate metadata for image: {$id}-{$size}" );
   }
	return false;
}

/*
 * pte_get_all_alternate_size_information
 *
 * Gets all pertinent data describing the alternate sizes
 */
function pte_get_all_alternate_size_information($id){
   $sizes = pte_get_alternate_sizes();
	foreach ( $sizes as $size => &$info ){
		$info = array_merge( $info, (array)pte_get_image_data( $id, $size ) );
	}
	return $sizes;
}

/*
 * pte_launch
 *
 * Outputs the base HTML needed to display and transform the inages
 *
 * Requires post id as $_GET['id']
 */
function pte_launch(){
	wp_register_script( 'jquery-tmpl'
		, PTE_PLUGINURL . 'apps/jquery-tmpl/jquery.tmpl.min.js'
		, array('jquery')
		, '1.0.0pre'
	);
	wp_register_script( 'pte-log'
		, PTE_PLUGINURL . 'js/log.js'
		, false
		, PTE_VERSION
	);
	wp_enqueue_script( 'pte'
		, PTE_PLUGINURL . 'js/pte.js'
		, array('jquery','imgareaselect','pte-log', 'jquery-tmpl')
		, PTE_VERSION
	);
	wp_enqueue_style( 'pte'
		, PTE_PLUGINURL . 'css/pte-new.css'
		, array('imgareaselect')
		, PTE_VERSION
	);

	$id = pte_check_id((int) $_GET['id']);

	$size_information = pte_get_all_alternate_size_information($id);

	// Get the information needed for image preview 
	//   (See wp-admin/includes/image-edit.php)
	$nonce = wp_create_nonce("image_editor-$id");
	$meta = wp_get_attachment_metadata($id);

	if ( is_array($meta) && isset($meta['width']) )
		$big = max( $meta['width'], $meta['height'] );

	$sizer = $big > 400 ? 400 / $big : 1;

	require( PTE_PLUGINPATH . "html/pte.html" );
}

function pte_check_id( $id ){
   if ( !$post =& get_post( $id ) ){
      pte_add_error( "Invalid id: {$id}" );
		return false;
	}
	return $id;
}

function pte_check_int( $int ){
	if (! is_numeric( $int ) ){
		pte_add_error("PARAM not numeric: '{$int}'");
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
	$new_image = wp_imagecreatetruecolor( $dst_w, $dst_h );

	//print_r( compact( "original_image", 'type', 'dst_x', 'dst_y', 'dst_w', 'dst_h',
	//   'src_x','src_y','src_w','src_h' ) );

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
	$dir = dirname( $destfilename );
	if ( ! is_dir( $dir ) ){
		if ( ! mkdir( $dir, 0777, true ) ){
			pte_add_error("Error creating directory: {$dir}");
		}
	}

	if ( IMAGETYPE_GIF == $orig_type ) {
		if ( !imagegif( $image, $destfilename ) ){
			pte_add_error("Resize path invalid");
			return false;
		}
	} 
	elseif ( IMAGETYPE_PNG == $orig_type ) {
		if ( !imagepng( $image, $destfilename ) ){
			pte_add_error("Resize path invalid");
			return false;
		}
	} 
	else {
		// all other formats are converted to jpg
		if ( !imagejpeg( $image, $destfilename, 90) ){
			pte_add_error("Resize path invalid: " . $destfilename);
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
 * OUTPUT: JSON object like --> "{ [ 'url1-image1', 'url2-image2'] }"
 */
function pte_resize_images(){
	global $pte_sizes;

	// for each size saveThumbnailAs...
	if ( ( !$id = pte_check_id( $_GET['id'] ) )
		|| ( !$w = pte_check_int( $_GET['w'] ) )
		|| ( !$h = pte_check_int( $_GET['h'] ) )
		|| ( !$x = pte_check_int( $_GET['x'] ) )
		|| ( !$y = pte_check_int( $_GET['y'] ) )
	){
		return pte_json_encode();
	}

	$pte_sizes      = $_GET['pte-sizes'];
	$sizes          = pte_get_all_alternate_size_information( $id );
	$dst_x          = 0;
	$dst_y          = 0;
	$original_file  = get_attached_file( $id );
	$original_image = wp_load_image( $original_file );
   $original_size  = @getimagesize( $original_file );

   if ( !$original_size ){
		pte_add_error("Could not read image size");
		return pte_json_encode();
	}

   list( $orig_w, $orig_h, $orig_type ) = $original_size;

	foreach ( $sizes as $size => $data ){
		// Get all the data needed to run image_create
		//
		//	$dst_w, $dst_h 
		extract( pte_get_width_height( $data, $w, $h ) );
		//
		// Set the directory
		$uploads  = wp_upload_dir();
		$basename = pte_generate_filename( $original_file, $dst_w, $dst_h );
		// Set the file and URL's
		$tmpfile  = $uploads['basedir'] . "/ptetmp/{$id}/{$basename}";
		$tmpurl   = $uploads['baseurl'] . "/ptetmp/{$id}/{$basename}";

		// === CREATE IMAGE ===================
		$image = pte_create_image( $original_image, $orig_type,
			$dst_x, $dst_y, $dst_w, $dst_h,
			$x,     $y,     $w,     $h );

		if ( ! isset( $image ) ){
			pte_add_error( "Error creating image: {$size}" );
			continue;
		}

		if ( ! pte_write_image( $image, $orig_type, $tmpfile ) ){
			pte_add_error( "Error writing image: {$size} to '{$tmpfile}'" );
			continue;
		}
		// === END CREATE IMAGE ===============

		// URL: wp_upload_dir => base_url/subdir + /basename of $tmpfile
		$urls[$size] = $tmpurl;
	}

	// we don't need the original in memory anymore
	imagedestroy( $original_image );

	if ( count( $urls ) < 1 ){
		return pte_json_encode();
	}

	//pte_json_encode( $thumbnails );
	pte_json_encode( array( 
		'thumbnails' => $urls,
		'pte-nonce'  => wp_create_nonce( "pte-{$id}" )
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
	global $pte_sizes, $pte_errors;

	$id = pte_check_id( (int) $_GET['id'] );

	// Check nonce
	if ( !check_ajax_referer( "pte-{$id}", 'pte-nonce', false ) ){
		pte_add_error( "CSRF Check failed" );
	}
		
   // Get the available sizes
	$pte_sizes = $_GET['pte-confirm'];
	$sizes = pte_get_all_alternate_size_information( $id );

	if ( count( $pte_errors ) > 0 ){
		return pte_json_encode();
	}

	// Move good images
	// Reset PTE/$id directory
	pte_json_encode( array( 'success' => "Yay!" ) );
}
