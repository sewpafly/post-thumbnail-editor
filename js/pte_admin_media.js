// Console.log
if (!window.console && !console.firebug)
{
    var names = ["log", "debug", "info", "warn", "error", "assert", "dir", "dirxml",
    "group", "groupEnd", "time", "timeEnd", "count", "trace", "profile", "profileEnd"];

    window.console = {};
    for (var i = 0; i < names.length; ++i)
        window.console[names[i]] = function() {}
}

jQuery(document).ready(function($){
   var pte_timeout = 1000;
   var pte_max_attempts = 5;
   var ias_instance = null;

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
      console.log(selection);

      var id = getID();
      if (!id) return false;

      var size = $("#pte-thumb").val()
      console.log("Clicked: " + size);
      if (!size || size == "" || size.length == 0) return false;

      // Our script needs to know the REAL x1, y1 and x2, y2
      // so it can crop and then scale...
      var scale_factor = $('#imgedit-sizer-'+id).val();
      var data = { 'action': 'pte_ajax'
                 , 'pte_action': 'resize-img'
                 , 'id': id
                 , 'size': size
                 , 'x1': selection.x1/scale_factor
                 , 'y1': selection.y1/scale_factor
                 , 'x2': selection.x2/scale_factor
                 , 'y2': selection.y2/scale_factor
                 , '_ajax_nonce': $('body').data('nonce')
                 }
      console.log(data);
      $.get( ajaxurl
           , data
           , function(data, txtstatus, xhr){
              console.log(data);
           }
           , 'text'
      );
   }

   function createPteDisplay(){
      var pte_display = $('#pte-display');
      if (pte_display.size() > 0){
         pte_display.find('#pte-edit').empty();
         pte_display.find('#pte-current').empty();
         return;
      }
      var pte_display_html = $('<div id="pte-display">'
         + '<div id="pte-edit"></div>'
         + '<div id="pte-info">Currently: (it\'s scaled to fit horizontally)...</div>'
         + '<div id="pte-current"></div>'
         + '<div id="pte-controls">'
         + '<input type="button" id="pte-stop" name="pte-stop" class="button" value="Cancel"/>'
         + '<input type="button" id="pte-save" name="pte-save" class="button-primary" value="Save"/>'
         + '</div></div>')
      .appendTo($('body'))
      .find("#pte-stop").click($.fancybox.close).end()
      .find("#pte-save").click(savePostThumbnail)
   }

   function getID(){
      try{
         //var id = "pte-" + $(".image-editor").attr("id");
         var id = /\d+$/.exec($(".image-editor").attr("id"))[0];
         console.log(id);
         return id;
      }
      catch(e){
         console.log("oops");
      }
      return null;
   }
   
   // sent from click on pte-submit event
   function createPostThumbnailEdit(event){
      var size = $("#pte-thumb").val()
      console.log("Clicked: " + size);
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
                         });

      $.get( ajaxurl
         , { 'action': 'pte_ajax'
           , 'pte_action': 'get-image-data'
           , 'id': id
           , 'size': size
         }
         , function(data,txt,xhr){
            console.log(data);
            $('body').data('nonce', data.nonce);
            var img = $('<img src="' + data.url + '">').appendTo('#pte-current');
            var img_width = sizes[size]['width'];
            if (img_width < parseInt(img.width())){
               console.log("Smallen [sic] the width");
               img.css('width', img_width);
            }
         }
         , 'json'
      );

      $('<a class="inline" href="#pte-display">Gnarly</a>')
         .fancybox(
            { 'onStart': function(){ 
                $('#pte-display').show();
                $.fancybox.center();
              }
              , 'onClosed': function(){ 
                if (ias_instance){
                  //ias_instance.setOptions({show: false});
                  ias_instance.setOptions({ show: false
                      , disable: true
                      , hide: true
                      , remove: true
                  });
                  //ias_instance.setSelection(null);
                  ias_instance.update();
                }
                $('#pte-display').hide()
              }
            }
         )
         .trigger('click')
      //$('<a class="inline" href="#pte-display">Gnarly</a>').fancybox().trigger('click');

      console.log("done with create Edit");
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

      console.log("Attempts remaining: " + pte_max_attempts);
      var editor = $(".imgedit-settings");
      if (editor.size() < 1 && pte_max_attempts-- < 0){
         console.log("Tried too many times, stopping");
         return;
      }
      else if (editor.size() < 1){
         console.log("No editor... Try again.");
         window.setTimeout(getImageEditor, pte_timeout);
         return;
      }
      
      console.log("Found: " + editor.size());

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
        console.log("Removing that bastard");
        editbox.remove().find("#pte-thumb").empty();
      });

      // Get list of available sizes
      console.log("ADMINAJAX: " + ajaxurl);
      $.get(ajaxurl 
         , { 'action': 'pte_ajax' 
           , 'pte_action': 'get-alternate-sizes'} 
         , function(data, status, xhr){
            console.log(data);
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
               console.log("ERROR: " + e);
            }
         }
         , 'json'
      );
   }

   // Listens for "Edit" event
   $('input[id^="imgedit-open"]').bind('click', function(){
      console.log("pushed the button");
      getImageEditor();
   });
});
