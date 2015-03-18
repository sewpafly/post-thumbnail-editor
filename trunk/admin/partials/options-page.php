<style type="text/css" media="screen">
.sub-option {
	margin-left: 30px;
	margin-top: 10px;
	font-size: smaller;
}
</style>
<div class="wrap">
	<h2><?php _e('Post Thumbnail Editor', 'post-thumbnail-editor'); ?></h2>
	<form action="options.php" method="post">
		<?php settings_fields('pte_options'); ?>
		<?php do_settings_sections('pte'); ?>
		<p class="submit">
			<input class="button-primary" 
				name="Submit" 
				type="submit" 
				value="<?php esc_attr_e('Save Changes', 'post-thumbnail-editor'); ?>" />
		</p>
	</form>
</div>
