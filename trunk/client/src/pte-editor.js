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
      <img src="{ data && data.url || '' }" alt="" width="{ width }" height="{ height }">
    </div>
  </div>
  <div id="actions" class="wp-core-ui">
    <button class="button-primary">CropText</button>
  </div>
</div>
`

let jcrop = null
let minWidth = 0
let minHeight = 0

function getLargestBox (ratio, w, h) {
  if ((w/h) > ratio)
    return [h * ratio, h]
  else
    return [w, w / ratio]
}

function setJcropAR(ratio) {
  if (!jcrop) {
    console.log('jcrop not defined')
    return
  }

  ratio = ratio ? ratio : 0
  jcrop.setOptions({
    aspectRatio: ratio
  })
}

function updateJcropOnEnd(e,s,c){
  let color = 'green'
  if (c.w < 10 && c.h < 10) {
    jcrop.removeSelection(jcrop.ui.selection)
    $('.jcrop-shades > div', jcrop.container).width(0).height(0)
    color = 'black'
  }
  if (c.w < minWidth || c.h < minHeight) {
    color = 'red'
  }
  setJcropColor(color)
}

function setJcropColor(color){
  jcrop.setOptions({
    bgColor: color
  })
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
    // change the image src/width/height
    this.update({data: data})

    $('img', this.root).Jcrop({
      boxHeight: this.height,
      boxWidth: this.width
    }, function(){
      jcrop = this
      jcrop.container.on('cropend', updateJcropOnEnd)
    })
  })

  rc.on(events.DATA_UPDATED, () => {
    // Set the aspect ratio?
    let thumbs = this.data.thumbnails.filter(t => t.selected)

    setJcropAR(findAR(thumbs))

    ;[minWidth, minHeight] = [0,0]

    thumbs.forEach(t => {
      minWidth = Math.max(t.size.width, minWidth)
      minHeight = Math.max(t.size.height, minHeight)
    })

    if (jcrop.ui.selection)
      updateJcropOnEnd(null, null, jcrop.getSelection())
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
