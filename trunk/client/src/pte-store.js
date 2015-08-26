'use client';

var riot = require('riot')
var events = require('./pte-constants.js').events
var $ = require('jQuery')

var data = {}

function getUrl(action, ...args) {
  let url = `./admin-ajax.php?action=pte_api&pte-action=${action}`
  return url + '&' + args.join('&')
}

let PteStore = function () {
  riot.observable(this);

  this.init = () => {
    getData()
  }

  let getData = () => {
    let id = /(id=\d+)/.exec(window.location.search)[0]
    let info_url = getUrl('get-image-info', id) 
    let thumb_url = getUrl('get-thumbnail-info', id) 
    $.when($.getJSON(info_url), $.getJSON(thumb_url))
    .done((i_data, t_data) => {
      $.extend(data, {
        img: i_data[0],
        thumb: t_data[0]
      })
      this.trigger(events.DATA_LOADED, data)
    })
  }

  //(global || window).setTimeout(() => {
  //    console.log('Trigger DATA_LOADED');
  //    this.trigger(events.DATA_LOADED);
  //}, 1000);
}

export default PteStore;
