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
	 * @param mixed    $sizes   The list of Thumbnail objects
	 * @param string   $filter  if present, (not 'none') apply the filter above
	 * @return mixed   $sizes   The list of Thumbnail objects
	 */
	public function get_sizes ( $filter = null ) {

		if ( ! isset( $this->thumbnails ) ) {
			$this->thumbnails = PTE_Thumbnail::get_all();
		}

		if ( is_null( $filter ) ) {
			return $this->thumbnails;
		}

		return array_filter( $this->thumbnails, array( $this, 'filter_sizes' ) );

	}
	
	/**
	 * Filter callback for get_sizes
	 *
	 * @param PTE_Thumbnail    $element   The thumbnail to check
	 * @return true   if the $element should be filtered
	 */
	private function filter_sizes ( $element ) {

		/**
		 * Check for the hidden sizes option
		 *
		 * Return the thumbnail information that the user should be able to
		 * modify.
		 *
		 * @since 2.0.0
		 * @param array    $sizes   The sizes the user can see
		 * @param string   $label   The option to get
		 */
		$filtered_sizes = apply_filters( 'pte_options_get', array(), 'pte_hidden_sizes');
		if ( is_array( $filtered_sizes ) && in_array( $element->label, $filtered_sizes ) ) {
			return true;
		}
		return false;

	}
	
}
