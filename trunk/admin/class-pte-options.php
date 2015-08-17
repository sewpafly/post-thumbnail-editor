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
	 * The combined options
	 *
	 * @since    3.0.0
	 * @access   private
	 * @var      mixed    $user_options    The cached site_options
	 */
	private $options;

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
	private function get_user_options () {
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
	 *
	 * @return array of site-level options and current configuration
	 */
	private function get_site_options () {
		if ( isset( $this->site_options ) ) {
			return $this->site_options;
		}

		$pte_site_options = get_option( 'pte-site-options' );

		if ( !is_array( $pte_site_options ) ){
			$pte_site_options = array();
		}

		$uploads 	= wp_upload_dir();
		$tmp_dir    = $uploads['basedir'] . DIRECTORY_SEPARATOR . "ptetmp" . DIRECTORY_SEPARATOR;
		$tmp_url    = $uploads['baseurl'] . "/ptetmp/";

		$defaults = array(
			'pte_hidden_sizes' => array(),
			'cache_buster' => true,
			'tmp_dir' => $tmp_dir,
			'tmp_url' => $tmp_url,
		);

		$this->site_options = array_merge( $defaults, $pte_site_options );
		return $this->site_options;

	}

	/**
	 * Get all the options
	 *
	 * @return void
	 */
	private function get_options () {

		if ( ! isset( $this->options ) ) {

			$options = array_merge( $this->get_user_options(), $this->get_site_options() );

			if ( WP_DEBUG )
				$pte_options['pte_debug'] = true;

			$this->options = $options;

		}

		return $this->options;

	}

	/**
	 * Get the option current value
	 *
	 * @since 3.0.0
	 *
	 * @return option value
	 */
	public function get_option ($default, $option_label ) {

		$options = $this->get_options();
		if ( isset( $options[$option_label] ) ) {
			return $options[$option_label];
		}
		return $default;

	}

	/**
	 * Action 'pte_options_load_jpeg_quality' changes what the filters
	 * jpeg_quality and wp_editor_set_quality return.
	 *
	 * @since 3.0.0
	 *
	 * @return void
	 */
	public function load_jpeg () {

		add_filter( 'jpeg_quality', array( $this, 'jpeg_quality' ) );
		add_filter( 'wp_editor_set_quality', array( $this, 'jpeg_quality' ) );

	}

	/**
	 * Set the JPEG Quality
	 *
	 * @since 3.0.0
	 *
	 * @param int  $quality   The quality to return
	 *
	 * @return void
	 */
	public function jpeg_quality ( $default ) {

		return $this->get_option( $default, 'pte_jpeg_compression' );

	}

	/**
	 * Validate user options
	 *
	 * This is called from a hook that's set in `init`. Wordpress handles the
	 * capability checking for this section for us.
	 *
	 * @since   3.0.0
	 * @return  array  options that are saved to database
	 */
	public function validate_user_options ( $input ) {

		$options = $this->get_user_options();

		if ( isset( $input['reset'] ) ){
			return array();
		}
		$checkboxes = array(
			'pte_debug',
			'pte_debug_out_chrome',
			'pte_debug_out_file',
			'pte_crop_save',
			'pte_imgedit_disk',
		);

		foreach ($checkboxes as $opt) {
			if (isset( $input[$opt] ) )
				$options[$opt] = true;
			else if (isset($options[$opt]))
				unset($options[$opt]);
		}

		// Check the imgedit_max_size value
		if ( $input['pte_imgedit_max_size'] != "" ){
			$tmp_size = (int) preg_replace( "/[\D]/", "", $input['pte_imgedit_max_size'] );
			if ( $tmp_size < 0 || $tmp_size > 10000 ) {
				add_settings_error( pte_get_option_name()
					, 'pte_options_error'
					, __( "Crop Size must be between 0 and 10000.", PTE_DOMAIN ) );
			}
			$options['pte_imgedit_max_size'] = $tmp_size;
		}
		else{
			unset( $options['pte_imgedit_max_size'] );
		}

		return $options;

	}

	/**
	 * Validate site options
	 *
	 * This is called from a hook that's set in `init`.  Wordpress won't enforce
	 * dual permissions, so we need to check that this user can set site level
	 * options.
	 *
	 * @since   3.0.0
	 * @return  array  options that are saved to database
	 */
	public function validate_site_options ( $input ) {

		if ( !current_user_can( 'manage_options' ) ){
			add_settings_error('pte_options_site'
				, 'pte_options_error'
				, __( "Only users with the 'manage_options' capability may make changes to these settings.", 'post-thumbnail-editor' ) );
			return $this->get_site_options();
		}

		/**
		 * Get the size information
		 *
		 * Return an array of thumbnail objects describing the size information
		 *
		 * @since 3.0.0
		 * @param  callback   $filter   filter results with this filter callback
		 */
		$sizes = apply_filters( 'pte_api_get_sizes', array() );

		$pte_hidden_sizes = array();

		foreach ( $sizes as $size ){
			// Hidden
			if ( isset($input['pte_hidden_sizes']) && is_array( $input['pte_hidden_sizes'] )
				&& in_array( $size->name, $input['pte_hidden_sizes'] ) ){
				$pte_hidden_sizes[] = $size->name;
			}
		}

		$output = array( 'pte_hidden_sizes' => $pte_hidden_sizes );

		// Check the JPEG Compression value
		if ( isset($input['pte_jpeg_compression']) && $input['pte_jpeg_compression'] != "" ){
			$tmp_jpeg_compression = (int) preg_replace( "/[\D]/", "", $input['pte_jpeg_compression'] );
			if ( ! is_int( $tmp_jpeg_compression )
				|| $tmp_jpeg_compression < 0
				|| $tmp_jpeg_compression > 100 )
			{
				add_settings_error('pte_options_site'
					, 'pte_options_error'
					, sprintf(
						__( "JPEG Compression needs to be set from 0 to 100. (%s %s)", 'post-thumbnail-editor' ),
						$tmp_jpeg_compression,
						$input['pte_jpeg_compression']
					)
				);
			}
			$output['pte_jpeg_compression'] = $tmp_jpeg_compression;
		}

		// Cache Buster
		$output['cache_buster'] = isset( $input['pte_cache_buster'] );

		return $output;

	}


	/**
	 * Shortcut for adding settings
	 *
	 * @since 3.0.0
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
	 * http://ottopress.com/2009/wordpress-settings-api-tutorial/
	 *
	 * @since 3.0.0
	 */
	public function init () {

		add_filter( 'option_page_capability_pte_options', array( $this, 'edit_options_cap' ) );

		register_setting( 'pte_options',
			$this->name(),   // Settings are per user
			array( $this, 'validate_user_options' )
		);

		add_settings_section( 'pte_main'
			, __('User Options', 'post-thumbnail-editor')
			, 'PTE_Options::noop'
			, 'pte' );

		// When you add a setting you create it with the callback in the form of
		// display_user_<function>_html: this will then attempt to load the
		// options-<function>.php file in the partials directory
		$this->add_setting( 'pte_debug', __( 'Debug', 'post-thumbnail-editor' ),
			display_user_debug_html', 'pte_main' );
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
				, array( $this, 'validate_site_options' )
			);

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
	 * Filter function to return the required capabilities for editing PTE
	 * options.
	 *
	 * @return required capability to modify PTE options (e.g. 'edit_posts')
	 */
	public function edit_options_cap ( $capability ) {
		return 'edit_posts';
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

	/**
	 * Static noop
	 *
	 * @since 3.0.0
	 */
	public static function noop () {}

}
