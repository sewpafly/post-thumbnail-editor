<?php

/**
 * Classes for using and cataloging the various sizes.
 *
 * Provide static methods for getting a list of sizes as well as the object
 * itself.
 *
 * @link       http://sewpafly.github.io/post-thumbnail-editor
 * @since      3.0.0
 *
 * @package    Post_Thumbnail_Editor
 * @subpackage Post_Thumbnail_Editor/includes
 */

class PTE_Thumbnail_Size {

	/**
	 * The thumbnail name
	 *
	 * @since    3.0.0
	 * @var      string    $name
	 */
	public $name;

	/**
	 * The thumbnail label
	 *
	 * @since    3.0.0
	 * @var      string    $label
	 */
	public $label;

	/**
	 * The thumbnail width
	 *
	 * @since    3.0.0
	 * @var      int       $width
	 */
	public $width;

	/**
	 * The thumbnail height
	 *
	 * @since    3.0.0
	 * @var      int       $height
	 */
	public $height;

	/**
	 * The thumbnail crop
	 *
	 * @since    3.0.0
	 * @var      int       $crop
	 */
	public $crop;

	/**
	 * Constructor for the thumbnail object
	 *
	 * @since 3.0.0
	 * @param mixed
	 */
	public function __construct ( $name, $label, $width, $height, $crop ) {

		$this->name = $name;
		$this->label = $label;
		$this->width = $width;
		$this->height = $height;
		$this->crop = $crop;

	}

	/**
	 * Create a list of thumbnails
	 *
	 * @return array of PTE_Thumbnail_Size
	 */
	public static function get_all () {

		$thumbnails = array();

		/**
		 * Apply the wordpress filter for defining image size translations
		 */
		$thumbnail_labels = apply_filters( 'image_size_names_choose', array(
			'thumbnail' => __( 'Thumbnail' ),
			'medium'    => __( 'Medium' ),
			'large'     => __( 'Large' ),
			'full'      => __( 'Full Size' )
		) );

		// get_intermediate_image_sizes is a wordpress function
		$sizes = get_intermediate_image_sizes();

		foreach ( $sizes as $size ) {
			$width = self::get_image_param( 'width', $size );
			$height = self::get_image_param( 'height', $size );
			$crop = self::get_image_param( 'crop', $size );
			$label = array_key_exists( $size, $thumbnail_labels )
				? $thumbnail_labels[$size]
				: $size;
			$thumbnails[] = new self($size, $label, $width, $height, $crop);
		}

		return $thumbnails;

	}

	/**
	 * Inspect the wordpress global $_wp_additional_image_sizes for image
	 * information.  If that information isn't found inspect the wordpress
	 * options.
	 *
	 * @return void
	 */
	private static function get_image_param ( $param, $name ) {

		global $_wp_additional_image_sizes;

		// For theme-added sizes
		if ( isset( $_wp_additional_image_sizes[$name][$param] ) ) {
			return intval( $_wp_additional_image_sizes[$name][$param] );
		}

		$option_mapping = array(
			'width' => "{$name}_size_w",
			'height' => "{$name}_size_h",
			'crop' => "{$name}_crop"
		);

		return intval( get_option( $option_mapping[$param] ) );
	}
}

/**
 * Class PTE_Thumbnail
 */
class PTE_Thumbnail {

	/**
	 * Create an PTE_Thumbnail from a given $id and PTE_Thumbnail
	 *
	 * @since 3.0.0
	 *
	 * @param int $id   The post/attachment id to get thumbnail information for.
	 * @param PTE_Thumbnail_Size $size  The size to return
	 *
	 * @return PTE_Thumbnail
	 */
	public function __construct ( $id, $size ) {

		$this->id = $id;
		$this->size = $size;

		$this->filepath = get_attached_file( $id );
		$path_information = image_get_intermediate_size($id, $size->name);

		// If the path doesn't exist, generate it...
		// We don't really care how it gets generated, just that it is...
		// see ajax-thumbnail-rebuild plugin for inspiration
		if ( $path_information === false || ! @file_exists(
			path_join( dirname($this->filepath), $path_information['file'])
		) ) {

			// Create the image and update the wordpress metadata
			$resized = image_make_intermediate_size( $fullsizepath,
				$size->width,
				$size->height,
				$size->crop
			);

			if ( $resized ) {

				$metadata = wp_get_attachment_metadata($id, true);
				$metadata['sizes'][$size->name] = $resized;
				wp_update_attachment_metadata( $id, $metadata);

			}

			$path_information = image_get_intermediate_size($id, $size->name);
		}

		$this->path   = $path_information['path'];
		$this->url    = $path_information['url'];
		$this->file   = $path_information['file'];
		$this->width  = $path_information['width'];
		$this->height = $path_information['height'];

	}

	/**
	 * Save the thumbnail metadata
	 *
	 * @return void
	 */
	public function save () {
		$metadata = wp_get_attachment_metadata( $this->id, true );
		$metadata['sizes'][$this->size->name] = array(
			'file' => $this->file,
			'width' => $this->width,
			'height' => $this->height,
		);
		wp_update_attachment_metadata( $this->id, $metadata );
		$this->saved = true;
	}

	/**
	 * Resize an individual thumbnail
	 *
	 * @since 3.0.0
	 *
	 * @param int                 $w        The proposed width
	 * @param int                 $h        The proposed height
	 * @param int                 $x        The proposed starting left point
	 * @param int                 $y        The proposed starting upper
	 *                                      point
	 * @param int/boolean         $save     Should the image be saved
	 *
	 * @return PTE_Thumbnail
	 */
	public function resize ( $w, $h, $x, $y ) {

		/**
		 * Action `pte_resize_thumbnail' is triggered when resize_thumbnails is
		 * ready to roll (after the parameters and correctly compiled and just
		 * before the resize_thumbnail function is called.
		 *
		 * @since 3.0.0
		 *
		 * @param mixed
		 *	   @type int            $id             The post id to resize
		 *	   @type int            $w              The proposed width
		 *	   @type int            $h              The proposed height
		 *	   @type int            $dst_w          The final width
		 *	   @type int            $dst_h          The final height
		 *	   @type int            $x              The proposed starting left point
		 *	   @type int            $y              The proposed starting upper
		 *	                                        point
		 *	   @type string         $tmpfile        Temporary file
		 *	   @type string         $tmpurl         Temporary url
		 *	   @type string         $original_file  The original file name
		 *
		 * @return filtered params ready to modify the image
		 */
		extract(apply_filters('pte_resize_thumbnail', array(
			'id' => $this->id,
			'w' => $w,
			'h' => $h,
			'x' => $x,
			'y' => $y,
			'size' => $this->size,
			'original_file' => $this->filepath,
		)));

		$editor = wp_get_image_editor( $original_file );

		if ( is_wp_error( $editor ) ) {
			throw new Exception( sprintf(
				__( 'Unable to load file: %s', 'post-thumbnail-editor' ), $original_file)
			);
		}

		if ( is_wp_error( $editor->crop( $x, $y, $w, $h, $dst_w, $dst_h ) ) ) {
			throw new Exception( sprintf(
				__( 'Error cropping image: %s', 'post-thumbnail-editor' ),
				$params['size']->name
			) );
		}

		wp_mkdir_p( dirname( $tmpfile ) );

		if ( is_wp_error( $editor->save( $tmpfile ) ) ) {
			throw new Exception( sprintf(
				__( 'Error writing image: %s to %s', 'post-thumbnail-editor' ),
				$this->size->label,
				$tmpfile
			) );
		}

		$oldfile = path_join(dirname($original_file), $this->file);
		$oldfile = ($oldfile == $tmpfile) ? FALSE : $oldfile;
		$this->url = $tmpurl;
		$this->file = basename($tmpfile);
		$this->width = $dst_w;
		$this->height = $dst_h;

		if ( apply_filters( 'pte_options_get', false, 'pte_crop_save' ) ) {
			$this->save();
			PTE_File_Utils::delete_file( $oldfile );
		}

		return $this;
	}

	/**
	 * Move files and update database
	 *
	 * @since 3.0.0
	 *
	 * @param mixed $params {
	 *	   @type string              $file     The temporary file to move
	 * }
	 *
	 * @return PTE_Thumbnail
	 */
	public function confirm ( $file ) {

		/**
		 * Action `pte_confirm_image' is triggered when confirm_image is
		 * ready to roll.
		 *
		 * @since 3.0.0
		 *
		 * @param See above
		 *
		 * @return filtered params ready to modify the image
		 */
		$tmp_dir = apply_filters('pte_options_get', false, 'tmp_dir');
		extract(apply_filters( 'pte_api_confirm_image',  array(
			'_file' => path_join($tmp_dir . $this->id, $file),
		)));

		$copy_from = $_file;
		$copy_to = path_join( dirname( $this->filepath ), $file );
		$url = dirname( $this->url ) . "/" . $file;

		// Move good image
		PTE_File_Utils::copy_file( $copy_from, $copy_to );

		$this->file = $file;
		$this->width = $dst_w;
		$this->height = $dst_h;
		$this->save();

		$thumb = new PTE_Thumbnail($this->id, $this->size);
		$thumb->save = true;
		return $thumb;

	}
}
