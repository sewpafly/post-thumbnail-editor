# TODO:
#   If the aspect ratio is shared by all images then set it

class TimerFunc
	constructor: (@fn, @timeout) ->
		@timer = null
	#doFunc: (e) ->
	doFunc: (e) =>
		window.clearTimeout @timer
		@timer = window.setTimeout @fn, @timeout
		true

window.randomness = ->
	Math.floor(Math.random()*1000001).toString(16)

window.debugTmpl = (data) ->
	log data
	true


jQuery(document).ready ($) ->
	# ===========================================================
	### Callback for resizing images (Stage 1 to 2) ###
	# ===========================================================
	# What do you do when you click submit --> onResizeImages
	$('#pte-submit').click (e) ->
		log "Clicked Submit..."
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
		log submit_data
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
		log data
		#$('#stage1').hide()
		#$('#stage2').html($('#stage2template').tmpl(data)).show(500)
		if data.error? and not data?.thumbnails?
			alert(data.error)
			return
		
		$('#stage1').animate
			left: -$(window).width()
		, 500, 'swing', ->
			$(this).hide()
			$('#stage2').html($('#stage2template').tmpl(data)).show(0, ->
				$(this).animate(
					left: 0
				, 500)
				true
			)
			#$('#stage2').animate({left: 0}, 500)
			true
		false
	# ===========================================================
	
	# ===========================================================
	### Callback for Stage 2 to 3 ###
	# ===========================================================
	$('#pte-confirm').live 'click', (e) ->
		log "Confirming"
		thumbnail_data = {}
		$('.pte-confirm').filter(':checked').each (i, elem) ->
			size = $(elem).val()
			thumbnail_data[size] = $("\#pte-#{ size }-file").val()
		submit_data =
			'id':            $('#pte-post-id').val()
			'action':        'pte_ajax'
			'pte-action':    'confirm-images'
			'pte-nonce':     $('#pte-nonce').val()
			'pte-confirm':   thumbnail_data
		log submit_data
		$.getJSON(ajaxurl, submit_data, onConfirmImages)

	onConfirmImages = (data, status, xhr) ->
		log data
		$('#stage2').animate
			left: -$(window).width()
		, 500, 'swing', ->
			$(this).hide()
			$('#stage3').html($('#stage3template').tmpl(data)).show(0, ->
				$(this).animate(
					left: 0
				, 500)
				true
			)
			true
		false
	# ===========================================================

	# A little randomness will help us to not cache images...
	randomness = ->
      Math.floor(Math.random()*1000001).toString(16)

	# ===========================================================
	### Set the height of the options ###
	# ===========================================================
	# Sets a timeout to be a little bit more responsive
	#reflow = timerFunction ->
	reflow = new TimerFunc ->
		log "reflow called..."
		offset = $("#pte-sizes").offset()
		window_height = $(window).height() - offset.top - 2
		$("#pte-sizes").height window_height
	, 100
	# Add to the resize and load events
	#proxy_reflow = $.proxy reflow.doFunc, reflow
	$(window).resize(reflow.doFunc).load(reflow.doFunc)


	# ===========================================================
	# Setting aspectRatio
	# This is what happens when a box is checked
	# ===========================================================
	gcd = (a, b) ->
		return b if a is 0
		while b > 0
			if a > b 
				a = a - b
			else
				b = b - a
		if a < 0 or b < 0 then return null
		a

	#pteCheckHandler = timerFunction (e) ->
	pteCheckHandler = new TimerFunc ->
		#log "# checked: #{ $('.pte-size:checked').size() }"
		ar = null
		selected_elements = $('.pte-size:checked').each (i,elem) ->
			{crop, width, height} = thumbnail_info[$(elem).val()]
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
				if ar? and tmp_ar? and tmp_ar isnt ar
					alert "2 images are trying to set different aspect ratios, disabling..."
					ar = null
					return false # stops the each loop
				ar = tmp_ar
		iasSetAR ar

		ias_defaults.onSelectEnd null, ias_instance.getSelection()
		true
	, 50
	$('.pte-size').click pteCheckHandler.doFunc
	#$('.pte-size').click $.proxy pteCheckHandler.doFunc, pteCheckHandler


	# ===========================================================
	### Select ALL|NONE ###
	# ===========================================================
	# Let the user quickly check/uncheck all the items
	uncheckAllSizes = (e) ->
		e?.preventDefault()
		elements = e.data?.selector ? '.pte-size'
		#$('.pte-size').filter(':checked').click()
		#log elements
		$(elements).filter(':checked').click()

	checkAllSizes = (e) ->
		e?.preventDefault()
		elements = e?.data?.selector ? '.pte-size'
		#log elements
		#$('.pte-size').not(':checked').click()
		$(elements).not(':checked').click()

	$("#pte-selectors .all").click(checkAllSizes)
	$("#pte-selectors .none").click(uncheckAllSizes).click()
	$('#stage2').delegate '#pte-stage2-selectors .all', 'click', {selector: '.pte-confirm'}, checkAllSizes
	$('#stage2').delegate '#pte-stage2-selectors .none', 'click', {selector: '.pte-confirm'}, uncheckAllSizes


	# ===========================================================
	### Enable imgareaselect plugin ###
	# ===========================================================
	ias_defaults =
		#parent: parent
		keys: true
		minWidth: 3
		minHeight: 3
		handles: true
		zIndex: 1200
		instance: true
		onSelectEnd: (img, s) ->
			if s.width && s.width > 0 and s.height && s.height > 0 and $('.pte-size').filter(':checked').size() > 0
				log "Enabling button"
				$('#pte-submit').removeAttr('disabled')
			else
				log "Disabling button"
				$('#pte-submit').attr('disabled', true)


	ias_instance = $('#pte-image img').imgAreaSelect ias_defaults
	iasSetAR = (ar) ->
		log "setting aspectRatio: #{ ar }"
		ias_instance.setOptions
			aspectRatio: ar
		ias_instance.update()




	# ===========================================================
	# This is our loading div
	# ===========================================================
	$('#pte-loading').hide()
	.ajaxStart( ->
		$(this).fadeIn 200
	).ajaxStop( ->
		$(this).fadeOut 200
	)



	
	# ===========================================================
	# Code to highlight selected rows
	# ===========================================================
	enableRowFeatures = ($elem) ->
		#$('#stage2').delegate 'tr', 'click', (e) ->
		$elem.delegate 'tr', 'click', (e) ->
			if e.target.type isnt 'checkbox'
				$(':checkbox', this).click()
			true
		#$('#stage2').delegate ':checkbox', 'click', (e) ->
		$elem.delegate ':checkbox', 'click', (e) ->
			if this.checked or $(this).is(':checked')
				$(this).parents('tr').first().removeClass 'selected'
			else
				$(this).parents('tr').first().addClass 'selected'
			true
	enableRowFeatures $('#stage2')
	enableRowFeatures $('#stage1')
	# ===========================================================




	# ===========================================================
	# Change stages
	# ===========================================================
	window.goBack = (e) ->
		e?.preventDefault()
		$('#stage2').animate
			left: 1200
		, 500, 'swing', ->
			# this = stage2
			$(this).hide()
			$('#stage1').show(0, ->
				$(this).animate(
					left: 0
				, 500)
				true
			)
		true

			
	#$('.stage').delegate '.stage-navigation', 'click', goBack


	# Finished
	true
