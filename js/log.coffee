# http://javascriptweblog.wordpress.com/2011/08/08/fixing-the-javascript-typeof-operator/
toType = (obj) ->
  ({}).toString.call(obj).match(/\s([a-z|A-Z]+)/)[1].toLowerCase()

class Message
	constructor: (@message) ->
		@date = new Date()
	toString: ->
		pad = (num, pad) ->
			while (""+num).length < pad
				num = "0" + num
			num

		d = @date
		y = pad d.getUTCFullYear(), 4
		M = pad d.getUTCMonth() + 1, 2
		D = pad d.getUTCDate(), 2
		h = pad d.getUTCHours(), 2
		m = pad d.getUTCMinutes(), 2
		s = pad d.getUTCSeconds(), 2

		switch toType @message
			when "string" then message = @message
			else message = $.toJSON @message

		"""#{ y }#{ M }#{ D } #{ h }:#{ m }:#{ s } - [#{toType @message}] #{ message }"""

do(pte) ->
	pte.messages = []
	pte.log = (obj) ->
		# Track date, time, and where the code was called from?
		return true if not window.options.pte_debug
		try
			pte.messages.push(new Message obj)
			console.log obj
			$('#pte-log-messages textarea').filter(':visible').val pte.formatLog()
		catch error
			#alert obj
		return
	pte.formatLog = ->
		log = ""
		log += """#{message}\n""" for message in pte.messages
		log
	pte.parseServerLog = (json) ->
		log "===== SERVER LOG ====="
		if json?.length? and json.length > 0
			for message in json
				log message
		true

	pte.sendToPastebin = (text) ->
		pastebin_url = "http://dpastey.appspot.com/"
		post_data =
			title: "PostThumbnailEditor Log"
			content: text
			lexer: "text"
			format: "json"
			#expire_options: "3600" # 1 hour
			expire_options: "2592000" # 1 month
		$.ajax
			url: pastebin_url
			data: post_data
			dataType: "json"
			global: false
			type: "POST"
			error: (xhr, status, errorThrown) ->
				$('#pte-log').fadeOut '900'
				alert objectL10n.pastebin_create_error
				log xhr
				log status
				log errorThrown
			success: (data, status, xhr) ->
				$('#pte-log').fadeOut '900'
				prompt objectL10n.pastebin_url, data.url


	# End PTE Log Section
	true

window.log = pte.log

$(document).ready ($) ->
	#$('#clipboard').click (e) ->
	#   text = pte.formatLog()
	#   prompt "Ctrl+C, Enter", text
	$('#test').click (e) ->
		e.stopImmediatePropagation()
		true
	$('#pastebin').click (e) ->
		pte.sendToPastebin pte.formatLog()
	$('#clear-log').click (e) ->
		pte.messages = []
		$('#pte-log-messages textarea').val pte.formatLog()
	$('#close-log').click (e) ->
		$('#pte-log').fadeOut '900'

	$('#pte-log-tools a').click (e) ->
		e.preventDefault()
	$('body').delegate '.show-log-messages', 'click', (e) ->
		e.preventDefault()
		$('#pte-log-messages textarea').val pte.formatLog()
		$('#pte-log').fadeIn '900'
