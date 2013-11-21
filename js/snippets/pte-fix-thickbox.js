(function($) {
	old_tb_position = tb_position;
	tb_position = function() {
		var tbWindow = $('#TB_window'), tbIframe = tbWindow.find('iframe');
		if ( tbIframe.size() > 0 && tbIframe.get(0).src.match(/pte_ajax/) ) {
			var W = tbWindow.width(), adminbar_height = 0;
			if ( $('body.admin-bar').length )
				adminbar_height = 28;
			tbWindow.css({'margin-left': '-' + parseInt((( W ) / 2),10) + 'px'});
			if ( typeof document.body.style.maxWidth != 'undefined' )
				tbWindow.css({'top': 20 + adminbar_height + 'px','margin-top':'0'});
		}
		else if (tbIframe.size() != 0) {
			old_tb_position();
		}
	}
})(jQuery);
