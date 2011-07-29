<?php

//http://ottopress.com/2009/wordpress-settings-api-tutorial/
function pte_options_init(){
	register_setting( 'pte_options', 
		pte_get_option_name(),
		'pte_options_validate' );

	add_settings_section( 'pte_main', __('Options'), '', 'pte' );
	
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
}

function pte_options_page(){
	?>
	<div class="wrap">
		<h2><?php _e('Post Thumbnail Editor'); ?></h2>
		<form action="options.php" method="post">
			<?php settings_fields('pte_options'); ?>
			<?php do_settings_sections('pte'); ?>
			<p class="submit">
				<input class="button-primary" name="Submit" type="submit" value="<?php esc_attr_e('Save Changes'); ?>" />
				<input class="button-secondary" name="<?php 
					echo( pte_get_option_name() ); 
				?>[reset]" type="submit" value="<?php esc_attr_e('Reset to Defaults'); ?>" />
			</p>
		</form>
	</div>
	<?php
}

/*********** Internal to options **************************************/

function pte_options_validate( $input ){
	$option_name = pte_get_option_name();
	$options = pte_get_options();
	//add_settings_error();

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
	<input type="checkbox" name="<?php
		print $option_label
	?>[pte_debug]" <?php 
		if ( $options['pte_debug'] ): print "checked"; endif; 
	?> id="pte_debug"/>&nbsp;<label for="pte_debug">Enable debug</label>
	<?php
}
?>
