# Handle to the IMGAREASELECT plugin
window.log = (obj) ->
	if !window.console
		names = [
			"log"  , "debug"   , "info"   , "warn"
			"error", "assert"  , "dir"    , "dirxml"
			"group", "groupEnd", "time"   , "timeEnd"
			"count", "trace"   , "profile", "profileEnd"
		]
		window.console[name] = alert for name in names
	console = window.console if !console
	console.log obj

