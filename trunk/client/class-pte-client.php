<?php

/**
 * The client-specific functionality of the plugin.
 *
 * @link       http://sewpafly.github.io/post-thumbnail-editor
 * @since      3.0.0
 *
 * @package    Post_Thumbnail_Editor
 * @subpackage Post_Thumbnail_Editor/client
 */
class PTE_Client {

	/**
	 * Return a URL to the PTE client
	 *
	 * @since 3.0.0
	 * @param int  $id   The ID of the image you want to modify
	 * @throws Exception if the post doesn't exist or the current user doesn't
	 *                   have access
	 */
	public function url ( $id, $iframe = 'none' ) {

		if ( $iframe !== 'none') {
			$url = admin_url( 'admin-ajax.php' )
				. "?action=pte_ajax&pte-action=iframe&pte-id={$id}"
				. "&TB_iframe=true";
		}
		else {
			$url = admin_url('upload.php') 
				. "?page=pte-edit&pte-id={$id}";
		}
		return $url;

	}

}
