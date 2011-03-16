<?php
/* Plugin name: Post Thumbnail Editor
   Plugin URI: http://github.com/sewpafly/post-thumbnail-editor
   Author: sewpafly
   Author URI: http://sewpafly.github.com/
   Version: 0.1.1
   Description: Individually manage your post thumbnails
   Max WP Version: 3.1

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

 */

/* 
 * Useful constants  
 */
define( PTE_PLUGINURL, plugins_url(basename( dirname(__FILE__))) . "/");
define( PTE_PLUGINPATH, dirname(__FILE__) . "/");

/*
 * Put Hooks and immediate hook functions in this file
 */

/* Hook into the Edit Image page */
function pte_admin_media_styles(){
   wp_enqueue_style('fancybox',
      PTE_PLUGINURL . 'apps/fancybox/jquery.fancybox-1.3.4.css');
   wp_enqueue_style('pte',
      PTE_PLUGINURL . 'css/pte.css');
}

function pte_admin_media_scripts(){
   wp_enqueue_script('imgareaselect');
   wp_enqueue_script('fancybox',
      PTE_PLUGINURL . 'apps/fancybox/jquery.fancybox-1.3.4.min.js',
      array('jquery')
   );
   wp_enqueue_script('pte',
      PTE_PLUGINURL . 'js/pte_admin_media.js',
      array('jquery')
   );
}

function pte_ajax(){
   // Move all adjuntant functions to a separate file and include that here
   require_once(PTE_PLUGINPATH . 'pte_functions.php');
   switch ($_GET['pte_action'])
   {
      case "get-alternate-sizes":
         pte_get_alternate_sizes();
         break;
      case "get-image-data":
         if ( is_numeric( $_GET['id'] ) ){
            pte_get_image_data($_GET['id'], $_GET['size']);
         }
         break;
      case "resize-img":
         // Check that the parameters are digits
         if ( is_numeric($_GET['id']) &&
            is_numeric($_GET['x']) &&
            is_numeric($_GET['y']) &&
            is_numeric($_GET['w']) &&
            is_numeric($_GET['h'])
         ) {
            check_ajax_referer("pte-{$_GET['id']}-{$_GET['size']}");
            pte_resize_img($_GET['id'], 
               $_GET['size'],
               $_GET['x'],
               $_GET['y'],
               $_GET['w'],
               $_GET['h']
            );
         }
         break;
   }
   die(-1);
}

/* This is the main admin media page */
add_action('admin_print_styles-media.php', 'pte_admin_media_styles');
add_action('admin_print_scripts-media.php', 'pte_admin_media_scripts');

/* This is for the popup media page */
add_action('admin_print_styles-media-upload-popup', 'pte_admin_media_styles');
add_action('admin_print_scripts-media-upload-popup', 'pte_admin_media_scripts');

/* For all purpose needs */
add_action('wp_ajax_pte_ajax', 'pte_ajax');

?>
