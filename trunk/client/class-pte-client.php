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
				. "?action=pte_api&pte-action=client&pte-id={$id}"
				. "&TB_iframe=true";
		}
		return $url;

	}

	/**
	 * Return the HTML for the client
	 *
	 * @return string of html
	 */
	public function launch()
	{
		$this->load_scripts();
		$styles = $this->load_styles();
		include 'client.php';
	}

	/**
	 * Load the required scripts
	 *
	 * @return WP_Scripts instance
	 */
	public function load_scripts()
	{
		$scripts = new WP_Scripts();
		$scripts->add('pte-client',
			plugins_url("post-thumbnail-editor/client/build/bundle.js"));
		$scripts->add_data('pte-client', 'group', 1);
		$scripts->enqueue('jquery-ui-button');
		$scripts->enqueue('jquery-ui-dialog');
		$scripts->enqueue('pte-client');
		add_action('pte_client_print_head_scripts', array($scripts, 'do_head_items'), 10, 0);
		add_action('pte_client_print_footer_scripts', array($scripts, 'do_footer_items'), 10, 0);
		return $scripts;
	}

	/**
	 * Load the required styles
	 *
	 * @return WP_Styles instance
	 */
	public function load_styles()
	{
		$styles = new WP_Styles();
		$styles->add('font-awesome',
			'//maxcdn.bootstrapcdn.com/font-awesome/4.4.0/css/font-awesome.min.css');
		$styles->add('normalize',
			'//cdnjs.cloudflare.com/ajax/libs/normalize/3.0.3/normalize.min.css');
		$styles->enqueue('normalize');
		$styles->enqueue('font-awesome');
		$styles->enqueue('buttons');
		$styles->enqueue('wp-jquery-ui-dialog');
		add_action('pte_client_print_styles', array($styles, 'do_items'), 10, 0);
		return $styles;
	}

}
