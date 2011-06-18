# TODO:
#   If the aspect ratio is shared by all images then set it

class TimerFunc
	constructor: (@fn, @timeout) ->
		@timer = null
	doFunc: (e) ->
		window.clearTimeout @timer
		@timer = window.setTimeout @fn, @timeout
		true

window.randomness = ->
	Math.floor(Math.random()*1000001).toString(16)

window.debugTmpl = (data) ->
	log data
	true


jQuery(document).ready ($) ->
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
	
	randomness = ->
      Math.floor(Math.random()*1000001).toString(16)

	ias_defaults =
		instance: true
		#parent: '#pte-image'
		onSelectEnd: (img, s) ->
		#s = ias_instance.getSelection()
		#log """x1: #{ s.x1 }, y1: #{ s.y1 }
		#   x2: #{ s.x2 }, y2: #{ s.y2 }
		#   w : #{ s.width }, h : #{ s.height }"""
			if s.width && s.width > 0 and s.height && s.height > 0 and $('.pte-size').filter(':checked').size() > 0
				log "Enabling button"
				$('#pte-submit').removeAttr('disabled')
			else
				log "Disabling button"
				$('#pte-submit').attr('disabled', true)

		handles: true
		zIndex: 1200

	###
	Set the height of the options
	###
	# Sets a timeout to be a little bit more responsive
	#reflow = timerFunction ->
	reflow = new TimerFunc ->
		offset = $("#pte-sizes").offset()
		$("#pte-sizes").height $(window).height() - offset.top - 2
	, 100
	# Add to the resize and load events
	proxy_reflow = $.proxy reflow.doFunc, reflow
	$(window).resize(proxy_reflow).load(proxy_reflow)


	###
	Select ALL|NONE
   ###
	# Let the user quickly check/uncheck all the items
	uncheckAllSizes = (e) ->
		e.preventDefault()
		elements = e.data?.selector ? '.pte-size'
		#$('.pte-size').filter(':checked').click()
		#log elements
		$(elements).filter(':checked').click()

	checkAllSizes = (e) ->
		e.preventDefault()
		elements = e.data?.selector ? '.pte-size'
		#log elements
		#$('.pte-size').not(':checked').click()
		$(elements).not(':checked').click()
	$("#pte-selectors .all").click(checkAllSizes)
	$("#pte-selectors .none").click(uncheckAllSizes)
	$('#stage2').delegate '#pte-stage2-selectors .all', 'click', {selector: '.pte-confirm'}, checkAllSizes
	$('#stage2').delegate '#pte-stage2-selectors .none', 'click', {selector: '.pte-confirm'}, uncheckAllSizes

	# This is what happens when a box is checked
	#pteCheckHandler = timerFunction (e) ->
	pteCheckHandler = new TimerFunc ->
		#log "# checked: #{ $('.pte-size:checked').size() }"
		selected_elements = $('.pte-size:checked')
		if selected_elements.size() is 1
			# Set the AR
			{crop, width, height} = thumbnail_info[selected_elements.val()]
			iasSetAR width, height, crop
		else # selected elements <> 1
			iasSetAR()
		ias_defaults.onSelectEnd null, ias_instance.getSelection()
		true
	, 50
	$('.pte-size').click $.proxy pteCheckHandler.doFunc, pteCheckHandler



	###
	Enable imgareaselect plugin
	###
	ias_instance = $('#pte-image img').imgAreaSelect ias_defaults
	iasSetAR = (width, height, crop) ->
		ar = null
		if crop != null and (crop is true or crop > 0)
			ar = "#{ width }:#{ height }" if width? > 0 and height? > 0
		log "setting aspectRatio: #{ ar }"
		ias_instance.setOptions
			aspectRatio: ar
		ias_instance.update()




	# This is our loading div
	$('#pte-loading').hide()
	.ajaxStart( ->
		$(this).fadeIn 200
	).ajaxStop( ->
		$(this).fadeOut 200
	)




	# What do you do when you click submit?
	$('#pte-submit').click (e) ->
		log "Clicked Submit..."
		selection = ias_instance.getSelection()
		scale_factor = $('#pte-sizer').val()
		submit_data = 
			'id':         $('#pte-post-id').val()
			'action':     'pte_ajax'
			'pte_action': 'resize_images'
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
	
	$('#pte-confirm').live 'click', (e) ->
		log "Confirming"
		submit_data =
			'id':            $('#pte-post-id').val()
			'action':        'pte_ajax'
			'pte_action':    'confirm_images'
			'pte_nonce':     $('#pte-nonce').val()
			'pte-confirm[]': $('.pte-confirm').filter(':checked').map(->
				$(this).val()
			).get()
		log submit_data
		if submit_data['pte-confirm[]']?.length < 1
			done()


	enableRowFeatures = ($elem) ->
		#$('#stage2').delegate 'tr', 'click', (e) ->
		$elem.delegate 'tr', 'click', (e) ->
			if e.target.type isnt 'checkbox'
				$(':checkbox', this).click()
			true
		#$('#stage2').delegate ':checkbox', 'click', (e) ->
		$elem.delegate ':checkbox', 'click', (e) ->
			$(this).parents('tr').first().toggleClass 'selected'
			true
	enableRowFeatures $('#stage2')
	enableRowFeatures $('#stage1')

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

	done = ->
		alert "all done..."

	# Finished jQuery
	true
