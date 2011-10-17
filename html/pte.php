<!DOCTYPE HTML>
<html>
	<head>
		<meta http-equiv="content-type" content="text/html; charset=utf-8">
		<title>PTE</title>
		<link rel="stylesheet" type="text/css" href="http://fonts.googleapis.com/css?family=Amaranth:regular,italic,bold|Puritan|PT+Serif">
		<style type="text/css" media="screen">
		</style>
		<?php wp_print_styles(); ?>
		<?php wp_print_scripts(); ?>
		<script type="text/javascript" charset="utf-8">
			var ajaxurl        = '<?php echo admin_url( 'admin-ajax.php' ); ?>';
			var thumbnail_info = <?php print( json_encode( $size_information ) ); ?>;
			var options        = <?php print( json_encode( $options ) ); ?>;
			jQuery(function(){ pte.editor(); });
		</script>
	</head>
	<body>
		<div id="stage1" class="stage">
			<h1 class="stage-header"><?php echo( __( 'Post Thumbnail Editor - Step 1', PTE_DOMAIN ) ); ?></h1>
			<?php if ( $options['pte_debug'] ): ?>
			<script type="text/javascript" charset="utf-8">
				var pte_errors = <?php print( json_encode( array( 'log' => $logger->get_logs() ) ) ); ?>;
				log( "===== STARTUP PHP MESSAGES =====");
				pte.parseServerLog(pte_errors.log);
			</script>
			<?php endif; ?>
			<div id="pte-image">
				<input type="hidden" id="pte-sizer" value="<?php echo $sizer; ?>" />
				<input type="hidden" id="pte-post-id" value="<?php echo $id; ?>" />
				<img id="pte-preview" src="<?php echo admin_url('admin-ajax.php'); ?>?action=imgedit-preview&amp;_ajax_nonce=<?php echo $nonce; ?>&amp;postid=<?php echo $id; ?>&amp;rand=<?php echo rand(1, 99999); ?>" />
				<div id="pte-buttons" class="buttons">
					<button disabled id="pte-submit"><?php _e( 'Create Thumbnails', PTE_DOMAIN ); ?></button>
				</div>

			</div>
			<div id="pte-sizes-container">
				<div style="font-size:smaller;">
					<?php _e( 'Choose the images/thumbnails that you want to edit:', PTE_DOMAIN ); ?><br>
				</div>
				<div id="pte-selectors">
					<?php printf( __( 'Select: %1$sAll%2$s | %3$sNone%4$s', PTE_DOMAIN )
						, '<a class="all" href="all">'
						, '</a>'
						, '<a class="none" href="none">'
						, '</a>'
					); ?>
				</div>
				<div id="pte-sizes">
				<table>
					<?php foreach ( $size_information as $size_label => $size_array ): ?>
					<tr>
						<td class="col1">
							<input class="pte-size" id="pte-size-<?php print $size_label; ?>" type="checkbox" name="pte-size[]" value="<?php print $size_label; ?>">
						</td>
						<td class="col2">
								<div class="pte-size-label"><?php print $size_label; ?> <?php
								print("(${size_array['width']}x${size_array['height']})");
								print("<br><span class='actual'>" . __( 'Current image:', PTE_DOMAIN )
									. $size_array['current']['width'] . "x" 
									. $size_array['current']['height'] . "</span>");
								?></div>
								<img src="<?php print( $size_array['current']['url'] . "?" . mt_rand() ); ?>"/><br/>
						</td>
					</tr>
					<?php endforeach; ?>
				</table>
			</div>
			</div>

		</div>
		<div id="stage2" class="stage">
		</div>
		<script id="stage2template" type="text/x-jquery-tmpl">
			<a onclick="goBack(); return false;" href="#back" class="stage-navigation"
				alt="Return to Step 1" title="Return to Step 1">
				<img src="<?php print( PTE_PLUGINURL . "images/back.gif" ); ?>"/> <?php 
					_e( 'Back', PTE_DOMAIN ); 
				?></a>
			<h1 class="stage-header"><?php echo( __( 'Post Thumbnail Editor - Step 2', PTE_DOMAIN ) ); ?></h1>
			{{if $data['error']}}
			<div id="error"><?php 
								_e( 'We noticed some potential issues:', PTE_DOMAIN );
								echo( "&nbsp;" );
								if ( $options['pte_debug'] ){
									printf( __( 'View %1$slogs%2$s for further information', PTE_DOMAIN )
										, '<a href="" class="show-log-messages">'
										, '</a>');
								}
								else {
									printf( __( '%1$sEnable debugging%2$s for additional information', PTE_DOMAIN )
										, '<a href="options-general.php?page=pte">'
										, '</a>');
								} ?>
				<ul>
					{{each $data['error']}}<li>${$value}</li>{{/each}}
				</ul>
			</div>
			{{/if}}
			<div id="pte-stage2-selectors">
				<?php printf( __( 'Select the images you want to keep: %1$sAll%2$s | %3$sNone%4$s', PTE_DOMAIN )
					, '<a class="all" href="all">'
					, '</a>'
					, '<a class="none" href="none">'
					, '</a>'
				); ?>
			</div>
			<input type="hidden" name="pte-nonce" value="${$data['pte-nonce']}" id="pte-nonce" />
			<input type="hidden" name="pte-delete-nonce" value="${$data['pte-delete-nonce']}" id="pte-delete-nonce" />
			<table>
			{{each $data.thumbnails}}
				<tr class="selected">
					<td class="col1"><input checked class="pte-confirm" type="checkbox" name="pte_confirm[]" value="${$index}"/></td>
					<td class="col2">
						<div class="pte-size-label">${$index}</div>
						<img src="${$value.url}?${randomness()}"/>
						<input class="pte-file" type="hidden" value="${$value.file}"/>
					</td>
				</tr>
			{{/each}}
			</table>
			<div id="stage2-buttons">
				<button id="pte-confirm">
					<?php _e( 'Okay, these look good...', PTE_DOMAIN ); ?>
				</button>&nbsp;
				<a href="#back" id="pte-cancel" 
						onclick="goBack(); return false;">
					<?php _e( 'I\'d rather start over...', PTE_DOMAIN ); ?>
				</a>
			</a>
		</script>
		<div id="stage3" class="stage">
		</div>
		<script id="stage3template" type="text/x-jquery-tmpl">
			<h1 class="stage-header"><?php _e( 'Post Thumbnail Editor', PTE_DOMAIN ); ?></h1>
			{{if $data['success']}}
			<div id="success">
				<p><?php _e( 'Images were created successfully.', PTE_DOMAIN ); ?></p>
				<p><?php
					printf( __( 'Click %1$shere%2$s to modify another thumbnail.' , PTE_DOMAIN ), '<a href="" onclick="window.location.reload();">', '</a>' );
					?></p>
			</div>
			{{/if}}
			{{if $data['error']}}
			<div id="error"><?php _e( 'We noticed some potential issues:', PTE_DOMAIN ); ?>
				<ul>
					{{each $data['error']}}<li>${$value}</li>{{/each}}
				</ul>
			</div>
			{{/if}}
		</script>
		<div id="pte-loading"> 
			<div class="pte-opaque"></div>
			<div id="pte-loading-spinner">
				<img src="<?php print PTE_PLUGINURL; ?>images/loading.gif" alt="<?php
					_e( 'Please wait', PTE_DOMAIN ); ?>" />
		</div></div>
		<?php if ( $options['pte_debug'] ): ?>
		<a id="pte-log-button" class="show-log-messages" href="" alt="<?php 
			_e( 'Click here to show application logs', PTE_DOMAIN ); 
			?>" title="Click here to show application logs"><?php 
			_e( 'Debug', PTE_DOMAIN ); ?></a>
		<div id="pte-log"> 
			<div class="pte-opaque"></div>
			<div id="pte-log-container">
				<div id="pte-log-messages">
					<p><?php printf( __( 'If you are having any issues with this plugin, create a problem report on %1$sgithub%2$s or %3$swordpress.org%4$s so that I can look into it. Copy these log statements and include some information about what you were trying to do, the expected output, and the output you got (the more information the better).  Thanks and good luck!', PTE_DOMAIN )
						, '<a href="https://github.com/sewpafly/post-thumbnail-editor/issues/new">'
						, '</a>'
						, '<a href="http://wordpress.org/tags/post-thumbnail-editor?forum_id=10#postform">'
						, '</a>'
					); ?></p>
					<textarea name="logs"></textarea>
					<!--<textarea name="logs" rows="8" cols="40"></textarea>-->
				</div>
				<div id="pte-log-tools">
<?php
					$testurl = admin_url('admin-ajax.php') . "?action=pte_ajax&pte-action=test&id=${id}";
?>
					<!--<a id="clipboard" href="">Copy to Clipboard</a>&nbsp;-->
					<a class="button" id="pastebin" href=""><?php _e( 'Send to Pastebin', PTE_DOMAIN ); ?></a>&nbsp;
					<a class="button" id="clear-log" href=""><?php _e( 'Clear Messages', PTE_DOMAIN ); ?></a>&nbsp;
					<a class="button" id="test" target="ptetest" href="<?php 
						echo( $testurl ); ?>"><?php 
						_e( 'Run Tests', PTE_DOMAIN ); ?></a>&nbsp;
					<a class="button" id="close-log" href=""><?php _e( 'Close', PTE_DOMAIN ); ?></a>
				</div>
			</div>
		</div>
		<?php endif; ?>
		<div style='clear:both'></div>
	</body>
</html>
