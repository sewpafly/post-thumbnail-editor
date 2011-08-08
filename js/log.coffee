window.log = (obj) ->
	return true if not window.options.pte_debug
	try
		console.log obj
	catch error
		alert obj
	true
