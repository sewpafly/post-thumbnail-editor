<?php

function pte_get_alternate_sizes($return_php = false){
   global $_wp_additional_image_sizes;
   $sizes = array();
   foreach (get_intermediate_image_sizes() as $s){
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
      $sizes[$s] = array(
         'width'  => $width,
         'height' => $height,
         'crop'   => $crop
      );
   }

   if ($return_php) return $sizes;
   else if ( function_exists('json_encode') ){
      print(json_encode(array('sizes' => $sizes)));
   }
   else {
      print("{\"error\":\"json_encode not available, upgrade your php\"}");
   }
   die();
}

   //wp_enqueue_scripts('jquery');
   //include(dirname(__FILE__) . "/html/editor.phtml");

// TODO: Check your inputs...
function pte_get_image_data($id, $size){
   //die(json_encode(image_get_intermediate_size($id, $size)));
   //die(json_encode(image_downsize($id, $size)));
   //die(json_encode(get_attached_file($id)));
   $path_information = image_get_intermediate_size($id, $size);
   $size_information = pte_get_alternate_sizes(true);
   $fullsizepath = get_attached_file( $id );

   // Get/Create nonce
   $nonce = wp_create_nonce("pte-{$id}-{$size}");

   if ( $path_information && 
      $path_information['width'] == $size_information[$size]['width'] &&
      $path_information['height'] == $size_information[$size]['height'] &&
      @file_exists(dirname($fullsizepath)."/".$path_information['file']))
   {
      $path_information['nonce'] = $nonce;
      die(json_encode($path_information));
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

   // Finish how we started
   $path_information = image_get_intermediate_size($id, $size);
   if ($path_information){
      $path_information['nonce'] = $nonce;
      die(json_encode($path_information));
   }
   else {
      //print("{\"error\":\"Couldn't find metadata for image: $id\"}");
      pte_error("Couldn't find metadata for image");
   }
}

function pte_error($message){
   die("{\"error\":\"{$message}\"}");
}

// TODO: Check your inputs...
function pte_resize_img($id, $thumb_size, $x1, $y1, $x2, $y2){
   // Call image_resize...
//function image_resize( $file, $max_w, $max_h, $crop = false, $suffix = null, $dest_path = null, $jpeg_quality = 90 ) {

   $file = get_attached_file( $id );
   $image = wp_load_image( $file );
   $size_information = pte_get_alternate_sizes(true);
   $path_information = image_get_intermediate_size($id, $thumb_size);
   //die(json_encode($path_information));

   if ( !is_resource( $image ) )
      pte_error("Error loading image");
      //return new WP_Error( 'error_loading_image', $image, $file );

   $size = @getimagesize( $file );
   if ( !$size )
      pte_error("Could not read image size");
      //return new WP_Error('invalid_image', __('Could not read image size'), $file);

   list($orig_w, $orig_h, $orig_type) = $size;

   // Error checking that the src is big enough to go into dst?
   $dst_x = 0;
   $dst_y = 0;
   $dst_w = $size_information[$thumb_size]['width'];
   $dst_h = $size_information[$thumb_size]['height'];
   $src_x = $x1;
   $src_y = $y1;
   $src_w = $x2 - $x1;
   $src_h = $y2 - $y1;

   $newimage = wp_imagecreatetruecolor( $dst_w, $dst_h );

   imagecopyresampled( $newimage, $image, $dst_x, $dst_y, $src_x, $src_y, $dst_w, $dst_h, $src_w, $src_h);

   // convert from full colors to index colors, like original PNG.
   if ( IMAGETYPE_PNG == $orig_type && function_exists('imageistruecolor') && !imageistruecolor( $image ) )
      imagetruecolortopalette( $newimage, false, imagecolorstotal( $image ) );

   // we don't need the original in memory anymore
   imagedestroy( $image );

   // Get the output filename
   // TODO: Does this file exist?
   //$destfilename = "{$dir}/{$name}-{$suffix}.{$ext}";
   $destfilename = dirname($file)."/".$path_information['file'];

   if ( IMAGETYPE_GIF == $orig_type ) {
      if ( !imagegif( $newimage, $destfilename ) )
         //return new WP_Error('resize_path_invalid', __( 'Resize path invalid' ));
         pte_error("Resize path invalid");
   } 
   elseif ( IMAGETYPE_PNG == $orig_type ) {
      if ( !imagepng( $newimage, $destfilename ) )
         //return new WP_Error('resize_path_invalid', __( 'Resize path invalid' ));
         pte_error("Resize path invalid");
   } 
   else {
      // all other formats are converted to jpg
      //if ( !imagejpeg( $newimage, $destfilename, apply_filters( 'jpeg_quality', $jpeg_quality, 'image_resize' ) ) )
      if ( !imagejpeg( $newimage, $destfilename, 90) )
         //return new WP_Error('resize_path_invalid', __( 'Resize path invalid' ));
         pte_error("Resize path invalid: " . $destfilename);
   }

   imagedestroy( $newimage );

   // Set correct file permissions
   $stat = stat( dirname( $destfilename ));
   $perms = $stat['mode'] & 0000666; //same permissions as parent folder, strip off the executable bits
   @ chmod( $destfilename, $perms );

   die(json_encode(array("file" => $destfilename)));
}

// TODO: Save the conversion data so if a batch script (a la ajax-thumbnail-rebuild)
//       that we can find what the scale/crop area was again.
