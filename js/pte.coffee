# TODO:
#   If the aspect ratio is shared by all images then set it
class TimerFunc
	constructor: (@fn, @timeout) ->
		@timer = null
	doFunc: (e) =>
		window.clearTimeout @timer
		@timer = window.setTimeout @fn, @timeout
		true

window.randomness = ->
	Math.floor(Math.random()*1000001).toString(16)

window.debugTmpl = (data) ->
	log "===== TEMPLATE DEBUG DATA FOLLOWS ====="
	log data
	true

# ===========================================================
# Change stages
# ===========================================================
# send cleanup request
deleteThumbs = (id) ->
	delete_options =
		"id": id
		'action': 'pte_ajax'
		'pte-action': 'delete-images'
		'pte-nonce': $('#pte-delete-nonce').val()
	# use ajax:global - false so the overlay isn't triggered
	$.ajax
		url: ajaxurl,
		data: delete_options
		global: false
		dataType: "json"
		success: deleteThumbsSuccessCallback

deleteThumbsSuccessCallback = (data, status, xhr) ->
	log "===== DELETE SUCCESSFUL, DATA DUMP FOLLOWS ====="
	log data
	pte.parseServerLog data.log

# Make jQuery function for moveRight and moveLeft
# include:
#  * callback support
#  * show/hide ability
#  * Make the left value dependent on screen size
#
# See: 
#   http://stackoverflow.com/questions/1058158/can-somebody-explain-jquery-queue-to-me/3314877#3314877
# TODO: Testing this needs to see if it runs for each element passed in
pte_queue = $({})
$.fn.extend
	move: (options) ->
		defaults =
			direction: 'left'
			speed: 500
			easing: 'swing'
			toggle: true
			callback: null
			callbackargs: null
		options = $.extend defaults, options

		this.each ->
			# Since no queue name defined, it will start automatically
			pte_queue.queue (next) =>
				$elem = $ this
				# if this object is in view, move it out of view
				# if this object is out of view, move it in view
				direction = if options.direction == 'left' then -1 else 1
				move_to = if $elem.css('left') == "0px" then $(window).width() * direction else 0

				# if this object is currently hidden, show it
				# if this object is currently shown, hide it
				isVisible = $elem.is(':visible')
				log [direction,move_to,isVisible]
				if (not isVisible)
					$elem.show 0, ->
						$(this).animate {'left': move_to}, options.speed, options.easing, next
				else
					$elem.animate {'left': move_to}, options.speed, options.easing
					$elem.hide 0, next
				true
		if options.callback?
			pte_queue.queue (next) ->
				if options.callbackargs?
					log "running callback with arguments"
					options.callback.apply this, options.callbackargs
				else
					log "running callback with no arguments"
					options.callback.apply this
				log "finished running callback"
				next()
		return this

	moveRight: (options) ->
		options = $.extend options, { direction: 'right' }
		this.move options
	moveLeft: (options) ->
		options = $.extend options, { direction: 'left' }
		this.move options

window.goBack = (e) ->
	e?.preventDefault()
	$('#stage2').moveRight()
	$('#stage1').moveRight
		callback: ->
			deleteThumbs $('#pte-post-id').val()
			# Reset stage2 to blank
			$('#stage2').html ''
		#callbackargs: [$('#pte-post-id').val()]
	true



gcd = (a, b) ->
	return b if a is 0
	while b > 0
		if a > b
			a = a - b
		else
			b = b - a
	if a < 0 or b < 0 then return null
	a

# functionally take a list of sizes
# convert to crop/width/height
# determine current ar and test against other ar's
# throw exception if more than 1?
determineAspectRatio = (current_ar, size_info) ->
	{crop, width, height} = size_info
	crop = +crop
	width = +width
	height = +height
	gc = gcd width, height
	if crop? and crop > 0
		tmp_ar = null
		if (width? > 0 and height? > 0)
			if gc?
				tmp_ar = "#{ width / gc }:#{ height / gc }"
			else
				tmp_ar = "#{ width }:#{ height }"
		if current_ar? and tmp_ar? and tmp_ar isnt current_ar
			throw objectL10n.aspect_ratio_disabled
		current_ar = tmp_ar
	current_ar

pte.functions =
	determineAspectRatio: determineAspectRatio

do (pte) ->
	# Call this on page instantiation
	# Initialize stage2 & stage3 to be set at screen width (for moveRight moveLeft functionality)
	editor = pte.editor = ->
		configurePageDisplay()
		addRowListeners()
		initImgAreaSelect()
		addRowListener()
		addSubmitListener()
		addVerifyListener()
		addCheckAllNoneListener()
		configureOverlay()
		true
	# ===========================================================
	# End Editor INIT
	# ===========================================================

	# ===========================================================
	# This configures our loading overlay
	# ===========================================================
	configureOverlay = ->
		$loading_screen = $('#pte-loading')
		closeLoadingScreen = ->
			$loading_screen.hide()
			true

		# overlay starts by being shown, this should close it
		$('#pte-preview').load closeLoadingScreen

		# AJAX calls will show/hide the overlay
		$loading_screen
		.ajaxStart( ->
			$(this).fadeIn 200
		).ajaxStop( ->
			$(this).fadeOut 200
		)
		# overlay starts by being shown, this WILL close it
		window.setTimeout closeLoadingScreen, 2000
		true


	# ===========================================================
	# Set the height of the options 
	# ===========================================================
	# Sets a timeout to be a little bit more responsive
	configurePageDisplay = ->
		# Set the height of the side column
		reflow = new TimerFunc ->
			log "===== REFLOW ====="
			pte.fixThickbox window.parent
			offset = $("#pte-sizes").offset()
			window_height = $(window).height() - offset.top - 2
			$("#pte-sizes").height window_height
			# Set the left position of stages2,3
			log """WINDOW WIDTH: #{$(window).width()}"""
			$('#stage2, #stage3').filter(":hidden").css
				left: $(window).width()
			true
		, 100
		# Add to the resize and load events
		$(window).resize(reflow.doFunc).load(reflow.doFunc)

		true


	# ===========================================================
	# Code to highlight selected rows
	# ===========================================================
	addRowListeners = ->
		enableRowFeatures = ($elem) ->
			# Check the checkbox
			$elem.delegate 'tr', 'click', (e) ->
				if e.target.type isnt 'checkbox'
					$('input:checkbox', this).click()
				true
			# Style the row
			$elem.delegate 'input:checkbox', 'click', (e) ->
				if this.checked or $(this).is('input:checked')
					$(this).parents('tr').first().removeClass 'selected'
				else
					$(this).parents('tr').first().addClass 'selected'
				true
		enableRowFeatures $('#stage2')
		enableRowFeatures $('#stage1')
	# ===========================================================




	# ===========================================================
	### Enable imgareaselect plugin ###
	# ===========================================================
	ias_instance = null
	ias_defaults =
		#parent: parent
		keys: true
		minWidth: 3
		minHeight: 3
		handles: true
		zIndex: 1200
		instance: true
		onSelectEnd: (img, s) ->
			# Check that getSelection returns valid information...
			if s.width && s.width > 0 and s.height && s.height > 0 and $('.pte-size').filter(':checked').size() > 0
				$('#pte-submit').removeAttr('disabled')
			else
				$('#pte-submit').attr('disabled', true)


	initImgAreaSelect = ->
		pte.ias = ias_instance = $('#pte-image img').imgAreaSelect ias_defaults
	iasSetAR = (ar) ->
		log "===== SETTING ASPECTRATIO: #{ ar } ====="
		ias_instance.setOptions
			aspectRatio: ar
		ias_instance.update()




	# ===========================================================
	# Setting aspectRatio
	# This is what happens when a box is checked
	# Functional call to reduce would work well here (underscore.js)
	# ===========================================================
	addRowListener = ->
		pteVerifySubmitButtonHandler = new TimerFunc ->
			# If the number of confirms clicked is > 0, enable the submit button
			log "===== CHECK SUBMIT BUTTON ====="
			if $('.pte-confirm').filter(':checked').size() > 0
				log "ENABLE"
				$('#pte-confirm').removeAttr('disabled')
			else
				log "DISABLE"
				$('#pte-confirm').attr('disabled', true)
			true
		, 50
		pteCheckHandler = new TimerFunc ->
			ar = null
			selected_elements = $('input.pte-size').filter(':checked').each (i,elem) ->
				try
					ar = determineAspectRatio ar, thumbnail_info[$(elem).val()]
				catch error
					ar = null
					if ar isnt ias_instance.getOptions().aspectRatio
						alert error
					return false
				true
			iasSetAR ar

			# Call our check to set the buttons' ability/disability
			ias_defaults.onSelectEnd null, ias_instance.getSelection()
			true
		, 50
		$.extend pte.functions,
			pteVerifySubmitButtonHandler: pteVerifySubmitButtonHandler
		$('input.pte-size').click pteCheckHandler.doFunc
		$('.pte-confirm').live 'click', (e) ->
			pteVerifySubmitButtonHandler.doFunc()


	# ===========================================================
	# Callback for resizing images (Stage 1 to 2) ###
	# ===========================================================
	# What do you do when you click submit --> onResizeImages
	addSubmitListener = ->
		$('#pte-submit').click (e) ->
			selection = ias_instance.getSelection()
			scale_factor = $('#pte-sizer').val()
			submit_data =
				'id':         $('#pte-post-id').val()
				'action':     'pte_ajax'
				'pte-action': 'resize-images'
				'pte-sizes[]': $('.pte-size').filter(':checked').map(->
					$(this).val()
				).get()
				'x': Math.floor(selection.x1/scale_factor)
				'y': Math.floor(selection.y1/scale_factor)
				'w': Math.floor(selection.width/scale_factor)
				'h': Math.floor(selection.height/scale_factor)
			log "===== RESIZE-IMAGES ====="
			log submit_data
			if isNaN(submit_data.x) or isNaN(submit_data.y) or isNaN(submit_data.w) or isNaN(submit_data.h)
				alert objectL10n.crop_submit_data_error
				log "ERROR with submit_data and NaN's"
				return false
			ias_instance.setOptions
				hide: true
				x1: 0
				y1: 0
				x2: 0
				y2: 0

			#$('#pte-loading').fadeToggle 200
			# Disable button
			$('#pte-submit').attr('disabled', true)
			$.getJSON(ajaxurl, submit_data, onResizeImages)
			true

		onResizeImages = (data, status, xhr) ->
			### Evaluate data ###
			log "===== RESIZE-IMAGES SUCCESS ====="
			log data
			pte.parseServerLog data.log
			if data.error? and not data.thumbnails?
				alert(data.error)
				return
			
			$('#stage1').moveLeft()
			$('#stage2').html($('#stage2template').tmpl(data)).moveLeft
				callback: pte.functions.pteVerifySubmitButtonHandler.doFunc
			false
	# ===========================================================
		
	# ===========================================================
	### Callback for Stage 2 to 3 ###
	# ===========================================================
	addVerifyListener = ->
		$('#pte-confirm').live 'click', (e) ->
			thumbnail_data = {}
			$('input.pte-confirm').filter(':checked').each (i, elem) ->
				size = $(elem).val()
				thumbnail_data[size] = $(elem).parent().parent().find('.pte-file').val()
			submit_data =
				'id':            $('#pte-post-id').val()
				'action':        'pte_ajax'
				'pte-action':    'confirm-images'
				'pte-nonce':     $('#pte-nonce').val()
				'pte-confirm':   thumbnail_data
			log "===== CONFIRM-IMAGES ====="
			log submit_data
			$.getJSON(ajaxurl, submit_data, onConfirmImages)

		onConfirmImages = (data, status, xhr) ->
			log "===== CONFIRM-IMAGES SUCCESS ====="
			log data
			pte.parseServerLog data.log
			$('#stage2').moveLeft()
			$('#stage3').html($('#stage3template').tmpl(data)).moveLeft()
			false
	# ===========================================================

	# ===========================================================
	### Select ALL|NONE ###
	# ===========================================================
	# Let the user quickly check/uncheck all the items
	addCheckAllNoneListener = ->
		uncheckAllSizes = (e) ->
			e?.preventDefault()
			elements = e.data?.selector ? '.pte-size'
			#$('.pte-size').filter(':checked').click()
			$(elements).filter(':checked').click()

		checkAllSizes = (e) ->
			e?.preventDefault()
			elements = e?.data?.selector ? '.pte-size'
			#$('.pte-size').not(':checked').click()
			$(elements).not(':checked').click()

		$("#pte-selectors .all").click(checkAllSizes)
		$("#pte-selectors .none").click(uncheckAllSizes).click()
		$('#stage2').delegate '#pte-stage2-selectors .all', 'click', {selector: '.pte-confirm'}, checkAllSizes
		$('#stage2').delegate '#pte-stage2-selectors .none', 'click', {selector: '.pte-confirm'}, uncheckAllSizes

		# Finished
		true
	
	$.extend pte.functions,
		iasSetAR: iasSetAR

