'use strict';

var riot = require('riot');
var rc = require('riotcontrol');
var css = require('./pte.less');

// Riot Tags
var pte_app = require('./pte-app.js');
var editor = require('./pte-editor.js');
var editor_menu = require('./pte-editor-menu.js');
var thumbnails = require('./pte-thumbnail-selector.js');

var PteStore = require('./pte-store.js');

let store = new PteStore();
rc.addStore(store);
riot.mount('*', {store: store});

