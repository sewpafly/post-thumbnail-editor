window = @
$ = window.jQuery

window.pte = pte = pte || {}

do(pte) ->
	# Let's add some thickbox fixes
	pte.fixThickbox = (parent) ->
		$p = parent.jQuery
		if $p == null or parent.frames.length < 1 then return
		log "===== FIXING THICKBOX ====="

		# How big should the window be
		width = window.options.pte_tb_width + 30
		height = window.options.pte_tb_height + 38

		$thickbox = $p("#TB_window")
		if $thickbox.width() >= width and $thickbox.height() >= height
			return
		log """THICKBOX: #{ $thickbox.width() } x #{ $thickbox.height() }"""

		# set the correct dimensions
		$thickbox.css
			'margin-left': 0 - (width / 2)
			'width': width
			'height': height
		.children("iframe").css
			'width': width

		# for some reason setting the height needs this hack
		parent.setTimeout ->
			if $p("iframe", $thickbox).height() > height then return
			$p("iframe", $thickbox).css
				'height': height
			#.resize()
			log """THICKBOX: #{ $thickbox.width() } x #{ $thickbox.height() }"""
			true
		, 1000

