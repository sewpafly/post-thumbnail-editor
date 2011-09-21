###
  POST-THUMBNAIL-EDITOR Script for Wordpress

  Hooks into the Wordpress Media Library
###
do (pte) ->

	pte.admin = ->
		timeout = 300
		thickbox = """&TB_iframe=true&height=#{ window.options.pte_tb_height }&width=#{ window.options.pte_tb_width }"""
		image_id = null
		pte_url = (override_id) ->
			id = override_id || image_id || $("#attachment-id").val()
			"#{ ajaxurl }?action=pte_ajax&pte-action=launch&id=#{ id }#{ thickbox }"
		$getLink = (id) ->
			$("""<a class="thickbox" href="#{ pte_url id }">#{ objectL10n.PTE }</a>""")



		checkExistingThickbox = (e) ->
			log "Start PTE..."
			if window.parent.frames.length > 0
				log "Modifying thickbox..."
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
			$('.media-item').each (i,elem) ->
				post_id = elem.id.replace "media-item-",""
				$getLink(post_id)
				.css
					'font-size': '.8em'
					'margin-left': '5px'
				.click(checkExistingThickbox)
				.appendTo $ 'tr.image-size th.label', elem

			if imageEdit.open?
				imageEdit.oldopen = imageEdit.open
				imageEdit.open = (id, nonce) ->
					image_id = id
					imageEdit.oldopen id,nonce
					launchPTE()
			true

		launchPTE = ->
			# Check if elements are loaded
			#selector = """p[id^="imgedit-save-target-#{ image_id }"]"""
			selector = """#imgedit-save-target-#{ image_id }"""
			$editmenu = $(selector)
			if $editmenu?.size() < 1
				window.log "Edit Thumbnail Menu not visible, waiting for #{ timeout }ms"
				window.setTimeout(launchPTE, timeout)
				return false

			# Add convenience functions to menu
			$editmenu.append $getLink().click checkExistingThickbox

		injectPTE()

