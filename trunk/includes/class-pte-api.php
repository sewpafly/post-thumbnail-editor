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
	 * Constructor for PTE_Api sets the arg_keys needed for ahooks (See
	 * PTE_Hooker)
	 *
	 * @since 3.0.0
	 *
	 * @param mixed 
	 */
	public function __construct () {

		$this->arg_keys['derive_dimensions'] = array( 'size', 'w', 'h' );
		$this->arg_keys['derive_basename'] = array(
			'original_file',
			'dst_w',
			'dst_h',
			'transparent'
	   	);

	}
	

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
	 *
	 * @param mixed    $filter  if present, (not 'none') apply the filter above.
	 *                          A 'truthy' value will check the options for
	 *                          filtered values.  An array will only return
	 *                          sizes with names in the array, and a single name
	 *                          will return just that size. 
	 *
	 * @return mixed   $sizes   The list of Thumbnail objects
	 */
	public function get_sizes ( $filter = null ) {

		if ( ! isset( $this->thumbnails ) ) {
			$this->thumbnails = PTE_Thumbnail_Size::get_all();
		}

		if ( empty( $filter ) ) {
			return $this->thumbnails;
		}

		$filtered_sizes = apply_filters( 'pte_options_get', array(), 'pte_hidden_sizes');

		foreach ( $this->thumbnails as $size ) {
			if ( in_array( $size->name, $filtered_sizes ) ) {
				continue;
			}
			if ( is_array( $filter ) && in_array( $size->name, $filter ) || $filter == $size->name ) {
				$thumbnails[] = $size;
			}
		}
		return $thumbnails;

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
	public function resize_thumbnails ( $id, $w, $h, $x, $y, $sizes, $save=false ) {

		$data = compact( 'id', 'w', 'h', 'x', 'y', 'save' );
		$data['original_file'] = _load_image_to_edit_path( $id );
		$data['original_size'] = @getimagesize( $data['original_file'] );
		$data['tmp_dir'] = apply_filters( 'pte_options_get', null, 'tmp_dir' );
		$data['tmp_url'] = apply_filters( 'pte_options_get', null, 'tmp_url' );

		if ( ! isset( $data['original_size'] ) ) {
			throw new Exception( __( 'Could not read image size', 'post-thumbnail-editor' ) );
		}

		$thumbnails = array();

		foreach ( $this->get_sizes( $sizes ) as $size ) {
			$data['size'] = $size;
			$thumbnail = $this->resize_thumbnail( $data );
			if ( ! empty( $thumbnail ) ) {
				$thumbnails[] = $thumbnail;
			}
		}

		return $thumbnails;
	}

	/**
	 * Resize an individual thumbnail
	 *
	 * @since 3.0.0
	 *
	 * @param mixed $params {
	 *	   @type int                 $id       The post id to resize
	 *	   @type PTE_Thumbnail_Size  $size     The thumbnail size
	 *	   @type int                 $w        The proposed width
	 *	   @type int                 $h        The proposed height
	 *	   @type int                 $x        The proposed starting left point
	 *	   @type int                 $y        The proposed starting upper
	 *	                                       point
	 *	   @type int/boolean         $save     Should the image be saved
	 *	   @type string              $tmp_dir  Temporary directory
	 *	   @type string              $tmp_file Temporary directory
	 *	   @type array          $original_size The original file image
	 *	                                       parameters
	 *	   @type string         $original_file The original file name
	 * }
	 *
	 * @return PTE_Thumbnail
	 */
	private function resize_thumbnail ( $params ) {

		/**
		 * Action `pte_resize_thumbnail' is triggered when resize_thumbnails is
		 * ready to roll (after the parameters and correctly compiled and just
		 * before the resize_thumbnail function is called.
		 *
		 * @since 3.0.0
		 *
		 * @param See above
		 *
		 * @return filtered params ready to modify the image
		 */
		$params = apply_filters( 'pte_api_resize_thumbnail', $params );

		var_dump( $params );
	}

	/**
	 * Derive the correct width/height given the input width/height and the
	 * output size
	 *
	 * @since 3.0.0
	 *
	 * @param PTE_Thumbnail_Size $size   The thumbnail size to output
	 * @param int                $width  The user selected width
	 * @param int                $height The user selected height
	 *
	 * @return array {
	 *    @type int width
	 *    @type int height
	 * }
	 */
	public function derive_dimensions ( $size, $w, $h ) {

		if ( $size->crop ) {

			$dst_w = $size->width;
			$dst_h = $size->height;

		}

		// Crop isn't set so the height / width should be based on the largest
		// side of the image itself, unless that side is set to 0 or something
		// really big, in which case use the other side.
		else {

			$use_width = false;

			if ( $w > $h ) $use_width = true;

			if ( ! $use_width && ( $size->height == 0 || $size->height > 9998 ) ) $use_width = true;
			else if ( $use_width && ( $size->width == 0 || $size->width > 9998 ) ) $use_width = false;

			if ( $use_width ) {

				$dst_w = $size->width;
				$dst_h = intval( round( ($dst_w / $w) * $h, 0 ) );

			}
			else {

				$dst_h = $size->height;
				$dst_w = intval( round( ($dst_h / $h) * $w, 0 ) );

			}

		}

		// Sanity Check
		if ( $dst_h == 0 || $dst_w == 0 ) {

			throw new Exception(
				sprintf(
					__( 'Invalid derived dimensions: %s x %s', 'post-thumbnail-editor' ),
					$dst_w, $dst_h
				)
		   	);

		}

		return compact( 'dst_w', 'dst_h' );

	}
	
	/**
	 * Generate a basename for the file
	 *
	 * @since 2.0.0
	 *
	 * @param string  $file   The original file name
	 * @param int     $width  The thumbnail width
	 * @param int     $height The thumbnail height
	 * @param bool    $trans  If the image needs transparency it must have a
	 *                        .png extension
	 *
	 * @return array {
	 *    @type string  $basename  The basename of the file to save
	 * }
	 */
	public function derive_basename ( $file, $w, $h, $transparent) {

		$info         = pathinfo( $file );
		$ext          = (false !== $transparent) ? 'png' : $info['extension'];
		$name         = wp_basename( $file, ".$ext" );
		$suffix       = "{$w}x{$h}";

		if ( apply_filters( 'pte_options_get', false, 'cache_buster' ) ){
			$cache_buster = time();
			return sprintf( "%s-%s-%s.%s",
				$name,
				$suffix,
				$cache_buster,
				$ext );
		}

		return array( 'basename' => "{$name}-{$suffix}.{$ext}" );

	}
	
}
