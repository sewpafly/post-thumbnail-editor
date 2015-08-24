/******/ (function(modules) { // webpackBootstrap
/******/ 	// The module cache
/******/ 	var installedModules = {};
/******/
/******/ 	// The require function
/******/ 	function __webpack_require__(moduleId) {
/******/
/******/ 		// Check if module is in cache
/******/ 		if(installedModules[moduleId])
/******/ 			return installedModules[moduleId].exports;
/******/
/******/ 		// Create a new module (and put it into the cache)
/******/ 		var module = installedModules[moduleId] = {
/******/ 			exports: {},
/******/ 			id: moduleId,
/******/ 			loaded: false
/******/ 		};
/******/
/******/ 		// Execute the module function
/******/ 		modules[moduleId].call(module.exports, module, module.exports, __webpack_require__);
/******/
/******/ 		// Flag the module as loaded
/******/ 		module.loaded = true;
/******/
/******/ 		// Return the exports of the module
/******/ 		return module.exports;
/******/ 	}
/******/
/******/
/******/ 	// expose the modules object (__webpack_modules__)
/******/ 	__webpack_require__.m = modules;
/******/
/******/ 	// expose the module cache
/******/ 	__webpack_require__.c = installedModules;
/******/
/******/ 	// __webpack_public_path__
/******/ 	__webpack_require__.p = "";
/******/
/******/ 	// Load entry module and return exports
/******/ 	return __webpack_require__(0);
/******/ })
/************************************************************************/
/******/ ([
/* 0 */
/***/ function(module, exports, __webpack_require__) {

	'use strict';
	
	var riot = __webpack_require__(1);
	var rc = __webpack_require__(2);
	var css = __webpack_require__(3);
	
	// Riot Tags
	var pte_app = __webpack_require__(7);
	var editor = __webpack_require__(10);
	var editor_menu = __webpack_require__(11);
	var thumbnails = __webpack_require__(12);
	
	var PteStore = __webpack_require__(13);
	
	var store = new PteStore();
	rc.addStore(store);
	riot.mount('*', { store: store });

/***/ },
/* 1 */
/***/ function(module, exports, __webpack_require__) {

	var __WEBPACK_AMD_DEFINE_RESULT__;/* Riot v2.2.4, @license MIT, (c) 2015 Muut Inc. + contributors */
	
	;(function(window, undefined) {
	  'use strict';
	var riot = { version: 'v2.2.4', settings: {} },
	  //// be aware, internal usage
	
	  // counter to give a unique id to all the Tag instances
	  __uid = 0,
	
	  // riot specific prefixes
	  RIOT_PREFIX = 'riot-',
	  RIOT_TAG = RIOT_PREFIX + 'tag',
	
	  // for typeof == '' comparisons
	  T_STRING = 'string',
	  T_OBJECT = 'object',
	  T_UNDEF  = 'undefined',
	  T_FUNCTION = 'function',
	  // special native tags that cannot be treated like the others
	  SPECIAL_TAGS_REGEX = /^(?:opt(ion|group)|tbody|col|t[rhd])$/,
	  RESERVED_WORDS_BLACKLIST = ['_item', '_id', 'update', 'root', 'mount', 'unmount', 'mixin', 'isMounted', 'isLoop', 'tags', 'parent', 'opts', 'trigger', 'on', 'off', 'one'],
	
	  // version# for IE 8-11, 0 for others
	  IE_VERSION = (window && window.document || {}).documentMode | 0,
	
	  // Array.isArray for IE8 is in the polyfills
	  isArray = Array.isArray
	
	riot.observable = function(el) {
	
	  el = el || {}
	
	  var callbacks = {},
	      _id = 0
	
	  el.on = function(events, fn) {
	    if (isFunction(fn)) {
	      if (typeof fn.id === T_UNDEF) fn._id = _id++
	
	      events.replace(/\S+/g, function(name, pos) {
	        (callbacks[name] = callbacks[name] || []).push(fn)
	        fn.typed = pos > 0
	      })
	    }
	    return el
	  }
	
	  el.off = function(events, fn) {
	    if (events == '*') callbacks = {}
	    else {
	      events.replace(/\S+/g, function(name) {
	        if (fn) {
	          var arr = callbacks[name]
	          for (var i = 0, cb; (cb = arr && arr[i]); ++i) {
	            if (cb._id == fn._id) arr.splice(i--, 1)
	          }
	        } else {
	          callbacks[name] = []
	        }
	      })
	    }
	    return el
	  }
	
	  // only single event supported
	  el.one = function(name, fn) {
	    function on() {
	      el.off(name, on)
	      fn.apply(el, arguments)
	    }
	    return el.on(name, on)
	  }
	
	  el.trigger = function(name) {
	    var args = [].slice.call(arguments, 1),
	        fns = callbacks[name] || []
	
	    for (var i = 0, fn; (fn = fns[i]); ++i) {
	      if (!fn.busy) {
	        fn.busy = 1
	        fn.apply(el, fn.typed ? [name].concat(args) : args)
	        if (fns[i] !== fn) { i-- }
	        fn.busy = 0
	      }
	    }
	
	    if (callbacks.all && name != 'all') {
	      el.trigger.apply(el, ['all', name].concat(args))
	    }
	
	    return el
	  }
	
	  return el
	
	}
	riot.mixin = (function() {
	  var mixins = {}
	
	  return function(name, mixin) {
	    if (!mixin) return mixins[name]
	    mixins[name] = mixin
	  }
	
	})()
	
	;(function(riot, evt, win) {
	
	  // browsers only
	  if (!win) return
	
	  var loc = win.location,
	      fns = riot.observable(),
	      started = false,
	      current
	
	  function hash() {
	    return loc.href.split('#')[1] || ''   // why not loc.hash.splice(1) ?
	  }
	
	  function parser(path) {
	    return path.split('/')
	  }
	
	  function emit(path) {
	    if (path.type) path = hash()
	
	    if (path != current) {
	      fns.trigger.apply(null, ['H'].concat(parser(path)))
	      current = path
	    }
	  }
	
	  var r = riot.route = function(arg) {
	    // string
	    if (arg[0]) {
	      loc.hash = arg
	      emit(arg)
	
	    // function
	    } else {
	      fns.on('H', arg)
	    }
	  }
	
	  r.exec = function(fn) {
	    fn.apply(null, parser(hash()))
	  }
	
	  r.parser = function(fn) {
	    parser = fn
	  }
	
	  r.stop = function () {
	    if (started) {
	      if (win.removeEventListener) win.removeEventListener(evt, emit, false) //@IE8 - the if()
	      else win.detachEvent('on' + evt, emit) //@IE8
	      fns.off('*')
	      started = false
	    }
	  }
	
	  r.start = function () {
	    if (!started) {
	      if (win.addEventListener) win.addEventListener(evt, emit, false) //@IE8 - the if()
	      else win.attachEvent('on' + evt, emit) //IE8
	      started = true
	    }
	  }
	
	  // autostart the router
	  r.start()
	
	})(riot, 'hashchange', window)
	/*
	
	//// How it works?
	
	
	Three ways:
	
	1. Expressions: tmpl('{ value }', data).
	   Returns the result of evaluated expression as a raw object.
	
	2. Templates: tmpl('Hi { name } { surname }', data).
	   Returns a string with evaluated expressions.
	
	3. Filters: tmpl('{ show: !done, highlight: active }', data).
	   Returns a space separated list of trueish keys (mainly
	   used for setting html classes), e.g. "show highlight".
	
	
	// Template examples
	
	tmpl('{ title || "Untitled" }', data)
	tmpl('Results are { results ? "ready" : "loading" }', data)
	tmpl('Today is { new Date() }', data)
	tmpl('{ message.length > 140 && "Message is too long" }', data)
	tmpl('This item got { Math.round(rating) } stars', data)
	tmpl('<h1>{ title }</h1>{ body }', data)
	
	
	// Falsy expressions in templates
	
	In templates (as opposed to single expressions) all falsy values
	except zero (undefined/null/false) will default to empty string:
	
	tmpl('{ undefined } - { false } - { null } - { 0 }', {})
	// will return: " - - - 0"
	
	*/
	
	
	var brackets = (function(orig) {
	
	  var cachedBrackets,
	      r,
	      b,
	      re = /[{}]/g
	
	  return function(x) {
	
	    // make sure we use the current setting
	    var s = riot.settings.brackets || orig
	
	    // recreate cached vars if needed
	    if (cachedBrackets !== s) {
	      cachedBrackets = s
	      b = s.split(' ')
	      r = b.map(function (e) { return e.replace(/(?=.)/g, '\\') })
	    }
	
	    // if regexp given, rewrite it with current brackets (only if differ from default)
	    return x instanceof RegExp ? (
	        s === orig ? x :
	        new RegExp(x.source.replace(re, function(b) { return r[~~(b === '}')] }), x.global ? 'g' : '')
	      ) :
	      // else, get specific bracket
	      b[x]
	  }
	})('{ }')
	
	
	var tmpl = (function() {
	
	  var cache = {},
	      OGLOB = '"in d?d:' + (window ? 'window).' : 'global).'),
	      reVars =
	      /(['"\/])(?:[^\\]*?|\\.|.)*?\1|\.\w*|\w*:|\b(?:(?:new|typeof|in|instanceof) |(?:this|true|false|null|undefined)\b|function\s*\()|([A-Za-z_$]\w*)/g
	
	  // build a template (or get it from cache), render with data
	  return function(str, data) {
	    return str && (cache[str] || (cache[str] = tmpl(str)))(data)
	  }
	
	
	  // create a template instance
	
	  function tmpl(s, p) {
	
	    if (s.indexOf(brackets(0)) < 0) {
	      // return raw text
	      s = s.replace(/\n|\r\n?/g, '\n')
	      return function () { return s }
	    }
	
	    // temporarily convert \{ and \} to a non-character
	    s = s
	      .replace(brackets(/\\{/g), '\uFFF0')
	      .replace(brackets(/\\}/g), '\uFFF1')
	
	    // split string to expression and non-expresion parts
	    p = split(s, extract(s, brackets(/{/), brackets(/}/)))
	
	    // is it a single expression or a template? i.e. {x} or <b>{x}</b>
	    s = (p.length === 2 && !p[0]) ?
	
	      // if expression, evaluate it
	      expr(p[1]) :
	
	      // if template, evaluate all expressions in it
	      '[' + p.map(function(s, i) {
	
	        // is it an expression or a string (every second part is an expression)
	        return i % 2 ?
	
	          // evaluate the expressions
	          expr(s, true) :
	
	          // process string parts of the template:
	          '"' + s
	
	            // preserve new lines
	            .replace(/\n|\r\n?/g, '\\n')
	
	            // escape quotes
	            .replace(/"/g, '\\"') +
	
	          '"'
	
	      }).join(',') + '].join("")'
	
	    return new Function('d', 'return ' + s
	      // bring escaped { and } back
	      .replace(/\uFFF0/g, brackets(0))
	      .replace(/\uFFF1/g, brackets(1)) + ';')
	
	  }
	
	
	  // parse { ... } expression
	
	  function expr(s, n) {
	    s = s
	
	      // convert new lines to spaces
	      .replace(/\n|\r\n?/g, ' ')
	
	      // trim whitespace, brackets, strip comments
	      .replace(brackets(/^[{ ]+|[ }]+$|\/\*.+?\*\//g), '')
	
	    // is it an object literal? i.e. { key : value }
	    return /^\s*[\w- "']+ *:/.test(s) ?
	
	      // if object literal, return trueish keys
	      // e.g.: { show: isOpen(), done: item.done } -> "show done"
	      '[' +
	
	          // extract key:val pairs, ignoring any nested objects
	          extract(s,
	
	              // name part: name:, "name":, 'name':, name :
	              /["' ]*[\w- ]+["' ]*:/,
	
	              // expression part: everything upto a comma followed by a name (see above) or end of line
	              /,(?=["' ]*[\w- ]+["' ]*:)|}|$/
	              ).map(function(pair) {
	
	                // get key, val parts
	                return pair.replace(/^[ "']*(.+?)[ "']*: *(.+?),? *$/, function(_, k, v) {
	
	                  // wrap all conditional parts to ignore errors
	                  return v.replace(/[^&|=!><]+/g, wrap) + '?"' + k + '":"",'
	
	                })
	
	              }).join('') +
	
	        '].join(" ").trim()' :
	
	      // if js expression, evaluate as javascript
	      wrap(s, n)
	
	  }
	
	
	  // execute js w/o breaking on errors or undefined vars
	
	  function wrap(s, nonull) {
	    s = s.trim()
	    return !s ? '' : '(function(v){try{v=' +
	
	      // prefix vars (name => data.name)
	      s.replace(reVars, function(s, _, v) { return v ? '(("' + v + OGLOB + v + ')' : s }) +
	
	      // default to empty string for falsy values except zero
	      '}catch(e){}return ' + (nonull === true ? '!v&&v!==0?"":v' : 'v') + '}).call(d)'
	  }
	
	
	  // split string by an array of substrings
	
	  function split(str, substrings) {
	    var parts = []
	    substrings.map(function(sub, i) {
	
	      // push matched expression and part before it
	      i = str.indexOf(sub)
	      parts.push(str.slice(0, i), sub)
	      str = str.slice(i + sub.length)
	    })
	    if (str) parts.push(str)
	
	    // push the remaining part
	    return parts
	  }
	
	
	  // match strings between opening and closing regexp, skipping any inner/nested matches
	
	  function extract(str, open, close) {
	
	    var start,
	        level = 0,
	        matches = [],
	        re = new RegExp('(' + open.source + ')|(' + close.source + ')', 'g')
	
	    str.replace(re, function(_, open, close, pos) {
	
	      // if outer inner bracket, mark position
	      if (!level && open) start = pos
	
	      // in(de)crease bracket level
	      level += open ? 1 : -1
	
	      // if outer closing bracket, grab the match
	      if (!level && close != null) matches.push(str.slice(start, pos + close.length))
	
	    })
	
	    return matches
	  }
	
	})()
	
	/*
	  lib/browser/tag/mkdom.js
	
	  Includes hacks needed for the Internet Explorer version 9 and bellow
	
	*/
	// http://kangax.github.io/compat-table/es5/#ie8
	// http://codeplanet.io/dropping-ie8/
	
	var mkdom = (function (checkIE) {
	
	  var rootEls = {
	        'tr': 'tbody',
	        'th': 'tr',
	        'td': 'tr',
	        'tbody': 'table',
	        'col': 'colgroup'
	      },
	      GENERIC = 'div'
	
	  checkIE = checkIE && checkIE < 10
	
	  // creates any dom element in a div, table, or colgroup container
	  function _mkdom(html) {
	
	    var match = html && html.match(/^\s*<([-\w]+)/),
	        tagName = match && match[1].toLowerCase(),
	        rootTag = rootEls[tagName] || GENERIC,
	        el = mkEl(rootTag)
	
	    el.stub = true
	
	    if (checkIE && tagName && (match = tagName.match(SPECIAL_TAGS_REGEX)))
	      ie9elem(el, html, tagName, !!match[1])
	    else
	      el.innerHTML = html
	
	    return el
	  }
	
	  // creates tr, th, td, option, optgroup element for IE8-9
	  /* istanbul ignore next */
	  function ie9elem(el, html, tagName, select) {
	
	    var div = mkEl(GENERIC),
	        tag = select ? 'select>' : 'table>',
	        child
	
	    div.innerHTML = '<' + tag + html + '</' + tag
	
	    child = div.getElementsByTagName(tagName)[0]
	    if (child)
	      el.appendChild(child)
	
	  }
	  // end ie9elem()
	
	  return _mkdom
	
	})(IE_VERSION)
	
	// { key, i in items} -> { key, i, items }
	function loopKeys(expr) {
	  var b0 = brackets(0),
	      els = expr.trim().slice(b0.length).match(/^\s*(\S+?)\s*(?:,\s*(\S+))?\s+in\s+(.+)$/)
	  return els ? { key: els[1], pos: els[2], val: b0 + els[3] } : { val: expr }
	}
	
	function mkitem(expr, key, val) {
	  var item = {}
	  item[expr.key] = key
	  if (expr.pos) item[expr.pos] = val
	  return item
	}
	
	
	/* Beware: heavy stuff */
	function _each(dom, parent, expr) {
	
	  remAttr(dom, 'each')
	
	  var tagName = getTagName(dom),
	      template = dom.outerHTML,
	      hasImpl = !!tagImpl[tagName],
	      impl = tagImpl[tagName] || {
	        tmpl: template
	      },
	      root = dom.parentNode,
	      placeholder = document.createComment('riot placeholder'),
	      tags = [],
	      child = getTag(dom),
	      checksum
	
	  root.insertBefore(placeholder, dom)
	
	  expr = loopKeys(expr)
	
	  // clean template code
	  parent
	    .one('premount', function () {
	      if (root.stub) root = parent.root
	      // remove the original DOM node
	      dom.parentNode.removeChild(dom)
	    })
	    .on('update', function () {
	      var items = tmpl(expr.val, parent)
	
	      // object loop. any changes cause full redraw
	      if (!isArray(items)) {
	
	        checksum = items ? JSON.stringify(items) : ''
	
	        items = !items ? [] :
	          Object.keys(items).map(function (key) {
	            return mkitem(expr, key, items[key])
	          })
	      }
	
	      var frag = document.createDocumentFragment(),
	          i = tags.length,
	          j = items.length
	
	      // unmount leftover items
	      while (i > j) {
	        tags[--i].unmount()
	        tags.splice(i, 1)
	      }
	
	      for (i = 0; i < j; ++i) {
	        var _item = !checksum && !!expr.key ? mkitem(expr, items[i], i) : items[i]
	
	        if (!tags[i]) {
	          // mount new
	          (tags[i] = new Tag(impl, {
	              parent: parent,
	              isLoop: true,
	              hasImpl: hasImpl,
	              root: SPECIAL_TAGS_REGEX.test(tagName) ? root : dom.cloneNode(),
	              item: _item
	            }, dom.innerHTML)
	          ).mount()
	
	          frag.appendChild(tags[i].root)
	        } else
	          tags[i].update(_item)
	
	        tags[i]._item = _item
	
	      }
	
	      root.insertBefore(frag, placeholder)
	
	      if (child) parent.tags[tagName] = tags
	
	    }).one('updated', function() {
	      var keys = Object.keys(parent)// only set new values
	      walk(root, function(node) {
	        // only set element node and not isLoop
	        if (node.nodeType == 1 && !node.isLoop && !node._looped) {
	          node._visited = false // reset _visited for loop node
	          node._looped = true // avoid set multiple each
	          setNamed(node, parent, keys)
	        }
	      })
	    })
	
	}
	
	
	function parseNamedElements(root, tag, childTags) {
	
	  walk(root, function(dom) {
	    if (dom.nodeType == 1) {
	      dom.isLoop = dom.isLoop || (dom.parentNode && dom.parentNode.isLoop || dom.getAttribute('each')) ? 1 : 0
	
	      // custom child tag
	      var child = getTag(dom)
	
	      if (child && !dom.isLoop) {
	        childTags.push(initChildTag(child, dom, tag))
	      }
	
	      if (!dom.isLoop)
	        setNamed(dom, tag, [])
	    }
	
	  })
	
	}
	
	function parseExpressions(root, tag, expressions) {
	
	  function addExpr(dom, val, extra) {
	    if (val.indexOf(brackets(0)) >= 0) {
	      var expr = { dom: dom, expr: val }
	      expressions.push(extend(expr, extra))
	    }
	  }
	
	  walk(root, function(dom) {
	    var type = dom.nodeType
	
	    // text node
	    if (type == 3 && dom.parentNode.tagName != 'STYLE') addExpr(dom, dom.nodeValue)
	    if (type != 1) return
	
	    /* element */
	
	    // loop
	    var attr = dom.getAttribute('each')
	
	    if (attr) { _each(dom, tag, attr); return false }
	
	    // attribute expressions
	    each(dom.attributes, function(attr) {
	      var name = attr.name,
	        bool = name.split('__')[1]
	
	      addExpr(dom, attr.value, { attr: bool || name, bool: bool })
	      if (bool) { remAttr(dom, name); return false }
	
	    })
	
	    // skip custom tags
	    if (getTag(dom)) return false
	
	  })
	
	}
	function Tag(impl, conf, innerHTML) {
	
	  var self = riot.observable(this),
	      opts = inherit(conf.opts) || {},
	      dom = mkdom(impl.tmpl),
	      parent = conf.parent,
	      isLoop = conf.isLoop,
	      hasImpl = conf.hasImpl,
	      item = cleanUpData(conf.item),
	      expressions = [],
	      childTags = [],
	      root = conf.root,
	      fn = impl.fn,
	      tagName = root.tagName.toLowerCase(),
	      attr = {},
	      propsInSyncWithParent = []
	
	  if (fn && root._tag) {
	    root._tag.unmount(true)
	  }
	
	  // not yet mounted
	  this.isMounted = false
	  root.isLoop = isLoop
	
	  // keep a reference to the tag just created
	  // so we will be able to mount this tag multiple times
	  root._tag = this
	
	  // create a unique id to this tag
	  // it could be handy to use it also to improve the virtual dom rendering speed
	  this._id = __uid++
	
	  extend(this, { parent: parent, root: root, opts: opts, tags: {} }, item)
	
	  // grab attributes
	  each(root.attributes, function(el) {
	    var val = el.value
	    // remember attributes with expressions only
	    if (brackets(/{.*}/).test(val)) attr[el.name] = val
	  })
	
	  if (dom.innerHTML && !/^(select|optgroup|table|tbody|tr|col(?:group)?)$/.test(tagName))
	    // replace all the yield tags with the tag inner html
	    dom.innerHTML = replaceYield(dom.innerHTML, innerHTML)
	
	  // options
	  function updateOpts() {
	    var ctx = hasImpl && isLoop ? self : parent || self
	
	    // update opts from current DOM attributes
	    each(root.attributes, function(el) {
	      opts[el.name] = tmpl(el.value, ctx)
	    })
	    // recover those with expressions
	    each(Object.keys(attr), function(name) {
	      opts[name] = tmpl(attr[name], ctx)
	    })
	  }
	
	  function normalizeData(data) {
	    for (var key in item) {
	      if (typeof self[key] !== T_UNDEF)
	        self[key] = data[key]
	    }
	  }
	
	  function inheritFromParent () {
	    if (!self.parent || !isLoop) return
	    each(Object.keys(self.parent), function(k) {
	      // some properties must be always in sync with the parent tag
	      var mustSync = !~RESERVED_WORDS_BLACKLIST.indexOf(k) && ~propsInSyncWithParent.indexOf(k)
	      if (typeof self[k] === T_UNDEF || mustSync) {
	        // track the property to keep in sync
	        // so we can keep it updated
	        if (!mustSync) propsInSyncWithParent.push(k)
	        self[k] = self.parent[k]
	      }
	    })
	  }
	
	  this.update = function(data) {
	    // make sure the data passed will not override
	    // the component core methods
	    data = cleanUpData(data)
	    // inherit properties from the parent
	    inheritFromParent()
	    // normalize the tag properties in case an item object was initially passed
	    if (data && typeof item === T_OBJECT) {
	      normalizeData(data)
	      item = data
	    }
	    extend(self, data)
	    updateOpts()
	    self.trigger('update', data)
	    update(expressions, self)
	    self.trigger('updated')
	  }
	
	  this.mixin = function() {
	    each(arguments, function(mix) {
	      mix = typeof mix === T_STRING ? riot.mixin(mix) : mix
	      each(Object.keys(mix), function(key) {
	        // bind methods to self
	        if (key != 'init')
	          self[key] = isFunction(mix[key]) ? mix[key].bind(self) : mix[key]
	      })
	      // init method will be called automatically
	      if (mix.init) mix.init.bind(self)()
	    })
	  }
	
	  this.mount = function() {
	
	    updateOpts()
	
	    // initialiation
	    if (fn) fn.call(self, opts)
	
	    // parse layout after init. fn may calculate args for nested custom tags
	    parseExpressions(dom, self, expressions)
	
	    // mount the child tags
	    toggle(true)
	
	    // update the root adding custom attributes coming from the compiler
	    // it fixes also #1087
	    if (impl.attrs || hasImpl) {
	      walkAttributes(impl.attrs, function (k, v) { root.setAttribute(k, v) })
	      parseExpressions(self.root, self, expressions)
	    }
	
	    if (!self.parent || isLoop) self.update(item)
	
	    // internal use only, fixes #403
	    self.trigger('premount')
	
	    if (isLoop && !hasImpl) {
	      // update the root attribute for the looped elements
	      self.root = root = dom.firstChild
	
	    } else {
	      while (dom.firstChild) root.appendChild(dom.firstChild)
	      if (root.stub) self.root = root = parent.root
	    }
	    // if it's not a child tag we can trigger its mount event
	    if (!self.parent || self.parent.isMounted) {
	      self.isMounted = true
	      self.trigger('mount')
	    }
	    // otherwise we need to wait that the parent event gets triggered
	    else self.parent.one('mount', function() {
	      // avoid to trigger the `mount` event for the tags
	      // not visible included in an if statement
	      if (!isInStub(self.root)) {
	        self.parent.isMounted = self.isMounted = true
	        self.trigger('mount')
	      }
	    })
	  }
	
	
	  this.unmount = function(keepRootTag) {
	    var el = root,
	        p = el.parentNode,
	        ptag
	
	    if (p) {
	
	      if (parent) {
	        ptag = getImmediateCustomParentTag(parent)
	        // remove this tag from the parent tags object
	        // if there are multiple nested tags with same name..
	        // remove this element form the array
	        if (isArray(ptag.tags[tagName]))
	          each(ptag.tags[tagName], function(tag, i) {
	            if (tag._id == self._id)
	              ptag.tags[tagName].splice(i, 1)
	          })
	        else
	          // otherwise just delete the tag instance
	          ptag.tags[tagName] = undefined
	      }
	
	      else
	        while (el.firstChild) el.removeChild(el.firstChild)
	
	      if (!keepRootTag)
	        p.removeChild(el)
	      else
	        // the riot-tag attribute isn't needed anymore, remove it
	        p.removeAttribute('riot-tag')
	    }
	
	
	    self.trigger('unmount')
	    toggle()
	    self.off('*')
	    // somehow ie8 does not like `delete root._tag`
	    root._tag = null
	
	  }
	
	  function toggle(isMount) {
	
	    // mount/unmount children
	    each(childTags, function(child) { child[isMount ? 'mount' : 'unmount']() })
	
	    // listen/unlisten parent (events flow one way from parent to children)
	    if (parent) {
	      var evt = isMount ? 'on' : 'off'
	
	      // the loop tags will be always in sync with the parent automatically
	      if (isLoop)
	        parent[evt]('unmount', self.unmount)
	      else
	        parent[evt]('update', self.update)[evt]('unmount', self.unmount)
	    }
	  }
	
	  // named elements available for fn
	  parseNamedElements(dom, this, childTags)
	
	
	}
	
	function setEventHandler(name, handler, dom, tag) {
	
	  dom[name] = function(e) {
	
	    var item = tag._item,
	        ptag = tag.parent,
	        el
	
	    if (!item)
	      while (ptag && !item) {
	        item = ptag._item
	        ptag = ptag.parent
	      }
	
	    // cross browser event fix
	    e = e || window.event
	
	    // ignore error on some browsers
	    try {
	      e.currentTarget = dom
	      if (!e.target) e.target = e.srcElement
	      if (!e.which) e.which = e.charCode || e.keyCode
	    } catch (ignored) { /**/ }
	
	    e.item = item
	
	    // prevent default behaviour (by default)
	    if (handler.call(tag, e) !== true && !/radio|check/.test(dom.type)) {
	      if (e.preventDefault) e.preventDefault()
	      e.returnValue = false
	    }
	
	    if (!e.preventUpdate) {
	      el = item ? getImmediateCustomParentTag(ptag) : tag
	      el.update()
	    }
	
	  }
	
	}
	
	// used by if- attribute
	function insertTo(root, node, before) {
	  if (root) {
	    root.insertBefore(before, node)
	    root.removeChild(node)
	  }
	}
	
	function update(expressions, tag) {
	
	  each(expressions, function(expr, i) {
	
	    var dom = expr.dom,
	        attrName = expr.attr,
	        value = tmpl(expr.expr, tag),
	        parent = expr.dom.parentNode
	
	    if (expr.bool)
	      value = value ? attrName : false
	    else if (value == null)
	      value = ''
	
	    // leave out riot- prefixes from strings inside textarea
	    // fix #815: any value -> string
	    if (parent && parent.tagName == 'TEXTAREA') value = ('' + value).replace(/riot-/g, '')
	
	    // no change
	    if (expr.value === value) return
	    expr.value = value
	
	    // text node
	    if (!attrName) {
	      dom.nodeValue = '' + value    // #815 related
	      return
	    }
	
	    // remove original attribute
	    remAttr(dom, attrName)
	    // event handler
	    if (isFunction(value)) {
	      setEventHandler(attrName, value, dom, tag)
	
	    // if- conditional
	    } else if (attrName == 'if') {
	      var stub = expr.stub,
	          add = function() { insertTo(stub.parentNode, stub, dom) },
	          remove = function() { insertTo(dom.parentNode, dom, stub) }
	
	      // add to DOM
	      if (value) {
	        if (stub) {
	          add()
	          dom.inStub = false
	          // avoid to trigger the mount event if the tags is not visible yet
	          // maybe we can optimize this avoiding to mount the tag at all
	          if (!isInStub(dom)) {
	            walk(dom, function(el) {
	              if (el._tag && !el._tag.isMounted) el._tag.isMounted = !!el._tag.trigger('mount')
	            })
	          }
	        }
	      // remove from DOM
	      } else {
	        stub = expr.stub = stub || document.createTextNode('')
	        // if the parentNode is defined we can easily replace the tag
	        if (dom.parentNode)
	          remove()
	        else
	        // otherwise we need to wait the updated event
	          (tag.parent || tag).one('updated', remove)
	
	        dom.inStub = true
	      }
	    // show / hide
	    } else if (/^(show|hide)$/.test(attrName)) {
	      if (attrName == 'hide') value = !value
	      dom.style.display = value ? '' : 'none'
	
	    // field value
	    } else if (attrName == 'value') {
	      dom.value = value
	
	    // <img src="{ expr }">
	    } else if (startsWith(attrName, RIOT_PREFIX) && attrName != RIOT_TAG) {
	      if (value)
	        dom.setAttribute(attrName.slice(RIOT_PREFIX.length), value)
	
	    } else {
	      if (expr.bool) {
	        dom[attrName] = value
	        if (!value) return
	      }
	
	      if (typeof value !== T_OBJECT) dom.setAttribute(attrName, value)
	
	    }
	
	  })
	
	}
	function each(els, fn) {
	  for (var i = 0, len = (els || []).length, el; i < len; i++) {
	    el = els[i]
	    // return false -> remove current item during loop
	    if (el != null && fn(el, i) === false) i--
	  }
	  return els
	}
	
	function isFunction(v) {
	  return typeof v === T_FUNCTION || false   // avoid IE problems
	}
	
	function remAttr(dom, name) {
	  dom.removeAttribute(name)
	}
	
	function getTag(dom) {
	  return dom.tagName && tagImpl[dom.getAttribute(RIOT_TAG) || dom.tagName.toLowerCase()]
	}
	
	function initChildTag(child, dom, parent) {
	  var tag = new Tag(child, { root: dom, parent: parent }, dom.innerHTML),
	      tagName = getTagName(dom),
	      ptag = getImmediateCustomParentTag(parent),
	      cachedTag
	
	  // fix for the parent attribute in the looped elements
	  tag.parent = ptag
	
	  cachedTag = ptag.tags[tagName]
	
	  // if there are multiple children tags having the same name
	  if (cachedTag) {
	    // if the parent tags property is not yet an array
	    // create it adding the first cached tag
	    if (!isArray(cachedTag))
	      ptag.tags[tagName] = [cachedTag]
	    // add the new nested tag to the array
	    if (!~ptag.tags[tagName].indexOf(tag))
	      ptag.tags[tagName].push(tag)
	  } else {
	    ptag.tags[tagName] = tag
	  }
	
	  // empty the child node once we got its template
	  // to avoid that its children get compiled multiple times
	  dom.innerHTML = ''
	
	  return tag
	}
	
	function getImmediateCustomParentTag(tag) {
	  var ptag = tag
	  while (!getTag(ptag.root)) {
	    if (!ptag.parent) break
	    ptag = ptag.parent
	  }
	  return ptag
	}
	
	function getTagName(dom) {
	  var child = getTag(dom),
	    namedTag = dom.getAttribute('name'),
	    tagName = namedTag && namedTag.indexOf(brackets(0)) < 0 ? namedTag : child ? child.name : dom.tagName.toLowerCase()
	
	  return tagName
	}
	
	function extend(src) {
	  var obj, args = arguments
	  for (var i = 1; i < args.length; ++i) {
	    if ((obj = args[i])) {
	      for (var key in obj) {      // eslint-disable-line guard-for-in
	        src[key] = obj[key]
	      }
	    }
	  }
	  return src
	}
	
	// with this function we avoid that the current Tag methods get overridden
	function cleanUpData(data) {
	  if (!(data instanceof Tag) && !(data && typeof data.trigger == T_FUNCTION)) return data
	
	  var o = {}
	  for (var key in data) {
	    if (!~RESERVED_WORDS_BLACKLIST.indexOf(key))
	      o[key] = data[key]
	  }
	  return o
	}
	
	function walk(dom, fn) {
	  if (dom) {
	    if (fn(dom) === false) return
	    else {
	      dom = dom.firstChild
	
	      while (dom) {
	        walk(dom, fn)
	        dom = dom.nextSibling
	      }
	    }
	  }
	}
	
	// minimize risk: only zero or one _space_ between attr & value
	function walkAttributes(html, fn) {
	  var m,
	      re = /([-\w]+) ?= ?(?:"([^"]*)|'([^']*)|({[^}]*}))/g
	
	  while ((m = re.exec(html))) {
	    fn(m[1].toLowerCase(), m[2] || m[3] || m[4])
	  }
	}
	
	function isInStub(dom) {
	  while (dom) {
	    if (dom.inStub) return true
	    dom = dom.parentNode
	  }
	  return false
	}
	
	function mkEl(name) {
	  return document.createElement(name)
	}
	
	function replaceYield(tmpl, innerHTML) {
	  return tmpl.replace(/<(yield)\/?>(<\/\1>)?/gi, innerHTML || '')
	}
	
	function $$(selector, ctx) {
	  return (ctx || document).querySelectorAll(selector)
	}
	
	function $(selector, ctx) {
	  return (ctx || document).querySelector(selector)
	}
	
	function inherit(parent) {
	  function Child() {}
	  Child.prototype = parent
	  return new Child()
	}
	
	function setNamed(dom, parent, keys) {
	  if (dom._visited) return
	  var p,
	      v = dom.getAttribute('id') || dom.getAttribute('name')
	
	  if (v) {
	    if (keys.indexOf(v) < 0) {
	      p = parent[v]
	      if (!p)
	        parent[v] = dom
	      else if (isArray(p))
	        p.push(dom)
	      else
	        parent[v] = [p, dom]
	    }
	    dom._visited = true
	  }
	}
	
	// faster String startsWith alternative
	function startsWith(src, str) {
	  return src.slice(0, str.length) === str
	}
	
	/*
	 Virtual dom is an array of custom tags on the document.
	 Updates and unmounts propagate downwards from parent to children.
	*/
	
	var virtualDom = [],
	    tagImpl = {},
	    styleNode
	
	function injectStyle(css) {
	
	  if (riot.render) return // skip injection on the server
	
	  if (!styleNode) {
	    styleNode = mkEl('style')
	    styleNode.setAttribute('type', 'text/css')
	  }
	
	  var head = document.head || document.getElementsByTagName('head')[0]
	
	  if (styleNode.styleSheet)
	    styleNode.styleSheet.cssText += css
	  else
	    styleNode.innerHTML += css
	
	  if (!styleNode._rendered)
	    if (styleNode.styleSheet) {
	      document.body.appendChild(styleNode)
	    } else {
	      var rs = $('style[type=riot]')
	      if (rs) {
	        rs.parentNode.insertBefore(styleNode, rs)
	        rs.parentNode.removeChild(rs)
	      } else head.appendChild(styleNode)
	
	    }
	
	  styleNode._rendered = true
	
	}
	
	function mountTo(root, tagName, opts) {
	  var tag = tagImpl[tagName],
	      // cache the inner HTML to fix #855
	      innerHTML = root._innerHTML = root._innerHTML || root.innerHTML
	
	  // clear the inner html
	  root.innerHTML = ''
	
	  if (tag && root) tag = new Tag(tag, { root: root, opts: opts }, innerHTML)
	
	  if (tag && tag.mount) {
	    tag.mount()
	    virtualDom.push(tag)
	    return tag.on('unmount', function() {
	      virtualDom.splice(virtualDom.indexOf(tag), 1)
	    })
	  }
	
	}
	
	riot.tag = function(name, html, css, attrs, fn) {
	  if (isFunction(attrs)) {
	    fn = attrs
	    if (/^[\w\-]+\s?=/.test(css)) {
	      attrs = css
	      css = ''
	    } else attrs = ''
	  }
	  if (css) {
	    if (isFunction(css)) fn = css
	    else injectStyle(css)
	  }
	  tagImpl[name] = { name: name, tmpl: html, attrs: attrs, fn: fn }
	  return name
	}
	
	riot.mount = function(selector, tagName, opts) {
	
	  var els,
	      allTags,
	      tags = []
	
	  // helper functions
	
	  function addRiotTags(arr) {
	    var list = ''
	    each(arr, function (e) {
	      list += ', *[' + RIOT_TAG + '="' + e.trim() + '"]'
	    })
	    return list
	  }
	
	  function selectAllTags() {
	    var keys = Object.keys(tagImpl)
	    return keys + addRiotTags(keys)
	  }
	
	  function pushTags(root) {
	    var last
	    if (root.tagName) {
	      if (tagName && (!(last = root.getAttribute(RIOT_TAG)) || last != tagName))
	        root.setAttribute(RIOT_TAG, tagName)
	
	      var tag = mountTo(root,
	        tagName || root.getAttribute(RIOT_TAG) || root.tagName.toLowerCase(), opts)
	
	      if (tag) tags.push(tag)
	    }
	    else if (root.length) {
	      each(root, pushTags)   // assume nodeList
	    }
	  }
	
	  // ----- mount code -----
	
	  if (typeof tagName === T_OBJECT) {
	    opts = tagName
	    tagName = 0
	  }
	
	  // crawl the DOM to find the tag
	  if (typeof selector === T_STRING) {
	    if (selector === '*')
	      // select all the tags registered
	      // and also the tags found with the riot-tag attribute set
	      selector = allTags = selectAllTags()
	    else
	      // or just the ones named like the selector
	      selector += addRiotTags(selector.split(','))
	
	    els = $$(selector)
	  }
	  else
	    // probably you have passed already a tag or a NodeList
	    els = selector
	
	  // select all the registered and mount them inside their root elements
	  if (tagName === '*') {
	    // get all custom tags
	    tagName = allTags || selectAllTags()
	    // if the root els it's just a single tag
	    if (els.tagName)
	      els = $$(tagName, els)
	    else {
	      // select all the children for all the different root elements
	      var nodeList = []
	      each(els, function (_el) {
	        nodeList.push($$(tagName, _el))
	      })
	      els = nodeList
	    }
	    // get rid of the tagName
	    tagName = 0
	  }
	
	  if (els.tagName)
	    pushTags(els)
	  else
	    each(els, pushTags)
	
	  return tags
	}
	
	// update everything
	riot.update = function() {
	  return each(virtualDom, function(tag) {
	    tag.update()
	  })
	}
	
	// @deprecated
	riot.mountTo = riot.mount
	
	  // share methods for other riot parts, e.g. compiler
	  riot.util = { brackets: brackets, tmpl: tmpl }
	
	  // support CommonJS, AMD & browser
	  /* istanbul ignore next */
	  if (typeof exports === T_OBJECT)
	    module.exports = riot
	  else if (true)
	    !(__WEBPACK_AMD_DEFINE_RESULT__ = function() { return (window.riot = riot) }.call(exports, __webpack_require__, exports, module), __WEBPACK_AMD_DEFINE_RESULT__ !== undefined && (module.exports = __WEBPACK_AMD_DEFINE_RESULT__))
	  else
	    window.riot = riot
	
	})(typeof window != 'undefined' ? window : void 0);


/***/ },
/* 2 */
/***/ function(module, exports, __webpack_require__) {

	var RiotControl = {
	  _stores: [],
	  addStore: function(store) {
	    this._stores.push(store);
	  }
	};
	
	['on','one','off','trigger'].forEach(function(api){
	  RiotControl[api] = function() {
	    var args = [].slice.call(arguments);
	    this._stores.forEach(function(el){
	      el[api].apply(null, args);
	    });
	  };
	});
	
	if (true) module.exports = RiotControl;


/***/ },
/* 3 */
/***/ function(module, exports, __webpack_require__) {

	// style-loader: Adds some css to the DOM by adding a <style> tag
	
	// load the styles
	var content = __webpack_require__(4);
	if(typeof content === 'string') content = [[module.id, content, '']];
	// add the styles to the DOM
	var update = __webpack_require__(6)(content, {});
	if(content.locals) module.exports = content.locals;
	// Hot Module Replacement
	if(false) {
		// When the styles change, update the <style> tags
		if(!content.locals) {
			module.hot.accept("!!./../node_modules/css-loader/index.js!./../node_modules/less-loader/index.js!./pte.less", function() {
				var newContent = require("!!./../node_modules/css-loader/index.js!./../node_modules/less-loader/index.js!./pte.less");
				if(typeof newContent === 'string') newContent = [[module.id, newContent, '']];
				update(newContent);
			});
		}
		// When the module is disposed, remove the <style> tags
		module.hot.dispose(function() { update(); });
	}

/***/ },
/* 4 */
/***/ function(module, exports, __webpack_require__) {

	exports = module.exports = __webpack_require__(5)();
	// imports
	
	
	// module
	exports.push([module.id, "body {\n  position: relative;\n}\n.no-title .ui-dialog-title,\n.no-title .ui-dialog-titlebar-close {\n  display: none;\n}\n.no-title .ui-dialog-titlebar {\n  height: 0;\n}\n#pte-spinner {\n  text-align: center;\n}\npte-editor {\n  display: block;\n  margin: 20px;\n  position: relative;\n}\n#pte-editor-main {\n  display: block;\n  width: auto;\n  height: 100px;\n  margin-right: 60px;\n  position: relative;\n}\n#pte-editor-main #image,\n#pte-editor-main #actions {\n  width: 100;\n}\n#pte-editor-main #actions {\n  text-align: right;\n  padding: 20px 20px 0 0;\n}\n#pte-editor-main #image {\n  background: #DDD;\n}\n#pte-editor-main #image img {\n  max-width: 100%;\n  max-height: 100%;\n}\npte-editor-menu {\n  background-color: #aaa;\n  display: block;\n  float: right;\n  width: 60px;\n  text-align: center;\n}\npte-editor-menu ul {\n  padding: 0;\n  list-style-type: none;\n}\npte-editor-menu ul li {\n  padding-bottom: 10px;\n}\n", ""]);
	
	// exports


/***/ },
/* 5 */
/***/ function(module, exports) {

	/*
		MIT License http://www.opensource.org/licenses/mit-license.php
		Author Tobias Koppers @sokra
	*/
	// css base code, injected by the css-loader
	module.exports = function() {
		var list = [];
	
		// return the list of modules as css string
		list.toString = function toString() {
			var result = [];
			for(var i = 0; i < this.length; i++) {
				var item = this[i];
				if(item[2]) {
					result.push("@media " + item[2] + "{" + item[1] + "}");
				} else {
					result.push(item[1]);
				}
			}
			return result.join("");
		};
	
		// import a list of modules into the list
		list.i = function(modules, mediaQuery) {
			if(typeof modules === "string")
				modules = [[null, modules, ""]];
			var alreadyImportedModules = {};
			for(var i = 0; i < this.length; i++) {
				var id = this[i][0];
				if(typeof id === "number")
					alreadyImportedModules[id] = true;
			}
			for(i = 0; i < modules.length; i++) {
				var item = modules[i];
				// skip already imported module
				// this implementation is not 100% perfect for weird media query combinations
				//  when a module is imported multiple times with different media queries.
				//  I hope this will never occur (Hey this way we have smaller bundles)
				if(typeof item[0] !== "number" || !alreadyImportedModules[item[0]]) {
					if(mediaQuery && !item[2]) {
						item[2] = mediaQuery;
					} else if(mediaQuery) {
						item[2] = "(" + item[2] + ") and (" + mediaQuery + ")";
					}
					list.push(item);
				}
			}
		};
		return list;
	};


/***/ },
/* 6 */
/***/ function(module, exports, __webpack_require__) {

	/*
		MIT License http://www.opensource.org/licenses/mit-license.php
		Author Tobias Koppers @sokra
	*/
	var stylesInDom = {},
		memoize = function(fn) {
			var memo;
			return function () {
				if (typeof memo === "undefined") memo = fn.apply(this, arguments);
				return memo;
			};
		},
		isOldIE = memoize(function() {
			return /msie [6-9]\b/.test(window.navigator.userAgent.toLowerCase());
		}),
		getHeadElement = memoize(function () {
			return document.head || document.getElementsByTagName("head")[0];
		}),
		singletonElement = null,
		singletonCounter = 0;
	
	module.exports = function(list, options) {
		if(false) {
			if(typeof document !== "object") throw new Error("The style-loader cannot be used in a non-browser environment");
		}
	
		options = options || {};
		// Force single-tag solution on IE6-9, which has a hard limit on the # of <style>
		// tags it will allow on a page
		if (typeof options.singleton === "undefined") options.singleton = isOldIE();
	
		var styles = listToStyles(list);
		addStylesToDom(styles, options);
	
		return function update(newList) {
			var mayRemove = [];
			for(var i = 0; i < styles.length; i++) {
				var item = styles[i];
				var domStyle = stylesInDom[item.id];
				domStyle.refs--;
				mayRemove.push(domStyle);
			}
			if(newList) {
				var newStyles = listToStyles(newList);
				addStylesToDom(newStyles, options);
			}
			for(var i = 0; i < mayRemove.length; i++) {
				var domStyle = mayRemove[i];
				if(domStyle.refs === 0) {
					for(var j = 0; j < domStyle.parts.length; j++)
						domStyle.parts[j]();
					delete stylesInDom[domStyle.id];
				}
			}
		};
	}
	
	function addStylesToDom(styles, options) {
		for(var i = 0; i < styles.length; i++) {
			var item = styles[i];
			var domStyle = stylesInDom[item.id];
			if(domStyle) {
				domStyle.refs++;
				for(var j = 0; j < domStyle.parts.length; j++) {
					domStyle.parts[j](item.parts[j]);
				}
				for(; j < item.parts.length; j++) {
					domStyle.parts.push(addStyle(item.parts[j], options));
				}
			} else {
				var parts = [];
				for(var j = 0; j < item.parts.length; j++) {
					parts.push(addStyle(item.parts[j], options));
				}
				stylesInDom[item.id] = {id: item.id, refs: 1, parts: parts};
			}
		}
	}
	
	function listToStyles(list) {
		var styles = [];
		var newStyles = {};
		for(var i = 0; i < list.length; i++) {
			var item = list[i];
			var id = item[0];
			var css = item[1];
			var media = item[2];
			var sourceMap = item[3];
			var part = {css: css, media: media, sourceMap: sourceMap};
			if(!newStyles[id])
				styles.push(newStyles[id] = {id: id, parts: [part]});
			else
				newStyles[id].parts.push(part);
		}
		return styles;
	}
	
	function createStyleElement() {
		var styleElement = document.createElement("style");
		var head = getHeadElement();
		styleElement.type = "text/css";
		head.appendChild(styleElement);
		return styleElement;
	}
	
	function createLinkElement() {
		var linkElement = document.createElement("link");
		var head = getHeadElement();
		linkElement.rel = "stylesheet";
		head.appendChild(linkElement);
		return linkElement;
	}
	
	function addStyle(obj, options) {
		var styleElement, update, remove;
	
		if (options.singleton) {
			var styleIndex = singletonCounter++;
			styleElement = singletonElement || (singletonElement = createStyleElement());
			update = applyToSingletonTag.bind(null, styleElement, styleIndex, false);
			remove = applyToSingletonTag.bind(null, styleElement, styleIndex, true);
		} else if(obj.sourceMap &&
			typeof URL === "function" &&
			typeof URL.createObjectURL === "function" &&
			typeof URL.revokeObjectURL === "function" &&
			typeof Blob === "function" &&
			typeof btoa === "function") {
			styleElement = createLinkElement();
			update = updateLink.bind(null, styleElement);
			remove = function() {
				styleElement.parentNode.removeChild(styleElement);
				if(styleElement.href)
					URL.revokeObjectURL(styleElement.href);
			};
		} else {
			styleElement = createStyleElement();
			update = applyToTag.bind(null, styleElement);
			remove = function() {
				styleElement.parentNode.removeChild(styleElement);
			};
		}
	
		update(obj);
	
		return function updateStyle(newObj) {
			if(newObj) {
				if(newObj.css === obj.css && newObj.media === obj.media && newObj.sourceMap === obj.sourceMap)
					return;
				update(obj = newObj);
			} else {
				remove();
			}
		};
	}
	
	var replaceText = (function () {
		var textStore = [];
	
		return function (index, replacement) {
			textStore[index] = replacement;
			return textStore.filter(Boolean).join('\n');
		};
	})();
	
	function applyToSingletonTag(styleElement, index, remove, obj) {
		var css = remove ? "" : obj.css;
	
		if (styleElement.styleSheet) {
			styleElement.styleSheet.cssText = replaceText(index, css);
		} else {
			var cssNode = document.createTextNode(css);
			var childNodes = styleElement.childNodes;
			if (childNodes[index]) styleElement.removeChild(childNodes[index]);
			if (childNodes.length) {
				styleElement.insertBefore(cssNode, childNodes[index]);
			} else {
				styleElement.appendChild(cssNode);
			}
		}
	}
	
	function applyToTag(styleElement, obj) {
		var css = obj.css;
		var media = obj.media;
		var sourceMap = obj.sourceMap;
	
		if(media) {
			styleElement.setAttribute("media", media)
		}
	
		if(styleElement.styleSheet) {
			styleElement.styleSheet.cssText = css;
		} else {
			while(styleElement.firstChild) {
				styleElement.removeChild(styleElement.firstChild);
			}
			styleElement.appendChild(document.createTextNode(css));
		}
	}
	
	function updateLink(linkElement, obj) {
		var css = obj.css;
		var media = obj.media;
		var sourceMap = obj.sourceMap;
	
		if(sourceMap) {
			// http://stackoverflow.com/a/26603875
			css += "\n/*# sourceMappingURL=data:application/json;base64," + btoa(unescape(encodeURIComponent(JSON.stringify(sourceMap)))) + " */";
		}
	
		var blob = new Blob([css], { type: "text/css" });
	
		var oldSrc = linkElement.href;
	
		linkElement.href = URL.createObjectURL(blob);
	
		if(oldSrc)
			URL.revokeObjectURL(oldSrc);
	}


/***/ },
/* 7 */
/***/ function(module, exports, __webpack_require__) {

	'use strict';
	
	var riot = __webpack_require__(1);
	var pte = __webpack_require__(8);
	var rc = __webpack_require__(2);
	var $ = __webpack_require__(9);
	
	module.exports = riot.tag('pte-app', '<div id="pte-spinner"><i class="fa fa-5x fa-spin fa-refresh"></i></div>\n    <pte-editor></pte-editor>\n    <pte-thumbnail-selector store={opts.store}></pte-thumbnail-selector>\n    <pte-instructions></pte-instructions>\n    <pte-confirmation></pte-confirmation>\n    ', function (opts) {
	    var _this = this;
	
	    this.loaded = false;
	
	    this.on('mount', function () {
	        $('#pte-spinner').dialog({
	            closeOnEscape: false,
	            modal: true,
	            width: 80,
	            height: 114,
	            resizable: false,
	            draggable: false,
	            dialogClass: 'no-title'
	        });
	        rc.trigger(pte.events.APP_STARTED);
	    });
	
	    rc.on(pte.events.DATA_LOADED, function () {
	        $('#pte-spinner').dialog('close');
	        console.log("DATA_LOADED received");
	        _this.loaded = true;
	        _this.update();
	    });
	});

/***/ },
/* 8 */
/***/ function(module, exports) {

	'use strict';
	
	Object.defineProperty(exports, '__esModule', {
	  value: true
	});
	var events = {
	  APP_STARTED: 'APP_STARTED',
	  DATA_LOADED: 'DATA_LOADED',
	  TOGGLE_THUMB_SELECTOR: "TOGGLE_THUMB_SELECTOR"
	};
	exports.events = events;

/***/ },
/* 9 */
/***/ function(module, exports) {

	module.exports = jQuery;

/***/ },
/* 10 */
/***/ function(module, exports, __webpack_require__) {

	'use strict';
	
	Object.defineProperty(exports, '__esModule', {
	  value: true
	});
	var events = __webpack_require__(8);
	var riot = __webpack_require__(1);
	var $ = __webpack_require__(9);
	
	var html = '\n<pte-editor-menu></pte-editor-menu>\n<div id="pte-editor-main">\n  <div id="image">\n    <img src="{ img.src }" alt="">\n  </div>\n  <div id="actions" class="wp-core-ui">\n    <button class="button-primary">CropText</button>\n  </div>\n</div>\n';
	
	var tag = riot.tag('pte-editor', html, function (opts) {
	  var _this = this;
	
	  var mounted = false;
	
	  this.img = {
	    src: 'http://wordpress/wp-content/uploads/2015/03/pin-up_motorcycle_fill.jpg',
	    width: '500',
	    height: '340'
	  };
	
	  this.resize = function () {
	    /*
	     * Determine how big the image pane can be.
	     * HEIGHT:
	     * 40px (2 x 20px margin for editor)
	     * - height of #actions
	     * WIDTH:
	     * should just be the width of #pte-editor-main
	     */
	    var $imgDiv = $('#image', _this.root);
	    var width = $imgDiv.width();
	    var height = $(window).innerHeight() - $('#actions', _this.root).outerHeight(true) - 40;
	    $imgDiv.height(height);
	
	    if (!mounted) {
	      return;
	    }
	
	    $('img', _this.root).position({
	      my: 'center center',
	      at: 'center center',
	      of: '#image',
	      collision: 'none'
	    });
	  };
	
	  this.on('mount', function () {
	    console.log('set the image dimensions of the image');
	    mounted = true;
	  });
	
	  this.on('update', function () {
	    console.log('pte-editor updated');
	    _this.resize();
	  });
	
	  $(window).resize(function () {
	    _this.resize();
	  });
	});
	
	exports['default'] = tag;
	module.exports = exports['default'];

/***/ },
/* 11 */
/***/ function(module, exports, __webpack_require__) {

	'use strict';
	
	Object.defineProperty(exports, '__esModule', {
	  value: true
	});
	var events = __webpack_require__(8).events;
	var riot = __webpack_require__(1);
	var rc = __webpack_require__(2);
	var $ = __webpack_require__(9);
	
	var html = '\n<ul>\n  <li><a title="Choose Thumbnails" onclick="{ showThumbnailSelector }">\n    <i class="fa fa-bars"></i>\n  </a></li>\n  <li><a title="Show Options" onclick={ showOptions }>\n    <i class="fa fa-cogs"></i>\n  </a></li>\n  <li><a title="Show Thumbnails" onclick={ showThumbnails }>\n    <i class="fa fa-picture-o"></i>\n  </a></li>\n</ul>\n';
	
	var tag = riot.tag('pte-editor-menu', html, function (opts) {
	  var _this = this;
	
	  this.showThumbnailSelector = function (e) {
	    console.log('show thumbnail selector');
	    rc.trigger(events.TOGGLE_THUMB_SELECTOR);
	  };
	
	  this.on('mount', function () {
	    $('a', _this.root).button();
	  });
	});
	
	exports['default'] = tag;
	module.exports = exports['default'];

/***/ },
/* 12 */
/***/ function(module, exports, __webpack_require__) {

	'use strict';
	
	Object.defineProperty(exports, '__esModule', {
	  value: true
	});
	var riot = __webpack_require__(1);
	var rc = __webpack_require__(2);
	var events = __webpack_require__(8).events;
	var $ = __webpack_require__(9);
	
	var html = '<div>THUMBNAIL_SELECTOR</div>\n';
	
	var tag = riot.tag('pte-thumbnail-selector', html, function (opts) {
	  var _this = this;
	
	  this.on('mount', function () {
	    $(_this.root).dialog({ autoOpen: false });
	  });
	  rc.on([events.DATA_LOADED, events.TOGGLE_THUMB_SELECTOR].join(' '), function () {
	    if ($(_this.root).dialog('isOpen')) $(_this.root).dialog('close');else $(_this.root).dialog('open');
	  });
	  this.on('update', function () {
	    console.log('pte-thumbnail-selector updated');
	  });
	});
	
	exports['default'] = tag;
	module.exports = exports['default'];

/***/ },
/* 13 */
/***/ function(module, exports, __webpack_require__) {

	/* WEBPACK VAR INJECTION */(function(global) {'use strict';
	
	'use client';
	
	Object.defineProperty(exports, '__esModule', {
	    value: true
	});
	var riot = __webpack_require__(1);
	var events = __webpack_require__(8).events;
	
	var PteStore = function PteStore() {
	    var _this = this;
	
	    riot.observable(this);
	
	    (global || window).setTimeout(function () {
	        console.log('Trigger DATA_LOADED');
	        _this.trigger(events.DATA_LOADED);
	    }, 1000);
	};
	
	exports['default'] = PteStore;
	module.exports = exports['default'];
	/* WEBPACK VAR INJECTION */}.call(exports, (function() { return this; }())))

/***/ }
/******/ ]);
//# sourceMappingURL=bundle.js.map