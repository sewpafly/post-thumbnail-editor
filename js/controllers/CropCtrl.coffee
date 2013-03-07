define [
   'cs!apps/pteApp'
   'cs!jcrop-api'
   'cs!jquery'
   'cs!settings'
], (app, jcrop, $, settings) ->
   app.controller "CropCtrl", ['$scope','$log', ($scope, $log) ->
      #$scope.aspectRatioPattern = /(^\d+(\.\d+)*)/
      $scope.$watch 'aspectRatio', ->
         ar = $scope.aspectRatio || null
         jcrop.setOptions
            aspectRatio: ar
         return

      $scope.changeAR = ->
         $scope.userChanged = true

      $scope.toggleOptions = ->
         $scope.cropOptions = !$scope.cropOptions
         if !$scope.cropOptions
            $scope.aspectRatio = null
            $scope.userChanged = false
            $scope.updateSelected()
         return

      ###
      # Change the AspectRatio based on the selected thumbnails
      #
      # This will create some interesting issues with the new option that
      # allows the user to manually set the aspectRatio, so I need to step
      # through the different options and it would be a good idea to actually
      # create some sort of a unit test to verify that they actually work.
      #
      # 1. If the user has previously selected an AR, this is the default AR
      #    until the user closes the options. If the user clears the AR
      #    manually with the refresh button, respect that.
      #
      # 2. In order to set the AR, each selected thumbnail needs to have the
      #    same AR.
      #
      # 3. If wordpress' crop setting is set then you can set the AR, but
      #    if it isn't set, then don't worry about unsetting it yet
      #
      ###
      $scope.updateSelected = ->
         $scope.setInfoMessage null
         # Respect the user setting (#1)
         if $scope.userChanged
            return

         ar = null
         try
            angular.forEach $scope.thumbnails, (thumbnail) ->
               # Get the crop/width/height and convert to numerals
               {crop, width, height} = thumbnail
               crop = +crop
               width = +width
               height = +height

               if thumbnail.selected and thumbnail.crop > 0
                  tmp_ar = width/height
                  if ar isnt null and ar != tmp_ar
                     throw "PTE_EXCEPTION"
                  ar = tmp_ar
         catch error
            $scope.setInfoMessage $scope.i18n.crop_problems
            $scope.aspectRatio = null
            return

         $scope.aspectRatio = ar
         return # end updateSelected

      $scope.submitCrop = ->
         if $scope.cropInProgress
            return
         $scope.cropInProgress = true
         # Check that there are thumbnails actually selected...
         selected_thumbs = $.map $scope.thumbnails, (thumb, i) ->
            if thumb.selected
               return thumb.name
            else
               return null
         if selected_thumbs.length is 0
            $scope.setErrorMessage $scope.i18n.no_t_selected
            $log.error $scope.i18n.no_t_selected
            $scope.cropInProgress = false
            return

         {x, y, w, h, x2, y2} = jcrop.tellSelect()
         if x is 0 and
         y is 0 and
         w is 0 and
         h is 0 and
         x2 is 0 and
         y2 is 0
            $scope.setErrorMessage $scope.i18n.no_c_selected
            $log.error $scope.i18n.no_c_selected
            $scope.cropInProgress = false
            return

         crop_options = 
            'pte-action': 'resize-images'
            'id': settings.getWindowVar 'post_id'
            'pte-sizes': selected_thumbs
            'w':w
            'h':h
            'x':x
            'y':y

         crop_results = $scope.thumbnailResource.get crop_options, ->
            $scope.cropInProgress = false
            $scope.setNonces
               'pte-nonce': crop_results['pte-nonce']
               'pte-delete-nonce': crop_results['pte-delete-nonce']
            $.each $scope.thumbnails, (i, thumb) ->
               if crop_results.thumbnails[thumb.name]
                  thumb.proposed = crop_results.thumbnails[thumb.name]
                  #thumb.proposed.file = crop_results.thumbnails[thumb.name].file
                  thumb.showProposed = true
               return
            return

         return # end submitCrop

      #$scope.thumbnailObject = $scope.thumbnailResource.get {id: id}, ->

      ###
      # Listener
      ###
      $scope.$on 'thumbnail_selected', (event) ->
         $scope.updateSelected()
         return

      return
   ]
   return app
