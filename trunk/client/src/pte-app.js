'use strict';

var riot = require('riot');
var pte = require('./pte-constants');
var rc = require('riotcontrol');
var $ = require('jQuery');

let html = `
<div id="pte-spinner"><i class="fa fa-5x fa-spin fa-refresh"></i></div>
<pte-editor></pte-editor>
<pte-thumbnail-selector store={opts.store}></pte-thumbnail-selector>
<pte-instructions></pte-instructions>
<pte-confirmation></pte-confirmation>
`

module.exports = riot.tag('pte-app', html, function (opts) {
        this.loaded = false;

        this.on('mount', () => {
            $('#pte-spinner').dialog({
                closeOnEscape: false,
                modal: true,
                width: 80,
                height: 114,
                resizable: false,
                draggable: false,
                dialogClass: 'no-title'
            })
            rc.trigger(pte.events.APP_STARTED);
        })

        rc.on(pte.events.DATA_LOADED, () => {
            $('#pte-spinner').dialog('close')
            console.log("DATA_LOADED received");
            this.loaded = true;
            this.update();
        });
    });
