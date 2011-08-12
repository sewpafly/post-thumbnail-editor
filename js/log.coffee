toType = (obj) ->
  ({}).toString.call(obj).match(/\s([a-z|A-Z]+)/)[1].toLowerCase()

class Message
	constructor: (@message) ->
		@date = new Date()
	toString: ->
		pad = (int, pad) ->
			while (""+int).length < pad
				int = "0" + int
			int

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
		catch error
			#alert obj
		true
	pte.formatLog = ->
		log = ""
		log += """#{message}\n""" for message in pte.messages
		log

	pte.sendToPastebin = (text) ->
		# Other potential options:
		#   DPASTE.DE
		#   https://github.com/bartTC/django-paste
		#   http://paste.kde.org/doc/api/
		pastebin_url = "http://dpastey.appspot.com/"
		post_data =
			title: "PostThumbnailEditor Log"
			content: text
			lexer: "text"
			expire_options: "3600" # 1 hour
			#expire_options: "2592000" # 1 month
		#pastebin_url = "http://paste.kde.org/"
		#post_data =
		#   paste_data: text
		#   paste_lang: "javascript"
		#   api_submit: "true"
		#   mode: "json"
		#   paste_expire: "3600" # 1 hour
		#   paste_project: "PostThumbnailEditor"
		$.ajax
			url: pastebin_url
			data: post_data
			dataType: "json"
			global: false
			type: "POST"
			complete: (xhr, status) ->
				log xhr
				log status
			error: (xhr, status, errorThrown) ->
				log xhr
				log status
				log errorThrown
			success: (data, status, xhr) ->
				log data
				log status
				log xhr


	# End PTE Log Section
	true

window.log = pte.log

$(document).ready ($) ->
	$('#pte-log-tools a').click (e) ->
		e.preventDefault()
	#$('#clipboard').click (e) ->
	#   text = pte.formatLog()
	#   prompt "Ctrl+C, Enter", text
	$('#pastebin').click (e) ->
		pte.sendToPastebin pte.formatLog()
	$('#clear-log').click (e) ->
		pte.messages = []
		$('#pte-log-messages textarea').val pte.formatLog()
	$('#close-log').click (e) ->
		$('#pte-log').fadeOut '900'

	$('body').delegate '.show-log-messages', 'click', (e) ->
		e.preventDefault()
		$('#pte-log-messages textarea').val pte.formatLog()
		$('#pte-log').fadeIn '900'
