(function() {
  /*
    POST-THUMBNAIL-EDITOR Script for Wordpress
  
    Hooks into the Wordpress Media Library
  */  jQuery(document).ready(function($) {
    var injectPTE, launchPTE, pte_url, timeout;
    timeout = 300;
    pte_url = "" + ajaxurl + "?action=pte_ajax&pte-action=launch&id=" + ($('#attachment_id').val());
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
      var editmenu;
      editmenu = $('p[id^="imgedit-save-target"]');
      if ((editmenu != null ? editmenu.size() : void 0) < 1) {
        log("Edit Thumbnail Menu not visible, waiting for " + timeout + "ms");
        window.setTimeout(launchPTE, timeout);
        return false;
      }
      return editmenu.append($("<a href=\"" + pte_url + "\">" + objectL10n.PTE + "</a>"));
    };
    return injectPTE();
  });
}).call(this);
