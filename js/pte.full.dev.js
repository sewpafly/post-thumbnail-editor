/*!
 * jQuery Templates Plugin 1.0.0pre
 * http://github.com/jquery/jquery-tmpl
 * Requires jQuery 1.4.2
 *
 * Copyright Software Freedom Conservancy, Inc.
 * Dual licensed under the MIT or GPL Version 2 licenses.
 * http://jquery.org/license
 */
(function( jQuery, undefined ){
	var oldManip = jQuery.fn.domManip, tmplItmAtt = "_tmplitem", htmlExpr = /^[^<]*(<[\w\W]+>)[^>]*$|\{\{\! /,
		newTmplItems = {}, wrappedItems = {}, appendToTmplItems, topTmplItem = { key: 0, data: {} }, itemKey = 0, cloneIndex = 0, stack = [];

	function newTmplItem( options, parentItem, fn, data ) {
		// Returns a template item data structure for a new rendered instance of a template (a 'template item').
		// The content field is a hierarchical array of strings and nested items (to be
		// removed and replaced by nodes field of dom elements, once inserted in DOM).
		var newItem = {
			data: data || (data === 0 || data === false) ? data : (parentItem ? parentItem.data : {}),
			_wrap: parentItem ? parentItem._wrap : null,
			tmpl: null,
			parent: parentItem || null,
			nodes: [],
			calls: tiCalls,
			nest: tiNest,
			wrap: tiWrap,
			html: tiHtml,
			update: tiUpdate
		};
		if ( options ) {
			jQuery.extend( newItem, options, { nodes: [], parent: parentItem });
		}
		if ( fn ) {
			// Build the hierarchical content to be used during insertion into DOM
			newItem.tmpl = fn;
			newItem._ctnt = newItem._ctnt || newItem.tmpl( jQuery, newItem );
			newItem.key = ++itemKey;
			// Keep track of new template item, until it is stored as jQuery Data on DOM element
			(stack.length ? wrappedItems : newTmplItems)[itemKey] = newItem;
		}
		return newItem;
	}

	// Override appendTo etc., in order to provide support for targeting multiple elements. (This code would disappear if integrated in jquery core).
	jQuery.each({
		appendTo: "append",
		prependTo: "prepend",
		insertBefore: "before",
		insertAfter: "after",
		replaceAll: "replaceWith"
	}, function( name, original ) {
		jQuery.fn[ name ] = function( selector ) {
			var ret = [], insert = jQuery( selector ), elems, i, l, tmplItems,
				parent = this.length === 1 && this[0].parentNode;

			appendToTmplItems = newTmplItems || {};
			if ( parent && parent.nodeType === 11 && parent.childNodes.length === 1 && insert.length === 1 ) {
				insert[ original ]( this[0] );
				ret = this;
			} else {
				for ( i = 0, l = insert.length; i < l; i++ ) {
					cloneIndex = i;
					elems = (i > 0 ? this.clone(true) : this).get();
					jQuery( insert[i] )[ original ]( elems );
					ret = ret.concat( elems );
				}
				cloneIndex = 0;
				ret = this.pushStack( ret, name, insert.selector );
			}
			tmplItems = appendToTmplItems;
			appendToTmplItems = null;
			jQuery.tmpl.complete( tmplItems );
			return ret;
		};
	});

	jQuery.fn.extend({
		// Use first wrapped element as template markup.
		// Return wrapped set of template items, obtained by rendering template against data.
		tmpl: function( data, options, parentItem ) {
			return jQuery.tmpl( this[0], data, options, parentItem );
		},

		// Find which rendered template item the first wrapped DOM element belongs to
		tmplItem: function() {
			return jQuery.tmplItem( this[0] );
		},

		// Consider the first wrapped element as a template declaration, and get the compiled template or store it as a named template.
		template: function( name ) {
			return jQuery.template( name, this[0] );
		},

		domManip: function( args, table, callback, options ) {
			if ( args[0] && jQuery.isArray( args[0] )) {
				var dmArgs = jQuery.makeArray( arguments ), elems = args[0], elemsLength = elems.length, i = 0, tmplItem;
				while ( i < elemsLength && !(tmplItem = jQuery.data( elems[i++], "tmplItem" ))) {}
				if ( tmplItem && cloneIndex ) {
					dmArgs[2] = function( fragClone ) {
						// Handler called by oldManip when rendered template has been inserted into DOM.
						jQuery.tmpl.afterManip( this, fragClone, callback );
					};
				}
				oldManip.apply( this, dmArgs );
			} else {
				oldManip.apply( this, arguments );
			}
			cloneIndex = 0;
			if ( !appendToTmplItems ) {
				jQuery.tmpl.complete( newTmplItems );
			}
			return this;
		}
	});

	jQuery.extend({
		// Return wrapped set of template items, obtained by rendering template against data.
		tmpl: function( tmpl, data, options, parentItem ) {
			var ret, topLevel = !parentItem;
			if ( topLevel ) {
				// This is a top-level tmpl call (not from a nested template using {{tmpl}})
				parentItem = topTmplItem;
				tmpl = jQuery.template[tmpl] || jQuery.template( null, tmpl );
				wrappedItems = {}; // Any wrapped items will be rebuilt, since this is top level
			} else if ( !tmpl ) {
				// The template item is already associated with DOM - this is a refresh.
				// Re-evaluate rendered template for the parentItem
				tmpl = parentItem.tmpl;
				newTmplItems[parentItem.key] = parentItem;
				parentItem.nodes = [];
				if ( parentItem.wrapped ) {
					updateWrapped( parentItem, parentItem.wrapped );
				}
				// Rebuild, without creating a new template item
				return jQuery( build( parentItem, null, parentItem.tmpl( jQuery, parentItem ) ));
			}
			if ( !tmpl ) {
				return []; // Could throw...
			}
			if ( typeof data === "function" ) {
				data = data.call( parentItem || {} );
			}
			if ( options && options.wrapped ) {
				updateWrapped( options, options.wrapped );
			}
			ret = jQuery.isArray( data ) ?
				jQuery.map( data, function( dataItem ) {
					return dataItem ? newTmplItem( options, parentItem, tmpl, dataItem ) : null;
				}) :
				[ newTmplItem( options, parentItem, tmpl, data ) ];
			return topLevel ? jQuery( build( parentItem, null, ret ) ) : ret;
		},

		// Return rendered template item for an element.
		tmplItem: function( elem ) {
			var tmplItem;
			if ( elem instanceof jQuery ) {
				elem = elem[0];
			}
			while ( elem && elem.nodeType === 1 && !(tmplItem = jQuery.data( elem, "tmplItem" )) && (elem = elem.parentNode) ) {}
			return tmplItem || topTmplItem;
		},

		// Set:
		// Use $.template( name, tmpl ) to cache a named template,
		// where tmpl is a template string, a script element or a jQuery instance wrapping a script element, etc.
		// Use $( "selector" ).template( name ) to provide access by name to a script block template declaration.

		// Get:
		// Use $.template( name ) to access a cached template.
		// Also $( selectorToScriptBlock ).template(), or $.template( null, templateString )
		// will return the compiled template, without adding a name reference.
		// If templateString includes at least one HTML tag, $.template( templateString ) is equivalent
		// to $.template( null, templateString )
		template: function( name, tmpl ) {
			if (tmpl) {
				// Compile template and associate with name
				if ( typeof tmpl === "string" ) {
					// This is an HTML string being passed directly in.
					tmpl = buildTmplFn( tmpl );
				} else if ( tmpl instanceof jQuery ) {
					tmpl = tmpl[0] || {};
				}
				if ( tmpl.nodeType ) {
					// If this is a template block, use cached copy, or generate tmpl function and cache.
					tmpl = jQuery.data( tmpl, "tmpl" ) || jQuery.data( tmpl, "tmpl", buildTmplFn( tmpl.innerHTML ));
					// Issue: In IE, if the container element is not a script block, the innerHTML will remove quotes from attribute values whenever the value does not include white space.
					// This means that foo="${x}" will not work if the value of x includes white space: foo="${x}" -> foo=value of x.
					// To correct this, include space in tag: foo="${ x }" -> foo="value of x"
				}
				return typeof name === "string" ? (jQuery.template[name] = tmpl) : tmpl;
			}
			// Return named compiled template
			return name ? (typeof name !== "string" ? jQuery.template( null, name ):
				(jQuery.template[name] ||
					// If not in map, and not containing at least on HTML tag, treat as a selector.
					// (If integrated with core, use quickExpr.exec)
					jQuery.template( null, htmlExpr.test( name ) ? name : jQuery( name )))) : null;
		},

		encode: function( text ) {
			// Do HTML encoding replacing < > & and ' and " by corresponding entities.
			return ("" + text).split("<").join("&lt;").split(">").join("&gt;").split('"').join("&#34;").split("'").join("&#39;");
		}
	});

	jQuery.extend( jQuery.tmpl, {
		tag: {
			"tmpl": {
				_default: { $2: "null" },
				open: "if($notnull_1){__=__.concat($item.nest($1,$2));}"
				// tmpl target parameter can be of type function, so use $1, not $1a (so not auto detection of functions)
				// This means that {{tmpl foo}} treats foo as a template (which IS a function).
				// Explicit parens can be used if foo is a function that returns a template: {{tmpl foo()}}.
			},
			"wrap": {
				_default: { $2: "null" },
				open: "$item.calls(__,$1,$2);__=[];",
				close: "call=$item.calls();__=call._.concat($item.wrap(call,__));"
			},
			"each": {
				_default: { $2: "$index, $value" },
				open: "if($notnull_1){$.each($1a,function($2){with(this){",
				close: "}});}"
			},
			"if": {
				open: "if(($notnull_1) && $1a){",
				close: "}"
			},
			"else": {
				_default: { $1: "true" },
				open: "}else if(($notnull_1) && $1a){"
			},
			"html": {
				// Unecoded expression evaluation.
				open: "if($notnull_1){__.push($1a);}"
			},
			"=": {
				// Encoded expression evaluation. Abbreviated form is ${}.
				_default: { $1: "$data" },
				open: "if($notnull_1){__.push($.encode($1a));}"
			},
			"!": {
				// Comment tag. Skipped by parser
				open: ""
			}
		},

		// This stub can be overridden, e.g. in jquery.tmplPlus for providing rendered events
		complete: function( items ) {
			newTmplItems = {};
		},

		// Call this from code which overrides domManip, or equivalent
		// Manage cloning/storing template items etc.
		afterManip: function afterManip( elem, fragClone, callback ) {
			// Provides cloned fragment ready for fixup prior to and after insertion into DOM
			var content = fragClone.nodeType === 11 ?
				jQuery.makeArray(fragClone.childNodes) :
				fragClone.nodeType === 1 ? [fragClone] : [];

			// Return fragment to original caller (e.g. append) for DOM insertion
			callback.call( elem, fragClone );

			// Fragment has been inserted:- Add inserted nodes to tmplItem data structure. Replace inserted element annotations by jQuery.data.
			storeTmplItems( content );
			cloneIndex++;
		}
	});

	//========================== Private helper functions, used by code above ==========================

	function build( tmplItem, nested, content ) {
		// Convert hierarchical content into flat string array
		// and finally return array of fragments ready for DOM insertion
		var frag, ret = content ? jQuery.map( content, function( item ) {
			return (typeof item === "string") ?
				// Insert template item annotations, to be converted to jQuery.data( "tmplItem" ) when elems are inserted into DOM.
				(tmplItem.key ? item.replace( /(<\w+)(?=[\s>])(?![^>]*_tmplitem)([^>]*)/g, "$1 " + tmplItmAtt + "=\"" + tmplItem.key + "\" $2" ) : item) :
				// This is a child template item. Build nested template.
				build( item, tmplItem, item._ctnt );
		}) :
		// If content is not defined, insert tmplItem directly. Not a template item. May be a string, or a string array, e.g. from {{html $item.html()}}.
		tmplItem;
		if ( nested ) {
			return ret;
		}

		// top-level template
		ret = ret.join("");

		// Support templates which have initial or final text nodes, or consist only of text
		// Also support HTML entities within the HTML markup.
		ret.replace( /^\s*([^<\s][^<]*)?(<[\w\W]+>)([^>]*[^>\s])?\s*$/, function( all, before, middle, after) {
			frag = jQuery( middle ).get();

			storeTmplItems( frag );
			if ( before ) {
				frag = unencode( before ).concat(frag);
			}
			if ( after ) {
				frag = frag.concat(unencode( after ));
			}
		});
		return frag ? frag : unencode( ret );
	}

	function unencode( text ) {
		// Use createElement, since createTextNode will not render HTML entities correctly
		var el = document.createElement( "div" );
		el.innerHTML = text;
		return jQuery.makeArray(el.childNodes);
	}

	// Generate a reusable function that will serve to render a template against data
	function buildTmplFn( markup ) {
		return new Function("jQuery","$item",
			// Use the variable __ to hold a string array while building the compiled template. (See https://github.com/jquery/jquery-tmpl/issues#issue/10).
			"var $=jQuery,call,__=[],$data=$item.data;" +

			// Introduce the data as local variables using with(){}
			"with($data){__.push('" +

			// Convert the template into pure JavaScript
			jQuery.trim(markup)
				.replace( /([\\'])/g, "\\$1" )
				.replace( /[\r\t\n]/g, " " )
				.replace( /\$\{([^\}]*)\}/g, "{{= $1}}" )
				.replace( /\{\{(\/?)(\w+|.)(?:\(((?:[^\}]|\}(?!\}))*?)?\))?(?:\s+(.*?)?)?(\(((?:[^\}]|\}(?!\}))*?)\))?\s*\}\}/g,
				function( all, slash, type, fnargs, target, parens, args ) {
					var tag = jQuery.tmpl.tag[ type ], def, expr, exprAutoFnDetect;
					if ( !tag ) {
						throw "Unknown template tag: " + type;
					}
					def = tag._default || [];
					if ( parens && !/\w$/.test(target)) {
						target += parens;
						parens = "";
					}
					if ( target ) {
						target = unescape( target );
						args = args ? ("," + unescape( args ) + ")") : (parens ? ")" : "");
						// Support for target being things like a.toLowerCase();
						// In that case don't call with template item as 'this' pointer. Just evaluate...
						expr = parens ? (target.indexOf(".") > -1 ? target + unescape( parens ) : ("(" + target + ").call($item" + args)) : target;
						exprAutoFnDetect = parens ? expr : "(typeof(" + target + ")==='function'?(" + target + ").call($item):(" + target + "))";
					} else {
						exprAutoFnDetect = expr = def.$1 || "null";
					}
					fnargs = unescape( fnargs );
					return "');" +
						tag[ slash ? "close" : "open" ]
							.split( "$notnull_1" ).join( target ? "typeof(" + target + ")!=='undefined' && (" + target + ")!=null" : "true" )
							.split( "$1a" ).join( exprAutoFnDetect )
							.split( "$1" ).join( expr )
							.split( "$2" ).join( fnargs || def.$2 || "" ) +
						"__.push('";
				}) +
			"');}return __;"
		);
	}
	function updateWrapped( options, wrapped ) {
		// Build the wrapped content.
		options._wrap = build( options, true,
			// Suport imperative scenario in which options.wrapped can be set to a selector or an HTML string.
			jQuery.isArray( wrapped ) ? wrapped : [htmlExpr.test( wrapped ) ? wrapped : jQuery( wrapped ).html()]
		).join("");
	}

	function unescape( args ) {
		return args ? args.replace( /\\'/g, "'").replace(/\\\\/g, "\\" ) : null;
	}
	function outerHtml( elem ) {
		var div = document.createElement("div");
		div.appendChild( elem.cloneNode(true) );
		return div.innerHTML;
	}

	// Store template items in jQuery.data(), ensuring a unique tmplItem data data structure for each rendered template instance.
	function storeTmplItems( content ) {
		var keySuffix = "_" + cloneIndex, elem, elems, newClonedItems = {}, i, l, m;
		for ( i = 0, l = content.length; i < l; i++ ) {
			if ( (elem = content[i]).nodeType !== 1 ) {
				continue;
			}
			elems = elem.getElementsByTagName("*");
			for ( m = elems.length - 1; m >= 0; m-- ) {
				processItemKey( elems[m] );
			}
			processItemKey( elem );
		}
		function processItemKey( el ) {
			var pntKey, pntNode = el, pntItem, tmplItem, key;
			// Ensure that each rendered template inserted into the DOM has its own template item,
			if ( (key = el.getAttribute( tmplItmAtt ))) {
				while ( pntNode.parentNode && (pntNode = pntNode.parentNode).nodeType === 1 && !(pntKey = pntNode.getAttribute( tmplItmAtt ))) { }
				if ( pntKey !== key ) {
					// The next ancestor with a _tmplitem expando is on a different key than this one.
					// So this is a top-level element within this template item
					// Set pntNode to the key of the parentNode, or to 0 if pntNode.parentNode is null, or pntNode is a fragment.
					pntNode = pntNode.parentNode ? (pntNode.nodeType === 11 ? 0 : (pntNode.getAttribute( tmplItmAtt ) || 0)) : 0;
					if ( !(tmplItem = newTmplItems[key]) ) {
						// The item is for wrapped content, and was copied from the temporary parent wrappedItem.
						tmplItem = wrappedItems[key];
						tmplItem = newTmplItem( tmplItem, newTmplItems[pntNode]||wrappedItems[pntNode] );
						tmplItem.key = ++itemKey;
						newTmplItems[itemKey] = tmplItem;
					}
					if ( cloneIndex ) {
						cloneTmplItem( key );
					}
				}
				el.removeAttribute( tmplItmAtt );
			} else if ( cloneIndex && (tmplItem = jQuery.data( el, "tmplItem" )) ) {
				// This was a rendered element, cloned during append or appendTo etc.
				// TmplItem stored in jQuery data has already been cloned in cloneCopyEvent. We must replace it with a fresh cloned tmplItem.
				cloneTmplItem( tmplItem.key );
				newTmplItems[tmplItem.key] = tmplItem;
				pntNode = jQuery.data( el.parentNode, "tmplItem" );
				pntNode = pntNode ? pntNode.key : 0;
			}
			if ( tmplItem ) {
				pntItem = tmplItem;
				// Find the template item of the parent element.
				// (Using !=, not !==, since pntItem.key is number, and pntNode may be a string)
				while ( pntItem && pntItem.key != pntNode ) {
					// Add this element as a top-level node for this rendered template item, as well as for any
					// ancestor items between this item and the item of its parent element
					pntItem.nodes.push( el );
					pntItem = pntItem.parent;
				}
				// Delete content built during rendering - reduce API surface area and memory use, and avoid exposing of stale data after rendering...
				delete tmplItem._ctnt;
				delete tmplItem._wrap;
				// Store template item as jQuery data on the element
				jQuery.data( el, "tmplItem", tmplItem );
			}
			function cloneTmplItem( key ) {
				key = key + keySuffix;
				tmplItem = newClonedItems[key] =
					(newClonedItems[key] || newTmplItem( tmplItem, newTmplItems[tmplItem.parent.key + keySuffix] || tmplItem.parent ));
			}
		}
	}

	//---- Helper functions for template item ----

	function tiCalls( content, tmpl, data, options ) {
		if ( !content ) {
			return stack.pop();
		}
		stack.push({ _: content, tmpl: tmpl, item:this, data: data, options: options });
	}

	function tiNest( tmpl, data, options ) {
		// nested template, using {{tmpl}} tag
		return jQuery.tmpl( jQuery.template( tmpl ), data, options, this );
	}

	function tiWrap( call, wrapped ) {
		// nested template, using {{wrap}} tag
		var options = call.options || {};
		options.wrapped = wrapped;
		// Apply the template, which may incorporate wrapped content,
		return jQuery.tmpl( jQuery.template( call.tmpl ), call.data, options, call.item );
	}

	function tiHtml( filter, textOnly ) {
		var wrapped = this._wrap;
		return jQuery.map(
			jQuery( jQuery.isArray( wrapped ) ? wrapped.join("") : wrapped ).filter( filter || "*" ),
			function(e) {
				return textOnly ?
					e.innerText || e.textContent :
					e.outerHTML || outerHtml(e);
			});
	}

	function tiUpdate() {
		var coll = this.nodes;
		jQuery.tmpl( null, null, null, this).insertBefore( coll[0] );
		jQuery( coll ).remove();
	}
})( jQuery );
(function() {
  var $, Message, TimerFunc, deleteThumbs, deleteThumbsSuccessCallback, determineAspectRatio, gcd, pte, pte_queue, toType, window;
  var __bind = function(fn, me){ return function(){ return fn.apply(me, arguments); }; };
  window = this;
  $ = window.jQuery;
  window.pte = pte = pte || {};
  (function(pte) {
    return pte.fixThickbox = function(parent) {
      var $p, $thickbox, height, width;
      $p = parent.jQuery;
      if ($p === null || parent.frames.length < 1) {
        return;
      }
      log("===== FIXING THICKBOX =====");
      width = window.options.pte_tb_width + 30;
      height = window.options.pte_tb_height + 38;
      $thickbox = $p("#TB_window");
      if ($thickbox.width() >= width && $thickbox.height() >= height) {
        return;
      }
      log("THICKBOX: " + ($thickbox.width()) + " x " + ($thickbox.height()));
      $thickbox.css({
        'margin-left': 0 - (width / 2),
        'width': width,
        'height': height
      }).children("iframe").css({
        'width': width
      });
      return parent.setTimeout(function() {
        if ($p("iframe", $thickbox).height() > height) {
          return;
        }
        $p("iframe", $thickbox).css({
          'height': height
        });
        log("THICKBOX: " + ($thickbox.width()) + " x " + ($thickbox.height()));
        return true;
      }, 1000);
    };
  })(pte);
  toType = function(obj) {
    return {}.toString.call(obj).match(/\s([a-z|A-Z]+)/)[1].toLowerCase();
  };
  Message = (function() {
    function Message(message) {
      this.message = message;
      this.date = new Date();
    }
    Message.prototype.toString = function() {
      var D, M, d, h, m, message, pad, s, y;
      pad = function(num, pad) {
        while (("" + num).length < pad) {
          num = "0" + num;
        }
        return num;
      };
      d = this.date;
      y = pad(d.getUTCFullYear(), 4);
      M = pad(d.getUTCMonth() + 1, 2);
      D = pad(d.getUTCDate(), 2);
      h = pad(d.getUTCHours(), 2);
      m = pad(d.getUTCMinutes(), 2);
      s = pad(d.getUTCSeconds(), 2);
      switch (toType(this.message)) {
        case "string":
          message = this.message;
          break;
        default:
          message = $.toJSON(this.message);
      }
      return "" + y + M + D + " " + h + ":" + m + ":" + s + " - [" + (toType(this.message)) + "] " + message;
    };
    return Message;
  })();
  (function(pte) {
    pte.messages = [];
    pte.log = function(obj) {
      if (!window.options.pte_debug) {
        return true;
      }
      try {
        pte.messages.push(new Message(obj));
        console.log(obj);
        $('#pte-log-messages textarea').filter(':visible').val(pte.formatLog());
      } catch (error) {

      }
    };
    pte.formatLog = function() {
      var log, message, _i, _len, _ref;
      log = "";
      _ref = pte.messages;
      for (_i = 0, _len = _ref.length; _i < _len; _i++) {
        message = _ref[_i];
        log += "" + message + "\n";
      }
      return log;
    };
    pte.parseServerLog = function(json) {
      var message, _i, _len;
      log("===== SERVER LOG =====");
      if (((json != null ? json.length : void 0) != null) && json.length > 0) {
        for (_i = 0, _len = json.length; _i < _len; _i++) {
          message = json[_i];
          log(message);
        }
      }
      return true;
    };
    pte.sendToPastebin = function(text) {
      var pastebin_url, post_data;
      pastebin_url = "http://dpastey.appspot.com/";
      post_data = {
        title: "PostThumbnailEditor Log",
        content: text,
        lexer: "text",
        format: "json",
        expire_options: "2592000"
      };
      return $.ajax({
        url: pastebin_url,
        data: post_data,
        dataType: "json",
        global: false,
        type: "POST",
        error: function(xhr, status, errorThrown) {
          $('#pte-log').fadeOut('900');
          alert(objectL10n.pastebin_create_error);
          log(xhr);
          log(status);
          return log(errorThrown);
        },
        success: function(data, status, xhr) {
          $('#pte-log').fadeOut('900');
          return prompt(objectL10n.pastebin_url, data.url);
        }
      });
    };
    return true;
  })(pte);
  window.log = pte.log;
  $(document).ready(function($) {
    $('#test').click(function(e) {
      e.stopImmediatePropagation();
      return true;
    });
    $('#pastebin').click(function(e) {
      return pte.sendToPastebin(pte.formatLog());
    });
    $('#clear-log').click(function(e) {
      pte.messages = [];
      return $('#pte-log-messages textarea').val(pte.formatLog());
    });
    $('#close-log').click(function(e) {
      return $('#pte-log').fadeOut('900');
    });
    $('#pte-log-tools a').click(function(e) {
      return e.preventDefault();
    });
    return $('body').delegate('.show-log-messages', 'click', function(e) {
      e.preventDefault();
      $('#pte-log-messages textarea').val(pte.formatLog());
      return $('#pte-log').fadeIn('900');
    });
  });
  /*
    POST-THUMBNAIL-EDITOR Script for Wordpress
  
    Hooks into the Wordpress Media Library
  */
  (function(pte) {
    return pte.admin = function() {
      var $getLink, checkExistingThickbox, image_id, injectPTE, launchPTE, pte_url, thickbox, timeout;
      timeout = 300;
      thickbox = "&TB_iframe=true&height=" + window.options.pte_tb_height + "&width=" + window.options.pte_tb_width;
      image_id = null;
      pte_url = function(override_id) {
        var id;
        id = override_id || image_id || $("#attachment-id").val();
        return "" + ajaxurl + "?action=pte_ajax&pte-action=launch&id=" + id + thickbox;
      };
      $getLink = function(id) {
        return $("<a class=\"thickbox\" href=\"" + (pte_url(id)) + "\">" + objectL10n.PTE + "</a>");
      };
      checkExistingThickbox = function(e) {
        log("Start PTE...");
        if (window.parent.frames.length > 0) {
          log("Modifying thickbox...");
          __bind(function() {
            window.parent.tb_click();
            return true;
          }, this)();
          return e.stopPropagation();
        }
      };
      injectPTE = function() {
        $('.media-item').each(function(i, elem) {
          var post_id;
          post_id = elem.id.replace("media-item-", "");
          return $getLink(post_id).css({
            'font-size': '.8em',
            'margin-left': '5px'
          }).click(checkExistingThickbox).appendTo($('tr.image-size th.label', elem));
        });
        if (imageEdit.open != null) {
          imageEdit.oldopen = imageEdit.open;
          imageEdit.open = function(id, nonce) {
            image_id = id;
            imageEdit.oldopen(id, nonce);
            return launchPTE();
          };
        }
        return true;
      };
      launchPTE = function() {
        var $editmenu, selector;
        selector = "#imgedit-save-target-" + image_id;
        $editmenu = $(selector);
        if (($editmenu != null ? $editmenu.size() : void 0) < 1) {
          window.log("Edit Thumbnail Menu not visible, waiting for " + timeout + "ms");
          window.setTimeout(launchPTE, timeout);
          return false;
        }
        return $editmenu.append($getLink().click(checkExistingThickbox));
      };
      return injectPTE();
    };
  })(pte);
  TimerFunc = (function() {
    function TimerFunc(fn, timeout) {
      this.fn = fn;
      this.timeout = timeout;
      this.doFunc = __bind(this.doFunc, this);
      this.timer = null;
    }
    TimerFunc.prototype.doFunc = function(e) {
      window.clearTimeout(this.timer);
      this.timer = window.setTimeout(this.fn, this.timeout);
      return true;
    };
    return TimerFunc;
  })();
  window.randomness = function() {
    return Math.floor(Math.random() * 1000001).toString(16);
  };
  window.debugTmpl = function(data) {
    log("===== TEMPLATE DEBUG DATA FOLLOWS =====");
    log(data);
    return true;
  };
  deleteThumbs = function(id) {
    var delete_options;
    delete_options = {
      "id": id,
      'action': 'pte_ajax',
      'pte-action': 'delete-images',
      'pte-nonce': $('#pte-delete-nonce').val()
    };
    return $.ajax({
      url: ajaxurl,
      data: delete_options,
      global: false,
      dataType: "json",
      success: deleteThumbsSuccessCallback
    });
  };
  deleteThumbsSuccessCallback = function(data, status, xhr) {
    log("===== DELETE SUCCESSFUL, DATA DUMP FOLLOWS =====");
    log(data);
    return pte.parseServerLog(data.log);
  };
  pte_queue = $({});
  $.fn.extend({
    move: function(options) {
      var defaults;
      defaults = {
        direction: 'left',
        speed: 500,
        easing: 'swing',
        toggle: true,
        callback: null,
        callbackargs: null
      };
      options = $.extend(defaults, options);
      this.each(function() {
        return pte_queue.queue(__bind(function(next) {
          var $elem, direction, isVisible, move_to;
          $elem = $(this);
          direction = options.direction === 'left' ? -1 : 1;
          move_to = $elem.css('left') === "0px" ? $(window).width() * direction : 0;
          isVisible = $elem.is(':visible');
          log([direction, move_to, isVisible]);
          if (!isVisible) {
            $elem.show(0, function() {
              return $(this).animate({
                'left': move_to
              }, options.speed, options.easing, next);
            });
          } else {
            $elem.animate({
              'left': move_to
            }, options.speed, options.easing);
            $elem.hide(0, next);
          }
          return true;
        }, this));
      });
      if (options.callback != null) {
        pte_queue.queue(function(next) {
          if (options.callbackargs != null) {
            log("running callback with arguments");
            options.callback.apply(this, options.callbackargs);
          } else {
            log("running callback with no arguments");
            options.callback.apply(this);
          }
          log("finished running callback");
          return next();
        });
      }
      return this;
    },
    moveRight: function(options) {
      options = $.extend(options, {
        direction: 'right'
      });
      return this.move(options);
    },
    moveLeft: function(options) {
      options = $.extend(options, {
        direction: 'left'
      });
      return this.move(options);
    }
  });
  window.goBack = function(e) {
    if (e != null) {
      e.preventDefault();
    }
    $('#stage2').moveRight();
    $('#stage1').moveRight({
      callback: function() {
        deleteThumbs($('#pte-post-id').val());
        return $('#stage2').html('');
      }
    });
    return true;
  };
  gcd = function(a, b) {
    if (a === 0) {
      return b;
    }
    while (b > 0) {
      if (a > b) {
        a = a - b;
      } else {
        b = b - a;
      }
    }
    if (a < 0 || b < 0) {
      return null;
    }
    return a;
  };
  determineAspectRatio = function(current_ar, size_info) {
    var crop, gc, height, tmp_ar, width;
    crop = size_info.crop, width = size_info.width, height = size_info.height;
    crop = +crop;
    width = +width;
    height = +height;
    gc = gcd(width, height);
    if ((crop != null) && crop > 0) {
      tmp_ar = null;
      if ((width != null) > 0 && (height != null) > 0) {
        if (gc != null) {
          tmp_ar = "" + (width / gc) + ":" + (height / gc);
        } else {
          tmp_ar = "" + width + ":" + height;
        }
      }
      if ((current_ar != null) && (tmp_ar != null) && tmp_ar !== current_ar) {
        throw objectL10n.aspect_ratio_disabled;
      }
      current_ar = tmp_ar;
    }
    return current_ar;
  };
  pte.functions = {
    determineAspectRatio: determineAspectRatio
  };
  (function(pte) {
    var addCheckAllNoneListener, addRowListener, addRowListeners, addSubmitListener, addVerifyListener, configureOverlay, configurePageDisplay, editor, iasSetAR, ias_defaults, ias_instance, initImgAreaSelect;
    editor = pte.editor = function() {
      configurePageDisplay();
      addRowListeners();
      initImgAreaSelect();
      addRowListener();
      addSubmitListener();
      addVerifyListener();
      addCheckAllNoneListener();
      configureOverlay();
      return true;
    };
    configureOverlay = function() {
      var $loading_screen, closeLoadingScreen;
      $loading_screen = $('#pte-loading');
      closeLoadingScreen = function() {
        $loading_screen.hide();
        return true;
      };
      $('#pte-preview').load(closeLoadingScreen);
      $loading_screen.ajaxStart(function() {
        return $(this).fadeIn(200);
      }).ajaxStop(function() {
        return $(this).fadeOut(200);
      });
      window.setTimeout(closeLoadingScreen, 2000);
      return true;
    };
    configurePageDisplay = function() {
      var reflow;
      reflow = new TimerFunc(function() {
        var offset, window_height;
        log("===== REFLOW =====");
        pte.fixThickbox(window.parent);
        offset = $("#pte-sizes").offset();
        window_height = $(window).height() - offset.top - 2;
        $("#pte-sizes").height(window_height);
        log("WINDOW WIDTH: " + ($(window).width()));
        $('#stage2, #stage3').filter(":hidden").css({
          left: $(window).width()
        });
        return true;
      }, 100);
      $(window).resize(reflow.doFunc).load(reflow.doFunc);
      return true;
    };
    addRowListeners = function() {
      var enableRowFeatures;
      enableRowFeatures = function($elem) {
        $elem.delegate('tr', 'click', function(e) {
          if (e.target.type !== 'checkbox') {
            $('input:checkbox', this).click();
          }
          return true;
        });
        return $elem.delegate('input:checkbox', 'click', function(e) {
          if (this.checked || $(this).is('input:checked')) {
            $(this).parents('tr').first().removeClass('selected');
          } else {
            $(this).parents('tr').first().addClass('selected');
          }
          return true;
        });
      };
      enableRowFeatures($('#stage2'));
      return enableRowFeatures($('#stage1'));
    };
    /* Enable imgareaselect plugin */
    ias_instance = null;
    ias_defaults = {
      keys: true,
      minWidth: 3,
      minHeight: 3,
      handles: true,
      zIndex: 1200,
      instance: true,
      onSelectEnd: function(img, s) {
        if (s.width && s.width > 0 && s.height && s.height > 0 && $('.pte-size').filter(':checked').size() > 0) {
          return $('#pte-submit').removeAttr('disabled');
        } else {
          return $('#pte-submit').attr('disabled', true);
        }
      }
    };
    initImgAreaSelect = function() {
      return pte.ias = ias_instance = $('#pte-image img').imgAreaSelect(ias_defaults);
    };
    iasSetAR = function(ar) {
      log("===== SETTING ASPECTRATIO: " + ar + " =====");
      ias_instance.setOptions({
        aspectRatio: ar
      });
      return ias_instance.update();
    };
    addRowListener = function() {
      var pteCheckHandler, pteVerifySubmitButtonHandler;
      pteVerifySubmitButtonHandler = new TimerFunc(function() {
        log("===== CHECK SUBMIT BUTTON =====");
        if ($('.pte-confirm').filter(':checked').size() > 0) {
          log("ENABLE");
          $('#pte-confirm').removeAttr('disabled');
        } else {
          log("DISABLE");
          $('#pte-confirm').attr('disabled', true);
        }
        return true;
      }, 50);
      pteCheckHandler = new TimerFunc(function() {
        var ar, selected_elements;
        ar = null;
        selected_elements = $('input.pte-size').filter(':checked').each(function(i, elem) {
          try {
            ar = determineAspectRatio(ar, thumbnail_info[$(elem).val()]);
          } catch (error) {
            ar = null;
            if (ar !== ias_instance.getOptions().aspectRatio) {
              alert(error);
            }
            return false;
          }
          return true;
        });
        iasSetAR(ar);
        ias_defaults.onSelectEnd(null, ias_instance.getSelection());
        return true;
      }, 50);
      $.extend(pte.functions, {
        pteVerifySubmitButtonHandler: pteVerifySubmitButtonHandler
      });
      $('input.pte-size').click(pteCheckHandler.doFunc);
      return $('.pte-confirm').live('click', function(e) {
        return pteVerifySubmitButtonHandler.doFunc();
      });
    };
    addSubmitListener = function() {
      var onResizeImages;
      $('#pte-submit').click(function(e) {
        var scale_factor, selection, submit_data;
        selection = ias_instance.getSelection();
        scale_factor = $('#pte-sizer').val();
        submit_data = {
          'id': $('#pte-post-id').val(),
          'action': 'pte_ajax',
          'pte-action': 'resize-images',
          'pte-sizes[]': $('.pte-size').filter(':checked').map(function() {
            return $(this).val();
          }).get(),
          'x': Math.floor(selection.x1 / scale_factor),
          'y': Math.floor(selection.y1 / scale_factor),
          'w': Math.floor(selection.width / scale_factor),
          'h': Math.floor(selection.height / scale_factor)
        };
        log("===== RESIZE-IMAGES =====");
        log(submit_data);
        if (isNaN(submit_data.x) || isNaN(submit_data.y) || isNaN(submit_data.w) || isNaN(submit_data.h)) {
          alert(objectL10n.crop_submit_data_error);
          log("ERROR with submit_data and NaN's");
          return false;
        }
        ias_instance.setOptions({
          hide: true,
          x1: 0,
          y1: 0,
          x2: 0,
          y2: 0
        });
        $('#pte-submit').attr('disabled', true);
        $.getJSON(ajaxurl, submit_data, onResizeImages);
        return true;
      });
      return onResizeImages = function(data, status, xhr) {
        /* Evaluate data */        log("===== RESIZE-IMAGES SUCCESS =====");
        log(data);
        pte.parseServerLog(data.log);
        if ((data.error != null) && !(data.thumbnails != null)) {
          alert(data.error);
          return;
        }
        $('#stage1').moveLeft();
        $('#stage2').html($('#stage2template').tmpl(data)).moveLeft({
          callback: pte.functions.pteVerifySubmitButtonHandler.doFunc
        });
        return false;
      };
    };
    /* Callback for Stage 2 to 3 */
    addVerifyListener = function() {
      var onConfirmImages;
      $('#pte-confirm').live('click', function(e) {
        var submit_data, thumbnail_data;
        thumbnail_data = {};
        $('input.pte-confirm').filter(':checked').each(function(i, elem) {
          var size;
          size = $(elem).val();
          return thumbnail_data[size] = $(elem).parent().parent().find('.pte-file').val();
        });
        submit_data = {
          'id': $('#pte-post-id').val(),
          'action': 'pte_ajax',
          'pte-action': 'confirm-images',
          'pte-nonce': $('#pte-nonce').val(),
          'pte-confirm': thumbnail_data
        };
        log("===== CONFIRM-IMAGES =====");
        log(submit_data);
        return $.getJSON(ajaxurl, submit_data, onConfirmImages);
      });
      return onConfirmImages = function(data, status, xhr) {
        log("===== CONFIRM-IMAGES SUCCESS =====");
        log(data);
        pte.parseServerLog(data.log);
        $('#stage2').moveLeft();
        $('#stage3').html($('#stage3template').tmpl(data)).moveLeft();
        return false;
      };
    };
    /* Select ALL|NONE */
    addCheckAllNoneListener = function() {
      var checkAllSizes, uncheckAllSizes;
      uncheckAllSizes = function(e) {
        var elements, _ref, _ref2;
        if (e != null) {
          e.preventDefault();
        }
        elements = (_ref = (_ref2 = e.data) != null ? _ref2.selector : void 0) != null ? _ref : '.pte-size';
        return $(elements).filter(':checked').click();
      };
      checkAllSizes = function(e) {
        var elements, _ref, _ref2;
        if (e != null) {
          e.preventDefault();
        }
        elements = (_ref = e != null ? (_ref2 = e.data) != null ? _ref2.selector : void 0 : void 0) != null ? _ref : '.pte-size';
        return $(elements).not(':checked').click();
      };
      $("#pte-selectors .all").click(checkAllSizes);
      $("#pte-selectors .none").click(uncheckAllSizes).click();
      $('#stage2').delegate('#pte-stage2-selectors .all', 'click', {
        selector: '.pte-confirm'
      }, checkAllSizes);
      $('#stage2').delegate('#pte-stage2-selectors .none', 'click', {
        selector: '.pte-confirm'
      }, uncheckAllSizes);
      return true;
    };
    return $.extend(pte.functions, {
      iasSetAR: iasSetAR
    });
  })(pte);
}).call(this);
