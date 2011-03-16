// log
function log(obj){
   if (!window.console || !console.firebug)
   {
      var names = ["log", "debug", "info", "warn", "error", "assert", "dir", "dirxml",
          "group", "groupEnd", "time", "timeEnd", "count", "trace", "profile", "profileEnd"];

      window.console = {};
      for (var i = 0; i < names.length; ++i)
         window.console[names[i]] = function() {}
   }
   if (!console) console = window.console;

   //console.log(obj);
   //alert(obj);
}

jQuery(document).ready(function($){
   var pte_timeout = 1000;
   var pte_max_attempts = 50;
   var ias_instance = null;

   /* 
    * Entry to our code
    * Override the imgEdit.open function
    */
   if (imageEdit.open){
      imageEdit.oldopen = imageEdit.open;
      imageEdit.open = function(id, nonce){
         getImageEditor();
         imageEdit.oldopen(id,nonce);
      }
   }
     
   function imgDebug(img, s){
      $('#pte-debug').html( "x1: " + s.x1 + "<br />"
                          + "y1: " + s.y1 + "<br />"
                          + "x2: " + s.x2 + "<br />"
                          + "y2: " + s.y2 + "<br />"
                          + "width: " + s.width + "<br />"
                          + "height: " + s.height + "<br />");
   }

   function closeImgAreaSelect(){
      if (ias_instance){
      ias_instance.setOptions({ show: false
         , disable: true
         , hide: true
         , remove: true
         });
      ias_instance.update();
      }
   }

   // http://en.wikipedia.org/wiki/Euclidean_algorithm#Implementations
   function gcd(a, b){
      if (a == 0) return b
      while(b > 0){
         if (a > b){
            a = a - b
         }
         else {
            b = b - a
         }
      }
      if ( a < 0 || b < 0 ) return null
      return a
   }

   function savePostThumbnail(){
      if (!ias_instance) return;
      var selection = ias_instance.getSelection();
      log(selection);

      var id = getID();
      if (!id) return false;

      var size = $("#pte-thumb").val()
      log("Clicked: " + size);
      if (!size || size == "" || size.length == 0) return false;

      // Our script needs to know the REAL x1, y1 and x2, y2
      // so it can crop and then scale...
      var scale_factor = $('#imgedit-sizer-'+id).val();
      var data = { 'action': 'pte_ajax'
                 , 'pte_action': 'resize-img'
                 , 'id': id
                 , 'size': size
                 , 'x': Math.floor(selection.x1/scale_factor)
                 , 'y': Math.floor(selection.y1/scale_factor)
                 , 'w': Math.floor(selection.width/scale_factor)
                 , 'h': Math.floor(selection.height/scale_factor)
                 , '_ajax_nonce': $('body').data('nonce')
                 }
      log(data);
      $.get( ajaxurl
           , data
           , function(data, txtstatus, xhr){
              log(data);
              if (data.error){
                alert("Sorry, we encountered an issue: " + data.error);
              }
              if (data.url){
                 closeImgAreaSelect();
                 $('#pte-display').html('<strong>Updated image</strong>: <br/>'
                    + '<img src="'+data.url+'?'+randomness()+'"/><br>'
                    + 'Reload your cache (Shift+F5 on the page you want to see the changes)'
                    + ' and everything should work...');
              }
              else
                $.fancybox.close();
           }
           , 'json'
      );
   }

   function createPteDisplay(){
      var pte_display = $('#pte-display')
         .find('#pte-edit').empty().end()
         .find('#pte-current').empty().end();
      var pte_display_html = '<div id="pte-edit"></div>'
         + '<div id="pte-info">Currently: (it\'s scaled to fit horizontally)...</div>'
         + '<div id="pte-current"></div>'
         + '<div id="pte-debug"></div>'
         + '<div id="pte-controls">'
         + '<input type="button" id="pte-stop" name="pte-stop" class="button" value="Cancel"/>'
         + '<input type="button" id="pte-save" name="pte-save" class="button-primary" value="Save"/>'
         + '</div>'
      if (pte_display.length > 0){ // Exists
         pte_display.html(pte_display_html);
      }
      else { // Doesn't exist
         pte_display = $('<div id="pte-display">')
            .html(pte_display_html)
            .appendTo($('body'))
      }
      pte_display
         .find("#pte-stop").click($.fancybox.close).end()
         .find("#pte-save").click(savePostThumbnail)
   }

   function randomness(){
      return Math.floor(Math.random()*1000001).toString(16);
   }

   function getID(){
      try{
         //var id = "pte-" + $(".image-editor").attr("id");
         var id = /\d+$/.exec($(".image-editor").attr("id"))[0];
         log(id);
         return id;
      }
      catch(e){
         log("oops");
      }
      return null;
   }
   
   // sent from click on pte-submit event
   function createPostThumbnailEdit(event){
      var size = $("#pte-thumb").val()
      log("Clicked: " + size);
      if (!size || size == "" || size.length == 0) return false;

      var id = getID();
      if (!id) return false;

      // Find the aspect ratio
      var sizes = $('body').data('sizes');
      var cd = gcd( parseInt(sizes[size]['width']) 
                  , parseInt(sizes[size]['height']));
      var ar = null;
      if (cd){
         ar = parseInt(sizes[size]['width']) / cd
            + ":"
            + parseInt(sizes[size]['height']) / cd;
      }


      // Get the images (the fake one & the thumbnail)
      //var main_img_url = $('#image-preview-'+id).attr('src');
      createPteDisplay();
      var img_preview = $('#image-preview-'+id)
      ias_instance = img_preview
         .clone(false)
         .appendTo('#pte-edit')
         .css({'height': img_preview.height()})
         //.removeAttr('onload')
         .imgAreaSelect( { handles: true
                         , zIndex: 1200
                         , instance: true
                         , aspectRatio: ar
                         , onSelectEnd: imgDebug
                         });

      $.get( ajaxurl
         , { 'action': 'pte_ajax'
           , 'pte_action': 'get-image-data'
           , 'id': id
           , 'size': size
         }
         , function(data,txt,xhr){
            log(data);
            $('body').data('nonce', data.nonce);
            var img = $('<img src="' + data.url + '?' + randomness() + '">').appendTo('#pte-current');
            var img_width = sizes[size]['width'];
            if (img_width < parseInt(img.width())){
               log("Smallen [sic] the width");
               img.css('width', img_width);
            }
         }
         , 'json'
      );

      $('<a class="inline" href="#pte-display">Gnarly</a>')
      .fancybox( { 
            // Fix for issue #1... IE8 & fancybox inline workaround...
            'href': '#pte-display'
            , 'onStart': function(){ 
               $('#pte-display').show();
               $.fancybox.center();
            }
            , 'onClosed': function(){ 
               closeImgAreaSelect();
               $('#pte-display').hide()
            }
         }
      )
      .trigger('click')
      //$('<a class="inline" href="#pte-display">Gnarly</a>').fancybox().trigger('click');

      log("done with create Edit");
      return false;
   }

   function getImageEditor(){
      // HTML to inject into the editor
      // This might need to be loaded by AJAX for localization purposes
      var editbox = $("<div class='imgedit-group pte-thumbs'>"
      + "<div class='imgedit-group-top'><strong>Edit Post Thumbnails</strong></div>"
      + "<select id='pte-thumb' name='pte-thumb'>"
      + "<option value=0 disabled selected>Choose a size to edit:</option>"
      + "</select><br/>"
      + "<input class='button-primary' type='submit' name='pte-edit-thumb' id='pte-submit' value='Edit'/>"
      + "</div");

      log("Attempts remaining: " + pte_max_attempts);
      var editor = $(".imgedit-settings");
      if (editor.size() < 1 && pte_max_attempts-- < 0){
         log("Tried too many times, stopping");
         return;
      }
      else if (editor.size() < 1){
         log("No editor... Try again.");
         window.setTimeout(getImageEditor, pte_timeout);
         return;
      }
      
      log("Found: " + editor.size());

      // Action: create image pop-up
      // Used when the user selects a post thumbnail to edit
      //  OPTION 1: Create an ajax/php script to recreate the picture/setup that already exists
      //  on the current page
      //  OPTION 2: Borrow the parameters, add the thumbnail that you're attempting to process
      //  and send the data to your script.
      //
      //  Let's try OPTION 2...
      editbox.find("#pte-submit").click(createPostThumbnailEdit);

      // Add the above HTML to the image-editor GUI
      editor.append(editbox)
      $('.imgedit-submit input.button').click(function(e){
        log("Removing that bastard");
        editbox.remove().find("#pte-thumb").empty();
      });

      // Get list of available sizes
      log("ADMINAJAX: " + ajaxurl);
      $.get(ajaxurl 
         , { 'action': 'pte_ajax' 
           , 'pte_action': 'get-alternate-sizes'} 
         , function(data, status, xhr){
            log(data);
            $('body').data('sizes',data.sizes);
            try {
               $.each(data.sizes, function(i,elem){
                  editbox.find("#pte-thumb")
                  .append("<option value='" + i
                     + "'>" + i 
                     + ": " + elem.width + "x" + elem.height 
                     + "</option>");
               })
            }
            catch(e){
               log("ERROR: " + e);
            }
         }
         , 'json'
      );
   }

   // Listens for "Edit" event
   //$('input[id^="imgedit-open"]').bind('click', function(){
   //   log("pushed the button");
   //   getImageEditor();
   //});
});
