define [], ->
   getWindowVar = (key) ->
      if !window[key]
         throw "PTE_EXCEPTION: Invalid window var: " + key
      window[key]
   settings =
      width:   getWindowVar 'post_width'
      height:  getWindowVar 'post_height'
      id:      getWindowVar 'post_id'
      ajaxurl: getWindowVar 'ajaxurl'
      i18n:    getWindowVar 'pteI18n'
      nonce:   getWindowVar 'pte_nonce'
      options_nonce: getWindowVar 'pte_options_nonce'

