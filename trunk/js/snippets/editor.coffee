do (jQuery) ->
   timeout = 300
   image_id = null
   pte_url = (override_id) ->
      id = override_id || image_id || jQuery("#attachment-id").val()
      _.template pteL10n.url, {'id': id}
   $getLink = (id) ->
      jQuery """<a class="thickbox" href="#{ pte_url id }">#{ pteL10n.PTE }</a>"""



   checkExistingThickbox = (e) ->
      if window.parent.frames.length > 0
         # Bind the current context (a href=...) so that thickbox
         # can act independent of me...
         do =>
            #window.parent.setTimeout tb_click, 0
            window.parent.tb_click()
            true
         e.stopPropagation()
   # 
   # Entry to our code
   # Override the imgEdit.open function
   #
   injectPTE = ->
      # Find and inject pte-url into size cell...
      #jQuery('.media-item').each (i,elem) ->
      #   post_id = elem.id.replace "media-item-",""
      #   $getLink(post_id)
      #   .css
      #      'font-size': '.8em'
      #      'margin-left': '5px'
      #   .click(checkExistingThickbox)
      #   .appendTo jQuery 'tr.image-size th.label', elem

      if imageEdit.open?
         imageEdit.oldopen = imageEdit.open
         imageEdit.open = (id, nonce) ->
            image_id = id
            imageEdit.oldopen id,nonce
            launchPTE()
      # Does the imgedit-save-target already exist?
      $editmenu = jQuery("""p[id^="imgedit-save-target-"]""")
      if $editmenu?.length > 0
         #set the image_id & launch it
         matches = $editmenu[0].id.match(/imgedit-save-target-(\d+)/)
         if matches[1]?
            image_id = matches[1]
            launchPTE()
      true

   launchPTE = ->
      # Check if elements are loaded
      #selector = """p[id^="imgedit-save-target-#{ image_id }"]"""
      selector = """#imgedit-save-target-#{ image_id }"""
      $editmenu = jQuery(selector)
      if $editmenu?.size() < 1
         window.setTimeout(launchPTE, timeout)
         return false

      # Add convenience functions to menu
      $link = $getLink().click checkExistingThickbox
      $link.addClass "button-primary"
      $link.css "margin-top", "10px"
      $editmenu.append $link

   injectPTE()

