<span><input type="checkbox" name="pte-site-options[pte_cache_buster]" <?php 
if ( $options['cache_buster'] ): print "checked"; endif; 
?> id="pte_cache_buster"/>&nbsp;
<label for="pte_cache_buster"><?php
_e( 'Append timestamp to filename. Useful for solving caching problems.', 'post-thumbnail-editor' ); 
?></label>
</span>
