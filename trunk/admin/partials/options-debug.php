<span><input type="checkbox" name="<?php
print $option_label;
?>[pte_debug]" <?php 
if ( $options['pte_debug'] ): print "checked"; endif; 
?> id="pte_debug"/>&nbsp;<label for="pte_debug"><?php _e( 'Enable debugging', 'post-thumbnail-editor' ); ?></label>
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
	<label for="pte_debug_out_file">LOGGING CURRENTLY UNAVAILABLE<?php /*printf(
		__('Write log output to a <a href="%s">file</a>'),
		PteLogFileHandler::getLogFileUrl()
	); */?></label>
</div>
