<?php

/**
 * The Post Thumbnail Editor options
 *
 * @link       http://sewpafly.github.io/post-thumbnail-editor
 * @since      3.0.0
 *
 * @package    Post_Thumbnail_Editor
 * @subpackage Post_Thumbnail_Editor/includes
 */

class PTE_Options {

	/**
	 * The user options
	 *
	 * @since    3.0.0
	 * @access   private
	 * @var      mixed    $user_options    The cached user_options
	 */
	private $user_options;

	/**
	 * The site options
	 *
	 * @since    3.0.0
	 * @access   private
	 * @var      mixed    $user_options    The cached site_options
	 */
	private $site_options;

	/**
	 * Get the current users option label/name.
	 *
	 * @since 3.0.0
	 */
	public function name () {

		global $current_user;
		if ( ! isset( $current_user ) ){
			get_currentuserinfo();
		}
		return "pte-option-{$current_user->ID}";

	}

	/**
	 * Get the current user options
	 *
	 * @since 3.0.0
	 */
	public function get_user_options () {
		if ( isset( $this->user_options ) ) {
			return $this->user_options;
		}

		$pte_options = get_option( $this->name() );
		if ( !is_array( $pte_options ) ){
			$pte_options = array();
		}
		$defaults = array( 'pte_debug' => false
			, 'pte_crop_save' => false
			, 'pte_thumbnail_bar' => 'horizontal'
			, 'pte_imgedit_disk' => false
			, 'pte_imgedit_max_size' => 600
			, 'pte_debug_out_chrome' => false
			, 'pte_debug_out_file' => false
		);

		// Attention: WORDPRESS DEBUG overrides user setting...
		$this->user_options = array_merge( $defaults, $pte_options );
		return $this->user_options;
	}

	/**
	 * Get the site level options
	 *
	 * @since 3.0.0
	 * @return array of site-level options and current configuration
	 */
	public function get_site_options () {
		if ( isset( $this->site_options ) ) {
			return $this->site_options;
		}

		$pte_site_options = get_option( 'pte-site-options' );

		if ( !is_array( $pte_site_options ) ){
			$pte_site_options = array();
		}
		$defaults = array(
			'pte_hidden_sizes' => array(),
		   	'cache_buster' => true
		);

		$this->site_options = array_merge( $defaults, $pte_site_options );
		return $this->site_options;

	}
	
	/**
	 * Shortcut for adding settings
	 *
	 * @since 3.0.0
	 */
	/**
	 * undocumented function
	 *
	 * @return void
	 */
	public function add_setting ( $name, $title, $callback, $section) {
		add_settings_field( $name, $title, array( $this, $callback ), 'pte', $section );
	}
	
	
	/**
	 * Init the option parameters
	 * 
	 * All the methods referred to here are dynamically created (overloaded)
	 * using the `__call` php method.  User options are shunted to
	 * display_html and site options go to display_site_html.  These
	 * functions just echo the HTML to output.
	 *
	 * Similarly `site_options_html`, a one-line function, is thrown into the
	 * `__call` method because I'm lazy.
	 *
	 * @since 3.0.0
	 */
	public function init () {
		
		add_filter( 'option_page_capability_pte_options', array( $this, 'edit_options_cap' ) );

		register_setting( 'pte_options',
			$this->name(),   // Settings are per user
			'pte_options_validate' );

		add_settings_section( 'pte_main'
			, __('User Options', 'post-thumbnail-editor')
			, 'PTE::noop'
			, 'pte' );

		// When you add a setting you create it with the callback in the form of
		// display_user_<function>_html: this will then attempt to load the
		// options-<function>.php file in the partials directory
		$this->add_setting( 'pte_debug', __( 'Debug', 'post-thumbnail-editor' ),
		   	'display_user_debug_html', 'pte_main' );
		$this->add_setting( 'pte_crop_save', __( 'Crop and Save', 'post-thumbnail-editor' ),
		   	'display_user_cropsave_html', 'pte_main' );
		$this->add_setting( 'pte_imgedit_max_size', __( 'Crop Picture Size', 'post-thumbnail-editor' ),
		   	'display_user_cropsize_html', 'pte_main' );
		$this->add_setting( 'pte_reset', __( 'Reset to defaults', 'post-thumbnail-editor' ),
		   	'display_user_reset_html', 'pte_main' );

		// Only show for admins...//
		if ( current_user_can( 'manage_options' ) ){

			register_setting( 'pte_options'
				, 'pte-site-options'     // Settings are site-wide
				, 'pte_site_options_validate' );

			add_settings_section( 'pte_site'
				, __('Site Options', 'post-thumbnail-editor')
				, array( $this, 'site_options_html' )
				, 'pte' );

			$this->add_setting( 'pte_sizes', __( 'Thumbnails', 'post-thumbnail-editor' ),
				'display_site_sizes_html', 'pte_site' );
			$this->add_setting( 'pte_jpeg_compression', __( 'JPEG Compression', 'post-thumbnail-editor' ),
				'display_site_jpegcompression_html', 'pte_site' );
			$this->add_setting( 'pte_cache_buster', __( 'Cache Buster', 'post-thumbnail-editor' ),
				'display_site_cachebuster_html', 'pte_site' );

		}

	}

	/**
	 * Launch the options page
	 *
	 * @since 3.0.0
	 */
	public function launch () {
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/partials/options-page.php';
	}
	
	/**
	 * Overload: Display the user HTML for a given snippet/partial
	 *
	 * @since 3.0.0
	 * @param    string    $name    The name of the user option to display
	 */
	public function display_html ( $name, $option_type ) {

		if ( $option_type === 'user' ) {
			$options = $this->get_user_options();
			$option_label = $this->name();
		}
		else {
			$options = $this->get_site_options();
		}
		require_once plugin_dir_path( dirname( __FILE__ ) ) . "admin/partials/options-${name}.php";
	}
	
	/**
	 * Filter function to return the required capabilities for editing PTE
	 * options.
	 *
	 * @return required capability to modify PTE options (e.g. 'edit_posts')
	 */
	public function edit_options_cap ( $capability ) {
		return 'edit_posts';
	}
	
	/**
	 * Overload methods
	 *
	 * @since 3.0.0
	 * @param string   $name         The method name
	 * @param mixed    $arguments    The arguments that the method was called
	 *                               with
	 */
	public function __call ($name, $arguments) {

		if ( preg_match( '/^display_(user|site)_(.*?)_html/', $name, $matches ) ) {
			return $this->display_html( $matches[2], $matches[1] );
		}
		else if ( preg_match ( '/^site_options_html$/', $name ) ) {
			_e( "These site-wide settings can only be changed by an administrator", 'post-thumbnail-editor' ); 
			return;
		}

		throw new BadMethodCallException( $name );

	}
	
}
