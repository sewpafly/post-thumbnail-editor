'use strict';

var riot = require('riot')
var rc = require('riotcontrol')
var events = require('./pte-constants').events
var $ = require('jQuery')

function roundToTwo(num) {
  return +(Math.round(num + "e+2")  + "e-2");
}

function toggle (thumbs) {
  let value = true
  let items = thumbs.filter(t => {
    return t.selected
  })

  if (items.length == thumbs.length){
    value = false
  }

  rc.trigger(events.SELECT_THUMB, thumbs, value)
}


let html = `<div>
  <div class="inline qs-div">
    <h2>Quick Select <span><a href="#" onclick="{ toggleAll }">All</a></span></h2>
    <p>These thumbnails share the same aspect ratio. Clicking them will toggle their selection</p>
    <ul>
      <li each="{ ratioGroups }"><a href="" onclick="{ toggleAR }">{ parent.printRGNames(this.ratios) }</a></li>
    </ul>
  </div>
  <div class="inline table-div">
    <table class='table table-striped'>
      <thead>
        <tr>
          <th>Name</th>
          <th class="r">Width</th>
          <th class="r">Height</th>
          <th class="r">Crop</th>
        </tr>
      </thead>
      <tbody>
        <tr each="{ thumbnails }" class="{ selected: selected }" onclick="{ toggleThumb }">
          <td>{ size.label }</td>
          <td class="r">{ size.width }</td>
          <td class="r">{ size.height }</td>
          <td class="r">{ size.crop ? 'Yes' : 'No' }</td>
        </tr>
      </tbody>
    </table>
  </div>
</div>
`

let tag = riot.tag('pte-thumbnail-selector', html, function (opts) {
  this.printRGNames = (ratioGroup) => {
    return ratioGroup.map(t => {
      return t.size.label
    }).join(', ')
  }

  this.toggleAR =  (e) => {
    toggle(e.item.ratios)
    e.preventUpdate = true
  }

  this.toggleAll = (e) => {
    toggle(this.thumbnails)
    e.preventUpdate = true
  }

  this.toggleThumb = (e) => {
    rc.trigger(events.SELECT_THUMB, [e.item], ! e.item.selected)
    e.preventUpdate = true
  }

  this.on('mount', () => {
    $('h2 a').button()
    $(this.root).dialog({
      autoOpen: false,
      buttons: {
        'OK': function() {
          $(this).dialog('close')
        }
      },
      minHeight: 300,
      modal: true,
      title: 'Thumbnails — Click the ones you want to edit',
      width: 650
    })
  })

  rc.on(events.DATA_LOADED, (data) => {
    let ratios = data.thumbnails
    // Only get those with properly defined data
    .filter(t => {
      return t.size.width > 0 && t.size.height > 0 && t.size.crop
    })
    // Change the thumbnail to a ratio
    .map(t => {
      return roundToTwo(t.size.width / t.size.height)
    })
    // Keep the unique ratios
    .filter((r,i,s) => {
      return s.indexOf(r) === i
    })

    let ratioGroups = []
    ratios.forEach(r => {
      let ratioGroup = []
      data.thumbnails.forEach(t => {
        if (! t.size.width || ! t.size.height || ! t.size.crop)
          return
        if (r - 0.01 < t.size.width / t.size.height &&
            r + 0.01 > t.size.width / t.size.height) {
          ratioGroup.push(t)
        }
      })
      ratioGroups.push({ratios: ratioGroup})
    })

    this.update({thumbnails: data.thumbnails, ratioGroups: ratioGroups})
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
