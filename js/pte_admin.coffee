###
  POST-THUMBNAIL-EDITOR Script for Wordpress

  Hooks into the Wordpress Media Library
###

jQuery(document).ready ($) ->
	timeout = 300
	pte_url = "#{ ajaxurl }?action=pte_ajax&pte-action=launch&id=#{ $('#attachment_id').val() }"

	# 
	# Entry to our code
	# Override the imgEdit.open function
	#
	injectPTE = ->
		if imageEdit.open?
			imageEdit.oldopen = imageEdit.open
			imageEdit.open = (id, nonce) ->
				imageEdit.oldopen id,nonce
				launchPTE()
		true

	launchPTE = ->
		# Check if elements are loaded
		editmenu = $ 'p[id^="imgedit-save-target"]'
		if editmenu?.size() < 1
			window.log "Edit Thumbnail Menu not visible, waiting for #{ timeout }ms"
			window.setTimeout(launchPTE, timeout)
			return false

		# Add convenience functions to menu
		editmenu.append $ """<a href="#{pte_url}">#{ objectL10n.PTE }</a>"""

	injectPTE()

