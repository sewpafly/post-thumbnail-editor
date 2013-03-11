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
      bgColor: 'transparent'
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
         settings.getWindowVar('post_width')
         settings.getWindowVar('post_height')
      ]

   $("#pte-preview").Jcrop crop_options, ->
      jcrop = this
      jcrop?.release()
      return
   # Block until jcrop has been loaded
   while jcrop is null
      noop = null
   return jcrop
