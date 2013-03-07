define [
   'angular'
   'cs!apps/pteApp'
   'cs!settings'
   'cs!jquery'
], (angular, app, settings, $) ->
   app.controller "PteCtrl", ['$scope','$resource','$log', ($scope, $resource, $log) ->
      ###
      # Page handling feature
      #
      # Switch the enabled page to change the view
      ###
      $scope.page =
         crop: on
         view: off
      $scope.changePage = (page) ->
         $scope.viewFilterValue = false
         for key, value of $scope.page
            if key == page
               $scope.page[key] = true
            else
               $scope.page[key] = false

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
      $scope.thumbnailResource = $resource settings.getWindowVar('ajaxurl'),
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
      # ViewFilter is a filter expression that checks if the viewFilter specifies
      # certain thumbnail names to display or if it should just be all modified.
      # 
      # Using the tab buttons will reset this feature.
      ###
      $scope.viewFilterValue = false
      $scope.view = (val) ->
         event?.stopPropagation?()
         $scope.changePage('view')
         $scope.viewFilterValue = val
         return
      $scope.viewFilterFunc = (thumbnail) ->
         if $scope.viewFilterValue is false
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
         if $scope.viewFilterValue
            return thumbnail.proposed?
         return true

      ###
      # SAVE
      ###
      $scope.save = (thumbnail_array) ->
         event.stopPropagation()
         #$log.log thumbnail_array
         data =
            'pte-action': 'confirm-images'
            'pte-nonce': nonces['pte-nonce']
            id: id
         for thumbnail in thumbnail_array
            if thumbnail.selected
               key = 'pte-confirm['+thumbnail.name+']'
               data[key] = thumbnail.proposed.file
         $log.log data
         confirm_results = $scope.thumbnailResource.get data, ->
            if !confirm_results.success
               $scope.setErrorMessage $scope.i18n.save_crop_problem
               return
            for thumbnail in thumbnail_array
               if thumbnail.selected
                  if !angular.isObject thumbnail.current
                     thumbnail.current = {}
                  thumbnail.current.url = thumbnail.proposed.url
                  thumbnail.selected = false
                  $scope.trash thumbnail
            return

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

      $scope.trashAll = ->
         deleteTemp()
         angular.forEach $scope.thumbnails, (thumb) ->
            $scope.trash thumb

      deleteTemp = ->
         if not nonces['pte-delete-nonce']?
            return
         deleteResults = $.ajax settings.getWindowVar('ajaxurl'),
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
      # Initialization
      ###
      id = settings.getWindowVar('post_id')
      if !id
         $log.error "No ID Found"

      $scope.i18n = settings.getWindowVar 'pteI18n'

      $scope.infoMessage = null
      $scope.setInfoMessage = (message) ->
         $scope.infoMessage = message

      $scope.errorMessage = null
      $scope.setErrorMessage = (message) ->
         $scope.errorMessage = message

      nonces = null
      $scope.setNonces = (nonceObj) ->
         nonces = nonceObj

      $scope.thumbnails = []

      $scope.thumbnailObject = $scope.thumbnailResource.get {id: id}, ->
         angular.forEach $scope.thumbnailObject, (thumb, name) ->
            thumb.name = name
            this.push thumb
         , $scope.thumbnails
         return

      $scope.anyProposed = ->
         for thumb in $scope.thumbnails
            if thumb.proposed?
               return true
         return false

      return
   ]
   return app


