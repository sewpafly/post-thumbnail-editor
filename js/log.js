(function() {
  window.log = function(obj) {
    var console, name, names, _i, _len;
    if (!window.console) {
      names = ["log", "debug", "info", "warn", "error", "assert", "dir", "dirxml", "group", "groupEnd", "time", "timeEnd", "count", "trace", "profile", "profileEnd"];
      for (_i = 0, _len = names.length; _i < _len; _i++) {
        name = names[_i];
        window.console[name] = alert;
      }
    }
    if (!console) {
      console = window.console;
    }
    return console.log(obj);
  };
}).call(this);
