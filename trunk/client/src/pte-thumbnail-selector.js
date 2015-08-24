'use strict';

var riot = require('riot')
var rc = require('riotcontrol')
var events = require('./pte-constants').events
var $ = require('jQuery')

let html = `<div>THUMBNAIL_SELECTOR</div>
`

let tag = riot.tag('pte-thumbnail-selector', html, function (opts) {
  this.on('mount', () => {
    $(this.root).dialog({autoOpen: false})
  })
  rc.on([events.DATA_LOADED, events.TOGGLE_THUMB_SELECTOR].join(' '), () => {
    if ($(this.root).dialog('isOpen'))
      $(this.root).dialog('close')
    else
      $(this.root).dialog('open')

  })
  this.on('update', () => {
    console.log('pte-thumbnail-selector updated');
  })
})

export default tag
