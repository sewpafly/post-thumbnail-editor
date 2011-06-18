(function() {
  var TimerFunc;
  TimerFunc = (function() {
    function TimerFunc(fn, timeout) {
      this.fn = fn;
      this.timeout = timeout;
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
  jQuery(document).ready(function($) {
    var checkAllSizes, done, enableRowFeatures, iasSetAR, ias_defaults, ias_instance, onResizeImages, proxy_reflow, pteCheckHandler, randomness, reflow, uncheckAllSizes;
    onResizeImages = function(data, status, xhr) {
      /* Evaluate data */      log(data);
      if ((data.error != null) && !((data != null ? data.thumbnails : void 0) != null)) {
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
    randomness = function() {
      return Math.floor(Math.random() * 1000001).toString(16);
    };
    ias_defaults = {
      instance: true,
      onSelectEnd: function(img, s) {
        if (s.width && s.width > 0 && s.height && s.height > 0 && $('.pte-size').filter(':checked').size() > 0) {
          log("Enabling button");
          return $('#pte-submit').removeAttr('disabled');
        } else {
          log("Disabling button");
          return $('#pte-submit').attr('disabled', true);
        }
      },
      handles: true,
      zIndex: 1200
    };
    /*
    	Set the height of the options
    	*/
    reflow = new TimerFunc(function() {
      var offset;
      offset = $("#pte-sizes").offset();
      return $("#pte-sizes").height($(window).height() - offset.top - 2);
    }, 100);
    proxy_reflow = $.proxy(reflow.doFunc, reflow);
    $(window).resize(proxy_reflow).load(proxy_reflow);
    /*
    	Select ALL|NONE
       */
    uncheckAllSizes = function(e) {
      var elements, _ref, _ref2;
      e.preventDefault();
      elements = (_ref = (_ref2 = e.data) != null ? _ref2.selector : void 0) != null ? _ref : '.pte-size';
      return $(elements).filter(':checked').click();
    };
    checkAllSizes = function(e) {
      var elements, _ref, _ref2;
      e.preventDefault();
      elements = (_ref = (_ref2 = e.data) != null ? _ref2.selector : void 0) != null ? _ref : '.pte-size';
      return $(elements).not(':checked').click();
    };
    $("#pte-selectors .all").click(checkAllSizes);
    $("#pte-selectors .none").click(uncheckAllSizes);
    $('#stage2').delegate('#pte-stage2-selectors .all', 'click', {
      selector: '.pte-confirm'
    }, checkAllSizes);
    $('#stage2').delegate('#pte-stage2-selectors .none', 'click', {
      selector: '.pte-confirm'
    }, uncheckAllSizes);
    pteCheckHandler = new TimerFunc(function() {
      var crop, height, selected_elements, width, _ref;
      selected_elements = $('.pte-size:checked');
      if (selected_elements.size() === 1) {
        _ref = thumbnail_info[selected_elements.val()], crop = _ref.crop, width = _ref.width, height = _ref.height;
        iasSetAR(width, height, crop);
      } else {
        iasSetAR();
      }
      ias_defaults.onSelectEnd(null, ias_instance.getSelection());
      return true;
    }, 50);
    $('.pte-size').click($.proxy(pteCheckHandler.doFunc, pteCheckHandler));
    /*
    	Enable imgareaselect plugin
    	*/
    ias_instance = $('#pte-image img').imgAreaSelect(ias_defaults);
    iasSetAR = function(width, height, crop) {
      var ar;
      ar = null;
      if (crop !== null && (crop === true || crop > 0)) {
        if ((width != null) > 0 && (height != null) > 0) {
          ar = "" + width + ":" + height;
        }
      }
      log("setting aspectRatio: " + ar);
      ias_instance.setOptions({
        aspectRatio: ar
      });
      return ias_instance.update();
    };
    $('#pte-loading').hide().ajaxStart(function() {
      return $(this).fadeIn(200);
    }).ajaxStop(function() {
      return $(this).fadeOut(200);
    });
    $('#pte-submit').click(function(e) {
      var scale_factor, selection, submit_data;
      log("Clicked Submit...");
      selection = ias_instance.getSelection();
      scale_factor = $('#pte-sizer').val();
      submit_data = {
        'id': $('#pte-post-id').val(),
        'action': 'pte_ajax',
        'pte_action': 'resize_images',
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
    $('#pte-confirm').live('click', function(e) {
      var submit_data, _ref;
      log("Confirming");
      submit_data = {
        'id': $('#pte-post-id').val(),
        'action': 'pte_ajax',
        'pte_action': 'confirm_images',
        'pte_nonce': $('#pte-nonce').val(),
        'pte-confirm[]': $('.pte-confirm').filter(':checked').map(function() {
          return $(this).val();
        }).get()
      };
      log(submit_data);
      if (((_ref = submit_data['pte-confirm[]']) != null ? _ref.length : void 0) < 1) {
        return done();
      }
    });
    enableRowFeatures = function($elem) {
      $elem.delegate('tr', 'click', function(e) {
        if (e.target.type !== 'checkbox') {
          $(':checkbox', this).click();
        }
        return true;
      });
      return $elem.delegate(':checkbox', 'click', function(e) {
        $(this).parents('tr').first().toggleClass('selected');
        return true;
      });
    };
    enableRowFeatures($('#stage2'));
    enableRowFeatures($('#stage1'));
    window.goBack = function(e) {
      if (e != null) {
        e.preventDefault();
      }
      $('#stage2').animate({
        left: 1200
      }, 500, 'swing', function() {
        $(this).hide();
        return $('#stage1').show(0, function() {
          $(this).animate({
            left: 0
          }, 500);
          return true;
        });
      });
      return true;
    };
    done = function() {
      return alert("all done...");
    };
    return true;
  });
}).call(this);
