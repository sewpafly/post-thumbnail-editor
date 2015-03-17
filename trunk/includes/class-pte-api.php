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

class PTE_Api {

	/**
	 * Assert that the current user has access to the given image/post
	 *
	 * @since 3.0.0
	 * @param int    $id    The Post ID of the image you want to check
	 * @throws Exception if id is invalid
	 */
	public function assert_valid_id ( $id ) {

		if ( !$post = get_post( $id ) ) {
			throw new Exception("invalid-post");
		}
		$user_can = current_user_can( 'edit_post', $id )
			|| current_user_can( 'pte-edit', $id );
		$user_can = apply_filters( 'pte-capability-check', $user_can, $id );

		if ( $user_can ) return;
		else {
			throw new Exception('pte-capability-check-failed');
		}

	}

}
