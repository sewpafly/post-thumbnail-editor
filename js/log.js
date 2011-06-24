(function() {
  window.log = function(obj) {
    if (!window.debug_enabled) {
      return true;
    }
    try {
      console.log(obj);
    } catch (error) {
      alert(obj);
    }
    return true;
  };
}).call(this);
