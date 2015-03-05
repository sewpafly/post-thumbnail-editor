define [
   'angular'
   'cs!apps/pteApp'
   'cs!settings'
   'cs!jquery'
], (angular, app, settings, $) ->
   app.controller "PteCtrl", ['$scope','$resource','$log', '$filter', ($scope, $resource, $log, $filter) ->
      ###
      # Page handling feature
      #
      # Switch the enabled page to change the view
      ###
      $scope.page =
         loading: on
         crop: off
         view: off
      $scope.changePage = (page) ->
         $scope.viewFilterValue = off
         for key, value of $scope.page
            if key == page
               $scope.page[key] = on
            else
               $scope.page[key] = off

      ###
      # Set the Tab Class to active when the page is enabled
      #
      # This is watched by the view on a ng-class directive.
      ###
      $scope.pageClass = (page) ->
         if $scope.page[page]
            return "nav-tab-active"

      ###
      # Resource
      ###
      $scope.thumbnailResource = $resource settings.ajaxurl,
         'action': 'pte_ajax'
         'pte-action': 'get-thumbnail-info'
      
      ###
      # Catch the thumbnail selected event and broadcast to all the children scopes
      ###
      #$scope.$on 'thumbnail_selected', (event, thumbs) ->
      #   $scope.$broadcast 'thumbnail_selected', thumbs
      #   return

      ###
      # Publish selected event
      ###
      $scope.updateSelected = ->
         $scope.$broadcast 'thumbnail_selected'

        
      ###
      # Update options
      #
      # Use this to update user/site options
      ###
      $scope.updateOptions = (update_options) ->
         update_options['pte-action'] = 'change-options'
         update_options['pte-nonce'] = settings.options_nonce
         $log.log "Updating Options", update_options

         updated = $scope.thumbnailResource.get update_options, ->
            $log.log "Updated options"
         return


      ###
      # ViewFilter is a filter expression that checks if the viewFilter specifies
      # certain thumbnail names to display or if it should just be all modified.
      # 
      # Using the tab buttons will reset this feature.
      ###
      $scope.viewFilterValue = off
      $scope.view = (val) ->
         event?.stopPropagation?()
         $scope.changePage('view')
         $scope.viewFilterValue = val
         return
      $scope.viewFilterFunc = (thumbnail) ->
         if $scope.viewFilterValue is off
            return true
         if angular.isString $scope.viewFilterValue
            if thumbnail.name is $scope.viewFilterValue
               return true
            else
               return false
         if angular.isArray $scope.viewFilterValue
            # check if thumbnail.name is in array
            if thumbnail.name in $scope.viewFilterValue
               return true
         # This sets the view to show only the recently changed images
         if $scope.viewFilterValue
            return thumbnail.proposed?
         return true

      ###
      # SAVE
      ###
      $scope.save = (thumbnail) ->
         #event?.stopPropagation?()
         #$log.log thumbnail_array
         data =
            'pte-action': 'confirm-images'
            'pte-nonce': nonces['pte-nonce']
            id: id
         thumbnail_array = []
         if !thumbnail
            angular.forEach $scope.thumbnails, (thumb) ->
               if thumb.proposed
                  thumbnail_array.push thumb
               return
            if thumbnail_array.length < 1
               return
         else
            thumbnail_array.push thumbnail

         for thumbnail in thumbnail_array
            key = 'pte-confirm['+thumbnail.name+']'
            data[key] = thumbnail.proposed.file

         $log.log data

         confirm_results = $scope.thumbnailResource.get data, ->
            $scope.confirmResults confirm_results

         return

      $scope.confirmResults = (confirm_results) ->
         if !confirm_results.thumbnails
            $scope.setErrorMessage $scope.i18n.save_crop_problem
            return
         #$filter('randomizeUrl') {reset: true}
         viewFilter = []
         resetUrls = []
         for thumbnail in $scope.thumbnails
            if confirm_results.thumbnails[thumbnail.name]
               viewFilter.push thumbnail.name
               thumbnail.current = confirm_results.thumbnails[thumbnail.name].current
               resetUrls.push thumbnail.current.url
               if thumbnail.proposed?.url
                   resetUrls.push thumbnail.proposed.url
               #if !angular.isObject thumbnail.current
               #   thumbnail.current = {}
               #thumbnail.current.url = thumbnail.proposed.url
               #thumbnail.selected = false
               $scope.trash thumbnail
         if confirm_results.immediate
            # Change to the view
            #$scope.view viewFilter
         else
            checkFilter()

         $filter('randomizeUrl') {reset: true, urls: resetUrls}
         return


      ###
      # Clean up procedures
      #
      # trash function just removes from view.
      # trashAll deletes from server, and we hook into the unload
      # event to cleanup after ourselves
      ###
      $scope.trash = (thumbnail) ->
         event?.stopPropagation?()
         delete thumbnail.proposed
         thumbnail.showProposed = false
         # if there aren't any other proposed, set the viewFilter to false
         checkFilter()

	  # if there aren't any other proposed, set the viewFilter to false
      checkFilter = ->
         for thumbnail in $scope.thumbnails
            if thumbnail.proposed
               return
         $scope.viewFilterValue = off

      $scope.trashAll = ->
         deleteTemp()
         angular.forEach $scope.thumbnails, (thumb) ->
            $scope.trash thumb
         $filter('randomizeUrl') {reset: true}
         return

      deleteTemp = ->
         if not nonces?['pte-delete-nonce']?
            return
         deleteResults = $.ajax settings.ajaxurl,
            async: false
            data:
               'action': 'pte_ajax'
               id: id
               'pte-action': 'delete-images'
               'pte-nonce': nonces['pte-delete-nonce']
         return

      $(window).unload (event) ->
         deleteTemp()
         return


      ###
      # Allow selecting based on the aspect ratio
      ###
      $scope.aspectRatios = []
      addToAspectRatios = (thumb) ->
         ar = thumb.width/thumb.height

         # Check for valid AspectRatio
         if !ar? or ar is Infinity
            return

         if !thumb.crop or +thumb.crop < 1
            return

         for aspectRatio in $scope.aspectRatios
            if ar - 0.01 < aspectRatio.size < ar + 0.01
               aspectRatio.thumbnails.push thumb.name
               return
         $scope.aspectRatios.push
            size: ar
            thumbnails: [thumb.name]
         return


      ###
      # Initialization
      ###
      id = settings.id
      if !id
         $log.error "No ID Found"

      $scope.i18n = settings.i18n

      $scope.infoMessage = null
      $scope.setInfoMessage = (message) ->
         $scope.infoMessage = message

      $scope.errorMessage = null
      $scope.setErrorMessage = (message) ->
         $scope.errorMessage = message

      nonces = null
      $scope.setNonces = (nonceObj) ->
         nonces = nonceObj

      ## LOAD THE THUMBNAIL INFORMATION ##
      $scope.thumbnails = []
      $scope.thumbnailObject = $scope.thumbnailResource.get {id: id}, ->
         angular.forEach $scope.thumbnailObject, (thumb, name) ->
            if name not in ["$promise", "$resolved"]
               thumb.name = name
               @thumbnails.push thumb
               addToAspectRatios thumb
               return
         , $scope
         $scope.updateSelected()

         # Turn off loading page
         $log.info "Disabling loading screen"
         $scope.changePage('crop')
         return

      $scope.anyProposed = ->
         for thumb in $scope.thumbnails
            if thumb.proposed?
               return true
         return false

      ###
      # Used to hide/show the existing thumbnails
      # 
      # @since 2.2.0
      ###
      $scope.anySelected = ->
         for thumb in $scope.thumbnails
            if thumb.selected
               return true
         return false

      ###
      # Update wordpress option when the currentThumbnailBarPosition changes
      ###
      $scope.$watch 'currentThumbnailBarPosition', (x,y) ->
         if x is y
            return

         $scope.updateOptions
            'pte_thumbnail_bar': $scope.currentThumbnailBarPosition

         return

      $scope.toggleCurrentThumbnailBarPosition = ->
         positions = ["vertical", "horizontal"]
         if $scope.currentThumbnailBarPosition is positions[0]
            $scope.currentThumbnailBarPosition = positions[1]
         else
            $scope.currentThumbnailBarPosition = positions[0]
         return

      ### STOP THE MADNESS ###
      return
   ]
   return app
