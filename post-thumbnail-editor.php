<?php
/* Plugin name: Post Thumbnail Editor
   Plugin URI: http://wordpress.org/extend/plugins/post-thumbnail-editor/
   Author: sewpafly
   Author URI: http://sewpafly.github.com/post-thumbnail-editor
   Version: 1.0-alpha
   Description: Individually manage your post thumbnails

    LICENSE

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
define( 'PTE_VERSION', "1.0-alpha");
define( 'PTE_POST_DATA', "pte-data");

/*
 * Put Hooks and immediate hook functions in this file
 */

/* Hook into the Edit Image page */
function pte_admin_media_styles(){
   wp_enqueue_style('fancybox',
      PTE_PLUGINURL . 'apps/fancybox/jquery.fancybox-1.3.4.css');
   wp_enqueue_style( 'pte'
       , PTE_PLUGINURL . 'css/pte.css'
       , false
       , PTE_VERSION
   );
}

function pte_admin_media_scripts(){
	//wp_enqueue_script('imgareaselect');
	//wp_enqueue_script('fancybox',
	//   PTE_PLUGINURL . 'apps/fancybox/jquery.fancybox-1.3.4.min.js',
	//   array('jquery')
	//);
	//wp_enqueue_script( 'pte'
	//    , PTE_PLUGINURL . 'js/pte_admin_media.js'
	//    , array('jquery')
	//    , PTE_VERSION
	//);
	wp_register_script( 'pte-log'
		, PTE_PLUGINURL . 'js/log.js'
		, false
		, PTE_VERSION
	);
	wp_enqueue_script( 'pte'
		, PTE_PLUGINURL . 'js/pte_admin.js'
		//, array('jquery','imgareaselect')
		, array('pte-log')
		, PTE_VERSION
	);
	wp_localize_script('pte'
		, 'objectL10n'
		, array('PTE' => __('Post Thumbnail Editor'))
	);
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
   }
   die(-1);
}

function pte_media_row_actions($actions, $post, $detached){
	$pte_url = admin_url('admin-ajax.php') 
		. "?action=pte_ajax&pte-action=launch&id=" 
		. $post->ID;
	$actions['pte'] = "<a href='${pte_url}' title='Edit Thumbnails'>Thumbnails</a>";
	return $actions;
}

/* This is the main admin media page */
//add_action('admin_print_styles-media.php', 'pte_admin_media_styles');
add_action('admin_print_scripts-media.php', 'pte_admin_media_scripts');

/* This is for the popup media page */
//add_action('admin_print_styles-media-upload-popup', 'pte_admin_media_styles');
add_action('admin_print_scripts-media-upload-popup', 'pte_admin_media_scripts');

/* For all purpose needs */
add_action('wp_ajax_pte_ajax', 'pte_ajax');

add_filter('media_row_actions', 'pte_media_row_actions', 10, 3);

?>
