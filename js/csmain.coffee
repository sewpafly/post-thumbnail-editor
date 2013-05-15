define [
   'require'
   'cs!jquery'
   'angular'
], (require, $, angular) ->
   $(window).load ->
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
