(function() {
  /*
    POST-THUMBNAIL-EDITOR Script for Wordpress
  
    Hooks into the Wordpress Media Library
  */  var __bind = function(fn, me){ return function(){ return fn.apply(me, arguments); }; };
  (function(pte) {
    return pte.admin = function() {
      var checkExistingThickbox, image_id, injectPTE, launchPTE, pte_url, thickbox, timeout;
      timeout = 300;
      thickbox = "&TB_iframe=true&height=500&width=750";
      image_id = null;
      pte_url = function() {
        var id;
        id = image_id || $("#attachment-id").val();
        return "" + ajaxurl + "?action=pte_ajax&pte-action=launch&id=" + id + thickbox;
      };
      checkExistingThickbox = function(e) {
        var _ref;
        log("Start PTE...");
        if ((((_ref = window.parent) != null ? _ref.tb_remove : void 0) != null)) {
          log("Closing thickbox...");
          __bind(function() {
            if (!(typeof id !== "undefined" && id !== null)) {
              alert("Couldn't find ID");
            }
            window.parent.setTimeout(tb_click, 0);
            return true;
          }, this)();
          return e.stopPropagation();
        }
      };
      injectPTE = function() {
        if (imageEdit.open != null) {
          imageEdit.oldopen = imageEdit.open;
          imageEdit.open = function(id, nonce) {
            imageEdit.oldopen(id, nonce);
            return launchPTE();
          };
        }
        return true;
      };
      launchPTE = function() {
        var $editmenu, id;
        $editmenu = $('p[id^="imgedit-save-target"]');
        id = +$editmenu.id().replace('imgedit-save-target-', '');
        if (($editmenu != null ? $editmenu.size() : void 0) < 1) {
          window.log("Edit Thumbnail Menu not visible, waiting for " + timeout + "ms");
          window.setTimeout(launchPTE, timeout);
          return false;
        }
        return $editmenu.append($("<a class=\"thickbox\" href=\"" + (pte_url()) + "\">" + objectL10n.PTE + "</a>").click(checkExistingThickbox));
      };
      return injectPTE();
    };
  })(pte);
}).call(this);
