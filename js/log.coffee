window.log = (obj) ->
	return true if not window.debug_enabled
	try
		console.log obj
	catch error
		alert obj
	true
