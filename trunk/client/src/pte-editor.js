'use strict';

var events = require('./pte-constants').events
var riot = require('riot')
var rc = require('riotcontrol')
var $ = require('jQuery')

let html = `
<pte-editor-menu></pte-editor-menu>
<div id="pte-editor-main">
  <div id="pte-editor-image">
    <div id="image-wrapper">
      <img src="{ data && data.img.url || '' }" alt="" width="{ width }" height="{ height }">
    </div>
  </div>
  <div id="actions" class="wp-core-ui">
    <button class="button-primary">CropText</button>
  </div>
</div>
`

let jcrop = null
let jcrop_options = {}

function getLargestBox (ratio, w, h) {
  if ((w/h) > ratio)
    return [h * ratio, h]
  else
    return [w, w / ratio]
}

let tag = riot.tag('pte-editor', html, function (opts) {

  let mounted = false

  this.resize = () => {
    /*
     * Determine how big the image pane can be.
     * HEIGHT:
     * 40px (2 x 20px margin for editor)
     * - height of #actions
     * WIDTH:
     * should just be the width of #pte-editor-main
     */
    var $imgDiv = $('#pte-editor-image', this.root)
    var height = $(window).innerHeight()
        - $('#actions', this.root).outerHeight(true)
        - 40;
    $imgDiv.height(height)

    if ( ! mounted || ! this.data) {
      return
    }

    let tw = Math.min($imgDiv.width(), this.data.img.width)
    let th = Math.min(height, this.data.img.height)
    let [w, h] = getLargestBox(this.data.img.width/this.data.img.height, tw, th)
    this.width = w
    this.height = h

    $('#image-wrapper', this.root)
    .width(w)
    .height(h)
    .position({
      my: 'center center',
      at: 'center center',
      of: '#pte-editor-image',
      collision: 'none'
    })
  }

  rc.on(events.DATA_LOADED, (data) => {
    // change the image src/width/height
    this.data = data
    this.update()

    $('img', this.root).Jcrop({
      boxHeight: this.height,
      boxWidth: this.width
    }, function(){
      jcrop = this
      jcrop.container.on('cropend', (e,s,c) => {
        console.log([e,s,c])
      })
    })
  })

  rc.on(events.DATA_UPDATED, () => {
    // Set the aspect ratio?
    let thumbs = this.data.thumb.filter(t => {
      return t.selected
    })
  })

  this.on('mount', () => {
    console.log('set the image dimensions of the image')
    mounted = true
  })

  this.on('update', () => {
    console.log('pte-editor updated');
    this.resize();
  })

  $(window).resize(() => {
    this.update()
  })

});

export default tag
