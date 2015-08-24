'use strict';

var events = require('./pte-constants')
var riot = require('riot')
var $ = require('jQuery')

let html = `
<pte-editor-menu></pte-editor-menu>
<div id="pte-editor-main">
  <div id="image">
    <img src="{ img.src }" alt="">
  </div>
  <div id="actions" class="wp-core-ui">
    <button class="button-primary">CropText</button>
  </div>
</div>
`

let tag = riot.tag('pte-editor', html, function (opts) {

  let mounted = false

  this.img = {
    src: 'http://wordpress/wp-content/uploads/2015/03/pin-up_motorcycle_fill.jpg',
    width: '500',
    height: '340',
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
    var $imgDiv = $('#image', this.root)
    var width = $imgDiv.width()
    var height = $(window).innerHeight()
        - $('#actions', this.root).outerHeight(true)
        - 40;
    $imgDiv.height(height)

    if ( ! mounted ) {
      return
    }

    $('img', this.root).position({
      my: 'center center',
      at: 'center center',
      of: '#image',
      collision: 'none'
    })
  }

  this.on('mount', () => {
    console.log('set the image dimensions of the image')
    mounted = true
  })

  this.on('update', () => {
    console.log('pte-editor updated');
    this.resize();
  })

  $(window).resize(() => {
    this.resize()
  })

});

export default tag
