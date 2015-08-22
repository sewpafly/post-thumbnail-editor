'use client';

var riot = require('riot');
var events = require('./pte-constants.js').events;

let PteStore = function () {
    riot.observable(this);

    (global || window).setTimeout(() => {
        console.log('Trigger DATA_LOADED');
        this.trigger(events.DATA_LOADED);
    }, 5000);
}

export default PteStore;
