(function($){
    // Affix the menu to the side
    var $style = $('<style/>').appendTo('body')
      , lastDonationHeight = null;
    $nav = $('#pte-nav');
    position = $nav.position();
    position.top -= 60;
    position.bottom = function(){
       var donationHeight = $('#toc_donations').parents('.span12').outerHeight() + 15;
       if (donationHeight == lastDonationHeight)
          return donationHeight;
       lastDonationHeight = donationHeight;
       $style.text('#pte-nav.affix-bottom { bottom: ' + (donationHeight - 22) + 'px; }' );
       return donationHeight;
    };
    console.log( position.top );
    $nav.affix({
        offset: position
    });

    $("h2,h3").each(function(i, h){
        $h = $(h);
        url = $("<a>").attr('href','#' + h.id).text($h.text()).addClass(h.nodeName.toLowerCase());
        if (i < 1)
            url.addClass('active');
        $nav.append( $('<li>').append(url) );
    })

    // refresh the scrollspy
    $('body').scrollspy({offset: 70});

})(jQuery);
