define [
   'cs!jquery'
   'cs!settings'
   'jcrop'
   'domReady!'
], ($, settings) ->
   jcrop = null
   crop_options =
      # TODO
      # Make the bgColor an option (dropdown, black or transparent)
      #bgColor: 'green' # make it an option?
      onChange: ->
         [crop_w, crop_h] = this.getOptions().cropConstraints
         {bgColor} = this.getOptions()
         {w, h} = this.tellSelect()
         #console.log w, h, crop_w, crop_h
         jcrop = this
         changeBgColor = (color) ->
            if color != bgColor
               jcrop.setOptions
                  bgColor: color
            return

         if (crop_w and crop_w > w) or (crop_h and crop_h > h)
            changeBgColor 'red'
         else
            changeBgColor 'green'
         return


      onRelease: ->
         {x, y, w, h, x2, y2} = this.tellSelect()
         if x isnt 0 or
         y isnt 0 or
         w isnt 0 or
         h isnt 0 or
         x2 isnt 0 or
         y2 isnt 0
            this.setSelect [0,0,0,0,0,0]
            this.release()
         return


      trueSize: [
         settings.width
         settings.height
      ]

   jcrop = $.Jcrop "#pte-preview", crop_options
   jcrop.release()
   return jcrop
