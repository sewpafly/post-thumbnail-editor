define [
   'angular'
   'angular-resource'
   'cs!apps/urlCacheBreaker'
], (angular) ->
   app = angular.module 'pte', ['ngResource', 'UrlCacheBreaker']
   #app.config ($locationProvider) ->
   #   $locationProvider.html5Mode true
   #   return



   # Taken from angular-ui.github.com
   #app.directive 'uiEvent', ['$parse', ($parse) ->
   #   (scope, elm, attrs) ->
   #      events = scope.$eval attrs.uiEvent
   #      angular.forEach events, (uiEvent, eventName) ->
   #         fn = $parse uiEvent
   #         elm.bind eventName, (evt) ->
   #            params = Array.prototype.slice.call arguments
   #            # Take out first paramater (event object);
   #            params = params.splice 1
   #            scope.$apply ->
   #               fn scope, {$event: evt, $params: params}
   #               return
   #            return
   #         return
   #      return
   #]
   return app
