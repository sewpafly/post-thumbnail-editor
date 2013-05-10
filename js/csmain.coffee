define [
   'require'
   'domReady'
   'cs!jquery'
   'angular'
], (require, domReady, $, angular) ->
#], ($, angular, pteApp, domReady) ->
   domReady ->
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
   return
