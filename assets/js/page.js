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

    /*
     if (this.unpin != null && (scrollTop + this.unpin <= position.top) )
        affix = false;
     else if (offsetBottom != null && (position.top + this.$element.height() >= scrollHeight - offsetBottom))
        affix = 'bottom';
     else if (offsetTop != null && scrollTop <= offsetTop)
        affix = 'top'
     else
        affix = false;
*/
    // Update the links
    //function updateLinks(h2Txt, id){
    //    var $elem = $('h2').filter(function(i){
    //        if ( h2Txt == $(this).text().toLowerCase() && this.id){
    //            return true;
    //        }
    //        return false;
    //    });

    //    $elem.each(function(){
    //        this.id = id;
    //    });
    //}

    //updateLinks('post thumbnail editor', 'editor');
    //updateLinks('post thumbnail extras', 'extras');

    // Generate Menu:
    var selector = [];
    $.each(['h2','h3'], function(i,elem){
        selector.push(elem + '[id^="toc_"]');
    });
    $(selector.join(",")).each(function(i, h){
        $h = $(h);
        url = $("<a>").attr('href','#' + h.id).text($h.text()).addClass(h.nodeName.toLowerCase());
        if (i < 1)
            url.addClass('active');
        $nav.append( $('<li>').append(url) );
    })

    // refresh the scrollspy
    $('body').scrollspy({offset: 70});

})(jQuery);
