'use strict';

var $ = require('jQuery')

function PteJcrop() {
  this.api = null

  function updateJcropOnEnd(e,s,c){
    let color = 'green'
    if (c.w < 10 && c.h < 10) {
      api.removeSelection(api.ui.selection)
      $('.jcrop-shades > div', api.container).width(0).height(0)
      color = 'black'
    }
    if (c.w < minWidth || c.h < minHeight) {
      color = 'red'
    }
    setJcropColor(color)
  }

  function setJcropColor(color){
    api.setOptions({
      bgColor: color
    })
  }

  this.init = ($el, options) => {
    $el.Jcrop(options, function(){
      api = this
      api.container.on('cropend', updateJcropOnEnd)
    })
  }

  this.setAR = function (ratio) {
    if (!api) {
      console.log('jcrop not defined')
      return
    }

    ratio = ratio ? ratio : 0
    api.setOptions({
      aspectRatio: ratio
    })
  }

  this.update = function() {
    if (api && api.ui.selection)
      updateJcropOnEnd(null,null,api.getSelection())
  }
}

let jcrop = new PteJcrop()

export default jcrop
