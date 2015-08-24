'use strict';

var events = require('./pte-constants').events
var riot = require('riot')
var rc = require('riotcontrol')
var $ = require('jQuery')

let html = `
<ul>
  <li><a title="Choose Thumbnails" onclick="{ showThumbnailSelector }">
    <i class="fa fa-bars"></i>
  </a></li>
  <li><a title="Show Options" onclick={ showOptions }>
    <i class="fa fa-cogs"></i>
  </a></li>
  <li><a title="Show Thumbnails" onclick={ showThumbnails }>
    <i class="fa fa-picture-o"></i>
  </a></li>
</ul>
`

let tag = riot.tag('pte-editor-menu', html, function (opts) {
  this.showThumbnailSelector = (e) => {
    console.log('show thumbnail selector');
    rc.trigger(events.TOGGLE_THUMB_SELECTOR);
  }

  this.on('mount', () => {
    $('a', this.root).button();
  })
});

export default tag
