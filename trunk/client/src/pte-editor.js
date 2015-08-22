'use strict';

var riot = require('riot')

let html = `
<div>
  <div id="image"></div>
  <div id="actions"></div>
</div>
<pte-editor-menu></pte-editor-menu>
`

let tag = riot.tag('pte-editor', html, function (opts) {
  this.on('mount', () => {
    console.log('set the image dimensions of the image')
  })
  this.on('update', () => {
    console.log('pte-editor updated');
  })
});

export default tag
