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
 * Class PTE_ActualThumbnail
 */
class PTE_Thumbnail {

	/**
	 * Create an PTE_Actual_Thumbnail from a given $id and PTE_Thumbnail
	 *
	 * @since 3.0.0
	 *
	 * @param int $id   The post/attachment id to get thumbnail information for.
	 * @param PTE_Thumbnail $size  The size to return
	 *
	 * @return PTE_ActualThumbnail
	 */
	public function __construct ( $id, $size ) {

		$this->id = $id;
		$this->size = $size;

		$fullsizepath = get_attached_file( $id );
		$path_information = image_get_intermediate_size($id, $size->name);
		$file = sprintf( '%s' . DIRECTORY_SEPARATOR . '%s',
			dirname( $fullsizepath ),
			isset( $path_information['file'] ) ? $path_information['file'] : ''
		);

		// If the path doesn't exist, generate it...
		// We don't really care how it gets generated, just that it is...
		// see ajax-thumbnail-rebuild plugin for inspiration
		if ( $path_information === false || ! @file_exists( $file ) ) {

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
	}
	
	/**
	 * Create a list of thumbnails
	 *
	 * @since 3.0.0
	 *
	 * @param int $id   The post/attachment id to get thumbnail information for
	 *
	 * @return array of PTE_Actual_Thumbnail
	 */
	public static function get_all ( $id ) {

		$sizes = PTE_Thumbnail_Size::get_all();

		foreach ( $sizes as $size ) {
			$thumbnails[] = new self( $id, $size );
		}

		return $thumbnails;

	}

}
