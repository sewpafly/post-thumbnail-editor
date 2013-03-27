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

         # Check for a reset object
         #  {reset: true} will reset all urls
         #  {reset: true, urls: ['url','url']} will set to a new random number
         if (angular.isObject url) and url.reset
            if url.urls?.length > 0
               for reset_url in url.urls
                  urlMap[reset_url] = random()
            else
               urlMap = {}
            return

         if url not of urlMap
            urlMap[url] = random()

         randomness = urlMap[url]

         separator = '?'
         if url?.indexOf('?') >= 0
            separator = '&'

         return url + separator + randomness

   return app
