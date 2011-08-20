<?php

// Anonymous Functions that can't be anonymous thanks to
// some versions of PHP
function pte_noop(){}
function pte_edit_posts_cap( $capability ){ return 'edit_posts'; }
function pte_site_options_html(){ echo "These site-wide settings can only be changed by an administrator"; }

//http://ottopress.com/2009/wordpress-settings-api-tutorial/
function pte_options_init(){
	add_filter( 'option_page_capability_pte_options', 'pte_edit_posts_cap' );
	register_setting( 'pte_options', 
		pte_get_option_name(),
		'pte_options_validate' );

	add_settings_section( 'pte_main'
		, __('User Options')
		, 'pte_noop'
		, 'pte' );
	
	add_settings_field( 'pte_dimensions', 
		__('Thickbox dimensions'), 
		'pte_dimensions_display', 
		'pte', 
		'pte_main' );

	add_settings_field( 'pte_debug', 
		__('Debug'), 
		'pte_debug_display', 
		'pte', 
		'pte_main' );

	add_settings_field( 'pte_reset', 
		__('Reset to defaults'), 
		'pte_reset_display', 
		'pte', 
		'pte_main' );

	// Only show for admins...//
	if ( current_user_can( 'manage_options' ) ){
		register_setting( 'pte_options', 
			'pte-site-options',
			'pte_site_options_validate' );
		add_settings_section( 'pte_site'
			, __('Site Options')
			, 'pte_site_options_html'
			, 'pte' );
		add_settings_field( 'pte_sizes', 
			__('Thumbnails'), 
			'pte_sizes_display', 
			'pte', 
			'pte_site' );
	}
	// End Admin only

}

function pte_options_page(){
	/*<code><pre><?php print_r( pte_get_options() ); ?></pre></code>*/
	?>
	<div class="wrap">
		<h2><?php _e('Post Thumbnail Editor'); ?></h2>
		<form action="options.php" method="post">
			<?php settings_fields('pte_options'); ?>
			<?php do_settings_sections('pte'); ?>
			<p class="submit">
				<input class="button-primary" 
					name="Submit" 
					type="submit" 
					value="<?php esc_attr_e('Save Changes'); ?>" />
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
			, "Only users with the 'manage_options' capability may make changes to these settings." );
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


	$output = array( 'pte_hidden_sizes' => $pte_hidden_sizes );
	return $output;
}

function pte_options_validate( $input ){
	$options = pte_get_user_options();

	if ( isset( $input['reset'] ) ){
		return array();
	}
	$options['pte_debug'] = isset( $input['pte_debug'] );

	$tmp_width = (int) preg_replace( "/[\D]/", "", $input['pte_tb_width'] );
	if ( !is_int( $tmp_width ) || $tmp_width < 750 ){
		add_settings_error('pte_options', 'pte_options_error', "Thickbox width must be at least 750 pixels.");
	}
	else {
		$options['pte_tb_width'] = $tmp_width;
	}

	$tmp_height = (int) preg_replace( "/[\D]/", "", $input['pte_tb_height'] );
	if ( !is_int( $tmp_height ) || $tmp_height < 550 ){
		add_settings_error('pte_options', 'pte_options_error', "Thickbox height must be greater than 550 pixels.");
	}
	else {
		$options['pte_tb_height'] = $tmp_height;
	}

	return $options;
}

function pte_dimensions_display(){
	$options = pte_get_options();
	$option_label = pte_get_option_name();

	?>
	<label for="pte_tb_width">Width:</label><br/>
	<span><input class="small-text" type="text" name="<?php
		print $option_label;
	?>[pte_tb_width]" value="<?php print $options['pte_tb_width']; ?>" id="pte_tb_width">&nbsp; 
	<?php _e("Set this to a value greater than 750."); ?>
	</span>

	<br/>

	<span>
	<label for="pte_tb_height">Height:</label><br/><input class="small-text" type="text" name="<?php
		print $option_label; 
	?>[pte_tb_height]" value="<?php print $options['pte_tb_height']; ?>" id="pte_tb_height">&nbsp;
	<?php _e("Set this to a value greater than 550.");
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
	?> id="pte_debug"/>&nbsp;<label for="pte_debug">Enable debug</label>
	</span>
	<?php
}

function pte_reset_display(){
	?>
				<input class="button-secondary" name="<?php 
					echo( pte_get_option_name() ); 
				?>[reset]" type="submit" value="<?php esc_attr_e('Reset User Options'); ?>" />
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
	<table><tr><th><?php _e("Post Thumbnail"); ?></th>
		<th><?php _e( "Hidden" ); ?></th>
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
?>
