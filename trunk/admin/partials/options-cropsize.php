<span><input class="small-text" type="text" 
		name="<?php print $option_label; ?>[pte_imgedit_max_size]" 
		value="<?php if ( isset( $options['pte_imgedit_max_size'] ) ){ print $options['pte_imgedit_max_size']; }?>" 
		id="pte_imgedit_max_size">&nbsp; 
<?php _e("Set the max size for the crop image.", 'post-thumbnail-editor'); ?>
<br/><em><?php _e("No entry defaults to 600", 'post-thumbnail-editor'); ?></em>
</span>
<div class="sub-option">
<span><input type="checkbox" 
		name="<?php print $option_label; ?>[pte_imgedit_disk]" 
		<?php if ($options['pte_imgedit_disk'] ): print "checked"; endif; ?>
		id="pte_imgedit_disk">&nbsp;<label for="pte_imgedit_disk"> 
	<?php _e("Check this to save the generated working image to disk instead of creating on the fly (experimental)", 'post-thumbnail-editor'); ?>
</label>
</span>
</div>
