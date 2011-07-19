<?php
/* Plugin name: Post Thumbnail Editor
   Plugin URI: http://wordpress.org/extend/plugins/post-thumbnail-editor/
   Author: sewpafly
   Author URI: http://sewpafly.github.com/post-thumbnail-editor
   Version: 1.0.0
   Description: Individually manage your post thumbnails

    LICENSE
	 =======

    Copyright 2011  (email : sewpafly@gmail.com)

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

	 CREDITS
	 =======
 */

/* 
 * Useful constants  
 */
define( 'PTE_PLUGINURL', plugins_url(basename( dirname(__FILE__))) . "/");
define( 'PTE_PLUGINPATH', dirname(__FILE__) . "/");
define( 'PTE_VERSION', "1.0.0");
define( 'PTE_TB_WIDTH', 750 ); // Only edit this if you feel like living dangerously... Thar be dragons...
define( 'PTE_TB_HEIGHT', 550 ); // This could go larger but I wouldn't go much smaller
										  // Pictures take up 400px with the header and potential error space
										  // 600 seems the best safe number. 
										  // Difficult to say with different screen heights

define( 'PTE_DEBUG', false );  // Eventually add a control panel option for this

/*
 * Put Hooks and immediate hook functions in this file
 */

/* Hook into the Edit Image page */
function pte_enable_thickbox(){
	wp_enqueue_style( 'thickbox' );
	wp_enqueue_script( 'thickbox' );
}

function pte_admin_media_scripts(){
	pte_enable_thickbox();

	if ( PTE_DEBUG ){
		wp_enqueue_script( 'pte'
			, PTE_PLUGINURL . 'js/pte.full.js'
			, array('jquery')
			, PTE_VERSION
		);
	}
	else {
		wp_enqueue_script( 'pte'
			, PTE_PLUGINURL . 'js/pte.full.min.js'
			, array('jquery')
			, PTE_VERSION
		);
	}
	wp_localize_script('pte'
		, 'objectL10n'
		, array('PTE' => __('Post Thumbnail Editor'))
	);
	add_action("admin_head","pte_enable_admin_js",100);
}

function pte_enable_admin_js(){
	$debug = PTE_DEBUG ? "var debug_enabled = true;" : "";
	$pte_tb_width = PTE_TB_WIDTH;
	$pte_tb_height = PTE_TB_HEIGHT;
	echo <<<EOT
		<script type="text/javascript">
			{$debug}
			var pte_tb_width = {$pte_tb_width};
			var pte_tb_height = {$pte_tb_height};
			jQuery( function(){ pte.admin(); } );
		</script>
EOT;
}

function pte_ajax(){
   // Move all adjuntant functions to a separate file and include that here
   require_once(PTE_PLUGINPATH . 'pte_functions.php');

   switch ($_GET['pte-action'])
   {
      case "launch":
			pte_launch();
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
   }
   die(-1);
}

function pte_media_row_actions($actions, $post, $detached){
	if ( !current_user_can( 'edit_post', $post->ID ) ){
		return $actions;
	}
	$pte_url = admin_url('admin-ajax.php') 
		. "?action=pte_ajax&pte-action=launch&id=" 
		. $post->ID
		. "&TB_iframe=true&height=". PTE_TB_HEIGHT ."&width=". PTE_TB_WIDTH;
	$actions['pte'] = "<a class='thickbox' href='${pte_url}' title='Edit Thumbnails'>Thumbnails</a>";
	return $actions;
}

/* This is the main admin media page */
add_action('admin_print_styles-media.php', 'pte_admin_media_scripts');

/* This is for the popup media page */
/* Slight redirect so this isn't called on all versions of the media upload page */
function pte_admin_media_upload(){
	add_action('admin_print_scripts-media-upload-popup', 'pte_admin_media_scripts');
}
add_action('media_upload_gallery', 'pte_admin_media_upload');
add_action('media_upload_library', 'pte_admin_media_upload');

/* Adds the Thumbnail option to the media library list */
add_action('admin_print_styles-upload.php', 'pte_enable_thickbox');
add_filter('media_row_actions', 'pte_media_row_actions', 10, 3); // priority: 10, args: 3

/* For all purpose needs */
add_action('wp_ajax_pte_ajax', 'pte_ajax');

?>
