<?php

// Anonymous Functions that can't be anonymous thanks to
// some versions of PHP
function pte_noop(){}
function pte_edit_posts_cap( $capability ){ return 'edit_posts'; }
function pte_site_options_html(){ 
	_e( "These site-wide settings can only be changed by an administrator", PTE_DOMAIN ); 
}

//http://ottopress.com/2009/wordpress-settings-api-tutorial/
function pte_options_init(){
	add_filter( 'option_page_capability_pte_options', 'pte_edit_posts_cap' );
	register_setting( 'pte_options', 
		pte_get_option_name(),
		'pte_options_validate' );

	add_settings_section( 'pte_main'
		, __('User Options', PTE_DOMAIN)
		, 'pte_noop'
		, 'pte' );
	
	add_settings_field( 'pte_thickbox', 
		__('Thickbox', PTE_DOMAIN), 
		'pte_thickbox_display', 
		'pte', 
		'pte_main' );

	add_settings_field( 'pte_dimensions', 
		__('Thickbox dimensions', PTE_DOMAIN), 
		'pte_dimensions_display', 
		'pte', 
		'pte_main' );

	add_settings_field( 'pte_debug', 
		__('Debug', PTE_DOMAIN), 
		'pte_debug_display', 
		'pte', 
		'pte_main' );

	add_settings_field( 'pte_reset', 
		__('Reset to defaults', PTE_DOMAIN), 
		'pte_reset_display', 
		'pte', 
		'pte_main' );

	// Only show for admins...//
	if ( current_user_can( 'manage_options' ) ){
		register_setting( 'pte_options', 
			'pte-site-options',
			'pte_site_options_validate' );
		add_settings_section( 'pte_site'
			, __('Site Options', PTE_DOMAIN)
			, 'pte_site_options_html'
			, 'pte' );
		add_settings_field( 'pte_sizes', 
			__('Thumbnails', PTE_DOMAIN), 
			'pte_sizes_display', 
			'pte', 
			'pte_site' );
		add_settings_field( 'pte_jpeg_compression', 
			__('JPEG Compression', PTE_DOMAIN), 
			'pte_jpeg_compression_display', 
			'pte', 
			'pte_site' );
	}
	// End Admin only

}

function pte_options_page(){
	/*<code><pre><?php print_r( pte_get_options() ); ?></pre></code>*/
	?>
	<div class="wrap">
		<h2><?php _e('Post Thumbnail Editor', PTE_DOMAIN); ?></h2>
		<form action="options.php" method="post">
			<?php settings_fields('pte_options'); ?>
			<?php do_settings_sections('pte'); ?>
			<p class="submit">
				<input class="button-primary" 
					name="Submit" 
					type="submit" 
					value="<?php esc_attr_e('Save Changes', PTE_DOMAIN); ?>" />
			</p>
		</form>
	</div>
	<?php
}

/*********** Internal to options **************************************/

function pte_site_options_validate( $input ){
	//$sizes = pte_get_alternate_sizes(false);
	if ( !current_user_can( 'manage_options' ) ){
		add_settings_error('pte_options_site'
			, 'pte_options_error'
			, __( "Only users with the 'manage_options' capability may make changes to these settings.", PTE_DOMAIN ) );
		return pte_get_site_options();
	}
	$sizes = get_intermediate_image_sizes();
	
	$pte_hidden_sizes = array();

	foreach ( $sizes as $size ){
		// Hidden
		if ( is_array( $input['pte_hidden_sizes'] ) 
				and in_array( $size, $input['pte_hidden_sizes'] ) ){
			$pte_hidden_sizes[] = $size;
		}
	}

	// Check the JPEG Compression value
	$tmp_jpeg_compression = (int) preg_replace( "/[\D]/", "", $input['pte_jpeg_compression'] );
	if ( ! is_int( $tmp_jpeg_compression ) 
		|| $tmp_jpeg_compression < 0 
		|| $tmp_jpeg_compression > 100 )
	{
		add_settings_error('pte_options_site'
			, 'pte_options_error'
			, __( "JPEG Compression needs to be set from 0 to 100.", PTE_DOMAIN ) );
	}

	$output = array( 'pte_hidden_sizes' => $pte_hidden_sizes
		, 'pte_jpeg_compression' => $tmp_jpeg_compression
  	);
	return $output;
}

function pte_options_validate( $input ){
	$options = pte_get_user_options();

	if ( isset( $input['reset'] ) ){
		return array();
	}
	$options['pte_debug'] = isset( $input['pte_debug'] );
	$options['pte_thickbox'] = isset( $input['pte_thickbox'] );

	$tmp_width = (int) preg_replace( "/[\D]/", "", $input['pte_tb_width'] );
	if ( !is_int( $tmp_width ) || $tmp_width < 750 ){
		add_settings_error('pte_options'
			, 'pte_options_error'
			, __( "Thickbox width must be at least 750 pixels.", PTE_DOMAIN ) );
	}
	else {
		$options['pte_tb_width'] = $tmp_width;
	}

	$tmp_height = (int) preg_replace( "/[\D]/", "", $input['pte_tb_height'] );
	if ( !is_int( $tmp_height ) || $tmp_height < 550 ){
		add_settings_error('pte_options'
			, 'pte_options_error'
			, __( "Thickbox height must be greater than 550 pixels.", PTE_DOMAIN ) );
	}
	else {
		$options['pte_tb_height'] = $tmp_height;
	}

	return $options;
}

function pte_thickbox_display(){
	$options = pte_get_options();
	$option_label = pte_get_option_name();
	?>
	<span><input type="checkbox" name="<?php
		print $option_label
	?>[pte_thickbox]" <?php 
		if ( $options['pte_thickbox'] ): print "checked"; endif; 
	?> id="pte_thickbox"/>&nbsp;<label for="pte_thickbox"><?php _e( 'Enable Thickbox', PTE_DOMAIN ); ?></label>
	</span>
	<?php
}

function pte_dimensions_display(){
	$options = pte_get_options();
	$option_label = pte_get_option_name();

	?>
	<label for="pte_tb_width"><?php _e( 'Width:', PTE_DOMAIN ); ?></label><br/>
	<span><input class="small-text" type="text" name="<?php
		print $option_label;
	?>[pte_tb_width]" value="<?php print $options['pte_tb_width']; ?>" id="pte_tb_width">&nbsp; 
	<?php _e("Set this to a value greater than 750.", PTE_DOMAIN); ?>
	</span>

	<br/>

	<span>
	<label for="pte_tb_height"><?php 
		_e( 'Height:', PTE_DOMAIN ); 
	?></label><br/><input class="small-text" type="text" name="<?php
		print $option_label; 
	?>[pte_tb_height]" value="<?php print $options['pte_tb_height']; ?>" id="pte_tb_height">&nbsp;
	<?php _e("Set this to a value greater than 550.", PTE_DOMAIN);
	print( "</span>" );
}

function pte_debug_display(){
	$options = pte_get_options();
	$option_label = pte_get_option_name();
	?>
	<span><input type="checkbox" name="<?php
		print $option_label
	?>[pte_debug]" <?php 
		if ( $options['pte_debug'] ): print "checked"; endif; 
	?> id="pte_debug"/>&nbsp;<label for="pte_debug"><?php _e( 'Enable debugging', PTE_DOMAIN ); ?></label>
	</span>
	<?php
}

function pte_reset_display(){
	?>
				<input class="button-secondary" name="<?php 
					echo( pte_get_option_name() ); 
				?>[reset]" type="submit" value="<?php esc_attr_e('Reset User Options', PTE_DOMAIN); ?>" />
	<?php
}

function pte_gcd($a, $b){
	if ( $a == 0 ) return b;
	while( $b > 0 ){
		if ( $a > $b ){
			$a = $a - $b;
		}
		else {
			$b = $b - $a;
		}
	}
	if ( $a < 0 or $b < 0 ){
		return null;
	}
	return $a;
}

function pte_sizes_display(){
	require_once( 'functions.php' );
	$options = pte_get_options();

	// Table Header
	?>
	<table><tr><th><?php _e("Post Thumbnail", PTE_DOMAIN); ?></th>
		<th><?php _e( "Hidden", PTE_DOMAIN ); ?></th>
		</tr>
	<?php
	// End table header

	$sizes = pte_get_alternate_sizes(false);

	foreach ( $sizes as $size => $size_data ){
		$hidden = ( in_array( $size, $options['pte_hidden_sizes'] ) ) ?
			"checked":"";

		print( "<tr><td><label for='{$size}'>{$size}</label></td>"
			. "<td><input type='checkbox' id='{$size}' name='pte-site-options[pte_hidden_sizes][]'"
		   . " value='{$size}' {$hidden}></td>"
			. "</tr>"
		);
	}

	print( '</table>' );
}

function pte_jpeg_compression_display(){
	$options = pte_get_options();
	$option_label = pte_get_option_name();
?>
	<span><input class="small-text" type="text" 
			 name="pte-site-options[pte_jpeg_compression]" 
			 value="<?php print $options['pte_jpeg_compression']; ?>" 
          id="pte_jpeg_compression">&nbsp; 
	<?php _e("Set the compression level for resizing jpeg images (0 to 100).", PTE_DOMAIN); ?>
	</span>
	<?php
}
?>
