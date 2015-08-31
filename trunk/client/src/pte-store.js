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

  let id = null

  this.init = () => {
    id = /(id=\d+)/.exec(window.location.search)[0]
    getData()
  }

  let getData = () => {
    let info_url = getUrl('get-metadata', id)
    $.getJSON(info_url)
    .done((info_data) => {
      $.extend(data, info_data)
      this.trigger(events.DATA_LOADED, data)
      //window.setTimeout(() => {
      //  this.trigger(events.DATA_LOADED, data)
      //}, 1000)
    })
  }

  let crop = () => {
    let crop_url = getUrl('resize-thumbnails', {
      x: data.x,
      y: data.y,
      w: data.w,
      h: data.h,
      sizes: data.thumbnails
      .filter( t => { t.selected })
      .map(t => { t.size.name })
      .join(',')
    })
    $.getJSON(crop_url)
    .done((crop_info) => {
      this.trigger(events.CROPPED, crop_info)
    })
  }

  this.on(events.SELECT_THUMB, (thumbs, value) => {
    thumbs.forEach(thumb => {
      thumb.selected = (value) ? true : false;
    })
    this.trigger(events.DATA_UPDATED)
  })

  this.on(events.CROP, (selection) => {
  })
}

export default PteStore;
