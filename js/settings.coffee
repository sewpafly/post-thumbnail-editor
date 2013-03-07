define [],
   getWindowVar: (key) ->
      if !window[key]
         throw "PTE_EXCEPTION: Invalid window var: " + key
      window[key]
