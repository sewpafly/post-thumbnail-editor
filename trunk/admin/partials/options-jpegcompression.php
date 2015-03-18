<span><input class="small-text" type="text" 
		 name="pte-site-options[pte_jpeg_compression]" 
		 value="<?php if ( isset( $options['pte_jpeg_compression'] ) ){ print $options['pte_jpeg_compression']; }?>" 
		 id="pte_jpeg_compression">&nbsp; 
<?php _e("Set the compression level for resizing jpeg images (0 to 100).", 'post-thumbnail-editor'); ?>
<br/><em><?php _e("No entry defaults to using the 'jpeg_quality' filter or 90", 'post-thumbnail-editor'); ?></em>
</span>
