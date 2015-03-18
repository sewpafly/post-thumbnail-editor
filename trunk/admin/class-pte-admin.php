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

	/**
	 * Add the Post Thumbnail Editor link to the featured image box on the post
	 * edit screen.
	 *
	 * @since 3.0.0
	 * @return string   HTML string to put in the featured image box
	 */
	public function link_featured_image ( $content, $post_id ) {

		$this->add_thickbox();

		$thumbnail_id = get_post_thumbnail_id( $post_id );

		if ( $thumbnail_id == null )
			return $content;

		return sprintf('%s<p id="pte-link" class="hide-if-no-js"><a class="thickbox" href="%s">%s</a></p>',
			$content,
			PTE::client()->url( $thumbnail_id, true ),
			esc_html__( 'Post Thumbnail Editor', 'post-thumbnail-editor' )
		);

	}

	/**
	 * Fix wordpress ridiculousness about making a thickbox max width=720.
	 *
	 * @since 3.0.0
	 */
	private function add_thickbox () {
		add_thickbox();
		wp_enqueue_script('pte-fix-thickbox',
			plugin_dir_url( __FILE__ ) . 'js/pte-fix-thickbox.js',
			array( 'media-upload' ),
			$this->version
		);
	}
	
	/**
	 * Add the options pages
	 *
	 * @since 3.0.0
	 */
	public function options () {
		
		PTE::options()->init();

	}
	
	/**
	 * These pages are linked into the hook system of wordpress, this means
	 * that almost any wp_admin page will work as long as you append "?page=pte"
	 * or "?page=pte-edit".  Try the function `'admin_url("index.php") . '?page=pte';`
	 *
	 * The function referred to here will output the HTML for the page that you want
	 * to display. However if you want to hook into enqueue_scripts or styles you
	 * should use the page-suffix that is returned from the function. (e.g.
	 * `add_action("load-".$hook, hook_func);`)
	 *
	 * There is also another hook with the same name as the hook that's returned.
	 * I don't remember in which order it is launched, but I believe the pertinent
	 * code is in admin-header.php.
	 *
	 * @since 3.0.0
	 */
	public function admin_menu ()
	{
		add_options_page( __('Post Thumbnail Editor', 'post-thumbnail-editor'),
			__('Post Thumbnail Editor', 'post-thumbnail-editor'),
			'edit_posts',
			'pte',
			array( $this, 'launch_options' )
		);

		// The submenu page function does not put a menu item in the wordpress sidebar.
		add_submenu_page(NULL, __('Post Thumbnail Editor', 'post-thumbnail-editor'),
			__('Post Thumbnail Editor', 'post-thumbnail-editor'),
			'edit_posts',
			'pte-edit',
			array( $this, 'launch_client' )
		);
	}
	

	/**
	 * Launch the options page
	 *
	 * @since 3.0.0
	 */
	public function launch_options () {

		PTE::options()->launch();

	}
	
}
