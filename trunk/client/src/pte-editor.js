'use strict';

var $ = require('jQuery')
var rc = require('riotcontrol')
var riot = require('riot')
var jcrop = require('./pte-jcropper')
var events = require('./pte-constants').events

let html = `
<pte-editor-menu></pte-editor-menu>
<div id="pte-editor-main">
  <div id="pte-editor-image">
    <div id="image-wrapper">
      <img src="{ data && data.url || '' }" alt="" width="{ width }" height="{ height }">
    </div>
  </div>
  <div id="actions" class="wp-core-ui">
    <button class="button-primary" onclick="{ crop }">CropText</button>
  </div>
</div>
`

function getLargestBox (ratio, w, h) {
  if ((w/h) > ratio)
    return [h * ratio, h]
  else
    return [w, w / ratio]
}


function findAR(thumbs) {
  let ar = null
  for (let i=0; i < thumbs.length; ++i) {
    if ( thumbs[i].size.crop != true || thumbs[i].size.height == 0 || thumbs[i].size.width == 0 ) {
      return 0
    }
    let tmp_ar = thumbs[i].size.width / thumbs[i].size.height
    if (!ar)
      ar = tmp_ar
    else {
      if (ar - 0.01 > tmp_ar || ar + 0.01 < tmp_ar) {
        return 0
      }
    }
  }
  return ar
}

let tag = riot.tag('pte-editor', html, function (opts) {

  let mounted = false

  this.crop = (e) => {
    rc.trigger(events.CROP, jcrop.getSelection())
  }

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

    let naturalWidth = this.data.width
    let naturalHeight = this.data.height
    let tw = Math.min($imgDiv.width(), naturalWidth)
    let th = Math.min(height, naturalHeight)
    let [w, h] = getLargestBox(naturalWidth/naturalHeight, tw, th)
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
    this.update({data: data})
    jcrop.init($('img', this.root), {boxHeight: this.height, boxWidth: this.width})
  })

  rc.on(events.DATA_UPDATED, () => {
    // Set the aspect ratio?
    let thumbs = this.data.thumbnails.filter(t => t.selected)

    jcrop.setAR(findAR(thumbs))

    let [minWidth, minHeight] = [0,0]

    thumbs.forEach(t => {
      minWidth = Math.max(t.size.width, minWidth)
      minHeight = Math.max(t.size.height, minHeight)
    })

    jcrop.update({minWidth,minHeight})
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
