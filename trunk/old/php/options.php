<?php

//http://ottopress.com/2009/wordpress-settings-api-tutorial/
function pte_options_init(){
	add_filter( 'option_page_capability_pte_options', 'pte_edit_posts_cap' );
	register_setting( 'pte_options'
		, pte_get_option_name()   // Settings are per user
		, 'pte_options_validate' );

	add_settings_section( 'pte_main'
		, __('User Options', PTE_DOMAIN)
		, 'pte_noop'
		, 'pte' );

	add_settings_field( 'pte_debug'
		, __('Debug', PTE_DOMAIN)
		, 'pte_debug_display'
		, 'pte'
		, 'pte_main' );

	add_settings_field( 'pte_crop_save'
		, __('Crop and Save', PTE_DOMAIN)
		, 'pte_crop_save_display'
		, 'pte'
		, 'pte_main' );

	add_settings_field( 'pte_imgedit_max_size'
		, __('Crop Picture Size', PTE_DOMAIN)
		, 'pte_imgedit_size_display'
		, 'pte'
		, 'pte_main' );

	add_settings_field( 'pte_reset'
		, __('Reset to defaults', PTE_DOMAIN)
		, 'pte_reset_display'
		, 'pte'
		, 'pte_main' );

	// Only show for admins...//
	if ( current_user_can( 'manage_options' ) ){
		register_setting( 'pte_options'
			, 'pte-site-options'     // Settings are site-wide
			, 'pte_site_options_validate' );
		add_settings_section( 'pte_site'
			, __('Site Options', PTE_DOMAIN)
			, 'pte_site_options_html'
			, 'pte' );
		add_settings_field( 'pte_sizes'
			, __('Thumbnails', PTE_DOMAIN)
			, 'pte_sizes_display'
			, 'pte'
			, 'pte_site' );
		add_settings_field( 'pte_jpeg_compression'
			, __('JPEG Compression', PTE_DOMAIN)
			, 'pte_jpeg_compression_display'
			, 'pte'
			, 'pte_site' );
		add_settings_field( 'pte_cache_buster'
			, __('Cache Buster', PTE_DOMAIN)
			, 'pte_cache_buster_display'
			, 'pte'
			, 'pte_site' );
	}
	// End Admin only

}

function pte_options_page(){
	/*<code><pre><?php print_r( pte_get_options() ); ?></pre></code>*/
?>
	<style type="text/css" media="screen">
	.sub-option {
		margin-left: 30px;
		margin-top: 10px;
		font-size: smaller;
	}
	</style>
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
		if ( isset($input['pte_hidden_sizes']) && is_array( $input['pte_hidden_sizes'] ) 
			&& in_array( $size, $input['pte_hidden_sizes'] ) ){
				$pte_hidden_sizes[] = $size;
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
				, __( "JPEG Compression needs to be set from 0 to 100.", PTE_DOMAIN ) . $tmp_jpeg_compression . "/" . $input['pte_jpeg_compression']);
		}
		$output['pte_jpeg_compression'] = $tmp_jpeg_compression;
	}

	// Cache Buster
	$output['cache_buster'] = isset( $input['pte_cache_buster'] );

	return $output;
}

function pte_options_validate( $input ){
	$options = pte_get_user_options();

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

function pte_debug_display(){
	$options = pte_get_user_options();
	$option_label = pte_get_option_name();
?>
	<span><input type="checkbox" name="<?php
	print $option_label;
	?>[pte_debug]" <?php 
	if ( $options['pte_debug'] ): print "checked"; endif; 
	?> id="pte_debug"/>&nbsp;<label for="pte_debug"><?php _e( 'Enable debugging', PTE_DOMAIN ); ?></label>
<?php if ( WP_DEBUG ) {
	print( "<br/><em>" );
	_e( "WP_DEBUG is currently set to true and will override this setting. (debug is enabled)" ); 
	print( "</em>" );
}?>
	</span>
	<div class="sub-option"><input type="checkbox" name="<?php echo $option_label; ?>[pte_debug_out_chrome]" <?php
		if ( $options['pte_debug_out_chrome'] ): print "checked"; endif; ?> id="pte_debug_out_chrome"/>
		&nbsp;
		<label for="pte_debug_out_chrome"><?php printf( __('Use <a href="%s">ChromePhp</a> for log output'),
			'https://github.com/ccampbell/chromephp'
		); ?></label>
	</div>
	<div class="sub-option"><input type="checkbox" name="<?php echo $option_label; ?>[pte_debug_out_file]" <?php
		if ( $options['pte_debug_out_file'] ): print "checked"; endif; ?> id="pte_debug_out_file"/>
		&nbsp;
		<label for="pte_debug_out_file"><?php printf(
			__('Write log output to a <a href="%s">file</a>'),
			PteLogFileHandler::getLogFileUrl()
		); ?></label>
	</div>
<?php
}

function pte_crop_save_display(){
	$options = pte_get_user_options();
	$option_label = pte_get_option_name();
?>
	<span><input type="checkbox" name="<?php
	print $option_label;
	?>[pte_crop_save]" <?php 
	if ( $options['pte_crop_save'] ): print "checked"; endif; 
	?> id="pte_crop_save"/>&nbsp;<label for="pte_crop_save"><?php _e( 'I know what I\'m doing, bypass the image verification.', PTE_DOMAIN ); ?></label>
		</span>
<?php
}

function pte_imgedit_size_display(){
	$options = pte_get_user_options();
	$option_label = pte_get_option_name();
?>
	<span><input class="small-text" type="text" 
			name="<?php print $option_label; ?>[pte_imgedit_max_size]" 
			value="<?php if ( isset( $options['pte_imgedit_max_size'] ) ){ print $options['pte_imgedit_max_size']; }?>" 
			id="pte_imgedit_max_size">&nbsp; 
	<?php _e("Set the max size for the crop image.", PTE_DOMAIN); ?>
	<br/><em><?php _e("No entry defaults to 600", PTE_DOMAIN); ?></em>
	</span>
	<div class="sub-option">
	<span><input type="checkbox" 
			name="<?php print $option_label; ?>[pte_imgedit_disk]" 
			<?php if ($options['pte_imgedit_disk'] ): print "checked"; endif; ?>
			id="pte_imgedit_disk">&nbsp;<label for="pte_imgedit_disk"> 
		<?php _e("Check this to save the generated working image to disk instead of creating on the fly (experimental)", PTE_DOMAIN); ?>
	</label>
	</span>
	</div>
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

		$name = isset( $size_data['display_name'] )? $size_data['display_name'] : $size;

		print( "<tr><td><label for='{$size}'>{$name}</label></td>"
			. "<td><input type='checkbox' id='{$size}' name='pte-site-options[pte_hidden_sizes][]'"
			. " value='{$size}' {$hidden}></td>"
			. "</tr>"
		);
	}

	print( '</table>' );
}

function pte_jpeg_compression_display(){
	$options = pte_get_site_options();
?>
	<span><input class="small-text" type="text" 
			 name="pte-site-options[pte_jpeg_compression]" 
			 value="<?php if ( isset( $options['pte_jpeg_compression'] ) ){ print $options['pte_jpeg_compression']; }?>" 
			 id="pte_jpeg_compression">&nbsp; 
	<?php _e("Set the compression level for resizing jpeg images (0 to 100).", PTE_DOMAIN); ?>
	<br/><em><?php _e("No entry defaults to using the 'jpeg_quality' filter or 90", PTE_DOMAIN); ?></em>
	</span>
<?php
}

function pte_cache_buster_display(){
	$options = pte_get_site_options();
?>
	<span><input type="checkbox" name="pte-site-options[pte_cache_buster]" <?php 
	if ( $options['cache_buster'] ): print "checked"; endif; 
?> id="pte_cache_buster"/>&nbsp;
<label for="pte_cache_buster"><?php
	_e( 'Append timestamp to filename. Useful for solving caching problems.', PTE_DOMAIN ); 
?></label>
	</span>
<?php
}

// Anonymous Functions that can't be anonymous thanks to
// some versions of PHP
function pte_noop(){}
function pte_edit_posts_cap( $capability ){ return 'edit_posts'; }
function pte_site_options_html(){ 
	_e( "These site-wide settings can only be changed by an administrator", PTE_DOMAIN ); 
}
