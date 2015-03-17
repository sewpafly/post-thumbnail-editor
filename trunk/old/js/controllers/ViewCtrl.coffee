define [
   'cs!apps/pteApp'
], (app) ->
   app.controller "ViewCtrl", ['$scope', ($scope) ->
      #$scope.orderBy = 'width'
      $scope.orderBy = (thumbnail) ->
         current = thumbnail.current?.width * thumbnail.current?.height
         if current? and current > 0
            return current
         thumbnail.width * thumbnail.height

      ###
      # Click on the picture to select it for cropping and then, change to
      # crop panel
      ###
      $scope.selectThumb = (thumbnail) ->
         thumbnail.selected = true
         $scope.changePage 'crop'
         $scope.updateSelected()
         return

      ###
      Add a class to all the thumbnails
         'modified': adds green background
         'original': adds a transition from green to normal (CSS3)
      ###
      $scope.thumbnailClass = (thumbnail) ->
         classes = []
         if thumbnail.proposed?
            classes.push 'modified'
         else
            classes.push 'original'

         if thumbnail.selected
            classes.push 'selected'
         return classes

         #if 'showProposed' of thumbnail
         #   # hack... Using CSS3 for the animations causes an issue
         #   # when anything set to 'original' gets recreated.
         #   window.setTimeout ->
         #      delete thumbnail.showProposed
         #   , 1100
         #   return 'original'
         #return ''

      return
   ]
   return app
