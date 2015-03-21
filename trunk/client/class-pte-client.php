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
class PTE_Client extends PTE_Hooker {

	/**
	 * Return a URL to the PTE client
	 *
	 * @since 3.0.0
	 * @param string $url     default url
	 * @param string $id      The ID of the image you want to modify
	 * @param string $iframe  'true' if URL should be to self-contained iframe
	 *
	 * @return url to client
	 */
	public function url ( $id, $iframe = 'false' ) {

		if ( empty( $iframe ) || $iframe != 'true') {
			$url = admin_url('upload.php') 
				. "?page=pte-edit&pte-id={$id}";
		}
		else {
			$url = admin_url( 'admin-ajax.php' )
				. "?action=pte_ajax&pte-action=iframe&pte-id={$id}"
				. "&TB_iframe=true";
		}
		return $url;

	}

}
