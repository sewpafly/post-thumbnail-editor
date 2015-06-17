<?php
/*
Plugin name: Post Thumbnail Editor
Plugin URI: http://sewpafly.github.io/post-thumbnail-editor/
Author: sewpafly
Author URI: http://sewpafly.github.io/post-thumbnail-editor/
Version: 2.4.6
Description: Individually manage your post thumbnails

LICENSE
=======

Copyright 2013  (email : sewpafly@gmail.com)

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA

*/

/* 
 * Useful constants  
 */
define( 'PTE_PLUGINURL', plugins_url(basename( dirname(__FILE__))) . "/");
define( 'PTE_PLUGINPATH', dirname(__FILE__) . "/");
define( 'PTE_DOMAIN', "post-thumbnail-editor");
define( 'PTE_VERSION', "2.4.6");

// TODO:
// * Find the best place for the require log (only when it's really needed, create an init function?)
// * Change all the log calls?
// * Rip out everything that's not a CONSTANT or a hook in here
// * Make this an object
// * Add a tour for new users
require_once( PTE_PLUGINPATH . 'php/log.php' );

/*
 * Option Functionality
 */
function pte_get_option_name(){
	global $current_user;
	if ( ! isset( $current_user ) ){
		get_currentuserinfo();
	}
	return "pte-option-{$current_user->ID}";
}

function pte_get_user_options(){
	$pte_options = get_option( pte_get_option_name() );
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

	// WORDPRESS DEBUG overrides user setting...
	return array_merge( $defaults, $pte_options );
}

function pte_get_site_options(){
	$pte_site_options = get_option( 'pte-site-options' );
	if ( !is_array( $pte_site_options ) ){
		$pte_site_options = array();
	}
	$defaults = array( 'pte_hidden_sizes' => array()
		, 'cache_buster' => true
	);
	return array_merge( $defaults, $pte_site_options );
}

function pte_get_options(){
	global $pte_options, $current_user;
	if ( isset( $pte_options ) ){
		return $pte_options;
	}

	$pte_options = array_merge( pte_get_user_options(), pte_get_site_options() );

	if ( WP_DEBUG )
		$pte_options['pte_debug'] = true;

	if ( !isset( $pte_options['pte_jpeg_compression'] ) ){
		$pte_options['pte_jpeg_compression'] = apply_filters( 'jpeg_quality', 90, 'pte_options' );
	}

	return $pte_options;
}

function pte_update_user_options(){
	require_once( PTE_PLUGINPATH . 'php/options.php' );
	$options = pte_get_user_options();

	// Check nonce
	if ( !check_ajax_referer( "pte-options", 'pte-nonce', false ) ){
		return pte_json_error( "CSRF Check failed" );
	}

	if ( isset( $_REQUEST['pte_crop_save'] ) ) {
		if ( strtolower( $_REQUEST['pte_crop_save'] ) === "true" )
			$options['pte_crop_save'] = true;
		else
			$options['pte_crop_save'] = false;
	}

	if ( isset( $_REQUEST['pte_thumbnail_bar'] ) ) {
		if ( strtolower( $_REQUEST['pte_thumbnail_bar'] ) == 'vertical' )
			$options['pte_thumbnail_bar'] = 'vertical';
		else
			$options['pte_thumbnail_bar'] = 'horizontal';
	}

	update_option( pte_get_option_name(), $options );
}



/**
 * Get the URL for the PTE interface
 *
 * @param $id the post id of the attachment to modify
 */
function pte_url( $id, $iframe=false ){
	if ($iframe) {
		$pte_url = admin_url( 'admin-ajax.php' )
			. "?action=pte_ajax&pte-action=iframe&pte-id={$id}"
			. "&TB_iframe=true";
	}
	else {
		$pte_url = admin_url('upload.php') 
			. "?page=pte-edit&pte-id={$id}";
	}

	return $pte_url;
}


/**
 * Used in functions.php, log.php & options.php to get pseudo-TMP file paths
 */
function pte_tmp_dir()
{
	$uploads 	    = wp_upload_dir();
	$PTE_TMP_DIR    = $uploads['basedir'] . DIRECTORY_SEPARATOR . "ptetmp" . DIRECTORY_SEPARATOR;
	$PTE_TMP_URL    = $uploads['baseurl'] . "/ptetmp/";
	return compact( 'PTE_TMP_DIR', 'PTE_TMP_URL' );
}


/*
 * Put Hooks and immediate hook functions in this file
 */

/** For the "Edit Image" stuff **/
/* Hook into the Edit Image page */
add_action('dbx_post_advanced', 'pte_edit_form_hook_redirect');
/* Slight redirect so this isn't called on all versions of the media upload page */
function pte_edit_form_hook_redirect(){
	add_action('add_meta_boxes', 'pte_admin_media_scripts');
}

add_action( 'media_upload_library', 'pte_admin_media_scripts_editor' );
add_action( 'media_upload_gallery', 'pte_admin_media_scripts_editor' );
add_action( 'media_upload_image', 'pte_admin_media_scripts_editor' );
function pte_admin_media_scripts_editor(){
	pte_admin_media_scripts('attachment');
}

function pte_admin_media_scripts($post_type){
	$options = pte_get_options();
	pte_add_thickbox();
	if ($post_type == "attachment") {
		wp_enqueue_script( 'pte'
			, PTE_PLUGINURL . 'apps/coffee-script.js'
			, array('underscore')
			, PTE_VERSION
		);
		add_action( 'admin_print_footer_scripts', 'pte_enable_editor_js', 100);
	}
	else {
		//add_action( 'admin_print_footer_scripts', 'pte_enable_media_js', 100);
		wp_enqueue_script( 'pte'
			, PTE_PLUGINURL . 'js/snippets/pte_enable_media.js'
			, array('media-views')
			, PTE_VERSION
			, true
		);
		wp_enqueue_style( 'pte'
			, PTE_PLUGINURL . 'css/pte-media.css'
			, NULL
			, PTE_VERSION
		);
	}
	wp_localize_script('pte'
		, 'pteL10n'
		, array('PTE' => __('Post Thumbnail Editor', PTE_DOMAIN)
			, 'url' => pte_url( "<%= id %>", true )
			, 'fallbackUrl' => pte_url( "<%= id %>" )
		)
	);
}

function pte_enable_editor_js(){
	injectCoffeeScript( PTE_PLUGINPATH . "js/snippets/editor.coffee" );
}

function pte_enable_media_js(){
	injectCoffeeScript( PTE_PLUGINPATH . "js/snippets/media.coffee" );
}

function injectCoffeeScript($coffeeFile){
	$coffee = @file_get_contents( $coffeeFile );
	//$options = json_encode( pte_get_options() );
	echo <<<EOT
<script type="text/coffeescript">
$coffee
</script>
EOT;
}



// Add the PTE link to the featured image in the post screen
// Called in wp-admin/includes/post.php
add_filter( 'admin_post_thumbnail_html', 'pte_admin_post_thumbnail_html', 10, 2 );

function pte_admin_post_thumbnail_html( $content, $post_id ){
	pte_add_thickbox();
	$thumbnail_id = get_post_thumbnail_id( $post_id );
	if ( $thumbnail_id == null )
		return $content;

	return $content .= '<p id="pte-link" class="hide-if-no-js"><a class="thickbox" href="' 
		. pte_url( $thumbnail_id, true )
		. '">' 
		. esc_html__( 'Post Thumbnail Editor', PTE_DOMAIN ) 
		. '</a></p>';
}

/* Fix wordpress ridiculousness about making a thickbox max width=720 */
function pte_add_thickbox() {
	add_thickbox();
	wp_enqueue_script('pte-fix-thickbox',
		PTE_PLUGINURL . "js/snippets/pte-fix-thickbox.js",
		array( 'media-upload' ),
		PTE_VERSION
	);
}


/* For all purpose needs */
add_action('wp_ajax_pte_ajax', 'pte_ajax');
function pte_ajax(){
	// Move all adjuntant functions to a separate file and include that here
	require_once(PTE_PLUGINPATH . 'php/functions.php');
	PteLogger::debug( "PARAMETERS: " . print_r( $_REQUEST, true ) );

	switch ($_GET['pte-action'])
	{
	case "iframe":
		pte_init_iframe();
		break;
	case "resize-images":
		pte_resize_images();
		break;
	case "confirm-images":
		pte_confirm_images();
		break;
	case "delete-images":
		pte_delete_images();
		break;
	case "get-thumbnail-info":
		$id = (int) $_GET['id'];
		if ( pte_check_id( $id ) )
			print( json_encode( pte_get_all_alternate_size_information( $id ) ) );
		break;
	case "change-options":
		pte_update_user_options();
		break;
	}
	die();
}

/**
 * Perform the capability check
 *
 * @param $id References the post that the user needs to have permission to edit
 * @returns boolean true if the current user has permission else false
 */
function pte_check_id( $id ) {
	if ( !$post = get_post( $id ) ) {
		return false;
	}
	if ( current_user_can( 'edit_post', $id )
		|| current_user_can( 'pte-edit', $id ) )
   	{
		return apply_filters( 'pte-capability-check', true, $id );
	}
	return apply_filters( 'pte-capability-check', false, $id );
}

/** 
 * Upload.php (the media library page) fires:
 * - 'load-upload.php' (wp-admin/admin.php)
 * - GRID VIEW:
 *   + 'wp_enqueue_media' (upload.php:wp-includes/media.php:wp_enqueue_media) 
 * - LIST VIEW:
 *   + 'media_row_actions' (filter)(class-wp-media-list-table.php)
 */
add_action('load-upload.php', 'pte_media_library_boot');
function pte_media_library_boot() { 
    add_action('wp_enqueue_media', 'pte_load_media_library');
}
function pte_load_media_library() {
    global $mode; 
    if ('grid' !== $mode) return;
	wp_enqueue_script( 'pte'
		, PTE_PLUGINURL . 'js/snippets/pte_enable_media.js'
		, null
		, PTE_VERSION
		, true
	);
	wp_localize_script('pte'
		, 'pteL10n'
		, array('PTE' => __('Post Thumbnail Editor', PTE_DOMAIN)
			, 'url' => pte_url( "<%= id %>", true )
			, 'fallbackUrl' => pte_url( "<%= id %>" )
		)
	);
}

/* Adds the Thumbnail option to the media library list */
add_filter('media_row_actions', 'pte_media_row_actions', 10, 3); // priority: 10, args: 3

function pte_media_row_actions($actions, $post, $detached){
	// Add capability check
	if ( !pte_check_id( $post->ID ) ) {
		return $actions;
	}
	$options = pte_get_options();

	$pte_url = pte_url( $post->ID );

	$actions['pte'] = "<a href='${pte_url}' title='"
		. __( 'Edit Thumbnails', PTE_DOMAIN )
		. "'>" . __( 'Thumbnails', PTE_DOMAIN ) . "</a>";
	return $actions;
}


/* Add Settings Page */
add_action( 'load-settings_page_pte', 'pte_options' );
/* Add Settings Page -> Submit/Update options */
add_action( 'load-options.php', 'pte_options' );
function pte_options(){
	require_once( PTE_PLUGINPATH . 'php/options.php' );
	pte_options_init();
}

/* Add SubMenus/Pages */
add_action( 'admin_menu', 'pte_admin_menu' );

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
 */
function pte_admin_menu(){
	add_options_page( __('Post Thumbnail Editor', PTE_DOMAIN),
		__('Post Thumbnail Editor', PTE_DOMAIN),
		'edit_posts',
		'pte',
		'pte_launch_options_page'
	);
	// The submenu page function does not put a menu item in the wordpress sidebar.
	add_submenu_page(NULL, __('Post Thumbnail Editor', PTE_DOMAIN),
		__('Post Thumbnail Editor', PTE_DOMAIN),
		'edit_posts',
		'pte-edit',
		'pte_edit_page'
	);
}

function pte_launch_options_page(){
	require_once( PTE_PLUGINPATH . 'php/options.php' );
	pte_options_page();
}

/**
 * This runs after headers have been sent, see the pte_edit_setup for the
 * function that runs before anything is sent to the browser
 */
function pte_edit_page(){
	// This is set via the pte_edit_setup function
	global $pte_body;
	echo( $pte_body );
}

/* Admin Edit Page: setup*/
/**
 * This hook (load-media_page_pte-edit)
 *    depends on which page you use in the admin section
 * (load-media_page_pte-edit) : wp-admin/upload.php?page=pte-edit
 * (dashboard_page_pte-edit)  : wp-admin/?page=pte-edit
 * (posts_page_pte-edit)      : wp-admin/edit.php?page=pte-edit
 */
add_action( 'load-media_page_pte-edit', 'pte_edit_setup' );
function pte_edit_setup() {
	global $post, $title, $pte_body;
	$post_id = (int) $_GET['pte-id'];
	if ( !isset( $post_id ) 
			|| !is_int( $post_id )
			|| !wp_attachment_is_image( $post_id )
			|| !pte_check_id( $post_id ) ) {
		//die("POST: $post_id IS_INT:" . is_int( $post_id ) . " ATTACHMENT: " . wp_attachment_is_image( $post_id ));
		wp_redirect( admin_url( "upload.php" ) );
		exit();
	}
	$post = get_post( $post_id );
	$title = __( "Post Thumbnail Editor", PTE_DOMAIN );

	include_once( PTE_PLUGINPATH . "php/functions.php" );

	// Add the scripts and styles
	wp_enqueue_script( 'jquery' );
	wp_enqueue_script( 'jquery-ui-dialog' );
	wp_enqueue_script( 'iris' );
	wp_enqueue_style( 'colors' );
	wp_enqueue_style( 'wp-jquery-ui-dialog' );

	$pte_body = pte_body( $post->ID );
}

/**
 * This code creates the image used for the crop
 *
 * By overwriting the wordpress code (same functions), we can change the default size
 * to our own option.
 */
add_action('wp_ajax_pte_imgedit_preview','pte_wp_ajax_imgedit_preview_wrapper');
function pte_wp_ajax_imgedit_preview_wrapper(){
	require_once( PTE_PLUGINPATH . "php/overwrite_imgedit_preview.php" );
	pte_wp_ajax_imgedit_preview();
}


/** End Settings Hooks **/

load_plugin_textdomain( PTE_DOMAIN
	, false
	, basename( PTE_PLUGINPATH ) . DIRECTORY_SEPARATOR . "i18n" );

