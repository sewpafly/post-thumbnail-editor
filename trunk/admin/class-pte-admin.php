<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       http://sewpafly.github.io/post-thumbnail-editor
 * @since      3.0.0
 *
 * @package    Post_Thumbnail_Editor
 * @subpackage Post_Thumbnail_Editor/admin
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Post_Thumbnail_Editor
 * @subpackage Post_Thumbnail_Editor/admin
 */
class PTE_Admin {

	/**
	 * The ID of this plugin.
	 *
	 * @since    3.0.0
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    3.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    3.0.0
	 * @param      string    $plugin_name       The name of this plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version = $version;

	}

	/**
	 * Register media row actions.
	 *
	 * When the media library is in row view, display a "Thumbnails" link that
	 * will launch the PTE client
	 *
	 * @since 1.0.0
	 * @param mixed  $actions  The actions to apply to the media row
	 * @param object $post     The post that the actions should apply to
	 * @param bool   $detached
	 */
	public function media_row_actions($actions, $post, $detached) {
		
		try {
			PTE::api()->assert_valid_id( $post->ID );
		}
		catch (Exception $e) {
			return $actions;
		}

		$url = PTE::client()->url( $post->ID );
		$actions['pte'] = sprintf("<a href='%s' title='%s'>%s</a>",
			$url,
			__( 'Edit Thumbnails', 'post-thumbnail-editor'),
			__( 'Thumbnails', 'post-thumbnail-editor')
		);

		return $actions;

	}

	/**
	 * Add media library boot action
	 *
	 * @since 3.0.0
	 */
	public function add_media_library_load_action () {

		add_action('wp_enqueue_media', array( $this, 'load_media_library' ));

	}

	/**
	 * Load media library (this should only run when upload.php is loaded and
	 * media is being displayed.
	 *
	 * We check for grid view to see if we should patch the media libraries
	 * javascript
	 *
	 * @since 3.0.0
	 */
	public function load_media_library () {

		global $mode; 
		if ('grid' !== $mode) return;
		wp_enqueue_script( 'pte',
			plugin_dir_url( __FILE__ ) . 'js/load-media-library.js',
			null,
			$this->version,
			true
		);
		wp_localize_script( 'pte',
			'pteL10n',
			array(
				'PTE' => __( 'Post Thumbnail Editor', 'post-thumbnail-editor' ),
				'url' => PTE::client()->url( "<%= id %>", 'iframe' ),
				'fallbackUrl' => PTE::client()->url( "<%= id %>" )
			)
		);

	}
}
