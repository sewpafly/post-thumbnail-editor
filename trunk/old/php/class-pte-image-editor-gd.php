<?php

class PTE_Image_Editor_GD extends WP_Image_Editor_GD {

	/**
	 * Crops Image.
	 *
	 * @since 3.5.0
	 * @access public
	 *
	 * @param string|int $src The source file or Attachment ID.
	 * @param int $src_x The start x position to crop from.
	 * @param int $src_y The start y position to crop from.
	 * @param int $src_w The width to crop.
	 * @param int $src_h The height to crop.
	 * @param int $dst_w Optional. The destination width.
	 * @param int $dst_h Optional. The destination height.
	 * @param boolean $src_abs Optional. If the source crop points are absolute.
	 * @return boolean|WP_Error
	 */
	public function crop( $src_x, $src_y, $src_w, $src_h, $dst_w = null, $dst_h = null, $src_abs = false ) {
		if ( pte_is_crop_border_enabled( $src_w, $src_h, $dst_w, $dst_h ) ){
			// Crop the image to the correct aspect ratio...
			$ar = $src_w / $src_h;
			$dst_ar = $dst_w / $dst_h;
			if ( $dst_ar > $ar ) { // constrain to the dst_h
				$tmp_dst_h = $dst_h;
				$tmp_dst_w = $dst_h * $ar;
				$tmp_dst_y = 0;
				$tmp_dst_x = ($dst_w / 2) - ($tmp_dst_w / 2);
			}
			else {
				$tmp_dst_w = $dst_w;
				$tmp_dst_h = $dst_w / $ar;
				$tmp_dst_x = 0;
				$tmp_dst_y = ($dst_h / 2) - ($tmp_dst_h / 2);
			}
			// copy $this->image unto a new image with the right width/height.
			$img = wp_imagecreatetruecolor( $dst_w, $dst_h );

			if ( function_exists( 'imageantialias' ) )
				imageantialias( $img, true );

			if ( pte_is_crop_border_opaque() ) {
				$c = self::getRgbFromHex( $_GET['pte-fit-crop-color'] );
				$color = imagecolorallocate( $img, $c[0], $c[1], $c[2] );
			}
			else {
				PteLogger::debug( "setting transparent/white" );
				//$color = imagecolorallocate( $img, 100, 100, 100 );
				$color = imagecolorallocatealpha( $img, 255, 255, 255, 127 );
			}

			imagefilledrectangle( $img, 0, 0, $dst_w, $dst_h, $color );
			imagecopyresampled( $img, $this->image,
				$tmp_dst_x, $tmp_dst_y, $src_x, $src_y,
				$tmp_dst_w, $tmp_dst_h, $src_w, $src_h
			);
			if ( is_resource( $img ) ) {
				imagedestroy( $this->image );
				$this->image = $img;
				$this->update_size();
				return true;
			}
			return new WP_Error( 'image_crop_error', __('Image crop failed.'), $this->file );
		}
		return parent::crop( $src_x, $src_y, $src_w, $src_h, $dst_w, $dst_h, $src_abs );
	}

	/**
	 * Pulled from: http://php.net/manual/en/ref.image.php
	 * 
	 * print_r( getRgbFromHex( "#FFFFFF" ) ) 
	 * --> // Output: Array ( [0] => 255 [1] => 255 [2] => 255 )
	 */
	public static function getRgbFromHex($color_hex) {
		return array_map( 'hexdec', explode( '|', wordwrap( substr( $color_hex, 1 ), 2, '|', 1 ) ) );
	}
	
}
