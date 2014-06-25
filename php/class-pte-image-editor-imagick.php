<?php

class PTE_Image_Editor_Imagick extends WP_Image_Editor_Imagick {

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

			if ( pte_is_crop_border_opaque() ) {
				$color = new ImagickPixel( $_GET['pte-fit-crop-color'] );
			}

			try {
				// crop the original image
				$this->image->cropImage( $src_w, $src_h, $src_x, $src_y );
				$this->image->scaleImage( $tmp_dst_w, $tmp_dst_h );

				// Create a new image and then compose the old one onto it.
				$img = new Imagick();
				$img->newImage( $dst_w, $dst_h, isset( $color ) ? $color : 'white' );
				$img->setImageFormat( $this->image->getImageFormat() );
				if ( ! isset( $color ) ) {
					$img->setImageOpacity( 0.0 );
				}
				$img->compositeImage( $this->image, Imagick::COMPOSITE_DEFAULT, $tmp_dst_x, $tmp_dst_y );
				$img->flattenImages();

				$this->image = $img;
			}
			catch ( Exception $e ) {
				return new WP_Error( 'image_crop_error', __('Image crop failed.'), $this->file );
			}
			return $this->update_size();

		}
		return parent::crop( $src_x, $src_y, $src_w, $src_h, $dst_w, $dst_h, $src_abs );
	}

}
