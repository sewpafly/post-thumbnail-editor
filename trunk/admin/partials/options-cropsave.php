<span><input type="checkbox" name="<?php
print $option_label;
?>[pte_crop_save]" <?php 
if ( $options['pte_crop_save'] ): print "checked"; endif; 
?> id="pte_crop_save"/>&nbsp;<label for="pte_crop_save"><?php _e( 'I know what I\'m doing, bypass the image verification.', 'post-thumbnail-editor' ); ?></label>
	</span>
