define [
   'cs!apps/pteApp'
], (app) ->
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
         if event.target.nodeName isnt 'INPUT'
            thumbnail.selected = !thumbnail.selected
         # This is for telling other Controllers that the selection changed
         $scope.updateSelected()



      ###
      # Used by the view to set all the rows to the same value
      ###
      $scope.toggleAll = ->
         for name, thumbnail of $scope.thumbnails
            thumbnail.selected = $scope.tableSelector
         $scope.updateSelected()
         return

      return
   ]
   return app
