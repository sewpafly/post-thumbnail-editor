<?php

/**
 * The file that defines the core plugin class
 *
 * A class definition that includes attributes and functions used across both the
 * public-facing side of the site and the admin area.
 *
 * @link       http://sewpafly.github.io/post-thumbnail-editor
 * @since      3.0.0
 *
 * @package    Post_Thumbnail_Editor
 * @subpackage Post_Thumbnail_Editor/includes
 */

/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 *
 * @since      3.0.0
 * @package    Post_Thumbnail_Editor
 * @subpackage Post_Thumbnail_Editor/includes
 */
class Post_Thumbnail_Editor {

	/**
	 * The loader that's responsible for maintaining and registering all hooks that power
	 * the plugin.
	 *
	 * @since    3.0.0
	 * @access   protected
	 * @var      PTE_Loader    $loader    Maintains and registers all hooks for the plugin.
	 */
	protected $loader;

	/**
	 * The unique identifier of this plugin.
	 *
	 * @since    3.0.0
	 * @access   protected
	 * @var      string    $plugin_name    The string used to uniquely identify this plugin.
	 */
	protected $plugin_name;

	/**
	 * The current version of the plugin.
	 *
	 * @since    3.0.0
	 * @access   protected
	 * @var      string    $version    The current version of the plugin.
	 */
	protected $version;

	/**
	 * Define the core functionality of the plugin.
	 *
	 * Set the plugin name and the plugin version that can be used throughout the plugin.
	 * Load the dependencies, define the locale, and set the hooks for the admin area and
	 * the public-facing side of the site.
	 *
	 * @since    3.0.0
	 */
	public function __construct() {

		$this->plugin_name = 'post-thumbnail-editor';
		$this->version = '3.0.0';

		$this->load_dependencies();
		$this->set_locale();
		$this->define_admin_hooks();
		$this->define_service_hooks();

	}

	/**
	 * Load the required dependencies for this plugin.
	 *
	 * Include the following files that make up the plugin:
	 *
	 * - PTE_Loader. Orchestrates the hooks of the plugin.
	 * - PTE_i18n. Defines internationalization functionality.
	 * - PTE_Admin. Defines all hooks for the admin area.
	 * - PTE_Service. Defines all hooks for the admin area.
	 *
	 * Create an instance of the loader which will be used to register the hooks
	 * with WordPress.
	 *
	 * @since    3.0.0
	 * @access   private
	 */
	private function load_dependencies() {

		/**
		 * The class responsible for orchestrating the actions and filters of the
		 * core plugin.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-pte-loader.php';

		/**
		 * The class responsible for defining internationalization functionality
		 * of the plugin.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-pte-i18n.php';

		/**
		 * The class responsible for defining shortcuts to api/options/client
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-pte.php';

		/**
		 * The class responsible for defining all actions that occur in the admin area.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-pte-admin.php';

		/**
		 * The class responsible for defining all actions that occur in the service.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'service/class-pte-service.php';

		$this->loader = new PTE_Loader();

	}

	/**
	 * Define the locale for this plugin for internationalization.
	 *
	 * Uses the Plugin_Name_i18n class in order to set the domain and to register the hook
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function set_locale() {

		$plugin_i18n = new PTE_i18n();
		$plugin_i18n->set_domain( $this->get_plugin_name() );

		$this->loader->add_action( 'plugins_loaded', $plugin_i18n, 'load_plugin_textdomain' );

	}

	/**
	 * Register all of the hooks related to the admin area functionality
	 * of the plugin.
	 *
	 * @since    3.0.0
	 * @access   private
	 */
	private function define_admin_hooks() {

		$plugin_admin = new PTE_Admin( $this->get_plugin_name(), $this->get_version() );

		$this->loader->add_filter( 'media_row_actions', $plugin_admin, 'media_row_actions', 10, 3 );

        // Upload.php (the media library page) fires:
        // - 'load-upload.php' (wp-admin/admin.php)
        // - GRID VIEW:
        //   + 'wp_enqueue_media' (upload.php:wp-includes/media.php:wp_enqueue_media) 
        // - LIST VIEW:
        //   + 'media_row_actions' (filter)(class-wp-media-list-table.php)

		$this->loader->add_action( 'load-upload.php', $plugin_admin, 'add_media_library_load_action' );

	}

	/**
	 * Register all of the hooks related to the service area functionality
	 * of the plugin.
	 *
	 * @since    3.0.0
	 * @access   private
	 */
	private function define_service_hooks() {

		$services = new PTE_Service( $this->get_plugin_name(), $this->get_version() );

		$this->loader->add_action( 'wp_ajax_pte_api', $services, 'api_handler' );

	}

	/**
	 * Run the loader to execute all of the hooks with WordPress.
	 *
	 * @since    3.0.0
	 */
	public function run() {
		$this->loader->run();
	}

	/**
	 * The name of the plugin used to uniquely identify it within the context of
	 * WordPress and to define internationalization functionality.
	 *
	 * @since     3.0.0
	 * @return    string    The name of the plugin.
	 */
	public function get_plugin_name() {
		return $this->plugin_name;
	}

	/**
	 * The reference to the class that orchestrates the hooks with the plugin.
	 *
	 * @since     3.0.0
	 * @return    Plugin_Name_Loader    Orchestrates the hooks of the plugin.
	 */
	public function get_loader() {
		return $this->loader;
	}

	/**
	 * Retrieve the version number of the plugin.
	 *
	 * @since     3.0.0
	 * @return    string    The version number of the plugin.
	 */
	public function get_version() {
		return $this->version;
	}

}
