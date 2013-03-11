define [
   'angular'
   'cs!apps/pteApp'
], (angular, app) ->
   app.controller "TableCtrl", ['$scope', ($scope) ->
      ###
      # Toggles the selected value from true to false on a row click
      #
      # Ignore the case where the checkbox itself is clicked, This might
      # need to be updated to be useful with the save/cancel icons also
      # found in the table row.
      #
      # @param thumbnail The thumbnail to change
      ###
      $scope.toggleSelected = (thumbnail) ->
         thumbnail.selected = !thumbnail.selected
         # This is for telling other Controllers that the selection changed
         $scope.updateSelected()
         return


      ###
      # Used by the view to set all the rows to the same value
      ###
      $scope.toggleAll = ->
         for name, thumbnail of $scope.thumbnails
            thumbnail.selected = $scope.tableSelector
         $scope.updateSelected()
         return

      ###
      # Toggle the thumbnails based on their aspectRatio
      #
      # If there are multiple thumbnails, use the value of the first thumbnail.selected
      # to determine the rest.
      ###
      $scope.selectAspectRatio = (ar) ->
         event?.stopPropagation?()
         selectVal = null
         angular.forEach $scope.thumbnails, (thumb) ->
            if thumb.name in ar.thumbnails
               if not selectVal?
                  selectVal = if thumb.selected? and thumb.selected then false else true
               thumb.selected = selectVal
            return
         $scope.updateSelected()
         return

      return
   ]
   return app
