define [
   'require'
   'cs!jquery'
   'angular'
], (require, $, angular) ->
   # COFFEESCRIPT VERSION OF UNDERSCORE ONCE:
   once = (func) ->
      ran = false
      memo = null
      return ->
         if ran
            return memo
         ran = true
         memo = func.apply this, arguments
         func = null
         return memo

   startupFunction = once ->
      require [
         'cs!apps/pteApp'
         'cs!controllers/PteCtrl'
         'cs!controllers/TableCtrl'
         'cs!controllers/CropCtrl'
         'cs!controllers/ViewCtrl'
      ], (pteApp) ->
         angular.bootstrap $(".wrap"), [pteApp.name]
         return
      return


   # If the page is already loaded then just run the startup
   # otherwise, set the load callback
   #
   #console.log document.readyState
   if document.readyState is 'complete' or document.readyState is 'loaded'
      startupFunction()
   else
      $(window).load ->
         startupFunction()

   # Provide failsafe
   timeout = 10000
   window.setTimeout ->
      startupFunction()
   , timeout

   return
