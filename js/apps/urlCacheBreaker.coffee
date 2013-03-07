define [
   'angular'
], (angular) ->
   app = angular.module 'UrlCacheBreaker', []
   app.filter 'randomizeUrl', ->
      random = ->
         Math.floor(Math.random()*1000001).toString(16)

      urlMap = {}

      (url) ->
         if not url? then return url

         if url not of urlMap
            urlMap[url] = random()

         randomness = urlMap[url]

         separator = '?'
         if url?.indexOf('?') >= 0
            separator = '&'

         return url + separator + randomness

   return app
