<?php

/**
 * Class for using and cataloging the various sizes.
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

class PTE_Thumbnail {

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
	 * @return array of PTE_Thumbnail
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
	public static function get_image_param ( $param, $name ) {

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

		return get_option( $option_mapping[$param] );
	}
	
}
