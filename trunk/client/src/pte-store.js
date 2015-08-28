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
    let info_url = getUrl('get-metadata', id) 
    $.getJSON(info_url)
    .done((info_data) => {
      $.extend(data, info_data)
      window.setTimeout(() => {
        this.trigger(events.DATA_LOADED, data)
      }, 1000)
    })
  }

  this.on(events.SELECT_THUMB, (thumbs, value) => {
    thumbs.forEach(thumb => {
      thumb.selected = (value) ? true : false;
    })
    this.trigger(events.DATA_UPDATED)
  })
}

export default PteStore;
