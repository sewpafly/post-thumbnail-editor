define [
   'cs!jquery'
   'cs!settings'
   'jcrop'
], ($, settings) ->
   # Adapted from underscore (underscorejs.org) source
   throttle = (func, wait) ->
      [context, args, timeout, result] = []
      previous = 0
      later = ->
         previous = new Date
         timeout = null
         result = func.apply(context, args)
         return null
      ->
         now = new Date
         remaining = wait - (now - previous)
         context = this
         args = arguments
         if (remaining <= 0)
           clearTimeout(timeout)
           timeout = null
           previous = now
           result = func.apply(context, args)
         else if (!timeout)
           timeout = setTimeout(later, remaining)
         return result


   jcrop = null
   crop_options =
      # TODO
      # Make the bgColor an option (dropdown, black or transparent)
      #bgColor: 'green' # make it an option?
      onChange: throttle( ->
         #jcrop = this
         changeBgColor = (color) ->
            if color != bgColor
               jcrop.setOptions
                  bgColor: color
            return

         isBad = ->
            compare = (constraint, dimension) ->
               if ! constraint?
                  return true
               if constraint is 0
                  return false
               constraint < dimension
            # If both dimensions are required
            # and if the constraint_w is less than the actual cropped width then we are no longer constrained
            # and if the constraint_h is less than the actual cropped height then we are no longer constrained
            if is_ar_enforced and compare(crop_w, w) and compare(crop_h, h)
               return false # GOOD
            # If only one dimension is required
            # if the constraint_w is less than the actual cropped width 
            #  - or - 
            # if the constraint_h is less than the actual cropped height then we are no longer constrained
            if !is_ar_enforced and compare(crop_w, w) or compare(crop_h, h)
               return false # GOOD
            return true



         cropConstraints = this.getOptions().cropConstraints
         if !cropConstraints
            changeBgColor 'black'
            return

         [crop_w, crop_h, is_ar_enforced] = cropConstraints
         {bgColor} = this.getOptions()
         {w, h} = this.tellSelect()
         #console.log w, h, crop_w, crop_h

         if isBad()
            changeBgColor 'red'
         else
            changeBgColor 'green'
         return
      , 50)


      onRelease: ->
         {x, y, w, h, x2, y2} = this.tellSelect()
         if isNaN x
            return
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
