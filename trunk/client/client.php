<!DOCTYPE HTML>
<html>
	<head>
		<title><?php print __('Post Thumbnail Editor Client', 'post-thumbnail-editor');?></title>

		<!-- STYLES -->
<?php do_action('pte_client_print_styles'); ?>

		<!-- SCRIPTS -->
<?php do_action('pte_client_print_head_scripts'); ?>

	</head>
<body>

	<pte-app></pte-app>

<?php do_action('pte_client_print_footer_scripts'); ?>

</body>
</html>
