<?php
global $post, $pte_iframe;
function u( $path ){
   printf( "%s%s?%s",
	   PTE_PLUGINURL,
	   $path,
	   PTE_VERSION
   );
}

?>

<!--
<base href="/wp-admin/"/>
-->
<script type="text/javascript" charset="utf-8">
   var post_id     = <?php echo $post->ID; ?> 
     , post_width  = <?php echo $meta['width']; ?> 
     , post_height = <?php echo $meta['height']; ?> 
     , pte_nonce   = "<?php echo wp_create_nonce("pte-resize-{$post->ID}"); ?>"
     , pte_options_nonce = "<?php echo wp_create_nonce("pte-options"); ?>"
     , pteI18n     = <?php echo json_encode( 
			  array( 'no_t_selected' => __( 'No thumbnails selected', PTE_DOMAIN )
			  , 'no_c_selected' => __( 'No crop selected', PTE_DOMAIN )
			  , 'crop_problems' => __( 'Cropping will likely result in skewed imagery', PTE_DOMAIN )
			  , 'save_crop_problem' => __( 'There was a problem saving the crop...', PTE_DOMAIN )
			  , 'cropSave' => __( 'Crop and Save', PTE_DOMAIN )
			  , 'crop' => __( 'Crop', PTE_DOMAIN )
			  , 'fitCrop_save' => __( 'Save', PTE_DOMAIN )
			  , 'fitCrop_transparent' => __( 'Set transparent', PTE_DOMAIN )
			  , 'transparent' => __( 'transparent/white', PTE_DOMAIN )
		  ));
?>;
<?php if ( $pte_iframe ) {
	echo 'var ajaxurl = "' . admin_url( 'admin-ajax.php' ) . '";';
}?>
</script>
 
<link rel="stylesheet" href="<?php u( 'apps/font-awesome/css/font-awesome.min.css' ) ?>"/>
<link rel="stylesheet" href="<?php u( 'apps/jcrop/css/jquery.Jcrop.css' ) ?>"/>
<link rel="stylesheet" href="<?php u( 'css/pte.css' ) ?>"/>

<div class="wrap ng-cloak" ng-init="currentThumbnailBarPosition='<?php echo $options['pte_thumbnail_bar'];?>'" ng-controller="PteCtrl">
   <?php if ( !isset( $_GET['title'] ) || $_GET['title'] != 'false' ) : ?>
   <?php screen_icon('upload'); ?>
   <h2><?php _e("Post Thumbnail Editor", PTE_DOMAIN);?> &ndash; 
      <span id="pte-subtitle"><?php _e("crop and resize", PTE_DOMAIN); ?></span>
   </h2>
   <div class="subtitle"><?php echo $post->post_title; ?></div>
   <?php endif; ?>
   <h3 class="nav-tab-wrapper">
      <a ng-href="" ng-class="pageClass('crop')" ng-click="changePage('crop')" class="nav-tab"><?php _e("Crop", PTE_DOMAIN); ?></a>
      <a ng-href="" ng-class="pageClass('view')" ng-click="changePage('view')" class="nav-tab"><?php _e("View", PTE_DOMAIN); ?></a>
   </h3>
   <div id="poststuff">
      <div id="post-body" class="metabox-holder columns-1">
         <div id="post-body-content">
            <div class="error-message" ng-show="errorMessage">
               <i class="fa-times" ng-click="errorMessage = null"></i>
               <i class="fa-warning"></i>
               {{ errorMessage }}
            </div>
            <div class="info-message" ng-show="infoMessage">
               <i class="fa-times" ng-click="infoMessage = null"></i>
               <i class="fa-info-circle"></i>
               {{ infoMessage }}
            </div>

			<!-- LOADING SPINNER -->
			<div class="pte-page-switcher" ng-show="page.loading">
				<div class="pte-loading">
					<img src="<?php echo( site_url( "wp-includes/images/wpspin-2x.gif" ) ); ?>"/>
				</div>
			</div>
			<!-- END LOADING -->

            <div class="pte-page-switcher" ng-show="page.crop">
            <div id="pte-image" ng-controller="CropCtrl">
               <img id="pte-preview" src="<?php 
					echo $editor_image;
               ?>"/>
      
               <div id="pte-crop-controls">
						<a ng-click="toggleOptions()" class="button button-secondary" ng-href=""><?php
							_e( "Options", PTE_DOMAIN ); ?>
							<i class="fa-caret-down" ng-hide="cropOptions"></i>
							<i class="fa-caret-up" ng-show="cropOptions"></i>
						</a>
						<a ng-disabled="cropInProgress" class="button button-primary" ng-href="" ng-click="submitCrop()">
							<span ng-hide="cropInProgress">{{ cropText() }}</span>
							<i ng-show="cropInProgress" class="fa-spin fa-spinner"></i>
						</a>
               </div>
					<div style="position: relative">
						<div id="pte-crop-settings" ng-show="cropOptions">
							<i class="fa-times" ng-click="toggleOptions()"></i>
							<form name="test">
							<ul>
								<li>
									<!--ui-event="{blur : 'aspectRatioBlur()'}"-->
									<label for="pte-aspect-ratio"><?php _e( "Aspect Ratio", PTE_DOMAIN ); ?>: </label>
									<input id="pte-aspect-ratio" 
											type="number"
											placeholder="<?php _e( "width/height", PTE_DOMAIN ); ?>"
											ng-model="aspectRatio" ng-change="changeAR()" name="pte-aspect-ratio"/>
									<!--ng-pattern="aspectRatioPattern"/>-->
									<i class="fa-undo" ng-click="aspectRatio = null"></i>
								</li>
								<li>
									<label for="pte-crop-and-save"><?php _e("Crop and save", PTE_DOMAIN); ?></label>
									<input ng-model="pteCropSave"
											ng-init="pteCropSave = <?php print( ( $options['pte_crop_save'] ) ? 'true':'false' ); ?>"
											ng-change=""
											type="checkbox"
											name="pte-crop-and-save"
											id="pte-crop-and-save"/>
								</li>
								<li>
                                    <?php _e( "Change the current thumbnails position:", PTE_DOMAIN ); ?>&nbsp;<button ng-click="toggleCurrentThumbnailBarPosition()">{{ currentThumbnailBarPosition }}</button>
								</li>
								<?php if ( $post->post_mime_type == "image/jpeg" ): # is JPEG file ?>
								<li><label for="pte-jpg-compression"><?php _e( "JPEG Compression", PTE_DOMAIN ); ?></label>&nbsp;
									<input id="pte-jpg-compression"
										type="number"
										ng-model="pteJpgCompression"
										placeholder="<?php printf( __( "0 to 100 (Default: %d)" ), $options['pte_jpeg_compression'] ); ?>"
										min="0"
										max="100"
										name="pte-jpg-compression"/>
								</li>
								<?php endif; ?>
								<li>
								<span ng-hide="aspectRatio">
									<label for="pteFitCrop">
										<?php _e( "Fit crop to thumbnail by adding border" ); ?>
									</label>
									<input id="pteFitCrop" 
												name="pte-fit-crop" 
												type="checkbox" 
												ng-model="pteFitCrop"
												ng-click="fitToCrop()"/>
									<span ng-click="fitToCrop()">{{ pteFitCropColor }}</span>
								</span>
								</li>
							</ul>
							</form>
						</div>
					</div>
				</div>
            <div id="pte-thumbnail-column" ng-controller="TableCtrl">
               <table id="pte-thumbnail-table" class="wp-list-table widefat" >
                  <thead>
                     <tr>
                        <th class="center">
                           <input type="checkbox" ng-model="tableSelector" ng-change="toggleAll()"/>
                        </th>
                        <th><?php _e( "Thumbnails" ); ?></th>
						<th class="align-right" title="<?php _e("width"); ?>"><?php _e( "W" ); ?></th>
						<th class="align-right" title="<?php _e("height"); ?>"><?php _e( "H" ); ?></th>
						<th title="<?php _e("crop"); ?>"><?php _e( "C" ); ?></th>
                        <th class="center">
                           <span class="pte-thumbnails-menu">
                              <i ng-show="anyProposed()" 
                                 ng-click="save()"
                                 id="pte-save-all"
                                 title="<?php _e( "Save all", PTE_DOMAIN ); ?>"
                                 class="fa-save"></i>
                              <i ng-show="anyProposed()" 
                                 ng-click="trashAll(); $event.stopPropagation()"
                                 id="pte-reset-all"
                                 title="<?php _e( "Reset all", PTE_DOMAIN ); ?>"
                                 class="fa-trash-o"></i>
                              <i ng-click="view(anyProposed());"
                                 id="pte-view-modified" 
                                 title="<?php _e( 'View all/modified', PTE_DOMAIN ); ?>" 
                                 class="fa-search"></i>
                           </span>
                        </th>
                     </tr>
                  </thead>
                  <tbody>
                     <tr ng-class="'selected-'+thumbnail.selected" 
                           ng-click="toggleSelected(thumbnail)"
                           ng-class-odd="'alternate'" 
                           ng-repeat="thumbnail in thumbnails">
                        <td class="center">
                           <input type="checkbox"
                              ng-click="$event.stopPropagation()"
                              ng-model="thumbnail.selected"
                              ng-change="updateSelected()"/>

                        </td>
                        <td>{{ thumbnail.display_name || thumbnail.name }}</td>
                        <td class="align-right">{{ thumbnail.width }}</td>
                        <td class="align-right">{{ thumbnail.height }}</td>
                        <td>{{ thumbnail.crop }}</td>
                        <td class="center pte-thumbnail-options">
                           <span class="pte-thumbnail-menu">
                              <i ng-show="thumbnail.proposed" 
                                 ng-click="save(thumbnail)"
                                 title="<?php _e( "Save", PTE_DOMAIN ); ?>" class="fa-save"></i>
                              <i ng-show="thumbnail.proposed" 
                                 ng-click="trash(thumbnail); $event.stopPropagation()"
                                 title="<?php _e( "Reset", PTE_DOMAIN ); ?>" class="fa-trash-o"></i>
                              <i ng-show="thumbnail.proposed" 
                                 ng-click="changePage('view'); view(thumbnail.name); $event.stopPropagation();" 
                                 title="<?php _e( "Compare/View", PTE_DOMAIN ); ?>" class="fa-search"></i>
                           </span>
                        </td>
                     </tr>
                  </tbody>
               </table>
               <div id="aspect-ratio-selector" ng-show="aspectRatios.length">
                  <?php _e( "These thumbnails have an aspect ratio set:", PTE_DOMAIN ); ?>
                  <ul>
                     <li ng-repeat="aspectRatio in aspectRatios | orderBy:size">
                        <a ng-click="selectAspectRatio(aspectRatio)" ng-href="">
                           <i class="fa-chevron-right"></i>
                           {{ aspectRatio.thumbnails.toString().replace(",",", ") }}</a></li>
                  </ul>
               </div>
               <div ng-class="currentThumbnailBarPosition" id="pte-remember" ng-show="anySelected()">
                   <h4><?php _e( "Current Thumbnails", PTE_DOMAIN ); ?></h4>
                   <ul id="pte-remember-list">
                       <li ng-repeat="thumbnail in thumbnails | filter:{selected:true}">
                           <img ng-src="{{ thumbnail.current.url | randomizeUrl }}" 
                                   ng-show="thumbnail.current"
                                   alt="{{ thumbnail.name }}" 
                                   title="{{ thumbnail.name }}"/>
                           <span title="{{ thumbnail.name }}" class="no-current-image" ng-hide="thumbnail.current">
                               <i class="fa-exclamation-circle"></i>
                           </span>
                       </li>
                   </ul>
               </div>
            </div>
            </div>
            <div class="pte-page-switcher" ng-show="page.view" ng-controller="ViewCtrl">
               <div class="pte-display-thumbnail" 
                     ng-repeat="thumbnail in thumbnails | filter:viewFilterFunc | orderBy:orderBy">
                  <div class="pte-display-thumbnail-image" ng-class="thumbnailClass(thumbnail)">
                     <div class="pte-display-thumbnail-menu" ng-show="thumbnail.proposed">
                        <button ng-click="thumbnail.showProposed = !thumbnail.showProposed"><i class="fa-refresh"></i></button>
                        <br/>
                        <button ng-click="save(thumbnail)" ng-show="thumbnail.showProposed"><i class="fa-save"></i></button>
                        <br/>
                        <button ng-click="trash(thumbnail); $event.stopPropagation()" ng-show="thumbnail.showProposed"><i class="fa-trash-o"></i></button>
                     </div>
                     <div 
                        ng-dblclick="changePage('crop');$event.stopPropagation();"
                        ng-click="thumbnail.selected = !thumbnail.selected;updateSelected();" 
                        ng-hide="thumbnail.showProposed">
                        <span ng-show="thumbnail.proposed"><strong><?php _e( "Original", PTE_DOMAIN ); ?>: {{ thumbnail.name }}</strong><br/></span>
                        <img ng-src="{{ thumbnail.current.url | randomizeUrl }}" 
                              ng-show="thumbnail.current"
                              alt="{{ thumbnail.name }}" 
                              title="{{ thumbnail.name }}"/>
                        <span class="no-current-image" ng-hide="thumbnail.current">
                           <i class="fa-exclamation-circle"></i>
                           <?php _e( "No image has been generated yet for image: ", PTE_DOMAIN ) ?> '{{ thumbnail.name }}'
                        </span>
                     </div>
                     <div
                        ng-dblclick="changePage('crop');$event.stopPropagation();"
                        ng-click="thumbnail.selected = !thumbnail.selected;updateSelected();"
                        ng-show="thumbnail.showProposed">
                        <span><strong><?php _e( "Proposed", PTE_DOMAIN ); ?>: {{ thumbnail.name }}</strong><br/></span>
                              <!--ng-click="selectThumb(thumbnail)"-->
                        <img ng-src="{{ thumbnail.proposed.url | randomizeUrl }}" 
                              ng-show="thumbnail.showProposed"
                              alt="{{ thumbnail.name }}" 
                              title="{{ thumbnail.name }}"/>
                     </div>
                  </div>
               </div>
            </div>
         </div>
      </div>
   </div>
</div>
<div id="pte-iris-dialog">
	<input type="text" name="pteIris" id="pteIris" value="" />
</div>
<?php

function enqueue_script_filter($tag, $handle) {
	if ('pte-require' !== $handle)
		return $tag;
	return str_replace(' src', ' data-main="' . PTE_PLUGINURL . 'js/main" src', $tag);
}

function enqueue_last() {
	wp_enqueue_script(
		'pte-require', 
		PTE_PLUGINURL . "apps/requirejs/require.js",
		null, 
		PTE_VERSION, 
		true
	);
}

$options = pte_get_options();

if ( $options['pte_debug'] ) {
	add_action('wp_print_footer_scripts', 'enqueue_last', 1, 0);
	add_action('admin_print_footer_scripts', 'enqueue_last', 1, 0);
	add_filter('script_loader_tag', 'enqueue_script_filter', 10, 2);
}
else {
	wp_enqueue_script(
		'pte-min-js', 
		PTE_PLUGINURL . "js-build/main.js",
		null, 
		PTE_VERSION, 
		true
	);
}
