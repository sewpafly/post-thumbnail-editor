(function() {
  var TimerFunc, determineAspectRatio, gcd;
  var __bind = function(fn, me){ return function(){ return fn.apply(me, arguments); }; };
  TimerFunc = (function() {
    function TimerFunc(fn, timeout) {
      this.fn = fn;
      this.timeout = timeout;
      this.doFunc = __bind(this.doFunc, this);
      this.timer = null;
    }
    TimerFunc.prototype.doFunc = function(e) {
      window.clearTimeout(this.timer);
      this.timer = window.setTimeout(this.fn, this.timeout);
      return true;
    };
    return TimerFunc;
  })();
  window.randomness = function() {
    return Math.floor(Math.random() * 1000001).toString(16);
  };
  window.debugTmpl = function(data) {
    log(data);
    return true;
  };
  gcd = function(a, b) {
    if (a === 0) {
      return b;
    }
    while (b > 0) {
      if (a > b) {
        a = a - b;
      } else {
        b = b - a;
      }
    }
    if (a < 0 || b < 0) {
      return null;
    }
    return a;
  };
  determineAspectRatio = function(current_ar, size) {
    var crop, gc, height, tmp_ar, width, _ref;
    _ref = thumbnail_info[size], crop = _ref.crop, width = _ref.width, height = _ref.height;
    crop = +crop;
    width = +width;
    height = +height;
    gc = gcd(width, height);
    if ((crop != null) && crop > 0) {
      tmp_ar = null;
      if ((width != null) > 0 && (height != null) > 0) {
        if (gc != null) {
          tmp_ar = "" + (width / gc) + ":" + (height / gc);
        } else {
          tmp_ar = "" + width + ":" + height;
        }
      }
      if ((current_ar != null) && (tmp_ar != null) && tmp_ar !== current_ar) {
        throw "Too many Aspect Ratios. Disabling";
      }
      return current_ar = tmp_ar;
    }
  };
  jQuery(document).ready(function($) {
    /* Callback for resizing images (Stage 1 to 2) */    var checkAllSizes, enableRowFeatures, iasSetAR, ias_defaults, ias_instance, onConfirmImages, onResizeImages, pteCheckHandler, reflow, uncheckAllSizes;
    $('#pte-submit').click(function(e) {
      var scale_factor, selection, submit_data;
      log("Clicked Submit...");
      selection = ias_instance.getSelection();
      scale_factor = $('#pte-sizer').val();
      submit_data = {
        'id': $('#pte-post-id').val(),
        'action': 'pte_ajax',
        'pte-action': 'resize-images',
        'pte-sizes[]': $('.pte-size').filter(':checked').map(function() {
          return $(this).val();
        }).get(),
        'x': Math.floor(selection.x1 / scale_factor),
        'y': Math.floor(selection.y1 / scale_factor),
        'w': Math.floor(selection.width / scale_factor),
        'h': Math.floor(selection.height / scale_factor)
      };
      log(submit_data);
      ias_instance.setOptions({
        hide: true,
        x1: 0,
        y1: 0,
        x2: 0,
        y2: 0
      });
      $('#pte-submit').attr('disabled', true);
      $.getJSON(ajaxurl, submit_data, onResizeImages);
      return true;
    });
    onResizeImages = function(data, status, xhr) {
      /* Evaluate data */      log(data);
      if ((data.error != null) && !(data.thumbnails != null)) {
        alert(data.error);
        return;
      }
      $('#stage1').animate({
        left: -$(window).width()
      }, 500, 'swing', function() {
        $(this).hide();
        $('#stage2').html($('#stage2template').tmpl(data)).show(0, function() {
          $(this).animate({
            left: 0
          }, 500);
          return true;
        });
        return true;
      });
      return false;
    };
    /* Callback for Stage 2 to 3 */
    $('#pte-confirm').live('click', function(e) {
      var submit_data, thumbnail_data;
      log("Confirming");
      thumbnail_data = {};
      $('.pte-confirm').filter(':checked').each(function(i, elem) {
        var size;
        size = $(elem).val();
        return thumbnail_data[size] = $("\#pte-" + size + "-file").val();
      });
      submit_data = {
        'id': $('#pte-post-id').val(),
        'action': 'pte_ajax',
        'pte-action': 'confirm-images',
        'pte-nonce': $('#pte-nonce').val(),
        'pte-confirm': thumbnail_data
      };
      log(submit_data);
      return $.getJSON(ajaxurl, submit_data, onConfirmImages);
    });
    onConfirmImages = function(data, status, xhr) {
      log(data);
      $('#stage2').animate({
        left: -$(window).width()
      }, 500, 'swing', function() {
        $(this).hide();
        $('#stage3').html($('#stage3template').tmpl(data)).show(0, function() {
          $(this).animate({
            left: 0
          }, 500);
          return true;
        });
        return true;
      });
      return false;
    };
    /* Set the height of the options */
    reflow = new TimerFunc(function() {
      var offset, window_height;
      log("reflow called...");
      offset = $("#pte-sizes").offset();
      window_height = $(window).height() - offset.top - 2;
      return $("#pte-sizes").height(window_height);
    }, 100);
    $(window).resize(reflow.doFunc).load(reflow.doFunc);
    pteCheckHandler = new TimerFunc(function() {
      var ar, selected_elements;
      ar = null;
      selected_elements = $('.pte-size:checked').each(function(i, elem) {
        try {
          ar = determineAspectRatio(ar, $(elem).val());
        } catch (error) {
          ar = null;
          if (ar !== ias_instance.getOptions().aspectRatio) {
            alert(error);
          }
          return false;
        }
        return true;
      });
      iasSetAR(ar);
      ias_defaults.onSelectEnd(null, ias_instance.getSelection());
      return true;
    }, 50);
    $('.pte-size').click(pteCheckHandler.doFunc);
    /* Select ALL|NONE */
    uncheckAllSizes = function(e) {
      var elements, _ref, _ref2;
      if (e != null) {
        e.preventDefault();
      }
      elements = (_ref = (_ref2 = e.data) != null ? _ref2.selector : void 0) != null ? _ref : '.pte-size';
      return $(elements).filter(':checked').click();
    };
    checkAllSizes = function(e) {
      var elements, _ref, _ref2;
      if (e != null) {
        e.preventDefault();
      }
      elements = (_ref = e != null ? (_ref2 = e.data) != null ? _ref2.selector : void 0 : void 0) != null ? _ref : '.pte-size';
      return $(elements).not(':checked').click();
    };
    $("#pte-selectors .all").click(checkAllSizes);
    $("#pte-selectors .none").click(uncheckAllSizes).click();
    $('#stage2').delegate('#pte-stage2-selectors .all', 'click', {
      selector: '.pte-confirm'
    }, checkAllSizes);
    $('#stage2').delegate('#pte-stage2-selectors .none', 'click', {
      selector: '.pte-confirm'
    }, uncheckAllSizes);
    /* Enable imgareaselect plugin */
    ias_defaults = {
      keys: true,
      minWidth: 3,
      minHeight: 3,
      handles: true,
      zIndex: 1200,
      instance: true,
      onSelectEnd: function(img, s) {
        if (s.width && s.width > 0 && s.height && s.height > 0 && $('.pte-size').filter(':checked').size() > 0) {
          log("Enabling button");
          return $('#pte-submit').removeAttr('disabled');
        } else {
          log("Disabling button");
          return $('#pte-submit').attr('disabled', true);
        }
      }
    };
    ias_instance = $('#pte-image img').imgAreaSelect(ias_defaults);
    iasSetAR = function(ar) {
      log("setting aspectRatio: " + ar);
      ias_instance.setOptions({
        aspectRatio: ar
      });
      return ias_instance.update();
    };
    $('#pte-loading').hide().ajaxStart(function() {
      log("ajaxStart");
      return $(this).fadeIn(200);
    }).ajaxStop(function() {
      return $(this).fadeOut(200);
    });
    enableRowFeatures = function($elem) {
      $elem.delegate('tr', 'click', function(e) {
        if (e.target.type !== 'checkbox') {
          $(':checkbox', this).click();
        }
        return true;
      });
      return $elem.delegate(':checkbox', 'click', function(e) {
        if (this.checked || $(this).is(':checked')) {
          $(this).parents('tr').first().removeClass('selected');
        } else {
          $(this).parents('tr').first().addClass('selected');
        }
        return true;
      });
    };
    enableRowFeatures($('#stage2'));
    enableRowFeatures($('#stage1'));
    window.goBack = function(e) {
      if (e != null) {
        e.preventDefault();
      }
      return $('#stage2').animate({
        left: 1200
      }, 500, 'swing', function() {
        $(this).hide();
        return $('#stage1').show(0, function() {
          return $(this).animate({
            left: 0
          }, 500, 'swing', function() {
            var delete_options;
            log("okay cleanup");
            delete_options = {
              "id": $('#pte-post-id').val(),
              'action': 'pte_ajax',
              'pte-action': 'delete-images',
              'pte-nonce': $('#pte-delete-nonce').val()
            };
            return $.ajax({
              url: ajaxurl,
              data: delete_options,
              global: false,
              dataType: "json",
              success: function(data, status, xhr) {
                if (data.error != null) {
                  return log(data.error);
                } else {
                  return log("Deleted tmp files");
                }
              }
            });
          });
        });
      });
    };
    return true;
  });
}).call(this);
