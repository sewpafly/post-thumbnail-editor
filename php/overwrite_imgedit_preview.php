<?php

/**
 * Kind of lame all I really need to do is change the ratio number, but in order to 
 * do that I have to overwrite 3 functions
 */



/**
 * From wp-admin/admin-ajax.php
 */
function pte_wp_ajax_imgedit_preview() {
	$post_id = intval($_GET['postid']);
	if ( empty( $post_id ) || !pte_check_id( $post_id ) )
		wp_die( -1 );

	check_ajax_referer( "image_editor-$post_id" );

	include_once( ABSPATH . 'wp-admin/includes/image-edit.php' );
	if ( ! pte_stream_preview_image($post_id) )
		wp_die( -1 );

	wp_die();
}

/**
 * From wp-admin/includes/image-edit.php
 */
function pte_stream_preview_image( $post_id ) {
	$post = get_post( $post_id );
	@ini_set( 'memory_limit', apply_filters( 'admin_memory_limit', WP_MAX_MEMORY_LIMIT ) );

	$img = wp_get_image_editor( _load_image_to_edit_path( $post_id ) );

	if ( is_wp_error( $img ) )
		return false;

	// scale the image
	$size = $img->get_size();
	$w = $size['width'];
	$h = $size['height'];

	$ratio = pte_image_get_preview_ratio( $w, $h );
	$w2 = $w * $ratio;
	$h2 = $h * $ratio;

	if ( is_wp_error( $img->resize( $w2, $h2 ) ) )
		return false;

	return wp_stream_image( $img, $post->post_mime_type, $post_id );
}

/**
 * From wp-admin/includes/image-edit.php
 */
function pte_image_get_preview_ratio($w, $h) {
	$options = pte_get_options();
	$img_max_size = $options['pte_imgedit_max_size'];
	$max = max($w, $h);
	return $max > $img_max_size ? ($img_max_size / $max) : 1;
}
