define [
   'cs!jquery'
   'angular'
   'cs!apps/pteApp'
   'cs!controllers/PteCtrl'
   'cs!controllers/TableCtrl'
   'cs!controllers/CropCtrl'
   'cs!controllers/ViewCtrl'
], ($, angular, pteApp) ->
   angular.bootstrap $(".wrap"), [pteApp.name]
   return
