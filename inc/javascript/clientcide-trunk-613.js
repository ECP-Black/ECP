/*
Script: Core.js
	MooTools - My Object Oriented JavaScript Tools.

License:
	MIT-style license.

Copyright:
	Copyright (c) 2006-2007 [Valerio Proietti](http://mad4milk.net/).

Code & Documentation:
	[The MooTools production team](http://mootools.net/developers/).

Inspiration:
	- Class implementation inspired by [Base.js](http://dean.edwards.name/weblog/2006/03/base/) Copyright (c) 2006 Dean Edwards, [GNU Lesser General Public License](http://opensource.org/licenses/lgpl-license.php)
	- Some functionality inspired by [Prototype.js](http://prototypejs.org) Copyright (c) 2005-2007 Sam Stephenson, [MIT License](http://opensource.org/licenses/mit-license.php)
*/

var MooTools = {
	'version': '1.2dev',
	'build': '466'
};
      
var Native = function(options){
	options = options || {};

	var afterImplement = options.afterImplement || function(){};
	var generics = options.generics;
	generics = (generics !== false);
	var legacy = options.legacy;
	var initialize = options.initialize;
	var protect = options.protect;
	var name = options.name;

	var object = initialize || legacy;

	object.constructor = Native;
	object.$family = {name: 'native'};
	if (legacy && initialize) object.prototype = legacy.prototype;
	object.prototype.constructor = object;

	if (name){
		var family = name.toLowerCase();
		object.prototype.$family = {name: family};
		Native.typize(object, family);
	}

	var add = function(obj, name, method, force){
		if (!protect || force || !obj.prototype[name]) obj.prototype[name] = method;
		if (generics) Native.genericize(obj, name, protect);
		afterImplement.call(obj, name, method);
		return obj;
	};
	
	object.implement = function(a1, a2, a3){
		if (typeof a1 == 'string') return add(this, a1, a2, a3);
		for (var p in a1) add(this, p, a1[p], a2);
		return this;
	};
	
	object.alias = function(a1, a2, a3){
		if (typeof a1 == 'string'){
			a1 = this.prototype[a1];
			if (a1) add(this, a2, a1, a3);
		} else {
			for (var a in a1) this.alias(a, a1[a], a2);
		}
		return this;
	};

	return object;
};

Native.implement = function(objects, properties){
	for (var i = 0, l = objects.length; i < l; i++) objects[i].implement(properties);
};

Native.genericize = function(object, property, check){
	if ((!check || !object[property]) && typeof object.prototype[property] == 'function') object[property] = function(){
		var args = Array.prototype.slice.call(arguments);
		return object.prototype[property].apply(args.shift(), args);
	};
};

Native.typize = function(object, family){
	if (!object.type) object.type = function(item){
		return ($type(item) === family);
	};
};

Native.alias = function(objects, a1, a2, a3){
	for (var i = 0, j = objects.length; i < j; i++) objects[i].alias(a1, a2, a3);
};

(function(objects){
	for (var name in objects) Native.typize(objects[name], name);
})({'boolean': Boolean, 'native': Native, 'object': Object});

(function(objects){
	for (var name in objects) new Native({name: name, initialize: objects[name], protect: true});
})({'String': String, 'Function': Function, 'Number': Number, 'Array': Array, 'RegExp': RegExp, 'Date': Date});

(function(object, methods){
	for (var i = methods.length; i--; i) Native.genericize(object, methods[i], true);
	return arguments.callee;
})
(Array, ['pop', 'push', 'reverse', 'shift', 'sort', 'splice', 'unshift', 'concat', 'join', 'slice', 'toString', 'valueOf', 'indexOf', 'lastIndexOf'])
(String, ['charAt', 'charCodeAt', 'concat', 'indexOf', 'lastIndexOf', 'match', 'replace', 'search', 'slice', 'split', 'substr', 'substring', 'toLowerCase', 'toUpperCase', 'valueOf']);

function $chk(obj){
	return !!(obj || obj === 0);
};

function $clear(timer){
	clearTimeout(timer);
	clearInterval(timer);
	return null;
};

function $defined(obj){
	return (obj != undefined);
};

function $empty(){};

function $arguments(i){
	return function(){
		return arguments[i];
	};
};

function $lambda(value){
	return (typeof value == 'function') ? value : function(){
		return value;
	};
};

function $extend(original, extended){
	for (var key in (extended || {})) original[key] = extended[key];
	return original;
};

function $unlink(object){
	var unlinked;
	
	switch ($type(object)){
		case 'object':
			unlinked = {};
			for (var p in object) unlinked[p] = $unlink(object[p]);
		break;
		case 'hash':
			unlinked = $unlink(object.getClean());
		break;
		case 'array':
			unlinked = [];
			for (var i = 0, l = object.length; i < l; i++) unlinked[i] = $unlink(object[i]);
		break;
		default: return object;
	}
	
	return unlinked;
};

function $merge(){
	var mix = {};
	for (var i = 0, l = arguments.length; i < l; i++){
		var object = arguments[i];
		if ($type(object) != 'object') continue;
		for (var key in object){
			var op = object[key], mp = mix[key];
			mix[key] = (mp && $type(op) == 'object' && $type(mp) == 'object') ? $merge(mp, op) : $unlink(op);
		}
	}
	return mix;
};

function $pick(){
	for (var i = 0, l = arguments.length; i < l; i++){
		if (arguments[i] != undefined) return arguments[i];
	}
	return null;
};

function $random(min, max){
	return Math.floor(Math.random() * (max - min + 1) + min);
};

function $splat(obj){
	var type = $type(obj);
	return (type) ? ((type != 'array' && type != 'arguments') ? [obj] : obj) : [];
};

var $time = Date.now || function(){
	return new Date().getTime();
};

function $try(){
	for (var i = 0, l = arguments.length; i < l; i++){
		try {
			return arguments[i]();
		} catch(e){}
	}
	return null;
};

function $type(obj){
	if (obj == undefined) return false;
	if (obj.$family) return (obj.$family.name == 'number' && !isFinite(obj)) ? false : obj.$family.name;
	if (obj.nodeName){
		switch (obj.nodeType){
			case 1: return 'element';
			case 3: return (/\S/).test(obj.nodeValue) ? 'textnode' : 'whitespace';
		}
	} else if (typeof obj.length == 'number'){
		if (obj.callee) return 'arguments';
		else if (obj.item) return 'collection';
	}
	return typeof obj;
};

var Hash = new Native({

	name: 'Hash',

	initialize: function(object){
		if ($type(object) == 'hash') object = $unlink(object.getClean());
		for (var key in object) this[key] = object[key];
		return this;
	}

});

Hash.implement({
	
	getLength: function(){
		var length = 0;
		for (var key in this){
			if (this.hasOwnProperty(key)) length++;
		}
		return length;
	},

	forEach: function(fn, bind){
		for (var key in this){
			if (this.hasOwnProperty(key)) fn.call(bind, this[key], key, this);
		}
	},
	
	getClean: function(){
		var clean = {};
		for (var key in this){
			if (this.hasOwnProperty(key)) clean[key] = this[key];
		}
		return clean;
	}

});

Hash.alias('forEach', 'each');

function $H(object){
	return new Hash(object);
};

Array.implement({

	forEach: function(fn, bind){
		for (var i = 0, l = this.length; i < l; i++) fn.call(bind, this[i], i, this);
	}

});

Array.alias('forEach', 'each');

function $A(iterable){
	if (iterable.item){
		var array = [];
		for (var i = 0, l = iterable.length; i < l; i++) array[i] = iterable[i];
		return array;
	}
	return Array.prototype.slice.call(iterable);
};

function $each(iterable, fn, bind){
	var type = $type(iterable);
	((type == 'arguments' || type == 'collection' || type == 'array') ? Array : Hash).each(iterable, fn, bind);
};


/*
Script: Browser.js
	The Browser Core. Contains Browser initialization, Window and Document, and the Browser Hash.

License:
	MIT-style license.
*/

var Browser = new Hash({
	Engine: {name: 'unknown', version: ''},
	Platform: {name: (navigator.platform.match(/mac|win|linux/i) || ['other'])[0].toLowerCase()},
	Features: {xpath: !!(document.evaluate), air: !!(window.runtime)},
	Plugins: {}
});

if (window.opera) Browser.Engine = {name: 'presto', version: (document.getElementsByClassName) ? 950 : 925};
else if (window.ActiveXObject) Browser.Engine = {name: 'trident', version: (window.XMLHttpRequest) ? 5 : 4};
else if (!navigator.taintEnabled) Browser.Engine = {name: 'webkit', version: (Browser.Features.xpath) ? 420 : 419};
else if (document.getBoxObjectFor != null) Browser.Engine = {name: 'gecko', version: (document.getElementsByClassName) ? 19 : 18};
Browser.Engine[Browser.Engine.name] = Browser.Engine[Browser.Engine.name + Browser.Engine.version] = true;

if (window.orientation != undefined) Browser.Platform.name = 'ipod';

Browser.Platform[Browser.Platform.name] = true;

Browser.Request = function(){
	return $try(function(){
		return new XMLHttpRequest();
	}, function(){
		return new ActiveXObject('MSXML2.XMLHTTP');
	});
};

Browser.Features.xhr = !!(Browser.Request());

Browser.Plugins.Flash = (function(){
	var version = ($try(function(){
		return navigator.plugins['Shockwave Flash'].description;
	}, function(){
		return new ActiveXObject('ShockwaveFlash.ShockwaveFlash').GetVariable('$version');
	}) || '0 r0').match(/\d+/g);
	return {version: parseInt(version[0] || 0 + '.' + version[1] || 0), build: parseInt(version[2] || 0)};
})();

function $exec(text){
	if (!text) return text;
	if (window.execScript){
		window.execScript(text);
	} else {
		var script = document.createElement('script');
		script.setAttribute('type', 'text/javascript');
		script.text = text;
		document.head.appendChild(script);
		document.head.removeChild(script);
	}
	return text;
};

Native.UID = 1;

var $uid = (Browser.Engine.trident) ? function(item){
	return (item.uid || (item.uid = [Native.UID++]))[0];
} : function(item){
	return item.uid || (item.uid = Native.UID++);
};

var Window = new Native({

	name: 'Window',

	legacy: (Browser.Engine.trident) ? null: window.Window,

	initialize: function(win){
		$uid(win);
		if (!win.Element){
			win.Element = $empty;
			if (Browser.Engine.webkit) win.document.createElement("iframe"); //fixes safari 2
			win.Element.prototype = (Browser.Engine.webkit) ? window["[[DOMElement.prototype]]"] : {};
		}
		return $extend(win, Window.Prototype);
	},

	afterImplement: function(property, value){
		window[property] = Window.Prototype[property] = value;
	}

});

Window.Prototype = {$family: {name: 'window'}};

new Window(window);

var Document = new Native({

	name: 'Document',

	legacy: (Browser.Engine.trident) ? null: window.Document,

	initialize: function(doc){
		$uid(doc);
		doc.head = doc.getElementsByTagName('head')[0];
		doc.html = doc.getElementsByTagName('html')[0];
		doc.window = doc.defaultView || doc.parentWindow;
		if (Browser.Engine.trident4) $try(function(){
			doc.execCommand("BackgroundImageCache", false, true);
		});
		return $extend(doc, Document.Prototype);
	},

	afterImplement: function(property, value){
		document[property] = Document.Prototype[property] = value;
	}

});

Document.Prototype = {$family: {name: 'document'}};

new Document(document);

/*
Script: Array.js
	Contains Array Prototypes like copy, each, contains, and remove.

License:
	MIT-style license.
*/

Array.implement({

	every: function(fn, bind){
		for (var i = 0, l = this.length; i < l; i++){
			if (!fn.call(bind, this[i], i, this)) return false;
		}
		return true;
	},

	filter: function(fn, bind){
		var results = [];
		for (var i = 0, l = this.length; i < l; i++){
			if (fn.call(bind, this[i], i, this)) results.push(this[i]);
		}
		return results;
	},
	
	clean: function() {
		return this.filter($defined);
	},

	indexOf: function(item, from){
		var len = this.length;
		for (var i = (from < 0) ? Math.max(0, len + from) : from || 0; i < len; i++){
			if (this[i] === item) return i;
		}
		return -1;
	},

	map: function(fn, bind){
		var results = [];
		for (var i = 0, l = this.length; i < l; i++) results[i] = fn.call(bind, this[i], i, this);
		return results;
	},

	some: function(fn, bind){
		for (var i = 0, l = this.length; i < l; i++){
			if (fn.call(bind, this[i], i, this)) return true;
		}
		return false;
	},

	associate: function(keys){
		var obj = {}, length = Math.min(this.length, keys.length);
		for (var i = 0; i < length; i++) obj[keys[i]] = this[i];
		return obj;
	},

	link: function(object){
		var result = {};
		for (var i = 0, l = this.length; i < l; i++){
			for (var key in object){
				if (object[key](this[i])){
					result[key] = this[i];
					delete object[key];
					break;
				}
			}
		}
		return result;
	},

	contains: function(item, from){
		return this.indexOf(item, from) != -1;
	},

	extend: function(array){
		for (var i = 0, j = array.length; i < j; i++) this.push(array[i]);
		return this;
	},

	getLast: function(){
		return (this.length) ? this[this.length - 1] : null;
	},

	getRandom: function(){
		return (this.length) ? this[$random(0, this.length - 1)] : null;
	},

	include: function(item){
		if (!this.contains(item)) this.push(item);
		return this;
	},

	combine: function(array){
		for (var i = 0, l = array.length; i < l; i++) this.include(array[i]);
		return this;
	},

	erase: function(item){
		for (var i = this.length; i--; i){
			if (this[i] === item) this.splice(i, 1);
		}
		return this;
	},

	empty: function(){
		this.length = 0;
		return this;
	},

	flatten: function(){
		var array = [];
		for (var i = 0, l = this.length; i < l; i++){
			var type = $type(this[i]);
			if (!type) continue;
			array = array.concat((type == 'array' || type == 'collection' || type == 'arguments') ? Array.flatten(this[i]) : this[i]);
		}
		return array;
	},

	hexToRgb: function(array){
		if (this.length != 3) return null;
		var rgb = this.map(function(value){
			if (value.length == 1) value += value;
			return value.toInt(16);
		});
		return (array) ? rgb : 'rgb(' + rgb + ')';
	},

	rgbToHex: function(array){
		if (this.length < 3) return null;
		if (this.length == 4 && this[3] == 0 && !array) return 'transparent';
		var hex = [];
		for (var i = 0; i < 3; i++){
			var bit = (this[i] - 0).toString(16);
			hex.push((bit.length == 1) ? '0' + bit : bit);
		}
		return (array) ? hex : '#' + hex.join('');
	}

});

/*
Script: Function.js
	Contains Function Prototypes like create, bind, pass, and delay.

License:
	MIT-style license.
*/

Function.implement({

	extend: function(properties){
		for (var property in properties) this[property] = properties[property];
		return this;
	},

	create: function(options){
		var self = this;
		options = options || {};
		return function(event){
			var args = options.arguments;
			args = (args != undefined) ? $splat(args) : Array.slice(arguments, (options.event) ? 1 : 0);
			if (options.event) args = [event || window.event].extend(args);
			var returns = function(){
				return self.apply(options.bind || null, args);
			};
			if (options.delay) return setTimeout(returns, options.delay);
			if (options.periodical) return setInterval(returns, options.periodical);
			if (options.attempt) return $try(returns);
			return returns();
		};
	},

	pass: function(args, bind){
		return this.create({arguments: args, bind: bind});
	},

	attempt: function(args, bind){
		return this.create({arguments: args, bind: bind, attempt: true})();
	},

	bind: function(bind, args){
		return this.create({bind: bind, arguments: args});
	},

	bindWithEvent: function(bind, args){
		return this.create({bind: bind, event: true, arguments: args});
	},

	delay: function(delay, bind, args){
		return this.create({delay: delay, bind: bind, arguments: args})();
	},

	periodical: function(interval, bind, args){
		return this.create({periodical: interval, bind: bind, arguments: args})();
	},

	run: function(args, bind){
		return this.apply(bind, $splat(args));
	}

});

/*
Script: Number.js
	Contains Number Prototypes like limit, round, times, and ceil.

License:
	MIT-style license.
*/

Number.implement({

	limit: function(min, max){
		return Math.min(max, Math.max(min, this));
	},

	round: function(precision){
		precision = Math.pow(10, precision || 0);
		return Math.round(this * precision) / precision;
	},

	times: function(fn, bind){
		for (var i = 0; i < this; i++) fn.call(bind, i, this);
	},

	toFloat: function(){
		return parseFloat(this);
	},

	toInt: function(base){
		return parseInt(this, base || 10);
	}

});

Number.alias('times', 'each');

(function(math){
	var methods = {};
	math.each(function(name){
		if (!Number[name]) methods[name] = function(){
			return Math[name].apply(null, [this].concat($A(arguments)));
		};
	});
	Number.implement(methods);
})(['abs', 'acos', 'asin', 'atan', 'atan2', 'ceil', 'cos', 'exp', 'floor', 'log', 'max', 'min', 'pow', 'sin', 'sqrt', 'tan']);

/*
Script: String.js
	Contains String Prototypes like camelCase, capitalize, test, and toInt.

License:
	MIT-style license.
*/

String.implement({

	test: function(regex, params){
		return ((typeof regex == 'string') ? new RegExp(regex, params) : regex).test(this);
	},

	contains: function(string, separator){
		return (separator) ? (separator + this + separator).indexOf(separator + string + separator) > -1 : this.indexOf(string) > -1;
	},

	trim: function(){
		return this.replace(/^\s+|\s+$/g, '');
	},

	clean: function(){
		return this.replace(/\s+/g, ' ').trim();
	},

	camelCase: function(){
		return this.replace(/-\D/g, function(match){
			return match.charAt(1).toUpperCase();
		});
	},

	hyphenate: function(){
		return this.replace(/[A-Z]/g, function(match){
			return ('-' + match.charAt(0).toLowerCase());
		});
	},

	capitalize: function(){
		return this.replace(/\b[a-z]/g, function(match){
			return match.toUpperCase();
		});
	},

	escapeRegExp: function(){
		return this.replace(/([-.*+?^${}()|[\]\/\\])/g, '\\$1');
	},

	toInt: function(base){
		return parseInt(this, base || 10);
	},

	toFloat: function(){
		return parseFloat(this);
	},

	hexToRgb: function(array){
		var hex = this.match(/^#?(\w{1,2})(\w{1,2})(\w{1,2})$/);
		return (hex) ? hex.slice(1).hexToRgb(array) : null;
	},

	rgbToHex: function(array){
		var rgb = this.match(/\d{1,3}/g);
		return (rgb) ? rgb.rgbToHex(array) : null;
	},

	stripScripts: function(option){
		var scripts = '';
		var text = this.replace(/<script[^>]*>([\s\S]*?)<\/script>/gi, function(){
			scripts += arguments[1] + '\n';
			return '';
		});
		if (option === true) $exec(scripts);
		else if ($type(option) == 'function') option(scripts, text);
		return text;
	},

	substitute: function(object, regexp){
		return this.replace(regexp || (/\\?\{([^}]+)\}/g), function(match, name){
			if (match.charAt(0) == '\\') return match.slice(1);
			return (object[name] != undefined) ? object[name] : '';
		});
	}

});

/*
Script: Hash.js
	Contains Hash Prototypes. Provides a means for overcoming the JavaScript practical impossibility of extending native Objects.

License:
	MIT-style license.
*/

Hash.implement({

	has: Object.prototype.hasOwnProperty,

	keyOf: function(value){
		for (var key in this){
			if (this.hasOwnProperty(key) && this[key] === value) return key;
		}
		return null;
	},

	hasValue: function(value){
		return (Hash.keyOf(this, value) !== null);
	},

	extend: function(properties){
		Hash.each(properties, function(value, key){
			Hash.set(this, key, value);
		}, this);
		return this;
	},

	combine: function(properties){
		Hash.each(properties, function(value, key){
			Hash.include(this, key, value);
		}, this);
		return this;
	},

	erase: function(key){
		if (this.hasOwnProperty(key)) delete this[key];
		return this;
	},

	get: function(key){
		return (this.hasOwnProperty(key)) ? this[key] : null;
	},

	set: function(key, value){
		if (!this[key] || this.hasOwnProperty(key)) this[key] = value;
		return this;
	},

	empty: function(){
		Hash.each(this, function(value, key){
			delete this[key];
		}, this);
		return this;
	},

	include: function(key, value){
		var k = this[key];
		if (k == undefined) this[key] = value;
		return this;
	},

	map: function(fn, bind){
		var results = new Hash;
		Hash.each(this, function(value, key){
			results.set(key, fn.call(bind, value, key, this));
		}, this);
		return results;
	},

	filter: function(fn, bind){
		var results = new Hash;
		Hash.each(this, function(value, key){
			if (fn.call(bind, value, key, this)) results.set(key, value);
		}, this);
		return results;
	},

	every: function(fn, bind){
		for (var key in this){
			if (this.hasOwnProperty(key) && !fn.call(bind, this[key], key)) return false;
		}
		return true;
	},

	some: function(fn, bind){
		for (var key in this){
			if (this.hasOwnProperty(key) && fn.call(bind, this[key], key)) return true;
		}
		return false;
	},

	getKeys: function(){
		var keys = [];
		Hash.each(this, function(value, key){
			keys.push(key);
		});
		return keys;
	},

	getValues: function(){
		var values = [];
		Hash.each(this, function(value){
			values.push(value);
		});
		return values;
	},
	
	toQueryString: function(base){
		var queryString = [];
		Hash.each(this, function(value, key){
			if (base) key = base + '[' + key + ']';
			var result;
			switch ($type(value)){
				case 'object': result = Hash.toQueryString(value, key); break;
				case 'array':
					var qs = {};
					value.each(function(val, i){
						qs[i] = val;
					});
					result = Hash.toQueryString(qs, key);
				break;
				default: result = key + '=' + encodeURIComponent(value);
			}
			if (value != undefined) queryString.push(result);
		});
		
		return queryString.join('&');
	}

});

Hash.alias({keyOf: 'indexOf', hasValue: 'contains'});

/*
Script: Event.js
	Contains the Event Native, to make the event object completely crossbrowser.

License:
	MIT-style license.
*/

var Event = new Native({

	name: 'Event',

	initialize: function(event, win){
		win = win || window;
		var doc = win.document;
		event = event || win.event;
		if (event.$extended) return event;
		this.$extended = true;
		var type = event.type;
		var target = event.target || event.srcElement;
		while (target && target.nodeType == 3) target = target.parentNode;
		
		if (type.test(/key/)){
			var code = event.which || event.keyCode;
			var key = Event.Keys.keyOf(code);
			if (type == 'keydown'){
				var fKey = code - 111;
				if (fKey > 0 && fKey < 13) key = 'f' + fKey;
			}
			key = key || String.fromCharCode(code).toLowerCase();
		} else if (type.match(/(click|mouse|menu)/i)){
			doc = (!doc.compatMode || doc.compatMode == 'CSS1Compat') ? doc.html : doc.body;
			var page = {
				x: event.pageX || event.clientX + doc.scrollLeft,
				y: event.pageY || event.clientY + doc.scrollTop
			};
			var client = {
				x: (event.pageX) ? event.pageX - win.pageXOffset : event.clientX,
				y: (event.pageY) ? event.pageY - win.pageYOffset : event.clientY
			};
			if (type.match(/DOMMouseScroll|mousewheel/)){
				var wheel = (event.wheelDelta) ? event.wheelDelta / 120 : -(event.detail || 0) / 3;
			}
			var rightClick = (event.which == 3) || (event.button == 2);
			var related = null;
			if (type.match(/over|out/)){
				switch (type){
					case 'mouseover': related = event.relatedTarget || event.fromElement; break;
					case 'mouseout': related = event.relatedTarget || event.toElement;
				}
				if (!(function(){
					while (related && related.nodeType == 3) related = related.parentNode;
					return true;
				}).create({attempt: Browser.Engine.gecko})()) related = false;
			}
		}

		return $extend(this, {
			event: event,
			type: type,
			
			page: page,
			client: client,
			rightClick: rightClick,
			
			wheel: wheel,
			
			relatedTarget: related,
			target: target,
			
			code: code,
			key: key,
			
			shift: event.shiftKey,
			control: event.ctrlKey,
			alt: event.altKey,
			meta: event.metaKey
		});
	}

});

Event.Keys = new Hash({
	'enter': 13,
	'up': 38,
	'down': 40,
	'left': 37,
	'right': 39,
	'esc': 27,
	'space': 32,
	'backspace': 8,
	'tab': 9,
	'delete': 46
});

Event.implement({

	stop: function(){
		return this.stopPropagation().preventDefault();
	},

	stopPropagation: function(){
		if (this.event.stopPropagation) this.event.stopPropagation();
		else this.event.cancelBubble = true;
		return this;
	},

	preventDefault: function(){
		if (this.event.preventDefault) this.event.preventDefault();
		else this.event.returnValue = false;
		return this;
	}

});

/*
Script: Class.js
	Contains the Class Function for easily creating, extending, and implementing reusable Classes.

License:
	MIT-style license.
*/

var Class = new Native({

	name: 'Class',

	initialize: function(properties){
		properties = properties || {};
		var klass = function(empty){
			for (var key in this) this[key] = $unlink(this[key]);
			for (var mutator in Class.Mutators){
				if (!this[mutator]) continue;
				Class.Mutators[mutator](this, this[mutator]);
				delete this[mutator];
			}

			this.constructor = klass;
			if (empty === $empty) return this;
			
			var self = (this.initialize) ? this.initialize.apply(this, arguments) : this;
			if (this.options && this.options.initialize) this.options.initialize.call(this);
			return self;
		};

		$extend(klass, this);
		klass.constructor = Class;
		klass.prototype = properties;
		return klass;
	}

});

Class.implement({

	implement: function(){
		Class.Mutators.Implements(this.prototype, Array.slice(arguments));
		return this;
	}

});

Class.Mutators = {
  
  Implements: function(self, klasses){
  	$splat(klasses).each(function(klass){
  		$extend(self, ($type(klass) == 'class') ? new klass($empty) : klass);
  	});
  },
  
  Extends: function(self, klass){
  	var instance = new klass($empty);
  	delete instance.parent;
  	delete instance.parentOf;

  	for (var key in instance){
  		var current = self[key], previous = instance[key];
  		if (current == undefined){
  			self[key] = previous;
  			continue;
  		}

  		var ctype = $type(current), ptype = $type(previous);
  		if (ctype != ptype) continue;

  		switch (ctype){
  			case 'function': 
  				// this code will be only executed if the current browser does not support function.caller (currently only opera).
  				// we replace the function code with brute force. Not pretty, but it will only be executed if function.caller is not supported.

  				if (!arguments.callee.caller) self[key] = eval('(' + String(current).replace(/\bthis\.parent\(\s*(\))?/g, function(full, close){
  					return 'arguments.callee._parent_.call(this' + (close || ', ');
  				}) + ')');

  				// end "opera" code
  				self[key]._parent_ = previous;
  			  break;
  			case 'object': self[key] = $merge(previous, current);
  		}

  	}

  	self.parent = function(){
  		return arguments.callee.caller._parent_.apply(this, arguments);
  	};

  	self.parentOf = function(descendant){
  		return descendant._parent_.apply(this, Array.slice(arguments, 1));
  	};
  }
  
};


/*
Script: Class.Extras.js
	Contains Utility Classes that can be implemented into your own Classes to ease the execution of many common tasks.

License:
	MIT-style license.
*/

var Chain = new Class({

	chain: function(){
		this.$chain = (this.$chain || []).extend(arguments);
		return this;
	},

	callChain: function(){
		return (this.$chain && this.$chain.length) ? this.$chain.shift().apply(this, arguments) : false;
	},

	clearChain: function(){
		if (this.$chain) this.$chain.empty();
		return this;
	}

});

var Events = new Class({

	addEvent: function(type, fn, internal){
		type = Events.removeOn(type);
		if (fn != $empty){
			this.$events = this.$events || {};
			this.$events[type] = this.$events[type] || [];
			this.$events[type].include(fn);
			if (internal) fn.internal = true;
		}
		return this;
	},

	addEvents: function(events){
		for (var type in events) this.addEvent(type, events[type]);
		return this;
	},

	fireEvent: function(type, args, delay){
		type = Events.removeOn(type);
		if (!this.$events || !this.$events[type]) return this;
		this.$events[type].each(function(fn){
			fn.create({'bind': this, 'delay': delay, 'arguments': args})();
		}, this);
		return this;
	},

	removeEvent: function(type, fn){
		type = Events.removeOn(type);
		if (!this.$events || !this.$events[type]) return this;
		if (!fn.internal) this.$events[type].erase(fn);
		return this;
	},

	removeEvents: function(type){
		for (var e in this.$events){
			if (type && type != e) continue;
			var fns = this.$events[e];
			for (var i = fns.length; i--; i) this.removeEvent(e, fns[i]);
		}
		return this;
	}

});

Events.removeOn = function(string){
	return string.replace(/^on([A-Z])/, function(full, first) {
		return first.toLowerCase();
	});
};

var Options = new Class({

	setOptions: function(){
		this.options = $merge.run([this.options].extend(arguments));
		if (!this.addEvent) return this;
		for (var option in this.options){
			if ($type(this.options[option]) != 'function' || !(/^on[A-Z]/).test(option)) continue;
			this.addEvent(option, this.options[option]);
			delete this.options[option];
		}
		return this;
	}

});

/*
Script: Element.js
	One of the most important items in MooTools. Contains the dollar function, the dollars function, and an handful of cross-browser,
	time-saver methods to let you easily work with HTML Elements.

License:
	MIT-style license.
*/

Document.implement({

	newElement: function(tag, props){
		if (Browser.Engine.trident && props){
			['name', 'type', 'checked'].each(function(attribute){
				if (!props[attribute]) return;
				tag += ' ' + attribute + '="' + props[attribute] + '"';
				if (attribute != 'checked') delete props[attribute];
			});
			tag = '<' + tag + '>';
		}
		return $.element(this.createElement(tag)).set(props);
	},

	newTextNode: function(text){
		return this.createTextNode(text);
	},

	getDocument: function(){
		return this;
	},

	getWindow: function(){
		return this.defaultView || this.parentWindow;
	},

	purge: function(){
		var elements = this.getElementsByTagName('*');
		for (var i = 0, l = elements.length; i < l; i++) Browser.freeMem(elements[i]);
	}

});

var Element = new Native({

	name: 'Element',

	legacy: window.Element,

	initialize: function(tag, props){
		var konstructor = Element.Constructors.get(tag);
		if (konstructor) return konstructor(props);
		if (typeof tag == 'string') return document.newElement(tag, props);
		return $(tag).set(props);
	},

	afterImplement: function(key, value){
		if (!Array[key]) Elements.implement(key, Elements.multi(key));
		Element.Prototype[key] = value;
	}

});

Element.Prototype = {$family: {name: 'element'}};

Element.Constructors = new Hash;

var IFrame = new Native({

	name: 'IFrame',

	generics: false,

	initialize: function(){
		var params = Array.link(arguments, {properties: Object.type, iframe: $defined});
		var props = params.properties || {};
		var iframe = $(params.iframe) || false;
		var onload = props.onload || $empty;
		delete props.onload;
		props.id = props.name = $pick(props.id, props.name, iframe.id, iframe.name, 'IFrame_' + $time());
		iframe = new Element(iframe || 'iframe', props);
		var onFrameLoad = function(){
			var host = $try(function(){
				return iframe.contentWindow.location.host;
			});
			if (host && host == window.location.host){
				var win = new Window(iframe.contentWindow);
				var doc = new Document(iframe.contentWindow.document);
				$extend(win.Element.prototype, Element.Prototype);
			}
			onload.call(iframe.contentWindow, iframe.contentWindow.document);
		};
		(!window.frames[props.id]) ? iframe.addListener('load', onFrameLoad) : onFrameLoad();
		return iframe;
	}

});

var Elements = new Native({

	initialize: function(elements, options){
		options = $extend({ddup: true, cash: true}, options);
		elements = elements || [];
		if (options.ddup || options.cash){
			var uniques = {}, returned = [];
			for (var i = 0, l = elements.length; i < l; i++){
				var el = $.element(elements[i], !options.cash);
				if (options.ddup){
					if (uniques[el.uid]) continue;
					uniques[el.uid] = true;
				}
				returned.push(el);
			}
			elements = returned;
		}
		return (options.cash) ? $extend(elements, this) : elements;
	}

});

Elements.implement({

	filter: function(filter, bind){
		if (!filter) return this;
		return new Elements(Array.filter(this, (typeof filter == 'string') ? function(item){
			return item.match(filter);
		} : filter, bind));
	}

});

Elements.multi = function(property){
	return function(){
		var items = [];
		var elements = true;
		for (var i = 0, j = this.length; i < j; i++){
			var returns = this[i][property].apply(this[i], arguments);
			items.push(returns);
			if (elements) elements = ($type(returns) == 'element');
		}
		return (elements) ? new Elements(items) : items;
	};
};

Window.implement({

	$: function(el, nocash){
		if (el && el.$family && el.uid) return el;
		var type = $type(el);
		return ($[type]) ? $[type](el, nocash, this.document) : null;
	},

	$$: function(selector){
		if (arguments.length == 1 && typeof selector == 'string') return this.document.getElements(selector);
		var elements = [];
		var args = Array.flatten(arguments);
		for (var i = 0, l = args.length; i < l; i++){
			var item = args[i];
			switch ($type(item)){
				case 'element': item = [item]; break;
				case 'string': item = this.document.getElements(item, true); break;
				default: item = false;
			}
			if (item) elements.extend(item);
		}
		return new Elements(elements);
	},

	getDocument: function(){
		return this.document;
	},

	getWindow: function(){
		return this;
	}

});

$.string = function(id, nocash, doc){
	id = doc.getElementById(id);
	return (id) ? $.element(id, nocash) : null;
};

$.element = function(el, nocash){
	$uid(el);
	if (!nocash && !el.$family && !(/^object|embed$/i).test(el.tagName)){
		var proto = Element.Prototype;
		for (var p in proto) el[p] = proto[p];
	};
	return el;
};

$.object = function(obj, nocash, doc){
	if (obj.toElement) return $.element(obj.toElement(doc), nocash);
	return null;
};

$.textnode = $.whitespace = $.window = $.document = $arguments(0);

Native.implement([Element, Document], {

	getElement: function(selector, nocash){
		return $(this.getElements(selector, true)[0] || null, nocash);
	},

	getElements: function(tags, nocash){
		tags = tags.split(',');
		var elements = [];
		var ddup = (tags.length > 1);
		tags.each(function(tag){
			var partial = this.getElementsByTagName(tag.trim());
			(ddup) ? elements.extend(partial) : elements = partial;
		}, this);
		return new Elements(elements, {ddup: ddup, cash: !nocash});
	}

});

Element.Storage = {

	get: function(uid){
		return (this[uid] || (this[uid] = {}));
	}

};

Element.Inserters = new Hash({

	before: function(context, element){
		if (element.parentNode) element.parentNode.insertBefore(context, element);
	},

	after: function(context, element){
		if (!element.parentNode) return;
		var next = element.nextSibling;
		(next) ? element.parentNode.insertBefore(context, next) : element.parentNode.appendChild(context);
	},

	bottom: function(context, element){
		element.appendChild(context);
	},

	top: function(context, element){
		var first = element.firstChild;
		(first) ? element.insertBefore(context, first) : element.appendChild(context);
	}

});

Element.Inserters.inside = Element.Inserters.bottom;

Element.Inserters.each(function(value, key){

	var Key = key.capitalize();

	Element.implement('inject' + Key, function(el){
		value(this, $(el, true));
		return this;
	});

	Element.implement('grab' + Key, function(el){
		value($(el, true), this);
		return this;
	});

});

Element.implement({

	getDocument: function(){
		return this.ownerDocument;
	},

	getWindow: function(){
		return this.ownerDocument.getWindow();
	},

	getElementById: function(id, nocash){
		var el = this.ownerDocument.getElementById(id);
		if (!el) return null;
		for (var parent = el.parentNode; parent != this; parent = parent.parentNode){
			if (!parent) return null;
		}
		return $.element(el, nocash);
	},

	set: function(prop, value){
		switch ($type(prop)){
			case 'object':
				for (var p in prop) this.set(p, prop[p]);
				break;
			case 'string':
				var property = Element.Properties.get(prop);
				(property && property.set) ? property.set.apply(this, Array.slice(arguments, 1)) : this.setProperty(prop, value);
		}
		return this;
	},

	get: function(prop){
		var property = Element.Properties.get(prop);
		return (property && property.get) ? property.get.apply(this, Array.slice(arguments, 1)) : this.getProperty(prop);
	},

	erase: function(prop){
		var property = Element.Properties.get(prop);
		(property && property.erase) ? property.erase.apply(this, Array.slice(arguments, 1)) : this.removeProperty(prop);
		return this;
	},

	match: function(tag){
		return (!tag || Element.get(this, 'tag') == tag);
	},

	inject: function(el, where){
		Element.Inserters.get(where || 'bottom')(this, $(el, true));
		return this;
	},

	wraps: function(el, where){
		el = $(el, true);
		return this.replaces(el).grab(el, where);
	},

	grab: function(el, where){
		Element.Inserters.get(where || 'bottom')($(el, true), this);
		return this;
	},

	appendText: function(text, where){
		return this.grab(this.getDocument().newTextNode(text), where);
	},

	adopt: function(){
		Array.flatten(arguments).each(function(element){
			element = $(element, true);
			if (element) this.appendChild(element);
		}, this);
		return this;
	},

	dispose: function(){
		return (this.parentNode) ? this.parentNode.removeChild(this) : this;
	},

	clone: function(contents, keepid){
		switch ($type(this)){
			case 'element':
				var attributes = {};
				for (var j = 0, l = this.attributes.length; j < l; j++){
					var attribute = this.attributes[j], key = attribute.nodeName.toLowerCase();
					if (Browser.Engine.trident && (/input/i).test(this.tagName) && (/width|height/).test(key)) continue;
					var value = (key == 'style' && this.style) ? this.style.cssText : attribute.nodeValue;
					if (!$chk(value) || key == 'uid' || (key == 'id' && !keepid)) continue;
					if (value != 'inherit' && ['string', 'number'].contains($type(value))) attributes[key] = value;
				}
				var element = new Element(this.nodeName.toLowerCase(), attributes);
				if (contents !== false){
					for (var i = 0, k = this.childNodes.length; i < k; i++){
						var child = Element.clone(this.childNodes[i], true, keepid);
						if (child) element.grab(child);
					}
				}
				return element;
			case 'textnode': return document.newTextNode(this.nodeValue);
		}
		return null;
	},

	replaces: function(el){
		el = $(el, true);
		el.parentNode.replaceChild(this, el);
		return this;
	},

	hasClass: function(className){
		return this.className.contains(className, ' ');
	},

	addClass: function(className){
		if (!this.hasClass(className)) this.className = (this.className + ' ' + className).clean();
		return this;
	},

	removeClass: function(className){
		this.className = this.className.replace(new RegExp('(^|\\s)' + className + '(?:\\s|$)'), '$1').clean();
		return this;
	},

	toggleClass: function(className){
		return this.hasClass(className) ? this.removeClass(className) : this.addClass(className);
	},

	getComputedStyle: function(property){
		if (this.currentStyle) return this.currentStyle[property.camelCase()];
		var computed = this.getWindow().getComputedStyle(this, null);
		return (computed) ? computed.getPropertyValue([property.hyphenate()]) : null;
	},

	empty: function(){
		$A(this.childNodes).each(function(node){
			Browser.freeMem(node);
			Element.empty(node);
			Element.dispose(node);
		}, this);
		return this;
	},

	destroy: function(){
		Browser.freeMem(this.empty().dispose());
		return null;
	},

	getSelected: function(){
		return new Elements($A(this.options).filter(function(option){
			return option.selected;
		}));
	},

	toQueryString: function(){
		var queryString = [];
		this.getElements('input, select, textarea').each(function(el){
			if (!el.name || el.disabled) return;
			var value = (el.tagName.toLowerCase() == 'select') ? Element.getSelected(el).map(function(opt){
				return opt.value;
			}) : ((el.type == 'radio' || el.type == 'checkbox') && !el.checked) ? null : el.value;
			$splat(value).each(function(val){
				if (val) queryString.push(el.name + '=' + encodeURIComponent(val));
			});
		});
		return queryString.join('&');
	},

	getProperty: function(attribute){
		var EA = Element.Attributes, key = EA.Props[attribute];
		var value = (key) ? this[key] : this.getAttribute(attribute, 2);
		return (EA.Bools[attribute]) ? !!value : (key) ? value : value || null;
	},

	getProperties: function(){
		var args = $A(arguments);
		return args.map(function(attr){
			return this.getProperty(attr);
		}, this).associate(args);
	},

	setProperty: function(attribute, value){
		var EA = Element.Attributes, key = EA.Props[attribute], hasValue = $defined(value);
		if (key && EA.Bools[attribute]) value = (value || !hasValue) ? true : false;
		else if (!hasValue) return this.removeProperty(attribute);
		(key) ? this[key] = value : this.setAttribute(attribute, value);
		return this;
	},

	setProperties: function(attributes){
		for (var attribute in attributes) this.setProperty(attribute, attributes[attribute]);
		return this;
	},

	removeProperty: function(attribute){
		var EA = Element.Attributes, key = EA.Props[attribute], isBool = (key && EA.Bools[attribute]);
		(key) ? this[key] = (isBool) ? false : '' : this.removeAttribute(attribute);
		return this;
	},

	removeProperties: function(){
		Array.each(arguments, this.removeProperty, this);
		return this;
	}

});

(function(){

var walk = function(element, walk, start, match, all, nocash){
	var el = element[start || walk];
	var elements = [];
	while (el){
		if (el.nodeType == 1 && (!match || Element.match(el, match))){
			elements.push(el);
			if (!all) break;
		}
		el = el[walk];
	}
	return (all) ? new Elements(elements, {ddup: false, cash: !nocash}) : $(elements[0], nocash);
};

Element.implement({

	getPrevious: function(match, nocash){
		return walk(this, 'previousSibling', null, match, false, nocash);
	},

	getAllPrevious: function(match, nocash){
		return walk(this, 'previousSibling', null, match, true, nocash);
	},

	getNext: function(match, nocash){
		return walk(this, 'nextSibling', null, match, false, nocash);
	},

	getAllNext: function(match, nocash){
		return walk(this, 'nextSibling', null, match, true, nocash);
	},

	getFirst: function(match, nocash){
		return walk(this, 'nextSibling', 'firstChild', match, false, nocash);
	},

	getLast: function(match, nocash){
		return walk(this, 'previousSibling', 'lastChild', match, false, nocash);
	},

	getParent: function(match, nocash){
		return walk(this, 'parentNode', null, match, false, nocash);
	},

	getParents: function(match, nocash){
		return walk(this, 'parentNode', null, match, true, nocash);
	},

	getChildren: function(match, nocash){
		return walk(this, 'nextSibling', 'firstChild', match, true, nocash);
	},

	hasChild: function(el){
		el = $(el, true);
		return (!!el && $A(this.getElementsByTagName(el.tagName)).contains(el));
	}

});

})();

Element.Properties = new Hash;

Element.Properties.style = {

	set: function(style){
		this.style.cssText = style;
	},

	get: function(){
		return this.style.cssText;
	},

	erase: function(){
		this.style.cssText = '';
	}

};

Element.Properties.tag = {get: function(){
	return this.tagName.toLowerCase();
}};

Element.Properties.href = {get: function(){
	return (!this.href) ? null : this.href.replace(new RegExp('^' + document.location.protocol + '\/\/' + document.location.host), '');
}};

Element.Properties.html = {set: function(){
	return this.innerHTML = Array.flatten(arguments).join('');
}};

Native.implement([Element, Window, Document], {

	addListener: function(type, fn){
		if (this.addEventListener) this.addEventListener(type, fn, false);
		else this.attachEvent('on' + type, fn);
		return this;
	},

	removeListener: function(type, fn){
		if (this.removeEventListener) this.removeEventListener(type, fn, false);
		else this.detachEvent('on' + type, fn);
		return this;
	},

	retrieve: function(property, dflt){
		var storage = Element.Storage.get(this.uid);
		var prop = storage[property];
		if ($defined(dflt) && !$defined(prop)) prop = storage[property] = dflt;
		return $pick(prop);
	},

	store: function(property, value){
		var storage = Element.Storage.get(this.uid);
		storage[property] = value;
		return this;
	},

	eliminate: function(property){
		var storage = Element.Storage.get(this.uid);
		delete storage[property];
		return this;
	}

});

Element.Attributes = new Hash({
	Props: {'html': 'innerHTML', 'class': 'className', 'for': 'htmlFor', 'text': (Browser.Engine.trident) ? 'innerText' : 'textContent'},
	Bools: ['compact', 'nowrap', 'ismap', 'declare', 'noshade', 'checked', 'disabled', 'readonly', 'multiple', 'selected', 'noresize', 'defer'],
	Camels: ['value', 'accessKey', 'cellPadding', 'cellSpacing', 'colSpan', 'frameBorder', 'maxLength', 'readOnly', 'rowSpan', 'tabIndex', 'useMap']
});

Browser.freeMem = function(item){
	if (!item) return;
	if (Browser.Engine.trident && (/object/i).test(item.tagName)){
		for (var p in item){
			if (typeof item[p] == 'function') item[p] = $empty;
		}
		Element.dispose(item);
	}
	if (item.uid && item.removeEvents) item.removeEvents();
};

(function(EA){

	var EAB = EA.Bools, EAC = EA.Camels;
	EA.Bools = EAB = EAB.associate(EAB);
	Hash.extend(Hash.combine(EA.Props, EAB), EAC.associate(EAC.map(function(v){
		return v.toLowerCase();
	})));
	EA.erase('Camels');

})(Element.Attributes);

window.addListener('unload', function(){
	window.removeListener('unload', arguments.callee);
	document.purge();
	if (Browser.Engine.trident) CollectGarbage();
});

/*
Script: Element.Event.js
	Contains Element methods for dealing with events, and custom Events.

License:
	MIT-style license.
*/

Element.Properties.events = {set: function(events){
	this.addEvents(events);
}};

Native.implement([Element, Window, Document], {

	addEvent: function(type, fn){
		var events = this.retrieve('events', {});
		events[type] = events[type] || {'keys': [], 'values': []};
		if (events[type].keys.contains(fn)) return this;
		events[type].keys.push(fn);
		var realType = type, custom = Element.Events.get(type), condition = fn, self = this;
		if (custom){
			if (custom.onAdd) custom.onAdd.call(this, fn);
			if (custom.condition){
				condition = function(event){
					if (custom.condition.call(this, event)) return fn.call(this, event);
					return false;
				};
			}
			realType = custom.base || realType;
		}
		var defn = function(){
			return fn.call(self);
		};
		var nativeEvent = Element.NativeEvents[realType] || 0;
		if (nativeEvent){
			if (nativeEvent == 2){
				defn = function(event){
					event = new Event(event, self.getWindow());
					if (condition.call(self, event) === false) event.stop();
				};
			}
			this.addListener(realType, defn);
		}
		events[type].values.push(defn);
		return this;
	},

	removeEvent: function(type, fn){
		var events = this.retrieve('events');
		if (!events || !events[type]) return this;
		var pos = events[type].keys.indexOf(fn);
		if (pos == -1) return this;
		var key = events[type].keys.splice(pos, 1)[0];
		var value = events[type].values.splice(pos, 1)[0];
		var custom = Element.Events.get(type);
		if (custom){
			if (custom.onRemove) custom.onRemove.call(this, fn);
			type = custom.base || type;
		}
		return (Element.NativeEvents[type]) ? this.removeListener(type, value) : this;
	},

	addEvents: function(events){
		for (var event in events) this.addEvent(event, events[event]);
		return this;
	},

	removeEvents: function(type){
		var events = this.retrieve('events');
		if (!events) return this;
		if (!type){
			for (var evType in events) this.removeEvents(evType);
			events = null;
		} else if (events[type]){
			while (events[type].keys[0]) this.removeEvent(type, events[type].keys[0]);
			events[type] = null;
		}
		return this;
	},

	fireEvent: function(type, args, delay){
		var events = this.retrieve('events');
		if (!events || !events[type]) return this;
		events[type].keys.each(function(fn){
			fn.create({'bind': this, 'delay': delay, 'arguments': args})();
		}, this);
		return this;
	},

	cloneEvents: function(from, type){
		from = $(from);
		var fevents = from.retrieve('events');
		if (!fevents) return this;
		if (!type){
			for (var evType in fevents) this.cloneEvents(from, evType);
		} else if (fevents[type]){
			fevents[type].keys.each(function(fn){
				this.addEvent(type, fn);
			}, this);
		}
		return this;
	}

});

Element.NativeEvents = {
	click: 2, dblclick: 2, mouseup: 2, mousedown: 2, contextmenu: 2, //mouse buttons
	mousewheel: 2, DOMMouseScroll: 2, //mouse wheel
	mouseover: 2, mouseout: 2, mousemove: 2, selectstart: 2, selectend: 2, //mouse movement
	keydown: 2, keypress: 2, keyup: 2, //keyboard
	focus: 2, blur: 2, change: 2, reset: 2, select: 2, submit: 2, //form elements
	load: 1, unload: 1, beforeunload: 2, resize: 1, move: 1, DOMContentLoaded: 1, readystatechange: 1, //window
	error: 1, abort: 1, scroll: 1 //misc
};

(function(){

var $check = function(event){
	var related = event.relatedTarget;
	if (related == undefined) return true;
	if (related === false) return false;
	return ($type(this) != 'document' && related != this && related.prefix != 'xul' && !this.hasChild(related));
};

Element.Events = new Hash({

	mouseenter: {
		base: 'mouseover',
		condition: $check
	},

	mouseleave: {
		base: 'mouseout',
		condition: $check
	},

	mousewheel: {
		base: (Browser.Engine.gecko) ? 'DOMMouseScroll' : 'mousewheel'
	}

});

})();

/*
Script: Element.Style.js
	Contains methods for interacting with the styles of Elements in a fashionable way.

License:
	MIT-style license.
*/

Element.Properties.styles = {set: function(styles){
	this.setStyles(styles);
}};

Element.Properties.opacity = {

	set: function(opacity, novisibility){
		if (!novisibility){
			if (opacity == 0){
				if (this.style.visibility != 'hidden') this.style.visibility = 'hidden';
			} else {
				if (this.style.visibility != 'visible') this.style.visibility = 'visible';
			}
		}
		if (!this.currentStyle || !this.currentStyle.hasLayout) this.style.zoom = 1;
		if (Browser.Engine.trident) this.style.filter = (opacity == 1) ? '' : 'alpha(opacity=' + opacity * 100 + ')';
		this.style.opacity = opacity;
		this.store('opacity', opacity);
	},

	get: function(){
		return this.retrieve('opacity', 1);
	}

};

Element.implement({
	
	setOpacity: function(value){
		return this.set('opacity', value, true);
	},
	
	getOpacity: function(){
		return this.get('opacity');
	},

	setStyle: function(property, value){
		switch (property){
			case 'opacity': return this.set('opacity', parseFloat(value));
			case 'float': property = (Browser.Engine.trident) ? 'styleFloat' : 'cssFloat';
		}
		property = property.camelCase();
		if ($type(value) != 'string'){
			var map = (Element.Styles.get(property) || '@').split(' ');
			value = $splat(value).map(function(val, i){
				if (!map[i]) return '';
				return ($type(val) == 'number') ? map[i].replace('@', Math.round(val)) : val;
			}).join(' ');
		} else if (value == String(Number(value))){
			value = Math.round(value);
		}
		this.style[property] = value;
		return this;
	},

	getStyle: function(property){
		switch (property){
			case 'opacity': return this.get('opacity');
			case 'float': property = (Browser.Engine.trident) ? 'styleFloat' : 'cssFloat';
		}
		property = property.camelCase();
		var result = this.style[property];
		if (!$chk(result)){
			result = [];
			for (var style in Element.ShortStyles){
				if (property != style) continue;
				for (var s in Element.ShortStyles[style]) result.push(this.getStyle(s));
				return result.join(' ');
			}
			result = this.getComputedStyle(property);
		}
		if (result){
			result = String(result);
			var color = result.match(/rgba?\([\d\s,]+\)/);
			if (color) result = result.replace(color[0], color[0].rgbToHex());
		}
		if (Browser.Engine.presto || (Browser.Engine.trident && !$chk(parseInt(result)))){
			if (property.test(/^(height|width)$/)){
				var values = (property == 'width') ? ['left', 'right'] : ['top', 'bottom'], size = 0;
				values.each(function(value){
					size += this.getStyle('border-' + value + '-width').toInt() + this.getStyle('padding-' + value).toInt();
				}, this);
				return this['offset' + property.capitalize()] - size + 'px';
			}
			if (Browser.Engine.presto && String(result).test('px')) return result;
			if (property.test(/(border(.+)Width|margin|padding)/)) return '0px';
		}
		return result;
	},

	setStyles: function(styles){
		for (var style in styles) this.setStyle(style, styles[style]);
		return this;
	},

	getStyles: function(){
		var result = {};
		Array.each(arguments, function(key){
			result[key] = this.getStyle(key);
		}, this);
		return result;
	}

});

Element.Styles = new Hash({
	left: '@px', top: '@px', bottom: '@px', right: '@px',
	width: '@px', height: '@px', maxWidth: '@px', maxHeight: '@px', minWidth: '@px', minHeight: '@px',
	backgroundColor: 'rgb(@, @, @)', backgroundPosition: '@px @px', color: 'rgb(@, @, @)',
	fontSize: '@px', letterSpacing: '@px', lineHeight: '@px', clip: 'rect(@px @px @px @px)',
	margin: '@px @px @px @px', padding: '@px @px @px @px', border: '@px @ rgb(@, @, @) @px @ rgb(@, @, @) @px @ rgb(@, @, @)',
	borderWidth: '@px @px @px @px', borderStyle: '@ @ @ @', borderColor: 'rgb(@, @, @) rgb(@, @, @) rgb(@, @, @) rgb(@, @, @)',
	zIndex: '@', 'zoom': '@', fontWeight: '@', textIndent: '@px', opacity: '@'
});

Element.ShortStyles = {margin: {}, padding: {}, border: {}, borderWidth: {}, borderStyle: {}, borderColor: {}};

['Top', 'Right', 'Bottom', 'Left'].each(function(direction){
	var Short = Element.ShortStyles;
	var All = Element.Styles;
	['margin', 'padding'].each(function(style){
		var sd = style + direction;
		Short[style][sd] = All[sd] = '@px';
	});
	var bd = 'border' + direction;
	Short.border[bd] = All[bd] = '@px @ rgb(@, @, @)';
	var bdw = bd + 'Width', bds = bd + 'Style', bdc = bd + 'Color';
	Short[bd] = {};
	Short.borderWidth[bdw] = Short[bd][bdw] = All[bdw] = '@px';
	Short.borderStyle[bds] = Short[bd][bds] = All[bds] = '@';
	Short.borderColor[bdc] = Short[bd][bdc] = All[bdc] = 'rgb(@, @, @)';
});


/*
Script: Element.Dimensions.js
	Contains methods to work with size, scroll, or positioning of Elements and the window object.

License:
	MIT-style license.

Credits:
	- Element positioning based on the [qooxdoo](http://qooxdoo.org/) code and smart browser fixes, [LGPL License](http://www.gnu.org/licenses/lgpl.html).
	- Viewport dimensions based on [YUI](http://developer.yahoo.com/yui/) code, [BSD License](http://developer.yahoo.com/yui/license.html).
*/

(function(){

Element.implement({

	scrollTo: function(x, y){
		if (isBody(this)){
			this.getWindow().scrollTo(x, y);
		} else {
			this.scrollLeft = x;
			this.scrollTop = y;
		}
		return this;
	},

	getSize: function(){
		if (isBody(this)) return this.getWindow().getSize();
		return {x: this.offsetWidth, y: this.offsetHeight};
	},

	getScrollSize: function(){
		if (isBody(this)) return this.getWindow().getScrollSize();
		return {x: this.scrollWidth, y: this.scrollHeight};
	},

	getScroll: function(){
		if (isBody(this)) return this.getWindow().getScroll();
		return {x: this.scrollLeft, y: this.scrollTop};
	},

	getScrolls: function(){
		var element = this, position = {x: 0, y: 0};
		while (element && !isBody(element)){
			position.x += element.scrollLeft;
			position.y += element.scrollTop;
			element = element.parentNode;
		}
		return position;
	},
	
	getOffsetParent: function(){
		var element = this;
		if (isBody(element)) return null; 
		if (!Browser.Engine.trident) return element.offsetParent;
		while ((element = element.parentNode) && !isBody(element)){ 
			if (styleString(element, 'position') != 'static') return element;
		} 
		return null;
	},

	getOffsets: function(){
		var element = this, position = {x: 0, y: 0};
		if (isBody(this)) return position;

		while (element && !isBody(element)){
			position.x += element.offsetLeft;
			position.y += element.offsetTop;

			if (Browser.Engine.gecko){
				if (!borderBox(element)){
					position.x += leftBorder(element);
					position.y += topBorder(element);
				}
				var parent = element.parentNode;
				if (parent && styleString(parent, 'overflow') != 'visible'){
					position.x += leftBorder(parent);
					position.y += topBorder(parent);
				}
			} else if (element != this && (Browser.Engine.trident || Browser.Engine.webkit)){
				position.x += leftBorder(element);
				position.y += topBorder(element);
			}

			element = element.offsetParent;
			if (Browser.Engine.trident){
				while (element && !element.currentStyle.hasLayout) element = element.offsetParent;
			}
		}
		if (Browser.Engine.gecko && !borderBox(this)){
			position.x -= leftBorder(this);
			position.y -= topBorder(this);
		}
		return position;
	},

	getPosition: function(relative){
		if (isBody(this)) return {x: 0, y: 0};
		var offset = this.getOffsets(), scroll = this.getScrolls();
		var position = {x: offset.x - scroll.x, y: offset.y - scroll.y};
		var relativePosition = (relative && (relative = $(relative))) ? relative.getPosition() : {x: 0, y: 0};
		return {x: position.x - relativePosition.x, y: position.y - relativePosition.y};
	},

	getCoordinates: function(element){
		if (isBody(this)) return this.getWindow().getCoordinates();
		var position = this.getPosition(element), size = this.getSize();
		var obj = {left: position.x, top: position.y, width: size.x, height: size.y};
		obj.right = obj.left + obj.width;
		obj.bottom = obj.top + obj.height;
		return obj;
	},

	computePosition: function(obj){
		return {left: obj.x - styleNumber(this, 'margin-left'), top: obj.y - styleNumber(this, 'margin-top')};
	},

	position: function(obj){
		return this.setStyles(this.computePosition(obj));
	}

});

Native.implement([Document, Window], {

	getSize: function(){
		var win = this.getWindow();
		if (Browser.Engine.presto || Browser.Engine.webkit) return {x: win.innerWidth, y: win.innerHeight};
		var doc = getCompatElement(this);
		return {x: doc.clientWidth, y: doc.clientHeight};
	},

	getScroll: function(){
		var win = this.getWindow();
		var doc = getCompatElement(this);
		return {x: win.pageXOffset || doc.scrollLeft, y: win.pageYOffset || doc.scrollTop};
	},

	getScrollSize: function(){
		var doc = getCompatElement(this);
		var min = this.getSize();
		return {x: Math.max(doc.scrollWidth, min.x), y: Math.max(doc.scrollHeight, min.y)};
	},

	getPosition: function(){
		return {x: 0, y: 0};
	},

	getCoordinates: function(){
		var size = this.getSize();
		return {top: 0, left: 0, bottom: size.y, right: size.x, height: size.y, width: size.x};
	}

});

// private methods

var styleString = Element.getComputedStyle;

function styleNumber(element, style){
	return styleString(element, style).toInt() || 0;
};

function borderBox(element){
	return styleString(element, '-moz-box-sizing') == 'border-box';
};

function topBorder(element){
	return styleNumber(element, 'border-top-width');
};

function leftBorder(element){
	return styleNumber(element, 'border-left-width');
};

function isBody(element){
	return (/^(?:body|html)$/i).test(element.tagName);
};

function getCompatElement(element){
	var doc = element.getDocument();
	return (!doc.compatMode || doc.compatMode == 'CSS1Compat') ? doc.html : doc.body;
};

})();

//aliases

Native.implement([Window, Document, Element], {

	getHeight: function(){
		return this.getSize().y;
	},

	getWidth: function(){
		return this.getSize().x;
	},

	getScrollTop: function(){
		return this.getScroll().y;
	},

	getScrollLeft: function(){
		return this.getScroll().x;
	},

	getScrollHeight: function(){
		return this.getScrollSize().y;
	},

	getScrollWidth: function(){
		return this.getScrollSize().x;
	},

	getTop: function(){
		return this.getPosition().y;
	},

	getLeft: function(){
		return this.getPosition().x;
	}

});

/*
Script: Selectors.js
	Adds advanced CSS Querying capabilities for targeting elements. Also includes pseudoselectors support.

License:
	MIT-style license.
*/

Native.implement([Document, Element], {
	
	getElements: function(expression, nocash){
		expression = expression.split(',');
		var items, local = {};
		for (var i = 0, l = expression.length; i < l; i++){
			var selector = expression[i], elements = Selectors.Utils.search(this, selector, local);
			if (i != 0 && elements.item) elements = $A(elements);
			items = (i == 0) ? elements : (items.item) ? $A(items).concat(elements) : items.concat(elements);
		}
		return new Elements(items, {ddup: (expression.length > 1), cash: !nocash});
	}
	
});

Element.implement({
	
	match: function(selector){
		if (!selector) return true;
		var tagid = Selectors.Utils.parseTagAndID(selector);
		var tag = tagid[0], id = tagid[1];
		if (!Selectors.Filters.byID(this, id) || !Selectors.Filters.byTag(this, tag)) return false;
		var parsed = Selectors.Utils.parseSelector(selector);
		return (parsed) ? Selectors.Utils.filter(this, parsed, {}) : true;
	}
	
});

var Selectors = {Cache: {nth: {}, parsed: {}}};

Selectors.RegExps = {
	id: (/#([\w-]+)/),
	tag: (/^(\w+|\*)/),
	quick: (/^(\w+|\*)$/),
	splitter: (/\s*([+>~\s])\s*([a-zA-Z#.*:\[])/g),
	combined: (/\.([\w-]+)|\[(\w+)(?:([!*^$~|]?=)["']?(.*?)["']?)?\]|:([\w-]+)(?:\(["']?(.*?)?["']?\)|$)/g)
};

Selectors.Utils = {
	
	chk: function(item, uniques){
		if (!uniques) return true;
		var uid = $uid(item);
		if (!uniques[uid]) return uniques[uid] = true;
		return false;
	},
	
	parseNthArgument: function(argument){
		if (Selectors.Cache.nth[argument]) return Selectors.Cache.nth[argument];
		var parsed = argument.match(/^([+-]?\d*)?([a-z]+)?([+-]?\d*)?$/);
		if (!parsed) return false;
		var inta = parseInt(parsed[1]);
		var a = (inta || inta === 0) ? inta : 1;
		var special = parsed[2] || false;
		var b = parseInt(parsed[3]) || 0;
		if (a != 0){
			b--;
			while (b < 1) b += a;
			while (b >= a) b -= a;
		} else {
			a = b;
			special = 'index';
		}
		switch (special){
			case 'n': parsed = {a: a, b: b, special: 'n'}; break;
			case 'odd': parsed = {a: 2, b: 0, special: 'n'}; break;
			case 'even': parsed =  {a: 2, b: 1, special: 'n'}; break;
			case 'first': parsed = {a: 0, special: 'index'}; break;
			case 'last': parsed = {special: 'last-child'}; break;
			case 'only': parsed = {special: 'only-child'}; break;
			default: parsed = {a: (a - 1), special: 'index'};
		}
		
		return Selectors.Cache.nth[argument] = parsed;
	},
	
	parseSelector: function(selector){
		if (Selectors.Cache.parsed[selector]) return Selectors.Cache.parsed[selector];
		var m, parsed = {classes: [], pseudos: [], attributes: []};
		while ((m = Selectors.RegExps.combined.exec(selector))){
			var cn = m[1], an = m[2], ao = m[3], av = m[4], pn = m[5], pa = m[6];
			if (cn){
				parsed.classes.push(cn);
			} else if (pn){
				var parser = Selectors.Pseudo.get(pn);
				if (parser) parsed.pseudos.push({parser: parser, argument: pa});
				else parsed.attributes.push({name: pn, operator: '=', value: pa});
			} else if (an){
				parsed.attributes.push({name: an, operator: ao, value: av});
			}
		}
		if (!parsed.classes.length) delete parsed.classes;
		if (!parsed.attributes.length) delete parsed.attributes;
		if (!parsed.pseudos.length) delete parsed.pseudos;
		if (!parsed.classes && !parsed.attributes && !parsed.pseudos) parsed = null;
		return Selectors.Cache.parsed[selector] = parsed;
	},
	
	parseTagAndID: function(selector){
		var tag = selector.match(Selectors.RegExps.tag);
		var id = selector.match(Selectors.RegExps.id);
		return [(tag) ? tag[1] : '*', (id) ? id[1] : false];
	},
	
	filter: function(item, parsed, local){
		var i;
		if (parsed.classes){
			for (i = parsed.classes.length; i--; i){
				var cn = parsed.classes[i];
				if (!Selectors.Filters.byClass(item, cn)) return false;
			}
		}
		if (parsed.attributes){
			for (i = parsed.attributes.length; i--; i){
				var att = parsed.attributes[i];
				if (!Selectors.Filters.byAttribute(item, att.name, att.operator, att.value)) return false;
			}
		}
		if (parsed.pseudos){
			for (i = parsed.pseudos.length; i--; i){
				var psd = parsed.pseudos[i];
				if (!Selectors.Filters.byPseudo(item, psd.parser, psd.argument, local)) return false;
			}
		}
		return true;
	},
	
	getByTagAndID: function(ctx, tag, id){
		if (id){
			var item = (ctx.getElementById) ? ctx.getElementById(id, true) : Element.getElementById(ctx, id, true);
			return (item && Selectors.Filters.byTag(item, tag)) ? [item] : [];
		} else {
			return ctx.getElementsByTagName(tag);
		}
	},
	
	search: function(self, expression, local){
		var splitters = [];
		
		var selectors = expression.trim().replace(Selectors.RegExps.splitter, function(m0, m1, m2){
			splitters.push(m1);
			return ':)' + m2;
		}).split(':)');
		
		var items, match, filtered, item;
		
		for (var i = 0, l = selectors.length; i < l; i++){
			
			var selector = selectors[i];
			
			if (i == 0 && Selectors.RegExps.quick.test(selector)){
				items = self.getElementsByTagName(selector);
				continue;
			}
			
			var splitter = splitters[i - 1];
			
			var tagid = Selectors.Utils.parseTagAndID(selector);
			var tag = tagid[0], id = tagid[1];

			if (i == 0){
				items = Selectors.Utils.getByTagAndID(self, tag, id);
			} else {
				var uniques = {}, found = [];
				for (var j = 0, k = items.length; j < k; j++) found = Selectors.Getters[splitter](found, items[j], tag, id, uniques);
				items = found;
			}
			
			var parsed = Selectors.Utils.parseSelector(selector);
			
			if (parsed){
				filtered = [];
				for (var m = 0, n = items.length; m < n; m++){
					item = items[m];
					if (Selectors.Utils.filter(item, parsed, local)) filtered.push(item);
				}
				items = filtered;
			}
			
		}
		
		return items;
		
	}
	
};

Selectors.Getters = {
	
	' ': function(found, self, tag, id, uniques){
		var items = Selectors.Utils.getByTagAndID(self, tag, id);
		for (var i = 0, l = items.length; i < l; i++){
			var item = items[i];
			if (Selectors.Utils.chk(item, uniques)) found.push(item);
		}
		return found;
	},
	
	'>': function(found, self, tag, id, uniques){
		var children = Selectors.Utils.getByTagAndID(self, tag, id);
		for (var i = 0, l = children.length; i < l; i++){
			var child = children[i];
			if (child.parentNode == self && Selectors.Utils.chk(child, uniques)) found.push(child);
		}
		return found;
	},
	
	'+': function(found, self, tag, id, uniques){
		while ((self = self.nextSibling)){
			if (self.nodeType == 1){
				if (Selectors.Utils.chk(self, uniques) && Selectors.Filters.byTag(self, tag) && Selectors.Filters.byID(self, id)) found.push(self);
				break;
			}
		}
		return found;
	},
	
	'~': function(found, self, tag, id, uniques){
		
		while ((self = self.nextSibling)){
			if (self.nodeType == 1){
				if (!Selectors.Utils.chk(self, uniques)) break;
				if (Selectors.Filters.byTag(self, tag) && Selectors.Filters.byID(self, id)) found.push(self);
			} 
		}
		return found;
	}
	
};

Selectors.Filters = {
	
	byTag: function(self, tag){
		return (tag == '*' || (self.tagName && self.tagName.toLowerCase() == tag));
	},
	
	byID: function(self, id){
		return (!id || (self.id && self.id == id));
	},
	
	byClass: function(self, klass){
		return (self.className && self.className.contains(klass, ' '));
	},
	
	byPseudo: function(self, parser, argument, local){
		return parser.call(self, argument, local);
	},
	
	byAttribute: function(self, name, operator, value){
		var result = Element.prototype.getProperty.call(self, name);
		if (!result) return false;
		if (!operator || value == undefined) return true;
		switch (operator){
			case '=': return (result == value);
			case '*=': return (result.contains(value));
			case '^=': return (result.substr(0, value.length) == value);
			case '$=': return (result.substr(result.length - value.length) == value);
			case '!=': return (result != value);
			case '~=': return result.contains(value, ' ');
			case '|=': return result.contains(value, '-');
		}
		return false;
	}
	
};

Selectors.Pseudo = new Hash({
	
	// w3c pseudo selectors
	
	empty: function(){
		return !(this.innerText || this.textContent || '').length;
	},
	
	not: function(selector){
		return !Element.match(this, selector);
	},
	
	contains: function(text){
		return (this.innerText || this.textContent || '').contains(text);
	},
	
	'first-child': function(){
		return Selectors.Pseudo.index.call(this, 0);
	},
	
	'last-child': function(){
		var element = this;
		while ((element = element.nextSibling)){
			if (element.nodeType == 1) return false;
		}
		return true;
	},
	
	'only-child': function(){
		var prev = this;
		while ((prev = prev.previousSibling)){
			if (prev.nodeType == 1) return false;
		}
		var next = this;
		while ((next = next.nextSibling)){
			if (next.nodeType == 1) return false;
		}
		return true;
	},
	
	'nth-child': function(argument, local){
		argument = (argument == undefined) ? 'n' : argument;
		var parsed = Selectors.Utils.parseNthArgument(argument);
		if (parsed.special != 'n') return Selectors.Pseudo[parsed.special].call(this, parsed.a, local);
		var count = 0;
		local.positions = local.positions || {};
		var uid = $uid(this);
		if (!local.positions[uid]){
			var self = this;
			while ((self = self.previousSibling)){
				if (self.nodeType != 1) continue;
				count ++;
				var position = local.positions[$uid(self)];
				if (position != undefined){
					count = position + count;
					break;
				}
			}
			local.positions[uid] = count;
		}
		return (local.positions[uid] % parsed.a == parsed.b);
	},
	
	// custom pseudo selectors
	
	index: function(index){
		var element = this, count = 0;
		while ((element = element.previousSibling)){
			if (element.nodeType == 1 && ++count > index) return false;
		}
		return (count == index);
	},
	
	even: function(argument, local){
		return Selectors.Pseudo['nth-child'].call(this, '2n+1', local);
	},

	odd: function(argument, local){
		return Selectors.Pseudo['nth-child'].call(this, '2n', local);
	}
	
});

/*
Script: Domready.js
	Contains the domready custom event.

License:
	MIT-style license.
*/

Element.Events.domready = {

	onAdd: function(fn){
		if (Browser.loaded) fn.call(this);
	}

};

(function(){
	
	var domready = function(){
		if (Browser.loaded) return;
		Browser.loaded = true;
		window.fireEvent('domready');
		document.fireEvent('domready');
	};
	
	switch (Browser.Engine.name){

		case 'webkit': (function(){
			(['loaded', 'complete'].contains(document.readyState)) ? domready() : arguments.callee.delay(50);
		})(); break;

		case 'trident':
			var temp = document.createElement('div');
			(function(){
				($try(function(){
					temp.doScroll('left');
					return $(temp).inject(document.body).set('html', 'temp').dispose();
				})) ? domready() : arguments.callee.delay(50);
			})();
		break;
		
		default:
			window.addEvent('load', domready);
			document.addEvent('DOMContentLoaded', domready);

	}
	
})();

/*
Script: JSON.js
	JSON encoder and decoder.

License:
	MIT-style license.

See Also:
	<http://www.json.org/>
*/

var JSON = new Hash({

	encode: function(obj){
		switch ($type(obj)){
			case 'string':
				return '"' + obj.replace(/[\x00-\x1f\\"]/g, JSON.$replaceChars) + '"';
			case 'array':
				return '[' + String(obj.map(JSON.encode).filter($defined)) + ']';
			case 'object': case 'hash':
				var string = [];
				Hash.each(obj, function(value, key){
					var json = JSON.encode(value);
					if (json) string.push(JSON.encode(key) + ':' + json);
				});
				return '{' + string + '}';
			case 'number': case 'boolean': return String(obj);
			case false: return 'null';
		}
		return null;
	},

	$specialChars: {'\b': '\\b', '\t': '\\t', '\n': '\\n', '\f': '\\f', '\r': '\\r', '"' : '\\"', '\\': '\\\\'},

	$replaceChars: function(chr){
		return JSON.$specialChars[chr] || '\\u00' + Math.floor(chr.charCodeAt() / 16).toString(16) + (chr.charCodeAt() % 16).toString(16);
	},

	decode: function(string, secure){
		if ($type(string) != 'string' || !string.length) return null;
		if (secure && !(/^[,:{}\[\]0-9.\-+Eaeflnr-u \n\r\t]*$/).test(string.replace(/\\./g, '@').replace(/"[^"\\\n\r]*"/g, ''))) return null;
		return eval('(' + string + ')');
	}

});

Native.implement([Hash, Array, String, Number], {

	toJSON: function(){
		return JSON.encode(this);
	}

});


/*
Script: Cookie.js
	Class for creating, loading, and saving browser Cookies.

License:
	MIT-style license.

Credits:
	Based on the functions by Peter-Paul Koch (http://quirksmode.org).
*/

var Cookie = new Class({

	Implements: Options,

	options: {
		path: false,
		domain: false,
		duration: false,
		secure: false,
		document: document
	},

	initialize: function(key, options){
		this.key = key;
		this.setOptions(options);
	},

	write: function(value){
		value = encodeURIComponent(value);
		if (this.options.domain) value += '; domain=' + this.options.domain;
		if (this.options.path) value += '; path=' + this.options.path;
		if (this.options.duration){
			var date = new Date();
			date.setTime(date.getTime() + this.options.duration * 24 * 60 * 60 * 1000);
			value += '; expires=' + date.toGMTString();
		}
		if (this.options.secure) value += '; secure';
		this.options.document.cookie = this.key + '=' + value;
		return this;
	},

	read: function(){
		var value = this.options.document.cookie.match('(?:^|;)\\s*' + this.key.escapeRegExp() + '=([^;]*)');
		return (value) ? decodeURIComponent(value[1]) : null;
	},

	dispose: function(){
		new Cookie(this.key, $merge(this.options, {duration: -1})).write('');
		return this;
	}

});

Cookie.write = function(key, value, options){
	return new Cookie(key, options).write(value);
};

Cookie.read = function(key){
	return new Cookie(key).read();
};

Cookie.dispose = function(key, options){
	return new Cookie(key, options).dispose();
};

/*
Script: Swiff.js
	Wrapper for embedding SWF movies. Supports (and fixes) External Interface Communication.

License:
	MIT-style license.

Credits:
	Flash detection & Internet Explorer + Flash Player 9 fix inspired by SWFObject.
*/

var Swiff = new Class({

	Implements: [Options],

	options: {
		id: null,
		height: 1,
		width: 1,
		container: null,
		properties: {},
		params: {
			quality: 'high',
			allowScriptAccess: 'always',
			wMode: 'transparent',
			swLiveConnect: true
		},
		callBacks: {},
		vars: {}
	},

	toElement: function(){
		return this.object;
	},

	initialize: function(path, options){
		this.instance = 'Swiff_' + $time();

		this.setOptions(options);
		options = this.options;
		var id = this.id = options.id || this.instance;
		var container = $(options.container);

		Swiff.CallBacks[this.instance] = {};

		var params = options.params, vars = options.vars, callBacks = options.callBacks;
		var properties = $extend({height: options.height, width: options.width}, options.properties);

		var self = this;

		for (var callBack in callBacks){
			Swiff.CallBacks[this.instance][callBack] = (function(option){
				return function(){
					return option.apply(self.object, arguments);
				};
			})(callBacks[callBack]);
			vars[callBack] = 'Swiff.CallBacks.' + this.instance + '.' + callBack;
		}

		params.flashVars = Hash.toQueryString(vars);
		if (Browser.Engine.trident){
			properties.classid = 'clsid:D27CDB6E-AE6D-11cf-96B8-444553540000';
			params.movie = path;
		} else {
			properties.type = 'application/x-shockwave-flash';
			properties.data = path;
		}
		var build = '<object id="' + id + '"';
		for (var property in properties) build += ' ' + property + '="' + properties[property] + '"';
		build += '>';
		for (var param in params){
			if (params[param]) build += '<param name="' + param + '" value="' + params[param] + '" />';
		}
		build += '</object>';
		this.object =  ((container) ? container.empty() : new Element('div')).set('html', build).firstChild;
	},

	replaces: function(element){
		element = $(element, true);
		element.parentNode.replaceChild(this.toElement(), element);
		return this;
	},

	inject: function(element){
		$(element, true).appendChild(this.toElement());
		return this;
	},

	remote: function(){
		return Swiff.remote.apply(Swiff, [this.toElement()].extend(arguments));
	}

});

Swiff.CallBacks = {};

Swiff.remote = function(obj, fn){
	var rs = obj.CallFunction('<invoke name="' + fn + '" returntype="javascript">' + __flash__argumentsToXML(arguments, 2) + '</invoke>');
	return eval(rs);
};

/*
Script: Fx.js
	Contains the basic animation logic to be extended by all other Fx Classes.

License:
	MIT-style license.
*/

var Fx = new Class({

	Implements: [Chain, Events, Options],

	options: {
		/*
		onStart: $empty,
		onCancel: $empty,
		onComplete: $empty,
		*/
		fps: 50,
		unit: false,
		duration: 500,
		link: 'ignore',
		transition: function(p){
			return -(Math.cos(Math.PI * p) - 1) / 2;
		}
	},

	initialize: function(options){
		this.subject = this.subject || this;
		this.setOptions(options);
		this.options.duration = Fx.Durations[this.options.duration] || this.options.duration.toInt();
		var wait = this.options.wait;
		if (wait === false) this.options.link = 'cancel';
	},

	step: function(){
		var time = $time();
		if (time < this.time + this.options.duration){
			var delta = this.options.transition((time - this.time) / this.options.duration);
			this.set(this.compute(this.from, this.to, delta));
		} else {
			this.set(this.compute(this.from, this.to, 1));
			this.complete();
		}
	},

	set: function(now){
		return now;
	},

	compute: function(from, to, delta){
		return Fx.compute(from, to, delta);
	},

	check: function(caller){
		if (!this.timer) return true;
		switch (this.options.link){
			case 'cancel': this.cancel(); return true;
			case 'chain': this.chain(caller.bind(this, Array.slice(arguments, 1))); return false;
		}
		return false;
	},

	start: function(from, to){
		if (!this.check(arguments.callee, from, to)) return this;
		this.from = from;
		this.to = to;
		this.time = 0;
		this.startTimer();
		this.onStart();
		return this;
	},

	complete: function(){
		if (this.stopTimer()) this.onComplete();
		return this;
	},

	cancel: function(){
		if (this.stopTimer()) this.onCancel();
		return this;
	},

	onStart: function(){
		this.fireEvent('start', this.subject);
	},

	onComplete: function(){
		this.fireEvent('complete', this.subject);
		if (!this.callChain()) this.fireEvent('chainComplete', this.subject);
	},

	onCancel: function(){
		this.fireEvent('cancel', this.subject).clearChain();
	},

	pause: function(){
		this.stopTimer();
		return this;
	},

	resume: function(){
		this.startTimer();
		return this;
	},

	stopTimer: function(){
		if (!this.timer) return false;
		this.time = $time() - this.time;
		this.timer = $clear(this.timer);
		return true;
	},

	startTimer: function(){
		if (this.timer) return false;
		this.time = $time() - this.time;
		this.timer = this.step.periodical(Math.round(1000 / this.options.fps), this);
		return true;
	}

});

Fx.compute = function(from, to, delta){
	return (to - from) * delta + from;
};

Fx.Durations = {'short': 250, 'normal': 500, 'long': 1000};


/*
Script: Fx.CSS.js
	Contains the CSS animation logic. Used by Fx.Tween, Fx.Morph, Fx.Elements.

License:
	MIT-style license.
*/

Fx.CSS = new Class({

	Extends: Fx,

	//prepares the base from/to object

	prepare: function(element, property, values){
		values = $splat(values);
		var values1 = values[1];
		if (!$chk(values1)){
			values[1] = values[0];
			values[0] = element.getStyle(property);
		}
		var parsed = values.map(this.parse);
		return {from: parsed[0], to: parsed[1]};
	},

	//parses a value into an array

	parse: function(value){
		value = $lambda(value)();
		value = (typeof value == 'string') ? value.split(' ') : $splat(value);
		return value.map(function(val){
			val = String(val);
			var found = false;
			Fx.CSS.Parsers.each(function(parser, key){
				if (found) return;
				var parsed = parser.parse(val);
				if ($chk(parsed)) found = {value: parsed, parser: parser};
			});
			found = found || {value: val, parser: Fx.CSS.Parsers.String};
			return found;
		});
	},

	//computes by a from and to prepared objects, using their parsers.

	compute: function(from, to, delta){
		var computed = [];
		(Math.min(from.length, to.length)).times(function(i){
			computed.push({value: from[i].parser.compute(from[i].value, to[i].value, delta), parser: from[i].parser});
		});
		computed.$family = {name: 'fx:css:value'};
		return computed;
	},

	//serves the value as settable

	serve: function(value, unit){
		if ($type(value) != 'fx:css:value') value = this.parse(value);
		var returned = [];
		value.each(function(bit){
			returned = returned.concat(bit.parser.serve(bit.value, unit));
		});
		return returned;
	},

	//renders the change to an element

	render: function(element, property, value, unit){
		element.setStyle(property, this.serve(value, unit));
	},

	//searches inside the page css to find the values for a selector

	search: function(selector){
		if (Fx.CSS.Cache[selector]) return Fx.CSS.Cache[selector];
		var to = {};
		Array.each(document.styleSheets, function(sheet, j){
			var href = sheet.href;
			if (href && href.contains('://') && !href.contains(document.domain)) return;
			var rules = sheet.rules || sheet.cssRules;
			Array.each(rules, function(rule, i){
				if (!rule.style) return;
				var selectorText = (rule.selectorText) ? rule.selectorText.replace(/^\w+/, function(m){
					return m.toLowerCase();
				}) : null;
				if (!selectorText || !selectorText.test('^' + selector + '$')) return;
				Element.Styles.each(function(value, style){
					if (!rule.style[style] || Element.ShortStyles[style]) return;
					value = String(rule.style[style]);
					to[style] = (value.test(/^rgb/)) ? value.rgbToHex() : value;
				});
			});
		});
		return Fx.CSS.Cache[selector] = to;
	}

});

Fx.CSS.Cache = {};

Fx.CSS.Parsers = new Hash({

	Color: {
		parse: function(value){
			if (value.match(/^#[0-9a-f]{3,6}$/i)) return value.hexToRgb(true);
			return ((value = value.match(/(\d+),\s*(\d+),\s*(\d+)/))) ? [value[1], value[2], value[3]] : false;
		},
		compute: function(from, to, delta){
			return from.map(function(value, i){
				return Math.round(Fx.compute(from[i], to[i], delta));
			});
		},
		serve: function(value){
			return value.map(Number);
		}
	},

	Number: {
		parse: parseFloat,
		compute: Fx.compute,
		serve: function(value, unit){
			return (unit) ? value + unit : value;
		}
	},

	String: {
		parse: $lambda(false),
		compute: $arguments(1),
		serve: $arguments(0)
	}

});


/*
Script: Fx.Tween.js
	Formerly Fx.Style, effect to transition any CSS property for an element.

License:
	MIT-style license.
*/

Fx.Tween = new Class({

	Extends: Fx.CSS,

	initialize: function(element, options){
		this.element = this.subject = $(element);
		this.parent(options);
	},

	set: function(property, now){
		if (arguments.length == 1){
			now = property;
			property = this.property || this.options.property;
		}
		this.render(this.element, property, now, this.options.unit);
		return this;
	},

	start: function(property, from, to){
		if (!this.check(arguments.callee, property, from, to)) return this;
		var args = Array.flatten(arguments);
		this.property = this.options.property || args.shift();
		var parsed = this.prepare(this.element, this.property, args);
		return this.parent(parsed.from, parsed.to);
	}

});

Element.Properties.tween = {

	set: function(options){
		var tween = this.retrieve('tween');
		if (tween) tween.cancel();
		return this.eliminate('tween').store('tween:options', $extend({link: 'cancel'}, options));
	},

	get: function(options){
		if (options || !this.retrieve('tween')){
			if (options || !this.retrieve('tween:options')) this.set('tween', options);
			this.store('tween', new Fx.Tween(this, this.retrieve('tween:options')));
		}
		return this.retrieve('tween');
	}

};

Element.implement({

	tween: function(property, from, to){
		this.get('tween').start(arguments);
		return this;
	},

	fade: function(how){
		var fade = this.get('tween'), o = 'opacity', toggle;
		how = $pick(how, 'toggle');
		switch (how){
			case 'in': fade.start(o, 1); break;
			case 'out': fade.start(o, 0); break;
			case 'show': fade.set(o, 1); break;
			case 'hide': fade.set(o, 0); break;
			case 'toggle':
				var flag = this.retrieve('fade:flag', this.get('opacity') == 1);
				fade.start(o, (flag) ? 0 : 1);
				this.store('fade:flag', !flag);
				toggle = true;
			break;
			default: fade.start(o, arguments);
		}
		if (!toggle) this.eliminate('fade:flag');
		return this;
	},

	highlight: function(start, end){
		if (!end){
			end = this.retrieve('highlight:original', this.getStyle('background-color'));
			end = (end == 'transparent') ? '#fff' : end;
		}
		var tween = this.get('tween');
		tween.start('background-color', start || '#ffff88', end).chain(function(){
			this.setStyle('background-color', this.retrieve('highlight:original'));
			tween.callChain();
		}.bind(this));
		return this;
	}

});


/*
Script: Fx.Morph.js
	Formerly Fx.Styles, effect to transition any number of CSS properties for an element using an object of rules, or CSS based selector rules.

License:
	MIT-style license.
*/

Fx.Morph = new Class({

	Extends: Fx.CSS,

	initialize: function(element, options){
		this.element = this.subject = $(element);
		this.parent(options);
	},

	set: function(now){
		if (typeof now == 'string') now = this.search(now);
		for (var p in now) this.render(this.element, p, now[p], this.options.unit);
		return this;
	},

	compute: function(from, to, delta){
		var now = {};
		for (var p in from) now[p] = this.parent(from[p], to[p], delta);
		return now;
	},

	start: function(properties){
		if (!this.check(arguments.callee, properties)) return this;
		if (typeof properties == 'string') properties = this.search(properties);
		var from = {}, to = {};
		for (var p in properties){
			var parsed = this.prepare(this.element, p, properties[p]);
			from[p] = parsed.from;
			to[p] = parsed.to;
		}
		return this.parent(from, to);
	}

});

Element.Properties.morph = {

	set: function(options){
		var morph = this.retrieve('morph');
		if (morph) morph.cancel();
		return this.eliminate('morph').store('morph:options', $extend({link: 'cancel'}, options));
	},

	get: function(options){
		if (options || !this.retrieve('morph')){
			if (options || !this.retrieve('morph:options')) this.set('morph', options);
			this.store('morph', new Fx.Morph(this, this.retrieve('morph:options')));
		}
		return this.retrieve('morph');
	}

};

Element.implement({

	morph: function(props){
		this.get('morph').start(props);
		return this;
	}

});

/*
Script: Fx.Transitions.js
	Contains a set of advanced transitions to be used with any of the Fx Classes.

License:
	MIT-style license.

Credits:
	Easing Equations by Robert Penner, <http://www.robertpenner.com/easing/>, modified and optimized to be used with MooTools.
*/

(function(){

	var old = Fx.prototype.initialize;

	Fx.prototype.initialize = function(options){
		old.call(this, options);
		var trans = this.options.transition;
		if (typeof trans == 'string' && (trans = trans.split(':'))){
			var base = Fx.Transitions;
			base = base[trans[0]] || base[trans[0].capitalize()];
			if (trans[1]) base = base['ease' + trans[1].capitalize() + (trans[2] ? trans[2].capitalize() : '')];
			this.options.transition = base;
		}
	};

})();

Fx.Transition = function(transition, params){
	params = $splat(params);
	return $extend(transition, {
		easeIn: function(pos){
			return transition(pos, params);
		},
		easeOut: function(pos){
			return 1 - transition(1 - pos, params);
		},
		easeInOut: function(pos){
			return (pos <= 0.5) ? transition(2 * pos, params) / 2 : (2 - transition(2 * (1 - pos), params)) / 2;
		}
	});
};

Fx.Transitions = new Hash({

	linear: $arguments(0)

});

Fx.Transitions.extend = function(transitions){
	for (var transition in transitions) Fx.Transitions[transition] = new Fx.Transition(transitions[transition]);
};

Fx.Transitions.extend({

	Pow: function(p, x){
		return Math.pow(p, x[0] || 6);
	},

	Expo: function(p){
		return Math.pow(2, 8 * (p - 1));
	},

	Circ: function(p){
		return 1 - Math.sin(Math.acos(p));
	},

	Sine: function(p){
		return 1 - Math.sin((1 - p) * Math.PI / 2);
	},

	Back: function(p, x){
		x = x[0] || 1.618;
		return Math.pow(p, 2) * ((x + 1) * p - x);
	},

	Bounce: function(p){
		var value;
		for (var a = 0, b = 1; 1; a += b, b /= 2){
			if (p >= (7 - 4 * a) / 11){
				value = - Math.pow((11 - 6 * a - 11 * p) / 4, 2) + b * b;
				break;
			}
		}
		return value;
	},

	Elastic: function(p, x){
		return Math.pow(2, 10 * --p) * Math.cos(20 * p * Math.PI * (x[0] || 1) / 3);
	}

});

['Quad', 'Cubic', 'Quart', 'Quint'].each(function(transition, i){
	Fx.Transitions[transition] = new Fx.Transition(function(p){
		return Math.pow(p, [i + 2]);
	});
});


/*
Script: Request.js
	Powerful all purpose Request Class. Uses XMLHTTPRequest.

License:
	MIT-style license.
*/

var Request = new Class({

	Implements: [Chain, Events, Options],

	options: {
		/*onRequest: $empty,
		onSuccess: $empty,
		onFailure: $empty,
		onException: $empty,*/
		url: '',
		data: '',
		headers: {
			'X-Requested-With': 'XMLHttpRequest',
			'Accept': 'text/javascript, text/html, application/xml, text/xml, */*'
		},
		async: true,
		format: false,
		method: 'post',
		link: 'ignore',
		isSuccess: null,
		emulation: true,
		urlEncoded: true,
		encoding: 'utf-8',
		evalScripts: false,
		evalResponse: false
	},

	initialize: function(options){
		this.xhr = new Browser.Request();
		this.setOptions(options);
		this.options.isSuccess = this.options.isSuccess || this.isSuccess;
		this.headers = new Hash(this.options.headers);
	},

	onStateChange: function(){
		if (this.xhr.readyState != 4 || !this.running) return;
		this.running = false;
		this.status = 0;
		$try(function(){
			this.status = this.xhr.status;
		}.bind(this));
		if (this.options.isSuccess.call(this, this.status)){
			this.response = {text: this.xhr.responseText, xml: this.xhr.responseXML};
			this.success(this.response.text, this.response.xml);
		} else {
			this.response = {text: null, xml: null};
			this.failure();
		}
		this.xhr.onreadystatechange = $empty;
	},

	isSuccess: function(){
		return ((this.status >= 200) && (this.status < 300));
	},

	processScripts: function(text){
		if (this.options.evalResponse || (/(ecma|java)script/).test(this.getHeader('Content-type'))) return $exec(text);
		return text.stripScripts(this.options.evalScripts);
	},

	success: function(text, xml){
		this.onSuccess(this.processScripts(text), xml);
	},
	
	onSuccess: function(){
		this.fireEvent('complete', arguments).fireEvent('success', arguments).callChain();
	},
	
	failure: function(){
		this.onFailure();
	},

	onFailure: function(){
		this.fireEvent('complete').fireEvent('failure', this.xhr);
	},

	setHeader: function(name, value){
		this.headers.set(name, value);
		return this;
	},

	getHeader: function(name){
		return $try(function(){
			return this.xhr.getResponseHeader(name);
		}.bind(this));
	},

	check: function(caller){
		if (!this.running) return true;
		switch (this.options.link){
			case 'cancel': this.cancel(); return true;
			case 'chain': this.chain(caller.bind(this, Array.slice(arguments, 1))); return false;
		}
		return false;
	},

	send: function(options){
		if (!this.check(arguments.callee, options)) return this;
		this.running = true;

		var type = $type(options);
		if (type == 'string' || type == 'element') options = {data: options};

		var old = this.options;
		options = $extend({data: old.data, url: old.url, method: old.method}, options);
		var data = options.data, url = options.url, method = options.method;

		switch ($type(data)){
			case 'element': data = $(data).toQueryString(); break;
			case 'object': case 'hash': data = Hash.toQueryString(data);
		}

		if (this.options.format){
			var format = 'format=' + this.options.format;
			data = (data) ? format + '&' + data : format;
		}

		if (this.options.emulation && ['put', 'delete'].contains(method)){
			var _method = '_method=' + method;
			data = (data) ? _method + '&' + data : _method;
			method = 'post';
		}

		if (this.options.urlEncoded && method == 'post'){
			var encoding = (this.options.encoding) ? '; charset=' + this.options.encoding : '';
			this.headers.set('Content-type', 'application/x-www-form-urlencoded' + encoding);
		}

		if (data && method == 'get'){
			url = url + (url.contains('?') ? '&' : '?') + data;
			data = null;
		}

		this.xhr.open(method.toUpperCase(), url, this.options.async);

		this.xhr.onreadystatechange = this.onStateChange.bind(this);

		this.headers.each(function(value, key){
			if (!$try(function(){
				this.xhr.setRequestHeader(key, value);
				return true;
			}.bind(this))) this.fireEvent('exception', [key, value]);
		}, this);

		this.fireEvent('request');
		this.xhr.send(data);
		if (!this.options.async) this.onStateChange();
		return this;
	},

	cancel: function(){
		if (!this.running) return this;
		this.running = false;
		this.xhr.abort();
		this.xhr.onreadystatechange = $empty;
		this.xhr = new Browser.Request();
		this.fireEvent('cancel');
		return this;
	}

});

(function(){

var methods = {};
['get', 'post', 'put', 'delete', 'GET', 'POST', 'PUT', 'DELETE'].each(function(method){
	methods[method] = function(){
		var params = Array.link(arguments, {url: String.type, data: $defined});
		return this.send($extend(params, {method: method.toLowerCase()}));
	};
});

Request.implement(methods);

})();

Element.Properties.send = {
	
	set: function(options){
		var send = this.retrieve('send');
		if (send) send.cancel();
		return this.eliminate('send').store('send:options', $extend({
			data: this, link: 'cancel', method: this.get('method') || 'post', url: this.get('action')
		}, options));
	},

	get: function(options){
		if (options || !this.retrieve('send')){
			if (options || !this.retrieve('send:options')) this.set('send', options);
			this.store('send', new Request(this.retrieve('send:options')));
		}
		return this.retrieve('send');
	}

};

Element.implement({

	send: function(url){
		var sender = this.get('send');
		sender.send({data: this, url: url || sender.options.url});
		return this;
	}

});


/*
Script: Request.HTML.js
	Extends the basic Request Class with additional methods for interacting with HTML responses.

License:
	MIT-style license.
*/

Request.HTML = new Class({

	Extends: Request,

	options: {
		update: false,
		evalScripts: true,
		filter: false
	},

	processHTML: function(text){
		var match = text.match(/<body[^>]*>([\s\S]*?)<\/body>/i);
		text = (match) ? match[1] : text;
		
		var container = new Element('div');
		
		return $try(function(){
			var root = '<root>' + text + '</root>', doc;
			if (Browser.Engine.trident){
				doc = new ActiveXObject('Microsoft.XMLDOM');
				doc.async = false;
				doc.loadXML(root);
			} else {
				doc = new DOMParser().parseFromString(root, 'text/xml');
			}
			root = doc.getElementsByTagName('root')[0];
			for (var i = 0, k = root.childNodes.length; i < k; i++){
				var child = Element.clone(root.childNodes[i], true, true);
				if (child) container.grab(child);
			}
			return container;
		}) || container.set('html', text);
	},

	success: function(text){
		var options = this.options, response = this.response;
		
		response.html = text.stripScripts(function(script){
			response.javascript = script;
		});
		
		var temp = this.processHTML(response.html);
		
		response.tree = temp.childNodes;
		response.elements = temp.getElements('*');
		
		if (options.filter) response.tree = response.elements.filter(options.filter);
		if (options.update) $(options.update).empty().adopt(response.tree);
		if (options.evalScripts) $exec(response.javascript);
		
		this.onSuccess(response.tree, response.elements, response.html, response.javascript);
	}

});

Element.Properties.load = {
	
	set: function(options){
		var load = this.retrieve('load');
		if (load) send.cancel();
		return this.eliminate('load').store('load:options', $extend({data: this, link: 'cancel', update: this, method: 'get'}, options));
	},

	get: function(options){
		if (options || ! this.retrieve('load')){
			if (options || !this.retrieve('load:options')) this.set('load', options);
			this.store('load', new Request.HTML(this.retrieve('load:options')));
		}
		return this.retrieve('load');
	}

};

Element.implement({
	
	load: function(){
		this.get('load').send(Array.link(arguments, {data: Object.type, url: String.type}));
		return this;
	}

});


/*
Script: Request.JSON.js
	Extends the basic Request Class with additional methods for sending and receiving JSON data.

License:
	MIT-style license.
*/

Request.JSON = new Class({

	Extends: Request,

	options: {
		secure: true
	},

	initialize: function(options){
		this.parent(options);
		this.headers.extend({'Accept': 'application/json', 'X-Request': 'JSON'});
	},

	success: function(text){
		this.response.json = JSON.decode(text, this.options.secure);
		this.onSuccess(this.response.json, text);
	}

});/*
Script: Fx.Slide.js
	Effect to slide an element in and out of view.

License:
	MIT-style license.
*/

Fx.Slide = new Class({

	Extends: Fx,

	options: {
		mode: 'vertical'
	},

	initialize: function(element, options){
		this.addEvent('complete', function(){
			this.open = (this.wrapper['offset' + this.layout.capitalize()] != 0);
			if (this.open && Browser.Engine.webkit419) this.element.dispose().inject(this.wrapper);
		}, true);
		this.element = this.subject = $(element);
		this.parent(options);
		var wrapper = this.element.retrieve('wrapper');
		this.wrapper = wrapper || new Element('div', {
			styles: $extend(this.element.getStyles('margin', 'position'), {'overflow': 'hidden'})
		}).wraps(this.element);
		this.element.store('wrapper', this.wrapper).setStyle('margin', 0);
		this.now = [];
		this.open = true;
	},

	vertical: function(){
		this.margin = 'margin-top';
		this.layout = 'height';
		this.offset = this.element.offsetHeight+8;
	},

	horizontal: function(){
		this.margin = 'margin-left';
		this.layout = 'width';
		this.offset = this.element.offsetWidth;
	},

	set: function(now){
		this.element.setStyle(this.margin, now[0]);
		this.wrapper.setStyle(this.layout, now[1]);
		return this;
	},

	compute: function(from, to, delta){
		var now = [];
		var x = 2;
		x.times(function(i){
			now[i] = Fx.compute(from[i], to[i], delta);
		});
		return now;
	},

	start: function(how, mode){
		if (!this.check(arguments.callee, how, mode)) return this;
		this[mode || this.options.mode]();
		var margin = this.element.getStyle(this.margin).toInt();
		var layout = this.wrapper.getStyle(this.layout).toInt();
		var caseIn = [[margin, layout], [0, this.offset]];
		var caseOut = [[margin, layout], [-this.offset, 0]];
		var start;
		switch (how){
			case 'in': start = caseIn; break;
			case 'out': start = caseOut; break;
			case 'toggle': start = (this.wrapper['offset' + this.layout.capitalize()] == 0) ? caseIn : caseOut;
		}
		return this.parent(start[0], start[1]);
	},

	slideIn: function(mode){
		return this.start('in', mode);
	},

	slideOut: function(mode){
		return this.start('out', mode);
	},

	hide: function(mode){
		this[mode || this.options.mode]();
		this.open = false;
		return this.set([-this.offset, 0]);
	},

	show: function(mode){
		this[mode || this.options.mode]();
		this.open = true;
		return this.set([0, this.offset]);
	},

	toggle: function(mode){
		return this.start('toggle', mode);
	}

});

Element.Properties.slide = {

	set: function(options){
		var slide = this.retrieve('slide');
		if (slide) slide.cancel();
		return this.eliminate('slide').store('slide:options', $extend({link: 'cancel'}, options));
	},
	
	get: function(options){
		if (options || !this.retrieve('slide')){
			if (options || !this.retrieve('slide:options')) this.set('slide', options);
			this.store('slide', new Fx.Slide(this, this.retrieve('slide:options')));
		}
		return this.retrieve('slide');
	}

};

Element.implement({

	slide: function(how, mode){
		how = how || 'toggle';
		var slide = this.get('slide'), toggle;
		switch (how){
			case 'hide': slide.hide(mode); break;
			case 'show': slide.show(mode); break;
			case 'toggle':
				var flag = this.retrieve('slide:flag', slide.open);
				slide[(flag) ? 'slideOut' : 'slideIn'](mode);
				this.store('slide:flag', !flag);
				toggle = true;
			break;
			default: slide.start(how, mode);
		}
		if (!toggle) this.eliminate('slide:flag');
		return this;
	}

});


/*
Script: Fx.Scroll.js
	Effect to smoothly scroll any element, including the window.

License:
	MIT-style license.
*/

Fx.Scroll = new Class({

	Extends: Fx,

	options: {
		offset: {'x': 0, 'y': 0},
		wheelStops: true
	},

	initialize: function(element, options){
		this.element = this.subject = $(element);
		this.parent(options);
		var cancel = this.cancel.bind(this, false);

		if ($type(this.element) != 'element') this.element = $(this.element.getDocument().body);

		var stopper = this.element;

		if (this.options.wheelStops){
			this.addEvent('start', function(){
				stopper.addEvent('mousewheel', cancel);
			}, true);
			this.addEvent('complete', function(){
				stopper.removeEvent('mousewheel', cancel);
			}, true);
		}
	},

	set: function(){
		var now = Array.flatten(arguments);
		this.element.scrollTo(now[0], now[1]);
	},

	compute: function(from, to, delta){
		var now = [];
		var x = 2;
		x.times(function(i){
			now.push(Fx.compute(from[i], to[i], delta));
		});
		return now;
	},

	start: function(x, y){
		if (!this.check(arguments.callee, x, y)) return this;
		var offsetSize = this.element.getSize(), scrollSize = this.element.getScrollSize();
		var scroll = this.element.getScroll(), values = {x: x, y: y};
		for (var z in values){
			var max = scrollSize[z] - offsetSize[z];
			if ($chk(values[z])) values[z] = ($type(values[z]) == 'number') ? values[z].limit(0, max) : max;
			else values[z] = scroll[z];
			values[z] += this.options.offset[z];
		}
		return this.parent([scroll.x, scroll.y], [values.x, values.y]);
	},

	toTop: function(){
		return this.start(false, 0);
	},

	toLeft: function(){
		return this.start(0, false);
	},

	toRight: function(){
		return this.start('right', false);
	},

	toBottom: function(){
		return this.start(false, 'bottom');
	},

	toElement: function(el){
		var position = $(el).getPosition(this.element);
		return this.start(position.x, position.y);
	}

});


/*
Script: Fx.Elements.js
	Effect to change any number of CSS properties of any number of Elements.

License:
	MIT-style license.
*/

Fx.Elements = new Class({

	Extends: Fx.CSS,

	initialize: function(elements, options){
		this.elements = this.subject = $$(elements);
		this.parent(options);
	},

	compute: function(from, to, delta){
		var now = {};
		for (var i in from){
			var iFrom = from[i], iTo = to[i], iNow = now[i] = {};
			for (var p in iFrom) iNow[p] = this.parent(iFrom[p], iTo[p], delta);
		}
		return now;
	},

	set: function(now){
		for (var i in now){
			var iNow = now[i];
			for (var p in iNow) this.render(this.elements[i], p, iNow[p], this.options.unit);
		}
		return this;
	},

	start: function(obj){
		if (!this.check(arguments.callee, obj)) return this;
		var from = {}, to = {};
		for (var i in obj){
			var iProps = obj[i], iFrom = from[i] = {}, iTo = to[i] = {};
			for (var p in iProps){
				var parsed = this.prepare(this.elements[i], p, iProps[p]);
				iFrom[p] = parsed.from;
				iTo[p] = parsed.to;
			}
		}
		return this.parent(from, to);
	}

});

/*
Script: Drag.js
	The base Drag Class. Can be used to drag and resize Elements using mouse events.

License:
	MIT-style license.
*/

var Drag = new Class({

	Implements: [Events, Options],

	options: {/*
		onBeforeStart: $empty,
		onStart: $empty,
		onDrag: $empty,
		onCancel: $empty,
		onComplete: $empty,*/
		snap: 6,
		unit: 'px',
		grid: false,
		style: true,
		limit: false,
		handle: false,
		invert: false,
		preventDefault: false,
		modifiers: {x: 'left', y: 'top'}
	},

	initialize: function(){
		var params = Array.link(arguments, {'options': Object.type, 'element': $defined});
		this.element = $(params.element);
		this.document = this.element.getDocument();
		this.setOptions(params.options || {});
		var htype = $type(this.options.handle);
		this.handles = (htype == 'array' || htype == 'collection') ? $$(this.options.handle) : $(this.options.handle) || this.element;
		this.mouse = {'now': {}, 'pos': {}};
		this.value = {'start': {}, 'now': {}};
		
		this.selection = (Browser.Engine.trident) ? 'selectstart' : 'mousedown';
		
		this.bound = {
			start: this.start.bind(this),
			check: this.check.bind(this),
			drag: this.drag.bind(this),
			stop: this.stop.bind(this),
			cancel: this.cancel.bind(this),
			eventStop: $lambda(false)
		};
		this.attach();
	},

	attach: function(){
		this.handles.addEvent('mousedown', this.bound.start);
		return this;
	},

	detach: function(){
		this.handles.removeEvent('mousedown', this.bound.start);
		return this;
	},

	start: function(event){
		if (this.options.preventDefault) event.preventDefault();
		this.fireEvent('beforeStart', this.element);
		this.mouse.start = event.page;
		var limit = this.options.limit;
		this.limit = {'x': [], 'y': []};
		for (var z in this.options.modifiers){
			if (!this.options.modifiers[z]) continue;
			if (this.options.style) this.value.now[z] = this.element.getStyle(this.options.modifiers[z]).toInt();
			else this.value.now[z] = this.element[this.options.modifiers[z]];
			if (this.options.invert) this.value.now[z] *= -1;
			this.mouse.pos[z] = event.page[z] - this.value.now[z];
			if (limit && limit[z]){
				for (var i = 2; i--; i){
					if ($chk(limit[z][i])) this.limit[z][i] = $lambda(limit[z][i])();
				}
			}
		}
		if ($type(this.options.grid) == 'number') this.options.grid = {'x': this.options.grid, 'y': this.options.grid};
		this.document.addEvents({mousemove: this.bound.check, mouseup: this.bound.cancel});
		this.document.addEvent(this.selection, this.bound.eventStop);
	},

	check: function(event){
		if (this.options.preventDefault) event.preventDefault();
		var distance = Math.round(Math.sqrt(Math.pow(event.page.x - this.mouse.start.x, 2) + Math.pow(event.page.y - this.mouse.start.y, 2)));
		if (distance > this.options.snap){
			this.cancel();
			this.document.addEvents({
				mousemove: this.bound.drag,
				mouseup: this.bound.stop
			});
			this.fireEvent('start', this.element).fireEvent('snap', this.element);
		}
	},

	drag: function(event){
		if (this.options.preventDefault) event.preventDefault();
		this.mouse.now = event.page;
		for (var z in this.options.modifiers){
			if (!this.options.modifiers[z]) continue;
			this.value.now[z] = this.mouse.now[z] - this.mouse.pos[z];
			if (this.options.invert) this.value.now[z] *= -1;
			if (this.options.limit && this.limit[z]){
				if ($chk(this.limit[z][1]) && (this.value.now[z] > this.limit[z][1])){
					this.value.now[z] = this.limit[z][1];
				} else if ($chk(this.limit[z][0]) && (this.value.now[z] < this.limit[z][0])){
					this.value.now[z] = this.limit[z][0];
				}
			}
			if (this.options.grid[z]) this.value.now[z] -= (this.value.now[z] % this.options.grid[z]);
			if (this.options.style) this.element.setStyle(this.options.modifiers[z], this.value.now[z] + this.options.unit);
			else this.element[this.options.modifiers[z]] = this.value.now[z];
		}
		this.fireEvent('drag', this.element);
	},

	cancel: function(event){
		this.document.removeEvent('mousemove', this.bound.check);
		this.document.removeEvent('mouseup', this.bound.cancel);
		if (event){
			this.document.removeEvent(this.selection, this.bound.eventStop);
			this.fireEvent('cancel', this.element);
		}
	},

	stop: function(event){
		this.document.removeEvent(this.selection, this.bound.eventStop);
		this.document.removeEvent('mousemove', this.bound.drag);
		this.document.removeEvent('mouseup', this.bound.stop);
		if (event) this.fireEvent('complete', this.element);
	}

});

Element.implement({
	
	makeResizable: function(options){
		return new Drag(this, $merge({modifiers: {'x': 'width', 'y': 'height'}}, options));
	}

});

/*
Script: Drag.Move.js
	A Drag extension that provides support for the constraining of draggables to containers and droppables.

License:
	MIT-style license.
*/

Drag.Move = new Class({

	Extends: Drag,

	options: {
		droppables: [],
		container: false
	},

	initialize: function(element, options){
		this.parent(element, options);
		this.droppables = $$(this.options.droppables);
		this.container = $(this.options.container);
		if (this.container && $type(this.container) != 'element') this.container = $(this.container.getDocument().body);
		element = this.element;
		
		var current = element.getStyle('position');
		var position = (current != 'static') ? current : 'absolute';
		if (element.getStyle('left') == 'auto' || element.getStyle('top') == 'auto') element.position(element.getPosition(element.offsetParent));
		
		element.setStyle('position', position);
		
		this.addEvent('start', function(){
			this.checkDroppables();
		}, true);
	},

	start: function(event){
		if (this.container){
			var el = this.element, cont = this.container, ccoo = cont.getCoordinates(el.offsetParent), cps = {}, ems = {};

			['top', 'right', 'bottom', 'left'].each(function(pad){
				cps[pad] = cont.getStyle('padding-' + pad).toInt();
				ems[pad] = el.getStyle('margin-' + pad).toInt();
			}, this);

			var width = el.offsetWidth + ems.left + ems.right, height = el.offsetHeight + ems.top + ems.bottom;
			var x = [ccoo.left + cps.left, ccoo.right - cps.right - width];
			var y = [ccoo.top + cps.top, ccoo.bottom - cps.bottom - height];

			this.options.limit = {x: x, y: y};
		}
		this.parent(event);
	},

	checkAgainst: function(el){
		el = el.getCoordinates();
		var now = this.mouse.now;
		return (now.x > el.left && now.x < el.right && now.y < el.bottom && now.y > el.top);
	},

	checkDroppables: function(){
		var overed = this.droppables.filter(this.checkAgainst, this).getLast();
		if (this.overed != overed){
			if (this.overed) this.fireEvent('leave', [this.element, this.overed]);
			if (overed){
				this.overed = overed;
				this.fireEvent('enter', [this.element, overed]);
			} else {
				this.overed = null;
			}
		}
	},

	drag: function(event){
		this.parent(event);
		if (this.droppables.length) this.checkDroppables();
	},

	stop: function(event){
		this.checkDroppables();
		this.fireEvent('drop', [this.element, this.overed]);
		this.overed = null;
		return this.parent(event);
	}

});

Element.implement({

	makeDraggable: function(options){
		return new Drag.Move(this, options);
	}

});


/*
Script: Hash.Cookie.js
	Class for creating, reading, and deleting Cookies in JSON format.

License:
	MIT-style license.
*/

Hash.Cookie = new Class({

	Extends: Cookie,

	options: {
		autoSave: true
	},

	initialize: function(name, options){
		this.parent(name, options);
		this.load();
	},

	save: function(){
		var value = JSON.encode(this.hash);
		if (!value || value.length > 4096) return false; //cookie would be truncated!
		if (value == '{}') this.dispose();
		else this.write(value);
		return true;
	},

	load: function(){
		this.hash = new Hash(JSON.decode(this.read(), true));
		return this;
	}

});

Hash.Cookie.implement((function(){
	
	var methods = {};
	
	Hash.each(Hash.prototype, function(method, name){
		methods[name] = function(){
			var value = method.apply(this.hash, arguments);
			if (this.options.autoSave) this.save();
			return value;
		};
	});
	
	return methods;
	
})());

/*
Script: Color.js
	Class for creating and manipulating colors in JavaScript. Supports HSB -> RGB Conversions and vice versa.

License:
	MIT-style license.
*/

var Color = new Native({
  
	initialize: function(color, type){
		if (arguments.length >= 3){
			type = "rgb"; color = Array.slice(arguments, 0, 3);
		} else if (typeof color == 'string'){
			if (color.match(/rgb/)) color = color.rgbToHex().hexToRgb(true);
			else if (color.match(/hsb/)) color = color.hsbToRgb();
			else color = color.hexToRgb(true);
		}
		type = type || 'rgb';
		switch (type){
			case 'hsb':
				var old = color;
				color = color.hsbToRgb();
				color.hsb = old;
			break;
			case 'hex': color = color.hexToRgb(true); break;
		}
		color.rgb = color.slice(0, 3);
		color.hsb = color.hsb || color.rgbToHsb();
		color.hex = color.rgbToHex();
		return $extend(color, this);
	}

});

Color.implement({

	mix: function(){
		var colors = Array.slice(arguments);
		var alpha = ($type(colors.getLast()) == 'number') ? colors.pop() : 50;
		var rgb = this.slice();
		colors.each(function(color){
			color = new Color(color);
			for (var i = 0; i < 3; i++) rgb[i] = Math.round((rgb[i] / 100 * (100 - alpha)) + (color[i] / 100 * alpha));
		});
		return new Color(rgb, 'rgb');
	},

	invert: function(){
		return new Color(this.map(function(value){
			return 255 - value;
		}));
	},

	setHue: function(value){
		return new Color([value, this.hsb[1], this.hsb[2]], 'hsb');
	},

	setSaturation: function(percent){
		return new Color([this.hsb[0], percent, this.hsb[2]], 'hsb');
	},

	setBrightness: function(percent){
		return new Color([this.hsb[0], this.hsb[1], percent], 'hsb');
	}

});

function $RGB(r, g, b){
	return new Color([r, g, b], 'rgb');
};

function $HSB(h, s, b){
	return new Color([h, s, b], 'hsb');
};

function $HEX(hex){
	return new Color(hex, 'hex');
};

Array.implement({

	rgbToHsb: function(){
		var red = this[0], green = this[1], blue = this[2];
		var hue, saturation, brightness;
		var max = Math.max(red, green, blue), min = Math.min(red, green, blue);
		var delta = max - min;
		brightness = max / 255;
		saturation = (max != 0) ? delta / max : 0;
		if (saturation == 0){
			hue = 0;
		} else {
			var rr = (max - red) / delta;
			var gr = (max - green) / delta;
			var br = (max - blue) / delta;
			if (red == max) hue = br - gr;
			else if (green == max) hue = 2 + rr - br;
			else hue = 4 + gr - rr;
			hue /= 6;
			if (hue < 0) hue++;
		}
		return [Math.round(hue * 360), Math.round(saturation * 100), Math.round(brightness * 100)];
	},

	hsbToRgb: function(){
		var br = Math.round(this[2] / 100 * 255);
		if (this[1] == 0){
			return [br, br, br];
		} else {
			var hue = this[0] % 360;
			var f = hue % 60;
			var p = Math.round((this[2] * (100 - this[1])) / 10000 * 255);
			var q = Math.round((this[2] * (6000 - this[1] * f)) / 600000 * 255);
			var t = Math.round((this[2] * (6000 - this[1] * (60 - f))) / 600000 * 255);
			switch (Math.floor(hue / 60)){
				case 0: return [br, t, p];
				case 1: return [q, br, p];
				case 2: return [p, br, t];
				case 3: return [p, q, br];
				case 4: return [t, p, br];
				case 5: return [br, p, q];
			}
		}
		return false;
	}

});

String.implement({

	rgbToHsb: function(){
		var rgb = this.match(/\d{1,3}/g);
		return (rgb) ? hsb.rgbToHsb() : null;
	},
	
	hsbToRgb: function(){
		var hsb = this.match(/\d{1,3}/g);
		return (hsb) ? hsb.hsbToRgb() : null;
	},
	replaceAll: function(searchValue, replaceValue, regExOptions){
		return this.replace(new RegExp(searchValue, $pick(regExOptions, 'gi')), replaceValue);
	}
});


/*
Script: Group.js
	Class for monitoring collections of events

License:
	MIT-style license.
*/

var Group = new Class({

	initialize: function(){
		this.instances = Array.flatten(arguments);
		this.events = {};
		this.checker = {};
	},

	addEvent: function(type, fn){
		this.checker[type] = this.checker[type] || {};
		this.events[type] = this.events[type] || [];
		if (this.events[type].contains(fn)) return false;
		else this.events[type].push(fn);
		this.instances.each(function(instance, i){
			instance.addEvent(type, this.check.bind(this, [type, instance, i]));
		}, this);
		return this;
	},

	check: function(type, instance, i){
		this.checker[type][i] = true;
		var every = this.instances.every(function(current, j){
			return this.checker[type][j] || false;
		}, this);
		if (!every) return;
		this.checker[type] = {};
		this.events[type].each(function(event){
			event.call(this, this.instances, instance);
		}, this);
	}

});


/*
Script: Assets.js
	Provides methods to dynamically load JavaScript, CSS, and Image files into the document.

License:
	MIT-style license.
*/

var Asset = new Hash({

	javascript: function(source, properties){
		properties = $extend({
			onload: $empty,
			document: document,
			check: $lambda(true)
		}, properties);
		
		var script = new Element('script', {'src': source, 'type': 'text/javascript'});
		
		var load = properties.onload.bind(script), check = properties.check, doc = properties.document;
		delete properties.onload; delete properties.check; delete properties.document;
		
		script.addEvents({
			load: load,
			readystatechange: function(){
				if (['loaded', 'complete'].contains(this.readyState)) load();
			}
		}).setProperties(properties);
		
		
		if (Browser.Engine.webkit419) var checker = (function(){
			if (!$try(check)) return;
			$clear(checker);
			load();
		}).periodical(50);
		
		return script.inject(doc.head);
	},

	css: function(source, properties){
		return new Element('link', $merge({
			'rel': 'stylesheet', 'media': 'screen', 'type': 'text/css', 'href': source
		}, properties)).inject(document.head);
	},

	image: function(source, properties){
		properties = $merge({
			'onload': $empty,
			'onabort': $empty,
			'onerror': $empty
		}, properties);
		var image = new Image();
		var element = $(image) || new Element('img');
		['load', 'abort', 'error'].each(function(name){
			var type = 'on' + name;
			var event = properties[type];
			delete properties[type];
			image[type] = function(){
				if (!image) return;
				if (!element.parentNode){
					element.width = image.width;
					element.height = image.height;
				}
				image = image.onload = image.onabort = image.onerror = null;
				event.delay(1, element, element);
				element.fireEvent(name, element, 1);
			};
		});
		image.src = element.src = source;
		if (image && image.complete) image.onload.delay(1);
		return element.setProperties(properties);
	},

	images: function(sources, options){
		options = $merge({
			onComplete: $empty,
			onProgress: $empty
		}, options);
		if (!sources.push) sources = [sources];
		var images = [];
		var counter = 0;
		sources.each(function(source){
			var img = new Asset.image(source, {
				'onload': function(){
					options.onProgress.call(this, counter, sources.indexOf(source));
					counter++;
					if (counter == sources.length) options.onComplete();
				}
			});
			images.push(img);
		});
		return new Elements(images);
	}

});

/*
Script: Sortables.js
	Class for creating a drag and drop sorting interface for lists of items.

License:
	MIT-style license.
*/

var Sortables = new Class({

	Implements: [Events, Options],

	options: {/*
		onSort: $empty,
		onStart: $empty,
		onComplete: $empty,*/
		snap: 4,
		opacity: 1,
		clone: false,
		revert: false,
		handle: false,
		constrain: false
	},

	initialize: function(lists, options){
		this.setOptions(options);
		this.elements = [];
		this.lists = [];
		this.idle = true;
		
		this.addLists($$($(lists) || lists));
		if (!this.options.clone) this.options.revert = false;
		if (this.options.revert) this.effect = new Fx.Morph(null, $merge({duration: 250, link: 'cancel'}, this.options.revert));
	},

	attach: function(){
		this.addLists(this.lists);
		return this;
	},

	detach: function(){
		this.lists = this.removeLists(this.lists);
		return this;
	},

	addItems: function(){
		Array.flatten(arguments).each(function(element){
			this.elements.push(element);
			var start = element.retrieve('sortables:start', this.start.bindWithEvent(this, element));
			(this.options.handle ? element.getElement(this.options.handle) || element : element).addEvent('mousedown', start);
		}, this);
		return this;
	},

	addLists: function(){
		Array.flatten(arguments).each(function(list){
			this.lists.push(list);
			this.addItems(list.getChildren());
		}, this);
		return this;
	},

	removeItems: function(){
		var elements = [];
		Array.flatten(arguments).each(function(element){
			elements.push(element);
			this.elements.erase(element);
			var start = element.retrieve('sortables:start');
			(this.options.handle ? element.getElement(this.options.handle) || element : element).removeEvent('mousedown', start);
		}, this);
		return $$(elements);
	},

	removeLists: function(){
		var lists = [];
		Array.flatten(arguments).each(function(list){
			lists.push(list);
			this.lists.erase(list);
			this.removeItems(list.getChildren());
		}, this);
		return $$(lists);
	},

	getClone: function(event, element){
		if (!this.options.clone) return new Element('div').inject(document.body);
		if ($type(this.options.clone) == 'function') return this.options.clone.call(this, event, element, this.list);
		return element.clone(true).setStyles({
			'margin': '0px',
			'position': 'absolute',
			'visibility': 'hidden',
			'width': element.getStyle('width')
		}).inject(this.list).position(element.getPosition(element.getOffsetParent()));
	},

	getDroppables: function(){
		var droppables = this.list.getChildren();
		if (!this.options.constrain) droppables = this.lists.concat(droppables).erase(this.list);
		return droppables.erase(this.clone).erase(this.element);
	},

	insert: function(dragging, element){
		var where = 'inside';
		if (this.lists.contains(element)){
			this.list = element;
			this.drag.droppables = this.getDroppables();
		} else {
			where = this.element.getAllPrevious().contains(element) ? 'before' : 'after';
		}
		this.element.inject(element, where);
		this.fireEvent('sort', [this.element, this.clone]);
	},

	start: function(event, element){
		if (!this.idle) return;
		this.idle = false;
		this.element = element;
		this.opacity = element.get('opacity');
		this.list = element.getParent();
		this.clone = this.getClone(event, element);
		
		this.drag = new Drag.Move(this.clone, {
			snap: this.options.snap,
			container: this.options.constrain && this.element.getParent(),
			droppables: this.getDroppables(),
			onSnap: function(){
				event.stop();
				this.clone.setStyle('visibility', 'visible');
				this.element.set('opacity', this.options.opacity || 0);
				this.fireEvent('start', [this.element, this.clone]);
			}.bind(this),
			onEnter: this.insert.bind(this),
			onCancel: this.reset.bind(this),
			onComplete: this.end.bind(this)
		});
		
		this.clone.inject(this.element, 'before');
		this.drag.start(event);
	},

	end: function(){
		this.drag.detach();
		this.element.set('opacity', this.opacity);
		if (this.effect){
			var dim = this.element.getStyles('width', 'height');
			var pos = this.clone.computePosition(this.element.getPosition(this.clone.offsetParent));
			this.effect.element = this.clone;
			this.effect.start({
				top: pos.top,
				left: pos.left,
				width: dim.width,
				height: dim.height,
				opacity: 0.25
			}).chain(this.reset.bind(this));
		} else {
			this.reset();
		}
	},

	reset: function(){
		this.idle = true;
		this.clone.destroy();
		this.fireEvent('complete', this.element);
	},

	serialize: function(){
		var params = Array.link(arguments, {modifier: Function.type, index: $defined});
		var serial = this.lists.map(function(list){
			return list.getChildren().map(params.modifier || function(element){
				return element.get('id');
			}, this);
		}, this);
		
		var index = params.index;
		if (this.lists.length == 1) index = 0;
		return $chk(index) && index >= 0 && index < this.lists.length ? serial[index] : serial;
	}

});

/*
Script: Tips.js
	Class for creating nice tips that follow the mouse cursor when hovering an element.

License:
	MIT-style license.
*/

var Tips = new Class({

	Implements: [Events, Options],

	options: {
		onShow: function(tip){
			tip.setStyle('visibility', 'visible');
		},
		onHide: function(tip){
			tip.setStyle('visibility', 'hidden');
		},
		showDelay: 100,
		hideDelay: 100,
		className: null,
		offsets: {x: 16, y: 16},
		fixed: false
	},

	initialize: function(){
		var params = Array.link(arguments, {options: Object.type, elements: $defined});
		this.setOptions(params.options || null);
		
		this.tip = new Element('div').inject(document.body);
		
		if (this.options.className) this.tip.addClass(this.options.className);
		
		var top = new Element('div', {'class': 'tip-top'}).inject(this.tip);
		this.container = new Element('div', {'class': 'tip'}).inject(this.tip);
		var bottom = new Element('div', {'class': 'tip-bottom'}).inject(this.tip);

		this.tip.setStyles({position: 'absolute', top: 0, left: 0, visibility: 'hidden'});
		
		if (params.elements) this.attach(params.elements);
	},
	
	attach: function(elements){
		$$(elements).each(function(element){
			var title = element.retrieve('tip:title', element.get('title'));
			var text = element.retrieve('tip:text', element.get('rel') || element.get('href'));
			var enter = element.retrieve('tip:enter', this.elementEnter.bindWithEvent(this, element));
			var leave = element.retrieve('tip:leave', this.elementLeave.bindWithEvent(this, element));
			element.addEvents({mouseenter: enter, mouseleave: leave});
			if (!this.options.fixed){
				var move = element.retrieve('tip:move', this.elementMove.bindWithEvent(this, element));
				element.addEvent('mousemove', move);
			}
			element.store('tip:native', element.get('title'));
			element.erase('title');
		}, this);
		return this;
	},
	
	detach: function(elements){
		$$(elements).each(function(element){
			element.removeEvent('mouseenter', element.retrieve('tip:enter') || $empty);
			element.removeEvent('mouseleave', element.retrieve('tip:leave') || $empty);
			element.removeEvent('mousemove', element.retrieve('tip:move') || $empty);
			element.eliminate('tip:enter').eliminate('tip:leave').eliminate('tip:move');
			var original = element.retrieve('tip:native');
			if (original) element.set('title', original);
		});
		return this;
	},
	
	elementEnter: function(event, element){
		
		$A(this.container.childNodes).each(Element.dispose);
		
		var title = element.retrieve('tip:title');
		
		if (title){
			this.titleElement = new Element('div', {'class': 'tip-title'}).inject(this.container);
			this.fill(this.titleElement, title);
		}
		
		var text = element.retrieve('tip:text');
		if (text){
			this.textElement = new Element('div', {'class': 'tip-text'}).inject(this.container);
			this.fill(this.textElement, text);
		}
		
		this.timer = $clear(this.timer);
		this.timer = this.show.delay(this.options.showDelay, this);

		this.position((!this.options.fixed) ? event : {page: element.getPosition()});
	},
	
	elementLeave: function(event){
		$clear(this.timer);
		this.timer = this.hide.delay(this.options.hideDelay, this);
	},
	
	elementMove: function(event){
		this.position(event);
	},
	
	position: function(event){
		var size = window.getSize(), scroll = window.getScroll();
		var tip = {x: this.tip.offsetWidth, y: this.tip.offsetHeight};
		var props = {x: 'left', y: 'top'};
		for (var z in props){
			var pos = event.page[z] + this.options.offsets[z];
			if ((pos + tip[z] - scroll[z]) > size[z]) pos = event.page[z] - this.options.offsets[z] - tip[z];
			this.tip.setStyle(props[z], pos);
		}
	},
	
	fill: function(element, contents){
		(typeof contents == 'string') ? element.set('html', contents) : element.adopt(contents);
	},

	show: function(){
		this.fireEvent('show', this.tip);
	},

	hide: function(){
		this.fireEvent('hide', this.tip);
	}

});

/*
Script: SmoothScroll.js
	Class for creating a smooth scrolling effect to all internal links on the page.

License:
	MIT-style license.
*/

var SmoothScroll = new Class({

	Extends: Fx.Scroll,

	initialize: function(options, context){
		context = context || document;
		var doc = context.getDocument(), win = context.getWindow();
		this.parent(doc, options);
		this.links = (this.options.links) ? $$(this.options.links) : $$(doc.links);
		var location = win.location.href.match(/^[^#]*/)[0] + '#';
		this.links.each(function(link){
			if (link.href.indexOf(location) != 0) return;
			var anchor = link.href.substr(location.length);
			if (anchor && $(anchor)) this.useLink(link, anchor);
		}, this);
		if (!Browser.Engine.webkit419) this.addEvent('complete', function(){
			win.location.hash = this.anchor;
		}, true);
	},

	useLink: function(link, anchor){
		link.addEvent('click', function(event){
			this.anchor = anchor;
			this.toElement(anchor);
			event.stop();
		}.bind(this));
	}

});

/*
Script: Slider.js
	Class for creating horizontal and vertical slider controls.

License:
	MIT-style license.
*/

var Slider = new Class({

	Implements: [Events, Options],

	options: {/*
		onChange: $empty,
		onComplete: $empty,*/
		onTick: function(position){
			if(this.options.snap) position = this.toPosition(this.step);
			this.knob.setStyle(this.property, position);
		},
		snap: false,
		offset: 0,
		range: false,
		wheel: false,
		steps: 100,
		mode: 'horizontal'
	},

	initialize: function(element, knob, options){
		this.setOptions(options);
		this.element = $(element);
		this.knob = $(knob);
		this.previousChange = this.previousEnd = this.step = -1;
		this.element.addEvent('mousedown', this.clickedElement.bind(this));
		if (this.options.wheel) this.element.addEvent('mousewheel', this.scrolledElement.bindWithEvent(this));
		var offset, limit = {}, modifiers = {'x': false, 'y': false};
		switch (this.options.mode){
			case 'vertical':
				this.axis = 'y';
				this.property = 'top';
				offset = 'offsetHeight';
				break;
			case 'horizontal':
				this.axis = 'x';
				this.property = 'left';
				offset = 'offsetWidth';
		}
		this.half = this.knob[offset] / 2;
		this.full = this.element[offset] - this.knob[offset] + (this.options.offset * 2);
		this.min = $chk(this.options.range[0]) ? this.options.range[0] : 0;
		this.max = $chk(this.options.range[1]) ? this.options.range[1] : this.options.steps;
		this.range = this.max - this.min;
		this.steps = this.options.steps || this.full;
		this.stepSize = Math.abs(this.range) / this.steps;
		this.stepWidth = this.stepSize * this.full / Math.abs(this.range) ;
		
		this.knob.setStyle('position', 'relative').setStyle(this.property, - this.options.offset);
		modifiers[this.axis] = this.property;
		limit[this.axis] = [- this.options.offset, this.full - this.options.offset];
		this.drag = new Drag(this.knob, {
			snap: 0,
			limit: limit,
			modifiers: modifiers,
			onDrag: this.draggedKnob.bind(this),
			onStart: this.draggedKnob.bind(this),
			onComplete: function(){
				this.draggedKnob();
				this.end();
			}.bind(this)
		});
		if (this.options.snap) {
			this.drag.options.grid = Math.ceil(this.stepWidth);
			this.drag.options.limit[this.axis][1] = this.full;
		}
	},

	set: function(step){
		if (!((this.range > 0) ^ (step < this.min))) step = this.min;
		if (!((this.range > 0) ^ (step > this.max))) step = this.max;
		
		this.step = Math.round(step);
		this.checkStep();
		this.end();
		this.fireEvent('tick', this.toPosition(this.step));
		return this;
	},

	clickedElement: function(event){
		var dir = this.range < 0 ? -1 : 1;
		var position = event.page[this.axis] - this.element.getPosition()[this.axis] - this.half;
		position = position.limit(-this.options.offset, this.full -this.options.offset);
		
		this.step = Math.round(this.min + dir * this.toStep(position));
		this.checkStep();
		this.end();
		this.fireEvent('tick', position);
	},
	
	scrolledElement: function(event){
		var mode = (this.options.mode == 'horizontal') ? (event.wheel < 0) : (event.wheel > 0);
		this.set(mode ? this.step - this.stepSize : this.step + this.stepSize);
		event.stop();
	},

	draggedKnob: function(){
		var dir = this.range < 0 ? -1 : 1;
		var position = this.drag.value.now[this.axis];
		position = position.limit(-this.options.offset, this.full -this.options.offset);
		this.step = Math.round(this.min + dir * this.toStep(position));
		this.checkStep();
	},

	checkStep: function(){
		if (this.previousChange != this.step){
			this.previousChange = this.step;
			this.fireEvent('change', this.step);
		}
	},

	end: function(){
		if (this.previousEnd !== this.step){
			this.previousEnd = this.step;
			this.fireEvent('complete', this.step + '');
		}
	},

	toStep: function(position){
		var step = (position + this.options.offset) * this.stepSize / this.full * this.steps;
		return this.options.steps ? Math.round(step -= step % this.stepSize) : step;
	},

	toPosition: function(step){
		return (this.full * Math.abs(this.min - step)) / (this.steps * this.stepSize) - this.options.offset;
	}

});

/*
Script: Scroller.js
	Class which scrolls the contents of any Element (including the window) when the mouse reaches the Element's boundaries.

License:
	MIT-style license.
*/

var Scroller = new Class({

	Implements: [Events, Options],

	options: {
		area: 20,
		velocity: 1,
		onChange: function(x, y){
			this.element.scrollTo(x, y);
		}
	},

	initialize: function(element, options){
		this.setOptions(options);
		this.element = $(element);
		this.listener = ($type(this.element) != 'element') ? $(this.element.getDocument().body) : this.element;
		this.timer = null;
		this.coord = this.getCoords.bind(this);
	},

	start: function(){
		this.listener.addEvent('mousemove', this.coord);
	},

	stop: function(){
		this.listener.removeEvent('mousemove', this.coord);
		this.timer = $clear(this.timer);
	},

	getCoords: function(event){
		this.page = (this.listener.get('tag') == 'body') ? event.client : event.page;
		if (!this.timer) this.timer = this.scroll.periodical(50, this);
	},

	scroll: function(){
		var size = this.element.getSize(), scroll = this.element.getScroll(), pos = this.element.getPosition(), change = {'x': 0, 'y': 0};
		for (var z in this.page){
			if (this.page[z] < (this.options.area + pos[z]) && scroll[z] != 0)
				change[z] = (this.page[z] - this.options.area - pos[z]) * this.options.velocity;
			else if (this.page[z] + this.options.area > (size[z] + pos[z]) && size[z] + size[z] != scroll[z])
				change[z] = (this.page[z] - size[z] + this.options.area - pos[z]) * this.options.velocity;
		}
		if (change.y || change.x) this.fireEvent('change', [scroll.x + change.x, scroll.y + change.y]);
	}

});

/*
Script: Accordion.js
	An Fx.Elements extension which allows you to easily create accordion type controls.

License:
	MIT-style license.
*/

var Accordion = new Class({

	Extends: Fx.Elements,

	options: {/*
		onActive: $empty,
		onBackground: $empty,*/
		display: 0,
		show: false,
		height: true,
		width: false,
		opacity: true,
		fixedHeight: false,
		fixedWidth: false,
		wait: false,
		alwaysHide: false
	},

	initialize: function(){
		var params = Array.link(arguments, {'container': Element.type, 'options': Object.type, 'togglers': $defined, 'elements': $defined});
		this.parent(params.elements, params.options);
		this.togglers = $$(params.togglers);
		this.container = $(params.container);
		this.previous = -1;
		if (this.options.alwaysHide) this.options.wait = true;
		if ($chk(this.options.show)){
			this.options.display = false;
			this.previous = this.options.show;
		}
		if (this.options.start){
			this.options.display = false;
			this.options.show = false;
		}
		this.effects = {};
		if (this.options.opacity) this.effects.opacity = 'fullOpacity';
		if (this.options.width) this.effects.width = this.options.fixedWidth ? 'fullWidth' : 'offsetWidth';
		if (this.options.height) this.effects.height = this.options.fixedHeight ? 'fullHeight' : 'scrollHeight';
		for (var i = 0, l = this.togglers.length; i < l; i++) this.addSection(this.togglers[i], this.elements[i]);
		this.elements.each(function(el, i){
			if (this.options.show === i){
				this.fireEvent('active', [this.togglers[i], el]);
			} else {
				for (var fx in this.effects) el.setStyle(fx, 0);
			}
		}, this);
		if ($chk(this.options.display)) this.display(this.options.display);
	},

	addSection: function(toggler, element, pos){
		toggler = $(toggler);
		element = $(element);
		var test = this.togglers.contains(toggler);
		var len = this.togglers.length;
		this.togglers.include(toggler);
		this.elements.include(element);
		if (len && (!test || pos)){
			pos = $pick(pos, len - 1);
			toggler.inject(this.togglers[pos], 'before');
			element.inject(toggler, 'after');
		} else if (this.container && !test){
			toggler.inject(this.container);
			element.inject(this.container);
		}
		var idx = this.togglers.indexOf(toggler);
		toggler.addEvent('click', this.display.bind(this, idx));
		if (this.options.height) element.setStyles({'padding-top': 0, 'border-top': 'none', 'padding-bottom': 0, 'border-bottom': 'none'});
		if (this.options.width) element.setStyles({'padding-left': 0, 'border-left': 'none', 'padding-right': 0, 'border-right': 'none'});
		element.fullOpacity = 1;
		if (this.options.fixedWidth) element.fullWidth = this.options.fixedWidth;
		if (this.options.fixedHeight) element.fullHeight = this.options.fixedHeight;
		element.setStyle('overflow', 'hidden');
		if (!test){
			for (var fx in this.effects) element.setStyle(fx, 0);
		}
		return this;
	},

	display: function(index){
		index = ($type(index) == 'element') ? this.elements.indexOf(index) : index;
		if ((this.timer && this.options.wait) || (index === this.previous && !this.options.alwaysHide)) return this;
		this.previous = index;
		var obj = {};
		this.elements.each(function(el, i){
			obj[i] = {};
			var hide = (i != index) || (this.options.alwaysHide && (el.offsetHeight > 0));
			this.fireEvent(hide ? 'background' : 'active', [this.togglers[i], el]);
			for (var fx in this.effects) obj[i][fx] = hide ? 0 : el[this.effects[fx]];
		}, this);
		return this.start(obj);
	}

});/*
Script: dbug.js
	A wrapper for Firebug console.* statements.

License:
	http://clientside.cnet.com/wiki/cnet-libraries#license
*/
var dbug = {
	logged: [],	
	timers: {},
	firebug: false, 
	enabled: false, 
	log: function() {
		dbug.logged.push(arguments);
	},
	nolog: function(msg) {
		dbug.logged.push(arguments);
	},
	time: function(name){
		dbug.timers[name] = new Date().getTime();
	},
	timeEnd: function(name){
		if (dbug.timers[name]) {
			var end = new Date().getTime() - dbug.timers[name];
			dbug.timers[name] = false;
			dbug.log('%s: %s', name, end);
		} else dbug.log('no such timer: %s', name);
	},
	enable: function(silent) { 
		if(dbug.firebug) {
			try {
				dbug.enabled = true;
				dbug.log = function(){
						(console.debug || console.log).apply(console, arguments);
				};
				dbug.time = function(){
					console.time.apply(console, arguments);
				};
				dbug.timeEnd = function(){
					console.timeEnd.apply(console, arguments);
				};
				if(!silent) dbug.log('enabling dbug');
				for(var i=0;i<dbug.logged.length;i++){ dbug.log.apply(console, dbug.logged[i]); }
				dbug.logged=[];
			} catch(e) {
				dbug.enable.delay(400);
			}
		}
	},
	disable: function(){ 
		if(dbug.firebug) dbug.enabled = false;
		dbug.log = dbug.nolog;
		dbug.time = function(){};
		dbug.timeEnd = function(){};
	},
	cookie: function(set){
		var value = document.cookie.match('(?:^|;)\\s*jsdebug=([^;]*)');
		var debugCookie = value ? unescape(value[1]) : false;
		if((debugCookie != 'true' || set) && !set) {
			dbug.enable();
			dbug.log('setting debugging cookie');
			var date = new Date();
			date.setTime(date.getTime()+(24*60*60*1000));
			document.cookie = 'jsdebug=true;expires='+date.toGMTString()+';path=/;';
		} else dbug.disableCookie();
	},
	disableCookie: function(){
		dbug.log('disabling debugging cookie');
		document.cookie = 'jsdebug=false;path=/;';
	}
};

(function(){
	var fb = typeof console != "undefined";
	var debugMethods = ['debug','info','warn','error','assert','dir','dirxml'];
	var otherMethods = ['trace','group','groupEnd','profile','profileEnd','count'];
	function set(methodList, defaultFunction) {
		for(var i = 0; i < methodList.length; i++){
			dbug[methodList[i]] = (fb && console[methodList[i]])?console[methodList[i]]:defaultFunction;
		}
	};
	set(debugMethods, dbug.log);
	set(otherMethods, function(){});
})();
if (typeof console != "undefined" && console.warn){
	dbug.firebug = true;
	var value = document.cookie.match('(?:^|;)\\s*jsdebug=([^;]*)');
	var debugCookie = value ? unescape(value[1]) : false;
	if(window.location.href.indexOf("jsdebug=true")>0 || debugCookie=='true') dbug.enable();
	if(debugCookie=='true')dbug.log('debugging cookie enabled');
	if(window.location.href.indexOf("jsdebugCookie=true")>0){
		dbug.cookie();
		if(!dbug.enabled)dbug.enable();
	}
	if(window.location.href.indexOf("jsdebugCookie=false")>0)dbug.disableCookie();
}


/*
Script: Browser.Extras.js
	Extends the Window native object to include methods useful in managing the window location and urls.

License:
	http://clientside.cnet.com/wiki/cnet-libraries#license
*/
Browser.combine({
	getHost:function(url){
		url = $pick(url, window.location.href);
		var host = url;
		if(url.test('http://')){
			url = url.substring(url.indexOf('http://')+7,url.length);
			if(url.test(':')) url = url.substring(0, url.indexOf(":"));
			if(url.test('/')) return url.substring(0,url.indexOf('/'));
			return url;
		}
		return false;
	},
	getQueryStringValue: function(key, url) {
		try { 
			return Browser.getQueryStringValues(url)[key];
		}catch(e){return null;}
	},
	getQueryStringValues: function(url){
		var qs = $pick(url, window.location.search, '').split('?')[1]; //get the query string
		if (!$chk(qs)) return {};
		if (qs.test('#')) qs = qs.substring(0, qs.indexOf('#'));
		try {
       if (qs) return qs.parseQuery();
		} catch(e){
			return null;
		}
		return {}; //if there isn't one, return null
	},
	getPort: function(url) {
		url = $pick(url, window.location.href);
		var re = new RegExp(':([0-9]{4})');
		var m = re.exec(url);
	  if (m == null) return false;
	  else {
			var port = false;
			m.each(function(val){
				if($chk(parseInt(val))) port = val;
			});
	  }
		return port;
	}
});
window.addEvent('domready', function(){
	var count = 0;
	//this is in case domready fires before string.extras loads
	function setQs(){
		function retry(){
			count++;
			if (count < 20) setQs.delay(50);
		}; 
		try {
			if (!Browser.set("qs", Browser.getQueryStringValues())) retry();
		} catch(e){
			retry();
		}
	}
	setQs();
});

/*
Script: FixPNG.js
	Extends the Browser hash object to include methods useful in managing the window location and urls.

License:
	http://clientside.cnet.com/wiki/cnet-libraries#license
*/
$extend(Browser, {
	fixPNG: function(el) {
		try {
			if (Browser.Engine.trident4){
				el = $(el);
				if (!el) return el;
				if (el.get('tag') == "img" && el.get('src').test(".png")) {
					var vis = el.isVisible();
					try { //safari sometimes crashes here, so catch it
						dim = el.getSize();
					}catch(e){}
					if(!vis){
						var before = {};
						//use this method instead of getStyles 
						['visibility', 'display', 'position'].each(function(style){
							before[style] = this.style[style]||'';
						}, this);
						//this.getStyles('visibility', 'display', 'position');
						this.setStyles({
							visibility: 'hidden',
							display: 'block',
							position:'absolute'
						});
						dim = el.getSize(); //works now, because the display isn't none
						this.setStyles(before); //put it back where it was
						el.hide();
					}
					var replacement = new Element('span', {
						id:(el.id)?el.id:'',
						'class':(el.className)?el.className:'',
						title:(el.title)?el.title:(el.alt)?el.alt:'',
						styles: {
							display: vis?'inline-block':'none',
							width: dim.x,
							height: dim.y,
							filter: "progid:DXImageTransform.Microsoft.AlphaImageLoader (src='" 
								+ el.src + "', sizingMethod='scale');"
						},
						src: el.src
					});
					if(el.style.cssText) {
						try {
							var styles = {};
							var s = el.style.cssText.split(';');
							s.each(function(style){
								var n = style.split(':');
								styles[n[0]] = n[1];
							});
							replacement.setStyle(styles);
						} catch(e){ dbug.log('fixPNG1: ', e)}
					}
					if(replacement.cloneEvents) replacement.cloneEvents(el);
					replacement.replaces(el);
				} else if (el.get('tag') != "img") {
				 	var imgURL = el.getStyle('background-image');
				 	if (imgURL.test(/\((.+)\)/)){
				 		el.setStyles({
				 			background: '',
				 			filter: "progid:DXImageTransform.Microsoft.AlphaImageLoader(enabled='true', sizingMethod='crop', src=" + imgURL.match(/\((.+)\)/)[1] + ")"
				 		});
				 	};
				}
			}
		} catch(e) {dbug.log('fixPNG2: ', e)}
	},
  pngTest: /\.png$/, // saves recreating the regex repeatedly
  scanForPngs: function(el, className) {
    className = className||'fixPNG';
    //TODO: should this also be testing the css background-image property for pngs?
    //Q: should it return an array of all those it has tweaked?
    if (document.getElements){ // more efficient but requires 'selectors'
      el = $(el||document.body);
      el.getElements('img[src$=.png]').addClass(className);
    } else { // scan the whole page
      var els = $$('img').each(function(img) {
        if (Browser.pngTest(img.src)){
          img.addClass(className);
        }
      });
    }
  }
});
if(Browser.Engine.trident4) window.addEvent('domready', function(){$$('img.fixPNG').each(Browser.fixPNG)});

/*
Script: IframeShim.js
	Defines IframeShim, a class for obscuring select lists and flash objects in IE.

License:
	http://clientside.cnet.com/wiki/cnet-libraries#license
*/	
var IframeShim = new Class({
	Implements: [Options, Events],
	options: {
		name: '',
		className:'iframeShim',
		display:false,
		zindex: null,
		margin: 0,
		offset: {
			x: 0,
			y: 0
		},
		browsers: (Browser.Engine.trident4 || (Browser.Engine.gecko && !Browser.Engine.gecko19 && Browser.Platform.mac))
	},
	initialize: function (element, options){
		this.setOptions(options);
		//legacy
		if(this.options.offset && this.options.offset.top) this.options.offset.y = this.options.offset.top;
		if(this.options.offset && this.options.offset.left) this.options.offset.x = this.options.offset.left;
		this.element = $(element);
		this.makeShim();
		return;
	},
	makeShim: function(){
		this.shim = new Element('iframe');
		this.id = this.options.name || new Date().getTime() + "_shim";
		if(this.element.getStyle('z-Index').toInt()<1 || isNaN(this.element.getStyle('z-Index').toInt()))
			this.element.setStyle('z-Index',5);
		var z = this.element.getStyle('z-Index')-1;
		
		if($chk(this.options.zindex) && 
			 this.element.getStyle('z-Index').toInt() > this.options.zindex)
			 z = this.options.zindex;
			
 		this.shim.setStyles({
			'position': 'absolute',
			'zIndex': z,
			'border': 'none',
			'filter': 'progid:DXImageTransform.Microsoft.Alpha(style=0,opacity=0)'
		}).setProperties({
			'src':'javascript:void(0);',
			'frameborder':'0',
			'scrolling':'no',
			'id':this.id
		}).addClass(this.options.className);
		
		this.element.store('shim', this);

		var inject = function(){
			this.shim.inject(this.element, 'after');
			if(this.options.display) this.show();
			else this.hide();
			this.fireEvent('onInject');
		};
		if(this.options.browsers){
			if(Browser.Engine.trident && !IframeShim.ready) {
				window.addEvent('load', inject.bind(this));
			} else {
				inject.run(null, this);
			}
		}
	},
	position: function(shim){
		if(!this.options.browsers || !IframeShim.ready) return this;
		var before = this.element.getStyles('display', 'visibility', 'position');
		this.element.setStyles({
			display: 'block',
			position: 'absolute',
			visibility: 'hidden'
		});
		var size = this.element.getSize();
		this.element.setStyles(before);
		if($type(this.options.margin)){
			size.x = size.x-(this.options.margin*2);
			size.y = size.y-(this.options.margin*2);
			this.options.offset.x += this.options.margin; 
			this.options.offset.y += this.options.margin;
		}
 		this.shim.setStyles({
			'width': size.x,
			'height': size.y
		}).setPosition({
			relativeTo: this.element,
			offset: this.options.offset
		});
		return this;
	},
	hide: function(){
		if(this.options.browsers) this.shim.setStyle('display','none');
		return this;
	},
	show: function(){
		if(!this.options.browsers) return this;
		this.shim.setStyle('display','block');
		return this.position();
	},
	dispose: function(){
		if(this.options.browsers) this.shim.dispose();
		return this;
	}
});
window.addEvent('load', function(){
	IframeShim.ready = true;
});


/*
Script: Popup.js
	Defines the Popup class useful for making popup windows.

License:
	http://clientside.cnet.com/wiki/cnet-libraries#license
*/

Browser.set("Popup", new Class({
	Implements:[Options, Events],
	options: {
		width: 500,
		height: 300,
		x: 50,
		y: 50,
		toolbar: 0,
		location: 0,
		directories: 0,
		status: 0,
		scrollbars: 'auto',
		resizable: 1,
		name: 'popup'
//	onBlock: $empty
	},
	initialize: function(url, options){
		this.url = url || false;
		this.setOptions(options);
		if(this.url) this.openWin();
	},
	openWin: function(url){
		url = url || this.url;
		var options = 'toolbar='+this.options.toolbar+
			',location='+this.options.location+
			',directories='+this.options.directories+
			',status='+this.options.status+
			',scrollbars='+this.options.scrollbars+
			',resizable='+this.options.resizable+
			',width='+this.options.width+
			',height='+this.options.height+
			',top='+this.options.y+
			',left='+this.options.x;
		this.window = window.open(url, this.options.name, options);
		if (!this.window) {
			this.window = window.open('', this.options.name, options);
			this.window.location.href = url;
		}
		this.focus.delay(100, this);
		return this;
	},
	focus: function(){
		if (this.window) this.window.focus();
		else if (this.focusTries<10) this.focus.delay(100, this); //try again
		else {
			this.blocked = true;
			this.fireEvent('onBlock');
		}
		return this;
	},
	focusTries: 0,
	blocked: null,
	close: function(){
		this.window.close();
		return this;
	}
}));

/*
Script: Date.js
	Extends the Date native object to include methods useful in managing dates.

License:
	http://clientside.cnet.com/wiki/cnet-libraries#license
*/

new Native({name: 'Date', initialize: Date, protect: true});
['now','parse','UTC'].each(function(method){
	Native.genericize(Date, method, true);
});
Date.$Methods = new Hash();
["Date", "Day", "FullYear", "Hours", "Milliseconds", "Minutes", "Month", "Seconds", "Time", "TimezoneOffset", 
	"Week", "Timezone", "GMTOffset", "DayOfYear", "LastMonth", "UTCDate", "UTCDay", "UTCFullYear",
	"AMPM", "UTCHours", "UTCMilliseconds", "UTCMinutes", "UTCMonth", "UTCSeconds"].each(function(method) {
	Date.$Methods.set(method.toLowerCase(), method);
});
$each({
	ms: "Milliseconds",
	year: "FullYear",
	min: "Minutes",
	mo: "Month",
	sec: "Seconds",
	hr: "Hours"
}, function(value, key){
	Date.$Methods.set(key, value);
});


Date.implement({
	set: function(key, value) {
		key = key.toLowerCase();
		var m = Date.$Methods;
		if (m.has(key)) this['set'+m.get(key)](value);
		return this;
	},
	get: function(key) {
		key = key.toLowerCase();
		var m = Date.$Methods;
		if (m.has(key)) return this['get'+m.get(key)]();
		return null;
	},
	clone: function() {
		return new Date(this.get('time'));
	},
	increment: function(interval, times) {
		return this.multiply(interval, times);
	},
	decrement: function(interval, times) {
		return this.multiply(interval, times, false);
	},
	multiply: function(interval, times, increment){
		interval = interval || 'day';
		times = $pick(times, 1);
		increment = $pick(increment, true);
		var multiplier = increment?1:-1;
		var month = this.format("%m").toInt()-1;
		var year = this.format("%Y").toInt();
		var time = this.get('time');
		var offset = 0;
		switch (interval) {
				case 'year':
					times.times(function(val) {
						if (Date.isLeapYear(year+val) && month > 1 && multiplier > 0) val++;
						if (Date.isLeapYear(year+val) && month <= 1 && multiplier < 0) val--;
						offset += Date.$units.year(year+val);
					});
					break;
				case 'month':
					times.times(function(val){
						if (multiplier < 0) val++;
						var mo = month+(val*multiplier);
						var year = year;
						if (mo < 0) {
							year--;
							mo = 12+mo;
						}
						if (mo > 11 || mo < 0) {
							year += (mo/12).toInt()*multiplier;
							mo = mo%12;
						}
						offset += Date.$units.month(mo, year);
					});
					break;
				default:
					offset = Date.$units[interval]()*times;
					break;
		}
		this.set('time', time+(offset*multiplier));
		return this;
	},
	isLeapYear: function() {
		return Date.isLeapYear(this.get('year'));
	},
	clearTime: function() {
		this.set('hr', 0);
		this.set('min',0);
		this.set('sec', 0);
		this.set('ms', 0);
		return this;
	},
	diff: function(d, resolution) {
		resolution = resolution || 'day';
		if($type(d) == 'string') d = Date.parse(d);
		switch (resolution) {
			case 'year':
				return d.format("%Y").toInt() - this.format("%Y").toInt();
				break;
			case 'month':
				var months = (d.format("%Y").toInt() - this.format("%Y").toInt())*12;
				return months + d.format("%m").toInt() - this.format("%m").toInt();
				break;
			default:
				var diff = d.get('time') - this.get('time');
				if (diff < 0 && Date.$units[resolution]() > (-1*(diff))) return 0;
				else if (diff >= 0 && diff < Date.$units[resolution]()) return 0;
				return ((d.get('time') - this.get('time')) / Date.$units[resolution]()).round();
		}
	},
	getWeek: function() {
		var day = (new Date(this.get('year'), 0, 1)).get('date');
		return Math.round((this.get('dayofyear') + (day > 3 ? day - 4 : day + 3)) / 7);
	},
	getTimezone: function() {
		return this.toString()
			.replace(/^.*? ([A-Z]{3}).[0-9]{4}.*$/, '$1')
			.replace(/^.*?\(([A-Z])[a-z]+ ([A-Z])[a-z]+ ([A-Z])[a-z]+\)$/, '$1$2$3');
	},
	getGMTOffset: function() {
		var off = this.get('timezoneOffset');
		return ((off > 0) ? '-' : '+')
			+ Math.floor(Math.abs(off) / 60).zeroise(2)
			+ (off % 60).zeroise(2);
	},
	parse: function(str) {
		this.set('time', Date.parse(str));
		return this;
	},
	format: function(f) {
		f = f || "%x %X";
		if (!this.valueOf()) return 'invalid date';
		//replace short-hand with actual format
		if (Date.$formats[f.toLowerCase()]) f = Date.$formats[f.toLowerCase()];
		var d = this;
		return f.replace(/\%([aAbBcdHIjmMpSUWwxXyYTZ])/g,
			function($1, $2) {
				switch ($2) {
					case 'a': return Date.$days[d.get('day')].substr(0, 3);
					case 'A': return Date.$days[d.get('day')];
					case 'b': return Date.$months[d.get('month')].substr(0, 3);
					case 'B': return Date.$months[d.get('month')];
					case 'c': return d.toString();
					case 'd': return d.get('date').zeroise(2);
					case 'H': return d.get('hr').zeroise(2);
					case 'I': return ((d.get('hr') % 12) || 12);
					case 'j': return d.get('dayofyear').zeroise(3);
					case 'm': return (d.get('mo') + 1).zeroise(2);
					case 'M': return d.get('min').zeroise(2);
					case 'p': return d.get('hr') < 12 ? 'AM' : 'PM';
					case 'S': return d.get('seconds').zeroise(2);
					case 'U': return d.get('week').zeroise(2);
					case 'W': throw new Error('%W is not supported yet');
					case 'w': return d.get('day');
					case 'x': 
						var c = Date.$cultures[Date.$culture];
						//return d.format("%{0}{3}%{1}{3}%{2}".substitute(c.map(function(s){return s.substr(0,1)}))); //grr!
						return d.format('%' + c[0].substr(0,1) +
							c[3] + '%' + c[1].substr(0,1) +
							c[3] + '%' + c[2].substr(0,1).toUpperCase());
					case 'X': return d.format('%I:%M%p');
					case 'y': return d.get('year').toString().substr(2);
					case 'Y': return d.get('year');
					case 'T': return d.get('GMTOffset');
					case 'Z': return d.get('Timezone');
					case '%': return '%';
				}
				return $2;
			}
		);
	},
	setAMPM: function(ampm){
		ampm = ampm.toUpperCase();
		if (this.format("%H").toInt() > 11 && ampm == "AM") 
			return this.decrement('hour', 12);
		else if (this.format("%H").toInt() < 12 && ampm == "PM")
			return this.increment('hour', 12);
		return this;
	}
});

Date.prototype.compare = Date.prototype.diff;
Date.prototype.strftime = Date.prototype.format;

Date.$nativeParse = Date.parse;

$extend(Date, {
	$months: ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'],
	$days: ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'],
	$daysInMonth: function(monthIndex, year) {
		if (Date.isLeapYear(year.toInt()) && monthIndex === 1) return 29;
		return [31, 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31][monthIndex];
	},
	$epoch: -1,
	$era: -2,
	$units: {
		ms: function(){return 1},
		second: function(){return 1000},
		minute: function(){return 60000},
		hour: function(){return 3600000},
		day: function(){return 86400000},
		week: function(){return 608400000},
		month: function(monthIndex, year) {
			var d = new Date();
			return Date.$daysInMonth($pick(monthIndex,d.format("%m").toInt()), $pick(year,d.format("%Y").toInt())) * 86400000;
		},
		year: function(year){
			year = year || new Date().format("%Y").toInt();
			return Date.isLeapYear(year.toInt())?31622400000:31536000000;
		}
	},
	$formats: {
		db: '%Y-%m-%d %H:%M:%S',
		compact: '%Y%m%dT%H%M%S',
		iso8601: '%Y-%m-%dT%H:%M:%S%T',
		rfc822: '%a, %d %b %Y %H:%M:%S %Z',
		'short': '%d %b %H:%M',
		'long': '%B %d, %Y %H:%M'
	},
	
	isLeapYear: function(yr) {
		return new Date(yr,1,29).getDate()==29;
	},

	parseUTC: function(value){
		var localDate = new Date(value);
		var utcSeconds = Date.UTC(localDate.get('year'), localDate.get('mo'),
		localDate.get('date'), localDate.get('hr'), localDate.get('min'), localDate.get('sec'));
		return new Date(utcSeconds);
	},
	
	parse: function(from) {
		var type = $type(from);
		if (type == 'number') return new Date(from);
		if (type != 'string') return from;
		if (!from.length) return null;
		for (var i = 0, j = Date.$parsePatterns.length; i < j; i++) {
			var r = Date.$parsePatterns[i].re.exec(from);
			if (r) {
				try {
					return Date.$parsePatterns[i].handler(r);
				} catch(e) {
					dbug.log('date parse error: ', e);
					return null;
				}
			}
		}
		return new Date(Date.$nativeParse(from));
	},

	parseMonth: function(month, num) {
		var ret = -1;
		switch ($type(month)) {
			case 'object':
				ret = Date.$months[month.get('mo')];
				break;
			case 'number':
				ret = Date.$months[month - 1] || false;
				if (!ret) throw new Error('Invalid month index value must be between 1 and 12:' + index);
				break;
			case 'string':
				var match = Date.$months.filter(function(name) {
					return this.test(name);
				}, new RegExp('^' + month, 'i'));
				if (!match.length) throw new Error('Invalid month string');
				if (match.length > 1) throw new Error('Ambiguous month');
				ret = match[0];
		}
		return (num) ? Date.$months.indexOf(ret) : ret;
	},

	parseDay: function(day, num) {
		var ret = -1;
		switch ($type(day)) {
			case 'number':
				ret = Date.$days[day - 1] || false;
				if (!ret) throw new Error('Invalid day index value must be between 1 and 7');
				break;
			case 'string':
				var match = Date.$days.filter(function(name) {
					return this.test(name);
				}, new RegExp('^' + day, 'i'));
				if (!match.length) throw new Error('Invalid day string');
				if (match.length > 1) throw new Error('Ambiguous day');
				ret = match[0];
		}
		return (num) ? Date.$days.indexOf(ret) : ret;
	},
	
	fixY2K: function(d){
		if (!isNaN(d)) {
			var newDate = new Date(d);
			if (newDate.get('year') < 2000 && d.toString().indexOf(newDate.get('year')) < 0) {
				newDate.increment('year', 100);
			}
			return newDate;
		} else return d;
	},

	$cultures: {
		'US': ['month', 'date', 'year', '/'],
		'GB': ['date', 'month', 'year', '/']
	},

	$culture: 'US',
	
	$cIndex: function(unit){
		return Date.$cultures[Date.$culture].indexOf(unit)+1;
	},

	$parsePatterns: [
		{
			//"12.31.08", "12-31-08", "12/31/08", "12.31.2008", "12-31-2008", "12/31/2008"
			re: /^(\d{1,2})[\.\-\/](\d{1,2})[\.\-\/](\d{2,4})$/,
			handler: function(bits){
				var d = new Date();
				var culture = Date.$cultures[Date.$culture];
				d.set('year', bits[Date.$cIndex('year')]);
				d.set('month', bits[Date.$cIndex('month')] - 1);
				d.set('date', bits[Date.$cIndex('date')]);
				return Date.fixY2K(d);
			}
		},
		//"12.31.08", "12-31-08", "12/31/08", "12.31.2008", "12-31-2008", "12/31/2008"
		//above plus "10:45pm" ex: 12.31.08 10:45pm
		{
			re: /^(\d{1,2})[\.\-\/](\d{1,2})[\.\-\/](\d{2,4})\s(\d{1,2}):(\d{1,2})(\w{2})$/,
			handler: function(bits){
				var d = new Date();
				d.set('year', bits[Date.$cIndex('year')]);
				d.set('month', bits[Date.$cIndex('month')] - 1);
				d.set('date', bits[Date.$cIndex('date')]);
				d.set('hr', bits[4]);
				d.set('min', bits[5]);
				d.set('ampm', bits[6]);
				return Date.fixY2K(d);
			}
		}
	]
});

Number.implement({
	zeroise: function(length) {
		return String(this).zeroise(length);
	}
});

String.implement({
	repeat: function(times) {
		var ret = [];
		for (var i = 0; i < times; i++) ret.push(this);
		return ret.join('');
	},
	zeroise: function(length) {
		return '0'.repeat(length - this.length) + this;
	}

});


/*
Script: Date.Extras.js
	Extends the Date native object to include extra methods (on top of those in Date.js).

License:
	http://clientside.cnet.com/wiki/cnet-libraries#license
*/

["LastDayOfMonth", "Ordinal"].each(function(method) {
	Date.$Methods.set(method.toLowerCase(), method);
});

Date.implement({
	timeAgoInWords: function(){
		var relative_to = (arguments.length > 0) ? arguments[1] : new Date();
		return Date.distanceOfTimeInWords(this, relative_to, arguments[2]);
	},
	getOrdinal: function() {
		var test = this.get('date');
		return (test > 3 && test < 21) ? 'th' : ['th', 'st', 'nd', 'rd', 'th'][Math.min(test % 10, 4)];
	},
	getDayOfYear: function() {
		return ((Date.UTC(this.getFullYear(), this.getMonth(), this.getDate() + 1, 0, 0, 0)
			- Date.UTC(this.getFullYear(), 0, 1, 0, 0, 0) ) / Date.$units.day());
	},
	getLastDayOfMonth: function() {
		var ret = this.clone();
		ret.setMonth(ret.getMonth() + 1, 0);
		return ret.getDate();
	}
});

$extend(Date, {
// http://twitter.pbwiki.com/RelativeTimeScripts	
	distanceOfTimeInWords: function(fromTime, toTime, includeTime) {
		var delta = parseInt((toTime.getTime() - fromTime.getTime()) / 1000);
		if(delta < 60) {
			return 'less than a minute ago';
		} else if(delta < 120) {
			return 'about a minute ago';
		} else if(delta < (45*60)) {
			return (parseInt(delta / 60)).toString() + ' minutes ago';
		} else if(delta < (90*60)) {
			return 'about an hour ago';
		} else if(delta < (24*60*60)) {
			return 'about ' + (parseInt(delta / 3600)).toString() + ' hours ago';
		} else if(delta < (48*60*60)) {
			return '1 day ago';
		} else {
			var days = (parseInt(delta / 86400)).toString();
			if(days > 30) {
				var fmt  = '%B %d';
				if(toTime.getYear() != fromTime.getYear()) { fmt += ', %Y'; }
				if(includeTime) fmt += ' %I:%M %p';
				return fromTime.strftime(fmt);
			} else {
			return days + " days ago";
			}
		}
	}
});

Date.$parsePatterns.extend([
	{
		// yyyy-mm-ddTHH:MM:SS-0500 (ISO8601) i.e.2007-04-17T23:15:22Z
		// inspired by: http://delete.me.uk/2005/03/iso8601.html
		re: /^(\d{4})(?:-?(\d{2})(?:-?(\d{2})(?:[T ](\d{2})(?::?(\d{2})(?::?(\d{2})(?:\.(\d+))?)?)?(?:Z|(?:([-+])(\d{2})(?::?(\d{2}))?)?)?)?)?)?$/,
		handler: function(bits) {
			var offset = 0;
			var d = new Date(bits[1], 0, 1);
			if (bits[2]) d.setMonth(bits[2] - 1);
			if (bits[3]) d.setDate(bits[3]);
			if (bits[4]) d.setHours(bits[4]);
			if (bits[5]) d.setMinutes(bits[5]);
			if (bits[6]) d.setSeconds(bits[6]);
			if (bits[7]) d.setMilliseconds(('0.' + bits[7]).toInt() * 1000);
			if (bits[9]) {
				offset = (bits[9].toInt() * 60) + bits[10].toInt();
				offset *= ((bits[8] == '-') ? 1 : -1);
			}
			offset -= d.getTimezoneOffset();
			d.setTime((d * 1) + (offset * 60 * 1000).toInt());
			return d;
		}
	}, {
		//"today"
		re: /^tod/i,
		handler: function() {
			return new Date();
		}
	}, {
		//"tomorow"
		re: /^tom/i,
		handler: function() {
			return new Date().increment();
		}
	}, {
		//"yesterday"
		re: /^yes/i,
		handler: function() {
			return new Date().decrement();
		}
	}, {
		//4th, 23rd
		re: /^(\d{1,2})(st|nd|rd|th)?$/i,
		handler: function(bits) {
			var d = new Date();
			d.setDate(bits[1].toInt());
			return d;
		}
	}, {
		//4th Jan, 23rd May
		re: /^(\d{1,2})(?:st|nd|rd|th)? (\w+)$/i,
		handler: function(bits) {
			var d = new Date();
			d.setMonth(Date.parseMonth(bits[2], true), bits[1].toInt());
			return d;
		}
	}, {
		//4th Jan 2000, 23rd May 2004
		re: /^(\d{1,2})(?:st|nd|rd|th)? (\w+),? (\d{4})$/i,
		handler: function(bits) {
			var d = new Date();
			d.setMonth(Date.parseMonth(bits[2], true), bits[1].toInt());
			d.setYear(bits[3]);
			return d;
		}
	}, {
		//Jan 4th
		re: /^(\w+) (\d{1,2})(?:st|nd|rd|th)?,? (\d{4})$/i,
		handler: function(bits) {
			var d = new Date();
			d.setMonth(Date.parseMonth(bits[1], true), bits[2].toInt());
			d.setYear(bits[3]);
			return d;
		}
	}, {
		//Jan 4th 2003
		re: /^next (\w+)$/i,
		handler: function(bits) {
			var d = new Date();
			var day = d.getDay();
			var newDay = Date.parseDay(bits[1], true);
			var addDays = newDay - day;
			if (newDay <= day) {
				addDays += 7;
			}
			d.setDate(d.getDate() + addDays);
			return d;
		}
	}, {
		//4 May 08:12
		re: /^\d+\s[a-zA-z]..\s\d.\:\d.$/,
		handler: function(bits){
			var d = new Date();
			bits = bits[0].split(" ");
			d.setDate(bits[0]);
			var m;
			Date.$months.each(function(mo, i){
				if (new RegExp("^"+bits[1]).test(mo)) m = i;
			});
			d.setMonth(m);
			d.setHours(bits[2].split(":")[0]);
			d.setMinutes(bits[2].split(":")[1]);
			d.setMilliseconds(0);
			return d;
		}
	}
/*	 {
		re: /^last (\w+)$/i,
		handler: function(bits) {
			throw new Error('Not yet implemented');
		}
	}	*/
]);


/*
Script: Hash.Extras.js
	Extends the Hash native object to include getFromPath which allows a path notation to child elements.

License:
	http://clientside.cnet.com/wiki/cnet-libraries#license
*/

Hash.implement({
	getFromPath: function(notation) {
		var source = this.getClean();
		notation.replace(/\[([^\]]+)\]|\.([^.[]+)|[^[.]+/g, function(match) {
			if (!source) return;
			var prop = arguments[2] || arguments[1] || arguments[0];
			source = (prop in source) ? source[prop] : null;
			return match;
		});
		return source;
	},
	cleanValues: function(method){
		method = method||$defined;
		this.each(function(v, k){
			if (!method(v)) this.erase(k);
		}, this);
		return this;
	}
});

/*
Script: String.Extras.js
	Extends the String native object to include methods useful in managing various kinds of strings (query strings, urls, html, etc).

License:
	http://clientside.cnet.com/wiki/cnet-libraries#license
*/
String.implement({
	stripTags: function() {
		return this.replace(/<\/?[^>]+>/gi, '');
  },
	parseQuery: function(encodeKeys, encodeValues) {
		encodeKeys = $pick(encodeKeys, true);
		encodeValues = $pick(encodeValues, true);
		var vars = this.split(/[&;]/);
		var rs = {};
		if (vars.length) vars.each(function(val) {
			var keys = val.split('=');
			if (keys.length && keys.length == 2) {
				rs[(encodeKeys)?encodeURIComponent(keys[0]):keys[0]] = (encodeValues)?encodeURIComponent(keys[1]):keys[1];
			}
		});
		return rs;
	},
	tidy: function() {
		var txt = this.toString();
		$each({
			"[\xa0\u2002\u2003\u2009]": " ",
			"\xb7": "*",
			"[\u2018\u2019]": "'",
			"[\u201c\u201d]": '"',
			"\u2026": "...",
			"\u2013": "-",
			"\u2014": "--",
			"\uFFFD": "&raquo;"
		}, function(value, key){
			txt = txt.replace(new RegExp(key, 'g'), value);
		});
		return txt;
	},
	cleanQueryString: function(method){
		return this.split("&").filter(method||function(set){
			return $chk(set.split("=")[1]);
		}).join("&");
	}
});


/*
Script: Element.Forms.js
	Extends the Element native object to include methods useful in managing inputs.

License:
	http://clientside.cnet.com/wiki/cnet-libraries#license
*/
Element.implement({
	tidy: function(){
		try {	
			this.set('value', this.get('value').tidy());
		}catch(e){dbug.log('element.tidy error: %o', e);}
	},
	getTextInRange: function(start, end) {
		return this.get('value').substring(start, end);
	},
	getSelectedText: function() {
		if(Browser.Engine.trident) return document.selection.createRange().text;
		return this.get('value').substring(this.getSelectionStart(), this.getSelectionEnd());
	},
	getSelectionStart: function() {
		if(Browser.Engine.trident) {
			var offset = (Browser.Engine.trident4)?3:2;
			this.focus();
			var range = document.selection.createRange();
			if (range.compareEndPoints("StartToEnd", range) != 0) range.collapse(true);
			return range.getBookmark().charCodeAt(2) - offset;
		}
		return this.selectionStart;
	},
	getSelectionEnd: function() {
		if(Browser.Engine.trident) {
			var offset = (Browser.Engine.trident4)?3:2;
			var range = document.selection.createRange();
			if (range.compareEndPoints("StartToEnd", range) != 0) range.collapse(false);
			return range.getBookmark().charCodeAt(2) - offset;
		}
		return this.selectionEnd;
	},
	getSelectedRange: function() {
		return {
			start: this.getSelectionStart(),
			end: this.getSelectionEnd()
		}
	},
	setCaretPosition: function(pos) {
		if(pos == 'end') pos = this.get('value').length;
		this.selectRange(pos, pos);
		return this;
	},
	getCaretPosition: function() {
		return this.getSelectedRange().start;
	},
	selectRange: function(start, end) {
		this.focus();
		if(Browser.Engine.trident) {
			var range = this.createTextRange();
			range.collapse(true);
			range.moveStart('character', start);
			range.moveEnd('character', end - start);
			range.select();
			return this;
		}
		this.setSelectionRange(start, end);
		return this;
	},
	insertAtCursor: function(value, select) {
		var start = this.getSelectionStart();
		var end = this.getSelectionEnd();
		this.set('value', this.get('value').substring(0, start) + value + this.get('value').substring(end, this.get('value').length));
 		if($pick(select, true)) this.selectRange(start, start + value.length);
		else this.setCaretPosition(start + value.length);
		return this;
	},
	insertAroundCursor: function(options, select) {
		options = $extend({
			before: '',
			defaultMiddle: 'SOMETHING HERE',
			after: ''
		}, options);
		value = this.getSelectedText() || options.defaultMiddle;
		var start = this.getSelectionStart();
		var end = this.getSelectionEnd();
		if(start == end) {
			var text = this.get('value');
			this.set('value', text.substring(0, start) + options.before + value + options.after + text.substring(end, text.length));
			this.selectRange(start + options.before.length, end + options.before.length + value.length);
			text = null;
		} else {
			text = this.get('value').substring(start, end);
			this.set('value', this.get('value').substring(0, start) + options.before + text + options.after + this.get('value').substring(end, this.get('value').length));
			var selStart = start + options.before.length;
			if($pick(select, true)) this.selectRange(selStart, selStart + text.length);
			else this.setCaretPosition(selStart + text.length);
		}	
		return this;
	}
});


Element.Properties.inputValue = {
 
    get: function(){
			 switch(this.get('tag')) {
			 	case 'select':
					vals = this.getSelected().map(function(op){ 
						var v = $pick(op.get('value'),op.get('text')); 
						return (v=="")?op.get('text'):v;
					});
					return this.get('multiple')?vals:vals[0];
				case 'input':
					switch(this.get('type')) {
						case 'checkbox':
							return this.get('checked')?this.get('value'):false;
						case 'radio':
							var checked;
							if (this.get('checked')) return this.get('value');
							$(this.getParent('form')||document.body).getElements('input').each(function(input){
								if (input.get('name') == this.get('name') && input.get('checked')) checked = input.get('value');
							}, this);
							return checked||null;
					}
			 	case 'input': case 'textarea':
					return this.get('value');
				default:
					return this.get('inputValue');
			 }
    },
 
    set: function(value){
			switch(this.get('tag')){
				case 'select':
					this.getElements('option').each(function(op){
						var v = $pick(op.get('value'), op.get('text'));
						if (v=="") v = op.get('text');
						op.set('selected', $splat(value).contains(v));
					});
					break;
				case 'input':
					if (['radio','checkbox'].contains(this.get('type'))) {
						this.set('checked', $type(value)=="boolean"?value:$splat(value).contains(this.get('value')));
						break;
					}
				case 'textarea': case 'input':
					this.set('value', value);
					break;
				default:
					this.set('inputValue', value);
			}
			return this;
    },
		
		erase: function() {
			switch(this.get('tag')) {
				case 'select':
					this.getElements('option').each(function(op) {
						op.set('selected', false);
					});
					break;
				case 'input':
					if (['radio','checkbox'].contains(this.get('type'))) {
						this.set('checked', false);
						break;
					}
				case 'input': case 'textarea':
					this.set('value', '');
					break;
				default:
					this.set('inputValue', '');
			}
			return this;
		}

};


/*
Script: Element.Measure.js
	Extends the Element native object to include methods useful in measuring dimensions.

License:
	http://clientside.cnet.com/wiki/cnet-libraries#license
*/

Element.implement({

	expose: function(){
		if (this.getStyle('display') != 'none') return $empty;
		var before = {};
		var styles = { visibility: 'hidden', display: 'block', position:'absolute' };
		//use this method instead of getStyles 
		$each(styles, function(value, style){
			before[style] = this.style[style]||'';
		}, this);
		//this.getStyles('visibility', 'display', 'position');
		this.setStyles(styles);
		return (function(){ this.setStyles(before); }).bind(this);
	},
	
	getDimensions: function(options) {
		options = $merge({computeSize: false},options);
		var dim = {};
		function getSize(el, options){
			return (options.computeSize)?el.getComputedSize(options):el.getSize();
		};
		if(this.getStyle('display') == 'none'){
			var restore = this.expose();
			dim = getSize(this, options); //works now, because the display isn't none
			restore(); //put it back where it was
		} else {
			try { //safari sometimes crashes here, so catch it
				dim = getSize(this, options);
			}catch(e){}
		}
		return $chk(dim.x)?$extend(dim, {width: dim.x, height: dim.y}):$extend(dim, {x: dim.width, y: dim.height});
	},
	
	getComputedSize: function(options){
		options = $merge({
			styles: ['padding','border'],
			plains: {height: ['top','bottom'], width: ['left','right']},
			mode: 'both'
		}, options);
		var size = {width: 0,height: 0};
		switch (options.mode){
			case 'vertical':
				delete size.width;
				delete options.plains.width;
				break;
			case 'horizontal':
				delete size.height;
				delete options.plains.height;
				break;
		};
		var getStyles = [];
		//this function might be useful in other places; perhaps it should be outside this function?
		$each(options.plains, function(plain, key){
			plain.each(function(edge){
				options.styles.each(function(style){
					getStyles.push((style=="border")?style+'-'+edge+'-'+'width':style+'-'+edge);
				});
			});
		});
		var styles = this.getStyles.apply(this, getStyles);
		var subtracted = [];
		$each(options.plains, function(plain, key){ //keys: width, height, plains: ['left','right'], ['top','bottom']
			size['total'+key.capitalize()] = 0;
			size['computed'+key.capitalize()] = 0;
			plain.each(function(edge){ //top, left, right, bottom
				size['computed'+edge.capitalize()] = 0;
				getStyles.each(function(style,i){ //padding, border, etc.
					//'padding-left'.test('left') size['totalWidth'] = size['width']+[padding-left]
					if(style.test(edge)) {
						styles[style] = styles[style].toInt(); //styles['padding-left'] = 5;
						if(isNaN(styles[style]))styles[style]=0;
						size['total'+key.capitalize()] = size['total'+key.capitalize()]+styles[style];
						size['computed'+edge.capitalize()] = size['computed'+edge.capitalize()]+styles[style];
					}
					//if width != width (so, padding-left, for instance), then subtract that from the total
					if(style.test(edge) && key!=style && 
						(style.test('border') || style.test('padding')) && !subtracted.contains(style)) {
						subtracted.push(style);
						size['computed'+key.capitalize()] = size['computed'+key.capitalize()]-styles[style];
					}
				});
			});
		});
		if($chk(size.width)) {
			size.width = size.width+this.offsetWidth+size.computedWidth;
			size.totalWidth = size.width + size.totalWidth;
			delete size.computedWidth;
		}
		if($chk(size.height)) {
			size.height = size.height+this.offsetHeight+size.computedHeight;
			size.totalHeight = size.height + size.totalHeight;
			delete size.computedHeight;
		}
		return $extend(styles, size);
	}
});


/*
Script: Element.MouseOvers.js

Collection of mouseover behaviours (images, class toggles, etc.).

License:
	http://clientside.cnet.com/wiki/cnet-libraries#license
*/

Element.implement({
	autoMouseOvers: function(options){
		options = $extend({
			outString: '_out',
			overString: '_over',
			cssOver: 'hover',
			cssOut: 'hoverOut',
			subSelector: '',
			applyToBoth: false
		}, options);
		return this.addEvents({
			mouseenter: function(){
				this.swapClass(options.cssOut, options.cssOver);
				dbug.log(this.src, options.outString);
				if (this.src && this.src.contains(options.outString))
					this.src = this.src.replace(options.outString, options.overString);
				if(options.applyToBoth && options.subSelector) {
					this.getElements(options.subSelector).each(function(el){
						el.swapClass(options.cssOut, options.cssOver);
					});
				}
			},
			mouseleave: function(){
				this.swapClass(options.cssOver, options.cssOut);
				if (this.src && this.src.contains(options.overString))
					this.src = this.src.replace(options.overString, options.outString);
				if(options.applyToBoth && options.subSelector) {
					this.getElements(options.subSelector).each(function(el){
						el.swapClass(options.cssOver, options.cssOut);
					});
				}
			}
		}).swapClass(options.cssOver, options.cssOut);
	}
});
window.addEvent('domready', function(){
	$$('img.autoMouseOver').each(function(img){
		img.autoMouseOvers();
	});
});


/*
Script: Element.Pin.js
	Extends the Element native object to include the pin method useful for fixed positioning for elements.

License:
	http://clientside.cnet.com/wiki/cnet-libraries#license
*/

window.addEvent('domready', function(){
	var test = new Element('div').setStyles({
		position: 'fixed',
		top: 0,
		right: 0
	}).inject(document.body);
	var supported = (test.offsetTop === 0);
	test.dispose();
	Browser.set('supportsPositionFixed', supported);
});

Element.implement({
	pin: function(enable){
		if (this.getStyle('display') == 'none') {
			dbug.log('cannot pin ' + this + ' because it is hidden');
			return;
		}
		var p = this.getPosition();
		if(enable!==false) {
			if(!this.get('pinned')) {
				var pos = {
					top: (p.y - window.getScroll().y),
					left: (p.x - window.getScroll().x)
				};
				if(Browser.get('supportsPositionFixed')) {
					this.setStyle('position','fixed').setStyles(pos);
				} else {
					this.setStyles({
						position: 'absolute',
						top: p.y,
						left: p.x
					});
					window.addEvent('scroll', function(){
						if(this.get('pinned')) {
							var to = {
								top: (pos.top.toInt() + window.getScroll().y),
								left: (pos.left.toInt() + window.getScroll().x)
							};
							this.setStyles(to);
						}
					}.bind(this));
				}
				this.set('pinned', true);
			}
		} else {
			this.set('pinned', false);
			var reposition = (Browser.get('supportsPositionFixed'))?
				{
					top: (p.y + window.getScroll().y),
					left: (p.x + window.getScroll().x)
				}:
				{
					top: (p.y),
					left: (p.x)
				};
			this.setStyles($merge(reposition, {position: 'absolute'}));
		}
		return this;
	},
	unpin: function(){
		return this.pin(false);
	},
	togglepin: function(){
		this.pin(!this.pinned);
	}
});


/*
Script: Element.Position.js
        Extends the Element native object to include methods useful positioning elements relative to others.


License:
        http://www.clientcide.com/wiki/cnet-libraries#license
*/


Element.implement({


        setPosition: function(options){
                $each(options||{}, function(v, k){ if (!$defined(v)) delete options[k]; });
                options = $merge({
                        relativeTo: document.body,
                        position: {
                                x: 'center', //left, center, right
                                y: 'center' //top, center, bottom
                        },
                        edge: false,
                        offset: {x:0,y:0},
                        returnPos: false,
                        relFixedPosition: false,
                        ignoreMargins: false,
                        allowNegative: false
                }, options);
                //compute the offset of the parent positioned element if this element is in one
                var parentOffset = {x: 0, y: 0};
                var parentPositioned = false;
                var putItBack = this.expose();
    /* dollar around getOffsetParent should not be necessary, but as it does not return 
     * a mootools extended element in IE, an error occurs on the call to expose. See:
                 * http://mootools.lighthouseapp.com/projects/2706/tickets/333-element-getoffsetparent-inconsistency-between-ie-and-other-browsers */
                var offsetParent = $(this.getOffsetParent());
                putItBack();
                if(offsetParent && offsetParent != this.getDocument().body) {
                        var putItBack = offsetParent.expose();
                        parentOffset = offsetParent.getPosition();
                        putItBack();
                        parentPositioned = true;
                        options.offset.x = options.offset.x - parentOffset.x;
                        options.offset.y = options.offset.y - parentOffset.y;
                }
                //upperRight, bottomRight, centerRight, upperLeft, bottomLeft, centerLeft
                //topRight, topLeft, centerTop, centerBottom, center
                function fixValue(option) {
                        if($type(option) != "string") return option;
                        option = option.toLowerCase();
                        var val = {};
                        if(option.test('left')) val.x = 'left';
                        else if(option.test('right')) val.x = 'right';
                        else val.x = 'center';


                        if(option.test('upper')||option.test('top')) val.y = 'top';
                        else if (option.test('bottom')) val.y = 'bottom';
                        else val.y = 'center';
                        return val;
                };
                options.edge = fixValue(options.edge);
                options.position = fixValue(options.position);
                if(!options.edge) {
                        if(options.position.x == 'center' && options.position.y == 'center') options.edge = {x:'center',y:'center'};
                        else options.edge = {x:'left',y:'top'};
                }
                
                this.setStyle('position', 'absolute');
                var rel = $(options.relativeTo) || document.body;
                var top = (rel == document.body)?window.getScroll().y:rel.getPosition().y;
                var left = (rel == document.body)?window.getScroll().x:rel.getPosition().x;
                var dim = this.getDimensions({computeSize: true, styles:['padding', 'border','margin']});
                if (options.ignoreMargins) {
                        options.offset.x = options.offset.x - dim['margin-left'];
                        options.offset.y = options.offset.y - dim['margin-top'];
                }
                var pos = {};
                var prefY = options.offset.y.toInt();
                var prefX = options.offset.x.toInt();
                switch(options.position.x) {
                        case 'left':
                                pos.x = left + prefX;
                                break;
                        case 'right':
                                pos.x = left + prefX + rel.offsetWidth;
                                break;
                        default: //center
                                pos.x = left + (((rel == document.body)?window.getSize().x:rel.offsetWidth)/2) + prefX;
                                break;
                };
                switch(options.position.y) {
                        case 'top':
                                pos.y = top + prefY;
                                break;
                        case 'bottom':
                                pos.y = top + prefY + rel.offsetHeight;
                                break;
                        default: //center
                                pos.y = top + (((rel == document.body)?window.getSize().y:rel.offsetHeight)/2) + prefY;
                                break;
                };
                
                if(options.edge){
                        var edgeOffset = {};
                        
                        switch(options.edge.x) {
                                case 'left':
                                        edgeOffset.x = 0;
                                        break;
                                case 'right':
                                        edgeOffset.x = -dim.x-dim.computedRight-dim.computedLeft;
                                        break;
                                default: //center
                                        edgeOffset.x = -(dim.x/2);
                                        break;
                        };
                        switch(options.edge.y) {
                                case 'top':
                                        edgeOffset.y = 0;
                                        break;
                                case 'bottom':
                                        edgeOffset.y = -dim.y-dim.computedTop-dim.computedBottom;
                                        break;
                                default: //center
                                        edgeOffset.y = -(dim.y/2);
                                        break;
                        };
                        pos.x = pos.x+edgeOffset.x;
                        pos.y = pos.y+edgeOffset.y;
                }
                pos = {
                        left: ((pos.x >= 0 || parentPositioned || options.allowNegative)?pos.x:0).toInt(),
                        top: ((pos.y >= 0 || parentPositioned || options.allowNegative)?pos.y:0).toInt()
                };
                if(rel.getStyle('position') == "fixed"||options.relFixedPosition) {
                        pos.top = pos.top.toInt() + window.getScroll().y;
                        pos.left = pos.left.toInt() + window.getScroll().x;
                }


                if(options.returnPos) return pos;
                else this.setStyles(pos);
                return this;
        }
});
/*
Script: Element.Shortcuts.js
	Extends the Element native object to include some shortcut methods.

License:
	http://clientside.cnet.com/wiki/cnet-libraries#license
*/

Element.implement({
	isVisible: function() {
		return this.getStyle('display') != 'none';
	},
	toggle: function() {
		return this[this.isVisible() ? 'hide' : 'show']();
	},
	hide: function() {
		var d;
		try {
			//IE fails here if the element is not in the dom
			d = this.getStyle('display');
		} catch(e){}
		this.store('originalDisplay', d||'block'); 
		this.setStyle('display','none');
		return this;
	},
	show: function(display) {
		original = this.retrieve('originalDisplay')?this.retrieve('originalDisplay'):this.get('originalDisplay');
		this.setStyle('display',(display || original || 'block'));
		return this;
	},
  swapClass: function(remove, add) {
    return this.removeClass(remove).addClass(add);
  },
	//TODO
	//DO NOT USE THIS METHOD
	//it is temporary, as Mootools 1.1 will negate its requirement
	fxOpacityOk: function(){
		return !Browser.Engine.trident4;
	}
});

/*
Script: Fx.Marquee.js
	Defines Fx.Marquee, a marquee class for animated notifications.

License:
	http://clientside.cnet.com/wiki/cnet-libraries#license
*/
Fx.Marquee = new Class({
	Extends: Fx.Morph,
	options: {
		mode: 'horizontal', //or vertical
		message: '', //the message to display
		revert: true, //revert back to the previous message after a specified time
		delay: 5000, //how long to wait before reverting
		cssClass: 'msg', //the css class to apply to that message
		showEffect: { opacity: 1 },
		hideEffect: {opacity: 0},
		revertEffect: { opacity: [0,1] },
		currentMessage: null
/*	onRevert: $empty,
		onMessage: $empty */
	},
	initialize: function(container, options){
		container = $(container); 
		var msg = this.options.currentMessage || (container.getChildren().length == 1)?container.getFirst():''; 
		var wrapper = new Element('div', {	
				styles: { position: 'relative' },
				'class':'fxMarqueeWrapper'
			}).inject(container); 
		this.parent(wrapper, options);
		this.current = this.wrapMessage(msg);
	},
	wrapMessage: function(msg){
		if($(msg) && $(msg).hasClass('fxMarquee')) { //already set up
			var wrapper = $(msg);
		} else {
			//create the wrapper
			var wrapper = new Element('span', {
				'class':'fxMarquee',
				styles: {
					position: 'relative'
				}
			});
			if($(msg)) wrapper.grab($(msg)); //if the message is a dom element, inject it inside the wrapper
			else if ($type(msg) == "string") wrapper.set('html', msg); //else set it's value as the inner html
		}
		return wrapper.inject(this.element); //insert it into the container
	},
	announce: function(options) {
		this.setOptions(options).showMessage();
		return this;
	},
	showMessage: function(reverting){
		//delay the fuction if we're reverting
		(function(){
			//store a copy of the current chained functions
			var chain = this.$chain?$A(this.$chain):[];
			//clear teh chain
			this.clearChain();
			this.element = $(this.element);
			this.current = $(this.current);
			this.message = $(this.message);
			//execute the hide effect
			this.start(this.options.hideEffect).chain(function(){
				//if we're reverting, hide the message and show the original
				if(reverting) {
					this.message.hide();
					if(this.current) this.current.show();
				} else {
					//else we're showing; remove the current message
					if(this.message) this.message.dispose();
					//create a new one with the message supplied
					this.message = this.wrapMessage(this.options.message);
					//hide the current message
					if(this.current) this.current.hide();
				}
				//if we're reverting, execute the revert effect, else the show effect
				this.start((reverting)?this.options.revertEffect:this.options.showEffect).chain(function(){
					//merge the chains we set aside back into this.$chain
					if (this.$chain) this.$chain.combine(chain);
					else this.$chain = chain;
					this.fireEvent((reverting)?'onRevert':'onMessage');
					//then, if we're reverting, show the original message
					if(!reverting && this.options.revert) this.showMessage(true);
					//if we're done, call the chain stack
					else this.callChain.delay(this.options.delay, this);
				}.bind(this));
			}.bind(this));
		}).delay((reverting)?this.options.delay:10, this);
		return this;
	}
});

/*
Script: Fx.Move.js
	Defines Fx.Move, a class that works with Element.Position.js to transition an element from one location to another.

License:
	http://clientside.cnet.com/wiki/cnet-libraries#license
*/
Fx.Move = new Class({
	Extends: Fx.Morph,
	options: {
		relativeTo: document.body,
		position: 'center',
		edge: false,
		offset: {x:0,y:0}
	},
	start: function(destination){
		return this.parent(this.element.setPosition($merge(this.options, destination, {returnPos: true})));
	}
});

Element.Properties.move = {

	set: function(options){
		var morph = this.retrieve('move');
		if (morph) morph.cancel();
		return this.eliminate('move').store('move:options', $extend({link: 'cancel'}, options));
	},

	get: function(options){
		if (options || !this.retrieve('move')){
			if (options || !this.retrieve('move:options')) this.set('move', options);
			this.store('move', new Fx.Move(this, this.retrieve('move:options')));
		}
		return this.retrieve('move');
	}

};

Element.implement({

	move: function(options){
		this.get('move').start(options);
		return this;
	}

});


/*
Script: Fx.Reveal.js
	Defines Fx.Reveal, a class that shows and hides elements with a transition.

License:
	http://clientside.cnet.com/wiki/cnet-libraries#license
*/
Fx.Reveal = new Class({
	Extends: Fx.Morph,
	options: {
		styles: ['padding','border','margin'],
		transitionOpacity: true,
		mode:'vertical',
		heightOverride: null,
		widthOverride: null
	},
	dissolve: function(){
		try {
			if(!this.hiding && !this.showing) {
				if(this.element.getStyle('display') != 'none'){
					this.hiding = true;
					this.showing = false;
					this.hidden = true;
					var startStyles = this.element.getComputedSize({
						styles: this.options.styles,
						mode: this.options.mode
					});
					var setToAuto = this.element.style.height === ""||this.element.style.height=="auto";
					this.element.setStyle('display', 'block');
					if (this.element.fxOpacityOk() && this.options.transitionOpacity) startStyles.opacity = 1;
					var zero = {};
					$each(startStyles, function(style, name){
						zero[name] = [style, 0]; 
					}, this);
					var overflowBefore = this.element.getStyle('overflow');
					this.element.setStyle('overflow', 'hidden');
					//put the final fx method at the front of the chain
					if (!this.$chain) this.$chain = [];
					this.$chain.unshift(function(){
						if(this.hidden) {
							this.hiding = false;
							$each(startStyles, function(style, name) {
								startStyles[name] = style;
							}, this);
							this.element.setStyles($merge({display: 'none', overflow: overflowBefore}, startStyles));
							if (setToAuto) this.element.setStyle('height', 'auto');
						}
						this.callChain();
					}.bind(this));
					this.start(zero);
				} else {
					this.callChain.delay(10, this);
					this.fireEvent('onComplete', this.element);
				}
			}
		} catch(e) {
			this.hiding = false;
			this.element.hide();
			this.callChain.delay(10, this);
			this.fireEvent('onComplete', this.element);
		}
		return this;
	},
	reveal: function(){
		try {
			if(!this.showing && !this.hiding) {
				if(this.element.getStyle('display') == "none" || 
					 this.element.getStyle('visiblity') == "hidden" || 
					 this.element.getStyle('opacity')==0){
					this.showing = true;
					this.hiding = false;
					this.hidden = false;
					//toggle display, but hide it
					var before = this.element.getStyles('visibility', 'display', 'position');
					this.element.setStyles({
						visibility: 'hidden',
						display: 'block',
						position:'absolute'
					});
					var setToAuto = this.element.style.height === ""||this.element.style.height=="auto";
					//enable opacity effects
					if(this.element.fxOpacityOk() && this.options.transitionOpacity) this.element.setStyle('opacity',0);
					//create the styles for the opened/visible state
					var startStyles = this.element.getComputedSize({
						styles: this.options.styles,
						mode: this.options.mode
					});
					//reset the styles back to hidden now
					this.element.setStyles(before);
					$each(startStyles, function(style, name) {
						startStyles[name] = style;
					}, this);
					//if we're overridding height/width
					if($chk(this.options.heightOverride)) startStyles['height'] = this.options.heightOverride.toInt();
					if($chk(this.options.widthOverride)) startStyles['width'] = this.options.widthOverride.toInt();
					if(this.element.fxOpacityOk() && this.options.transitionOpacity) startStyles.opacity = 1;
					//create the zero state for the beginning of the transition
					var zero = { 
						height: 0,
						display: 'block'
					};
					$each(startStyles, function(style, name){ zero[name] = 0 }, this);
					var overflowBefore = this.element.getStyle('overflow');
					//set to zero
					this.element.setStyles($merge(zero, {overflow: 'hidden'}));
					//start the effect
					this.start(startStyles);
					if (!this.$chain) this.$chain = [];
					this.$chain.unshift(function(){
						if (!this.options.heightOverride && setToAuto) {
							if (["vertical", "both"].contains(this.options.mode)) this.element.setStyle('height', 'auto');
							if (["width", "both"].contains(this.options.mode)) this.element.setStyle('width', 'auto');
						}
						if(!this.hidden) this.showing = false;
						this.element.setStyle('overflow', overflowBefore);
						this.callChain();
					}.bind(this));
				} else {
					this.callChain();
					this.fireEvent('onComplete', this.element);
				}
			}
		} catch(e) {
			this.element.setStyles({
				display: 'block',
				visiblity: 'visible',
				opacity: 1
			});
			this.showing = false;
			this.callChain.delay(10, this);
			this.fireEvent('onComplete', this.element);
		}
		return this;
	},
	toggle: function(){
		try {
			if(this.element.getStyle('display') == "none" || 
				 this.element.getStyle('visiblity') == "hidden" || 
				 this.element.getStyle('opacity')==0){
				this.reveal();
		 	} else {
				this.dissolve();
			}
		} catch(e) { this.show(); }
	 return this;
	}
});

Element.Properties.reveal = {

	set: function(options){
		var reveal = this.retrieve('reveal');
		if (reveal) reveal.cancel();
		return this.eliminate('reveal').store('reveal:options', $extend({link: 'cancel'}, options));
	},

	get: function(options){
		if (options || !this.retrieve('reveal')){
			if (options || !this.retrieve('reveal:options')) this.set('reveal', options);
			this.store('reveal', new Fx.Reveal(this, this.retrieve('reveal:options')));
		}
		return this.retrieve('reveal');
	}

};

Element.Properties.dissolve = Element.Properties.reveal;

Element.implement({

	reveal: function(options){
		this.get('reveal', options).reveal();
		return this;
	},
	
	dissolve: function(options){
		this.get('reveal', options).dissolve();
		return this;
	}

});


/*
Script: Fx.Sort.js
	Defines Fx.Sort, a class that reorders lists with a transition.

License:
	http://clientside.cnet.com/wiki/cnet-libraries#license
*/
Fx.Sort = new Class({
	Extends: Fx.Elements,
	options: {
			mode: 'vertical' //or 'horizontal'
	},
	initialize: function(elements, options){
			this.parent(elements, options);
			//set the position of each element to relative
			this.elements.each(function(el){
					if(el.getStyle('position') == 'static') el.setStyle('position', 'relative');
			});
			this.setDefaultOrder();
	},
	setDefaultOrder: function(){
			this.currentOrder = this.elements.map(function(el, index){
				return index;
			});
	},
	sort: function(newOrder){
		if($type(newOrder) != 'array') return false;
		var top = 0;
		var left = 0;
		var zero = {};
		var vert = this.options.mode == "vertical";
		//calculate the current location of all the elements
		var current = this.elements.map(function(el, index){
			var size = el.getComputedSize({styles:['border','padding','margin']});
			var val;
			if(vert) {
				val =	{
					top: top,
					margin: size['margin-top'],
					height: size.totalHeight
				};
				top += val.height - size['margin-top'];
			} else {
				val = {
					left: left,
					margin: size['margin-left'],
					width: size.totalWidth
				};
				left += val.width;
			}
			var plain = vert?'top':'left';
			zero[index]={};
			var start = el.getStyle(plain).toInt();
			zero[index][plain] = ($chk(start))?start:0;
			return val;
		}, this);
		this.set(zero);
		//if the array passed in is not the same size as
		//the amount of elements we have, fill it in
		//or cut it short
		newOrder = newOrder.map(function(i){ return i.toInt() });
		if (newOrder.length != this.elements.length){
			this.currentOrder.each(function(index) {
				if(!newOrder.contains(index)) newOrder.push(index);
			});
			if(newOrder.length > this.elements.length) {
				newOrder.splice(this.elements.length-1, newOrder.length-this.elements.length);
			}
		}
		var top = 0;
		var left = 0;
		var margin = 0;
		var next = {};
		//calculate the new location of each item
		newOrder.each(function(item, index){
			var newPos = {};
			if(vert) {
					newPos.top = top - current[item].top - margin;
					top += current[item].height;
			} else {
					newPos.left = left - current[item].left;	
					left += current[item].width;
			}
			margin = margin + current[item].margin;
			next[item]=newPos;
		}, this);
		var mapped = {};
		$A(newOrder).sort().each(function(index){
			mapped[index] = next[index];
		});
		//store the current order
		//execute the effect
		this.start(mapped);
		this.currentOrder = newOrder;
		return this;
	},
	rearrangeDOM: function(newOrder){
		newOrder = newOrder || this.currentOrder;
		var parent = this.elements[0].getParent();
		var rearranged = [];
		this.elements.setStyle('opacity', 0);
		//move each element and store the new default order
		newOrder.each(function(index) {
			rearranged.push(this.elements[index].inject(parent).setStyles({
				top: 0,
				left: 0
			}));
		}, this);
		this.elements.setStyle('opacity', 1);
		this.elements = $$(rearranged);
		this.setDefaultOrder();
		return this;
	},
	getDefaultOrder: function(){
		return this.elements.map(function(el, index) {
			return index;
		})
	},
	forward: function(){
		return this.sort(this.getDefaultOrder());
	},
	backward: function(){
		return this.sort(this.getDefaultOrder().reverse());
	},
	reverse: function(){
		return this.sort(this.currentOrder.reverse());
	},
	sortByElements: function(elements){
		return this.sort(elements.map(function(el){
			return this.elements.indexOf(el);
		}));
	},
	swap: function(one, two) {
		if($type(one) == 'element') {
			one = this.elements.indexOf(one);
			two = this.elements.indexOf(two);
		}
		var indexOne = this.currentOrder.indexOf(one);
		var indexTwo = this.currentOrder.indexOf(two);
		var newOrder = $A(this.currentOrder);
		newOrder[indexOne] = two;
		newOrder[indexTwo] = one;
		this.sort(newOrder);
	}
});


/*
Script: JsonP.js
	Defines JsonP, a class for cross domain javascript via script injection.

License:
	http://clientside.cnet.com/wiki/cnet-libraries#license
*/
var JsonP = new Class({
	Implements: [Options, Events],
	options: {
//	onComplete: $empty,
		callBackKey: "callback",
		queryString: "",
		data: {},
		timeout: 5000,
		retries: 0
	},
	initialize: function(url, options){
		this.setOptions(options);
		this.url = this.makeUrl(url).url;
		this.fired = false;
		this.scripts = [];
		this.requests = 0;
		this.triesRemaining = [];
	},
	request: function(url, requestIndex){
		var u = this.makeUrl(url);
		if(!$chk(requestIndex)) {
			requestIndex = this.requests;
			this.requests++;
		}
		if(!$chk(this.triesRemaining[requestIndex])) this.triesRemaining[requestIndex] = this.options.retries;
		var remaining = this.triesRemaining[requestIndex]; //saving bytes
		dbug.log('retrieving by json script method: %s', u.url);
		var dl = (Browser.Engine.trident)?50:0; //for some reason, IE needs a moment here...
		(function(){
			var script = new Element('script', {
				src: u.url, 
				type: 'text/javascript',
				id: 'jsonp_'+u.index+'_'+requestIndex
			});
			this.fired = true;
			this.addEvent('onComplete', function(){
				try {script.dispose();}catch(e){}
			}.bind(this));
			script.inject(document.head);

			if(remaining) {
				(function(){
					this.triesRemaining[requestIndex] = remaining - 1;
					if(script.getParent() && remaining) {
						dbug.log('removing script (%o) and retrying: try: %s, remaining: %s', requestIndex, remaining);
						script.dispose();
						this.request(url, requestIndex);
					}
				}).delay(this.options.timeout, this);
			}
		}.bind(this)).delay(dl);
		return this;
	},
	makeUrl: function(url){
		var index = (JsonP.requestors.contains(this))?
								JsonP.requestors.indexOf(this):
								JsonP.requestors.push(this) - 1;
		if(url) {
			var separator = (url.test('\\?'))?'&':'?';
			var jurl = url + separator + this.options.callBackKey + "=JsonP.requestors[" +
				index+"].handleResults";
			if(this.options.queryString) jurl += "&"+this.options.queryString;
			jurl += "&"+Hash.toQueryString(this.options.data);
		} else var jurl = this.url;
		return {url: jurl, index: index};
	},
	handleResults: function(data){
		dbug.log('jsonp received: ', data);
		this.fireEvent('onComplete', [data, this]);
	}
});
JsonP.requestors = [];

/*
Script: ErrorAlert.js
	Defines errorAlert, a simple little alert box with a close button.

License:
	http://clientside.cnet.com/wiki/cnet-libraries#license
*/
var errorAlert = function(msghdr, msg, baseHref) {
	baseHref = baseHref||"templates/"+DESIGN+"/images/javascripts/simple.error.popup";
	msg = '<p class="errorMsg SWclearfix">' +
						'<img src="'+baseHref+'/icon_problems_sm.gif"'+
						' class="bang clearfix" style="float: left; width: 30px; height: 30px; margin: 3px 5px 5px 0px;">'
						 + msg + '</p>';
	var body = StickyWin.ui(msghdr, msg, {width: 250});
	return new StickyWinModal({
		modalOptions: {
			modalStyle: {
				zIndex: 11000
			}
		},
		zIndex: 110001,
		content: body,
		position: 'center' //center, corner
	});
};

/*
Script: modalizer.js
	Defines Modalizer: functionality to overlay the window contents with a semi-transparent layer that prevents interaction with page content until it is removed

License:
	http://clientside.cnet.com/wiki/cnet-libraries#license
*/
var Modalizer = new Class({
	defaultModalStyle: {
		display:'block',
		position:'fixed',
		top:0,
		left:0,	
		'z-index':5000,
		'background-color':'#333',
		opacity:0.8
	},
	setModalOptions: function(options){
		this.modalOptions = $merge({
			width:(window.getScrollSize().x+300),
			height:(window.getScrollSize().y+300),
			elementsToHide: 'select',
			onModalHide: $empty,
			onModalShow: $empty,
			hideOnClick: true,
			modalStyle: {},
			updateOnResize: true
		}, this.modalOptions, options);
		return this;
	},
	toElement: function(){
		return $('modalOverlay');
	},
	resize: function(){
		if($('modalOverlay')) {
			$('modalOverlay').setStyles({
				width:(window.getScrollSize().x+300),
				height:(window.getScrollSize().y+300)
			});
		}
	},
	setModalStyle: function (styleObject){
		this.modalOptions.modalStyle = styleObject;
		this.modalStyle = $merge(this.defaultModalStyle, {
			width:this.modalOptions.width,
			height:this.modalOptions.height
		}, styleObject);
		if($('modalOverlay')) $('modalOverlay').setStyles(this.modalStyle);
		return(this.modalStyle);
	},
	modalShow: function(options){
		this.setModalOptions(options);
		var overlay = null;
		if($('modalOverlay')) overlay = $('modalOverlay');
		if(!overlay) overlay = new Element('div', {id: 'modalOverlay'}).inject(document.body);
		overlay.setStyles(this.setModalStyle(this.modalOptions.modalStyle));
		if(Browser.Engine.trident4) overlay.setStyle('position','absolute');
		$('modalOverlay').removeEvents('click').addEvent('click', function(){
			this.modalHide(this.modalOptions.hideOnClick);
		}.bind(this));
		this.bound = this.bound||{};
		if(!this.bound.resize && this.modalOptions.updateOnResize) {
			this.bound.resize = this.resize.bind(this);
			window.addEvent('resize', this.bound.resize);
		}
		if ($type(this.modalOptions.onModalShow)  == "function") this.modalOptions.onModalShow();
		this.togglePopThroughElements(0);
		overlay.setStyle('display','block');
		return this;
	},
	modalHide: function(override){
		if(override === false) return false; //this is internal, you don't need to pass in an argument
		this.togglePopThroughElements(1);
		if ($type(this.modalOptions.onModalHide) == "function") this.modalOptions.onModalHide();
		if($('modalOverlay'))$('modalOverlay').setStyle('display','none');
		if(this.modalOptions.updateOnResize) {
			this.bound = this.bound||{};
			if(!this.bound.resize) this.bound.resize = this.resize.bind(this);
			window.removeEvent('resize', this.bound.resize);
		}
		return this;
	},
	togglePopThroughElements: function(opacity){
		if(Browser.Engine.trident4 || (Browser.Engine.gecko && Browser.Platform.mac)) {
			$$(this.modalOptions.elementsToHide).each(function(sel){
				sel.setStyle('opacity', opacity);
			});
		}
	}
});


/*
Script: ObjectBrowser.js
	Creates a tree view of any javascript object.

License:
	http://clientside.cnet.com/wiki/cnet-libraries#license
*/

var ObjectBrowser = new Class({
	Implements: [Options, Events],
	options: {
//	onLeafClick: $empty,
		onBranchClick: function(data){
			this.showLevel(data.path?data.path+'.'+data.key:data.key, data.nodePath);
		},
		initPath: '',
		buildOnInit: true,
		data: {},
		excludeKeys: [],
		includeKeys: []
	},
	initialize: function(container, options){
		this.container = $(container);
		this.setOptions(options);
		this.data = $H(this.options.data);
		this.levels = {};
		this.elements = {};
		if(this.options.buildOnInit) this.showLevel(this.options.initPath, this.container);
	},
	toElement: function(){
		return this.container;
	},
	//gets a member of the object by path; eg "fruits.apples.green" will return the value at that path.
	//path - the string path
	//parent - (boolean) if true, will return the parent of the item found ( in the example above, fruits.apples)
	getMemberByPath: function(path, parent){
		if (path === "" || path == "top") return this.data.getClean();
		var h = parent?$H(parent):this.data;
		return h.getFromPath(path);
	},
	//replaceMemberByPath will set the location at the path to the value passed in
	replaceMemberByPath: function(path, value){
		if (path === "" || path == "top") return this.data = $H(value);
		var parentObj = this.getMemberByPath( path, true );
		parentObj[path.split(".").pop()] = value;
		return this.data;
	},
	//gets the path for a given dom node.
	getPathByNode: function(el) {
		return $H(this.elements).keyOf(el);
	},
	//validates that a key is a valid node value
	//against options.includeKeys and options.excludeKeys
	validLevel: function(key){
		return (!this.options.excludeKeys.contains(key) && 
			 (!this.options.includeKeys.length || this.options.includeKeys.contains(key)));
	},
	//builds a level into the interface given a path
	buildLevel:function(path) {
		//if the path ends in a dot, remove it
		if (path.test(".$")) path = path.substring(0, path.length);
		//get the corresponding level for the path
		var level = this.getMemberByPath(path);
		//if the path already has been built, return
		if (this.levels[path]) return this.levels[path];
		//create the section
		var section = new Element('ul');
		switch($type(level)) {
			case "function":
					this.buildNode(level, "function()", section, path, true);
				break;
			case "string": case "number":
					this.buildNode(level, null, section, path, true);
				break;
			case "array":
				level.each(function(node, index){
					this.buildNode(node, index, section, path, ["string", "function"].contains($type(node)));
				}.bind(this));
				break;
			default:
				$H(level).each(function(value, key){
					var db = false;
					if (key == "element_dimensions") db = true;
					if (db) dbug.log(key);
					if (this.validLevel(key)) {
						if (db) dbug.log('is valid level');
						var isLeaf;
						if ($type(value) == "object") {
							isLeaf = false;
							$each(value, function(v, k){
								if (this.validLevel(k)) {
									if (db) dbug.log('not a leaf!');
									isLeaf = false;
								} else {
									isLeaf = true;
								}
							}, this);
							if (isLeaf) value = false;
						}
						if (db) dbug.log(value, key, section, path, $chk(isLeaf)?isLeaf:null);
						this.buildNode(value, key, section, path, $chk(isLeaf)?isLeaf:null);
					}
				}, this);
		}
		//set the resulting DOM element to the levels map
		this.levels[path] = section;
		//return the section
		return section;
	},
	//gets the parent node for an element
	getParentFromPath: function(path){
		return this.elements[(path || "top")+'NODE'];
	},
	//displays a level given a path
	//if the level hasn't been built yet,
	//the level is built and then injected
	//into the target using the given method
	//example:
	//showLevel("fruit.apples", "fruit", "injectInside");
	//note that target and method are set to the parent path and injectInside by default
	showLevel: function(path, target, method){
		target = target || path;
		if (! this.elements[path]) 
			this.elements[path] = this.buildLevel(path)[method||"inject"](this.elements[target]||this.container);
		else this.elements[path].toggle();
		dbug.log('toggle class');
		this.elements[path].getParent().toggleClass('collapsed');
		return this;
	},
	//builds a node given the arguments:
	//value - the value of the node
	//key - the key of the node
	//section - the container where this node goes; typically a section generated by buildLevel
	//path - the path to this node
	//leaf - boolean; true if this is a leaf node
	//note: if the key or the value is an empty string, leaf will be set to true.
	buildNode: function(value, key, section, path, leaf){
		if (key==="" || value==="") leaf = true;
		if(!this.validLevel(key)) return null;
		var nodePath = (path?path+'.'+key:key)+'NODE';
		var lnk = this.buildLink((leaf)?value||key:$chk(key)?key:value, leaf);
		var li = new Element('li').addClass((leaf)?'leaf':'branch collapsed').adopt(lnk).inject(section);
		lnk.addEvent('click', function(e){
			e.stopPropagation();
			if (leaf) {
				this.fireEvent('onLeafClick', {
					li: li, 
					key: key, 
					value: value, 
					path: path,
					nodePath: nodePath,
					event: e
				});
			} else {
				this.fireEvent('onBranchClick', {
					li: li, 
					key: key, 
					value: value, 
					path: path,
					nodePath: nodePath,
					event: e
				});
			}							
		}.bind(this));
		this.elements[nodePath] = li;
		return li;
	},
	//builds a link for a given key
	buildLink: function(key) {
		if($type(key) == "function") {
			key = key.toString();
			key = key.substring(0, key.indexOf("{")+1)+"...";
		}
		return new Element('a', {
			href: "javascript: void(0);"
		}).set('html', key);
	}
});

/*
Script: PopupDetails.js
	Creates hover detail popups for a collection of elements and data.

License:
	http://clientside.cnet.com/wiki/cnet-libraries#license
*/
var PopupDetail = new Class({
	Implements: [Options, Events],
	visible: false,
	observed: false,
	hasData: false,
	options: {
		observerAction: 'mouseenter', //or click
		closeOnMouseOut: true,
		linkPopup: false, //or true to use observer href, or url
		data: {}, //key/value parse to parse in to html
		templateOptions: {}, //see simple template parser
		useAjax: false,
		ajaxOptions:{
			method: 'get'
		},
		ajaxLink: false, //defaults to use observer.src
		ajaxCache: {},
		delayOn: 100,
		delayOff: 100,
		stickyWinOptions:{},
//	stickyWinToUse: null,
		showNow: false,
		htmlResponse: false,
		regExp: /\\?%([^%]+)%/g
/*	onPopupShow: $empty,
		onPopupHide: $empty */
	},
	initialize: function(html, observer, options){
		this.setOptions(options);
		try {
			this.options.stickyWinToUse = this.options.stickyWinToUse || StickyWinFx;
		} catch(e) {
			this.options.stickyWinToUse = StickyWin;
		}
		this.observer = $(observer);
		this.html = ($(html))?$(html).get('html'):html||'';
		if(this.options.showNow) this.show.delay(this.options.delayOn, this);
		this.setUpObservers();
	},
	setUpObservers: function(){
		var opt = this.options; //saving bytes here
		this.observer.addEvent(opt.observerAction, function(){
			this.observed = true;
			this.show.delay(opt.delayOn, this);
		}.bind(this));
		if((opt.observerAction == "mouseenter" || opt.observerAction == "mouseover") && this.options.closeOnMouseOut){
			this.observer.addEvent("mouseleave", function(){
				this.observed = false;
				this.hide.delay(opt.delayOff, this);
			}.bind(this));
		}
		return this;
	},
	parseTemplate: function(string, values){
		return string.substitute(values, this.options.regExp);
	},
	makePopup: function(){
		if(!this.stickyWin){
			var opt = this.options;//saving bytes
			if (opt.htmlResponse) this.content = this.data;
			else this.content = this.parseTemplate(this.html, opt.data);
			this.stickyWin = new opt.stickyWinToUse($merge(opt.stickyWinOptions, {
				relativeTo: this.observer,
				showNow: false,
				content: this.content,
				allowMultipleByClass: true
			}));
			if($(opt.linkPopup) || $type(opt.linkPopup)=='string') {
				this.stickyWin.win.setStyle('cursor','pointer').addEvent('click', function(){
					window.location.href = ($type(url)=='string')?url:url.src;
				});
			}
			this.stickyWin.win.addEvent('mouseenter', function(){
				this.observed = true;
			}.bind(this));
			this.stickyWin.win.addEvent('mouseleave', function(){
				this.observed = false;
				if(opt.closeOnMouseOut) this.hide.delay(opt.delayOff, this);
			}.bind(this));
		}
		return this;
	},
	getContent: function(){
		try {
			new Request($merge(this.options.ajaxOptions, {
					url: this.options.ajaxLink || this.observer.href,
					onSuccess: this.show.bind(this)
				})
			).send();
		} catch(e) {
			dbug.log('ajax error on PopupDetail: %s', e);
		}
	},
	show: function(data){
		var opt = this.options;
		if(data) this.data = data;
		if(this.observed && !this.visible) {
			if(opt.useAjax && !this.data) {
				var cachedVal = opt.ajaxCache[this.options.ajaxLink] || opt.ajaxCache[this.observer.href];
				if (cachedVal) {
					this.fireEvent('onPopupShow', this);
					return this.show(cachedVal);
				}
				this.cursorStyle = this.observer.getStyle('cursor');
				this.observer.setStyle('cursor', 'wait');
				this.getContent();
				return false;
			} else {
				if(this.cursorStyle) this.observer.setStyle('cursor', this.cursorStyle);
				if(opt.useAjax && !opt.htmlResponse) opt.data = JSON.decode(this.data);
				this.makePopup();
				this.fireEvent('onPopupShow', this);
				this.stickyWin.show();
				this.visible = true;
				return this;
			}
		}
		return this;
	},
	hide: function(){
		if(!this.observed){
			this.fireEvent('onPopupHide');
			if(this.stickyWin)this.stickyWin.hide();
			this.visible = false;
		}
		return this;
	}
});

var PopupDetailCollection = new Class({
	Implements: [Options],
	options: {
		details: {},
		links: [],
		ajaxLinks: [],
		useCache: true,
		template: '',
		popupDetailOptions: {}
	},
	cache: {},
	initialize: function(observers, options) {
		this.observers = $$(observers);
		this.setOptions(options);
		var ln = this.options.ajaxLinks.length;
		if(ln <= 0) ln = this.options.details.length;
		if (this.observers.length != ln) 
			dbug.log("warning: observers and details are out of sync.");
		this.makePopupDetails();
	},
	makePopupDetails: function(){
		this.popupDetailObjs = this.observers.map(function(observer, index){
			var opt = this.options.popupDetailOptions;//saving bytes
			var pd = new PopupDetail(this.options.template, observer, $merge(opt, {
				data: $pick(this.options.details[index], {}),
				linkItem: $pick(this.options.links[index], $pick(opt.linkItem, false)),
				ajaxLink: $pick(this.options.ajaxLinks[index], false),
				ajaxCache: (this.options.useCache)?this.cache:{},
				useAjax: this.options.ajaxLinks.length>0
			}));
			return pd;
		}, this);
	}
});


/*
Script: StyleWriter.js

Provides a simple method for injecting a css style element into the DOM if it's not already present.

License:
	http://clientside.cnet.com/wiki/cnet-libraries#license
*/

var StyleWriter = new Class({
	createStyle: function(css, id) {
		window.addEvent('domready', function(){
			try {
				if($(id) && id) return;
				var style = new Element('style', {id: id||''}).inject($$('head')[0]);
				if (Browser.Engine.trident) style.styleSheet.cssText = css;
				else style.set('text', css);
			}catch(e){dbug.log('error: %s',e);}
		}.bind(this));
	}
});

/*
Script: StickyWin.js

Creates a div within the page with the specified contents at the location relative to the element you specify; basically an in-page popup maker.

License:
	http://clientside.cnet.com/wiki/cnet-libraries#license
*/

var StickyWin = new Class({
	Implements: [Options, Events, StyleWriter],
	options: {
//	onDisplay: $empty,
//	onClose: $empty,
		closeClassName: 'closeSticky',
		pinClassName: 'pinSticky',
		content: '',
		zIndex: 10000,
		className: '',
		//id: ... set above in initialize function
/*  these are the defaults for setPosition anyway
/************************************************
//		edge: false, //see Element.setPosition in element.cnet.js
//		position: 'center', //center, corner == upperLeft, upperRight, bottomLeft, bottomRight
//		offset: {x:0,y:0},
//	  relativeTo: document.body, */
		width: false,
		height: false,
		timeout: -1,
		allowMultipleByClass: false,
		allowMultiple: true,
		showNow: true,
		useIframeShim: true,
		iframeShimSelector: ''
	},
	css: '.SWclearfix:after {content: "."; display: block; height: 0; clear: both; visibility: hidden;}'+
			 '.SWclearfix {display: inline-table;}'+
			 '* html .SWclearfix {height: 1%;}'+
			 '.SWclearfix {display: block;}',
	initialize: function(options){
		this.setOptions(options);
		this.id = this.options.id || 'StickyWin_'+new Date().getTime();
		this.makeWindow();
		if(this.options.content) this.setContent(this.options.content);
		if(this.options.showNow) this.show();
		//add css for clearfix
		this.createStyle(this.css, 'StickyWinClearFix');
	},
	toElement: function() {
		return this.win;
	},
	makeWindow: function(){
		this.destroyOthers();
		if(!$(this.id)) {
			this.win = new Element('div', {
				id:		this.id
			}).addClass(this.options.className).addClass('StickyWinInstance').addClass('SWclearfix').setStyles({
			 	display:'none',
				position:'absolute',
				zIndex:this.options.zIndex
			}).inject(document.body).store('StickyWin', this);			
		} else this.win = $(this.id);
		if(this.options.width && $type(this.options.width.toInt())=="number") this.win.setStyle('width', this.options.width.toInt());
		if(this.options.height && $type(this.options.height.toInt())=="number") this.win.setStyle('height', this.options.height.toInt());
		return this;
	},
	show: function(){
		this.fireEvent('onDisplay');
		this.showWin();
		if(this.options.useIframeShim) this.showIframeShim();
		this.visible = true;
		return this;
	},
	showWin: function(){
		this.win.setStyle('display','block');
		if(!this.positioned) this.position();
	},
	hide: function(){
		this.fireEvent('onClose');
		this.hideWin();
		if(this.options.useIframeShim) this.hideIframeShim();
		this.visible = false;
		return this;
	},
	hideWin: function(){
		this.win.setStyle('display','none');
	},
	destroyOthers: function() {
		if(!this.options.allowMultipleByClass || !this.options.allowMultiple) {
			$$('div.StickyWinInstance').each(function(sw) {
				if(!this.options.allowMultiple || (!this.options.allowMultipleByClass && sw.hasClass(this.options.className))) 
					sw.dispose();
			}, this);
		}
	},
	setContent: function(html) {
		if(this.win.getChildren().length>0) this.win.empty();
		if($type(html) == "string") this.win.set('html', html);
		else if ($(html)) this.win.adopt(html);
		this.win.getElements('.'+this.options.closeClassName).each(function(el){
			el.addEvent('click', this.hide.bind(this));
		}, this);
		this.win.getElements('.'+this.options.pinClassName).each(function(el){
			el.addEvent('click', this.togglepin.bind(this));
		}, this);
		return this;
	},	
	position: function(){
		this.positioned = true;
		this.win.setPosition({
			relativeTo: this.options.relativeTo,
			position: this.options.position,
			offset: this.options.offset,
			edge: this.options.edge
		});
		if(this.shim) this.shim.position();
		return this;
	},
	pin: function(pin) {
		if(!this.win.pin) {
			dbug.log('you must include element.pin.js!');
			return this;
		}
		this.pinned = $pick(pin, true);
		this.win.pin(pin);
		return this;
	},
	unpin: function(){
		return this.pin(false);
	},
	togglepin: function(){
		return this.pin(!this.pinned);
	},
	makeIframeShim: function(){
		if(!this.shim){
			var el = (this.options.iframeShimSelector)?this.win.getElement(this.options.iframeShimSelector):this.win;
			this.shim = new IframeShim(el, {
				display: false,
				name: 'StickyWinShim'
			});
		}
	},
	showIframeShim: function(){
		if(this.options.useIframeShim) {
			this.makeIframeShim();
			this.shim.show();
		}
	},
	hideIframeShim: function(){
		if(this.options.useIframeShim)
			this.shim.hide();
	},
	destroy: function(){
		if (this.win) this.win.dispose();
		if(this.options.useIframeShim) this.shim.dispose();
		if($('modalOverlay'))$('modalOverlay').dispose();
	}
});


/*
Script: StickyWinFx.js

Extends StickyWin to create popups that fade in and out and can be dragged and resized (requires StickyWinFx.Drag.js).

License:
	http://clientside.cnet.com/wiki/cnet-libraries#license
*/
var StickyWinFx = new Class({
	Extends: StickyWin,
	options: {
		fade: true,
		fadeDuration: 150,
//	fadeTransition: 'sine:in:out',
		draggable: false,
		dragOptions: {},
		dragHandleSelector: '.dragHandle',
		resizable: false,
		resizeOptions: {},
		resizeHandleSelector: ''
	},
	setContent: function(html){
		this.parent(html);
		if(this.options.draggable) this.makeDraggable();
		if(this.options.resizable) this.makeResizable();
		return this;
	},	
	hideWin: function(){
		if(this.options.fade) this.fade(0);
		else this.parent();
	},
	showWin: function(){
		if(this.options.fade) this.fade(1);
		else this.parent();
	},
	fade: function(to){
		if(!this.fadeFx) {
			this.win.setStyles({
				opacity: 0,
				display: 'block'
			});
			var opts = {
				property: 'opacity',
				duration: this.options.fadeDuration
			};
			if (this.options.fadeTransition) opts.transition = this.options.fadeTransition;
			this.fadeFx = new Fx.Tween(this.win, opts);
		}
		if (to > 0) {
			this.win.setStyle('display','block');
			this.position();
		}
		this.fadeFx.clearChain();
		this.fadeFx.start(to).chain(function (){
			if(to == 0) this.win.setStyle('display', 'none');
		}.bind(this));
		return this;
	},
	makeDraggable: function(){
		dbug.log('you must include Drag.js, cannot make draggable');
	},
	makeResizable: function(){
		dbug.log('you must include Drag.js, cannot make resizable');
	}
});

/*
Script: StickyWinFx.Drag.js

Implements drag and resize functionaity into StickyWinFx. See StickyWinFx for the options.

License:
	http://clientside.cnet.com/wiki/cnet-libraries#license
*/

if(typeof Drag != "undefined"){
	StickyWinFx.implement({
		makeDraggable: function(){
			var toggled = this.toggleVisible(true);
			if(this.options.useIframeShim) {
				this.makeIframeShim();
				var onComplete = (this.options.dragOptions.onComplete || $empty);
				this.options.dragOptions.onComplete = function(){
					onComplete();
					this.shim.position();
				}.bind(this);
			}
			if(this.options.dragHandleSelector) {
				var handle = this.win.getElement(this.options.dragHandleSelector);
				if (handle) {
					handle.setStyle('cursor','move');
					this.options.dragOptions.handle = handle;
				}
			}
			this.win.makeDraggable(this.options.dragOptions);
			if (toggled) this.toggleVisible(false);
		}, 
		makeResizable: function(){
			var toggled = this.toggleVisible(true);
			if(this.options.useIframeShim) {
				this.makeIframeShim();
				var onComplete = (this.options.resizeOptions.onComplete || $empty);
				this.options.resizeOptions.onComplete = function(){
					onComplete();
					this.shim.position();
				}.bind(this);
			}
			if(this.options.resizeHandleSelector) {
				var handle = this.win.getElement(this.options.resizeHandleSelector);
				if(handle) this.options.resizeOptions.handle = this.win.getElement(this.options.resizeHandleSelector);
			}
			this.win.makeResizable(this.options.resizeOptions);
			if (toggled) this.toggleVisible(false);
		},
		toggleVisible: function(show){
			if(!this.visible && Browser.Engine.webkit && $pick(show, true)) {
				this.win.setStyles({
					display: 'block',
					opacity: 0
				});
				return true;
			} else if(!$pick(show, false)){
				this.win.setStyles({
					display: 'none',
					opacity: 1
				});
				return false;
			}
			return false;
		}
	});
}


/*
Script: StickyWin.Modal.js

This script extends StickyWin and StickyWinFx classes to add Modalizer functionality.

License:
	http://clientside.cnet.com/wiki/cnet-libraries#license
*/
var StickyWinModal, StickyWinFxModal;
(function(){
var modalWinBase = function(extend){
	return {
		Extends: extend,
		initialize: function(options){
			options = options||{};
			this.setModalOptions($merge(options.modalOptions||{}, {
				onModalHide: function(){
						this.hide(false);
					}.bind(this)
				}));
			this.parent(options);
		},
		show: function(showModal){
			if($pick(showModal, true)) {
				this.modalShow();
				this.win.getElements(this.modalOptions.elementsToHide).setStyle('opacity', 1);
			}
			this.parent();
		},
		hide: function(hideModal){
			if($pick(hideModal, true))this.modalHide();
			this.parent();
		}
	}
};
StickyWinModal = new Class(modalWinBase(StickyWin));
StickyWinModal.implement(new Modalizer);
StickyWinFxModal = (typeof StickyWinFx != "undefined")?new Class(modalWinBase(StickyWinFx)):$empty;
try { StickyWinFxModal.implement(new Modalizer()); }catch(e){}
})();

/*
Script: StickyWin.Ajaxjs

Adds ajax functionality to all the StickyWin classes.

License:
	http://clientside.cnet.com/wiki/cnet-libraries#license
*/
(function(){
	var SWA = function(extend){
		return {
			Extends: extend,
			options: {
				url: '',
				showNow: false,
				requestOptions: {
					method: 'get'
				},
				wrapWithUi: false, 
				caption: '',
				uiOptions:{},
				handleResponse: function(response){
					var responseScript;
					this.Request.response.text.stripScripts(function(script){	responseScript = script; });
					if(this.options.wrapWithUi) response = StickyWin.ui(this.options.caption, response, this.options.uiOptions);
					this.setContent(response);
					this.show();
					if (this.evalScripts) $exec(responseScript);
				}
			},
			initialize: function(options){
				this.parent(options);
				this.evalScripts = this.options.requestOptions.evalScripts;
				this.options.requestOptions.evalScripts = false;
				this.createRequest();
			},
			createRequest: function(){
				this.Request = new Request(this.options.requestOptions).addEvent('onSuccess',
					this.options.handleResponse.bind(this));
			},
			update: function(url, options){
				this.Request.setOptions(options).send({url: url||this.options.url});
				return this;
			}
		};
	};
	try {	StickyWin.Ajax = new Class(SWA(StickyWin)); } catch(e){}
	try {	StickyWinFx.Ajax = new Class(SWA(StickyWinFx)); } catch(e){}
	try {	StickyWinModal.Ajax = new Class(SWA(StickyWinModal)); } catch(e){}
	try {	StickyWinFxModal.Ajax = new Class(SWA(StickyWinFxModal)); } catch(e){}
})();

/*
Script: StickyWin.ui.js

Creates an html holder for in-page popups using a default style.

License:
	http://clientside.cnet.com/wiki/cnet-libraries#license
*/

StickyWin.ui = function(caption, body, options){
	options = $extend({
		width: 300,
		css: "div.DefaultStickyWin div.body{font-family:verdana; font-size:11px; line-height: 13px;}"+
			"div.DefaultStickyWin div.top_ul{background:url({%baseHref%}full.png) top left no-repeat; height:30px; width:15px; float:left}"+
			"div.DefaultStickyWin div.top_ur{position:relative; left:0px !important; left:-4px; background:url({%baseHref%}full.png) top right !important; height:30px; margin:0px 0px 0px 15px !important; margin-right:-4px; padding:0px}"+
			"div.DefaultStickyWin h1.caption{margin:0px 5px 0px 0px; overflow: hidden; padding:0; font-weight:bold; color:#555; font-size:14px; position:relative; top:8px; left:5px; float: left; height: 22px;}"+
			"div.DefaultStickyWin div.middle, div.DefaultStickyWin div.closeBody {background:url({%baseHref%}body.png) top left repeat-y; margin:0px 20px 0px 0px !important;	margin-bottom: -3px; position: relative;	top: 0px !important; top: -3px;}"+
			"div.DefaultStickyWin div.body{background:url({%baseHref%}body.png) top right repeat-y; padding:8px 30px 8px 0px; margin-left:5px; position:relative; right:-20px}"+
			"div.DefaultStickyWin div.bottom{clear:both}"+
			"div.DefaultStickyWin div.bottom_ll{background:url({%baseHref%}full.png) bottom left no-repeat; width:15px; height:15px; float:left}"+
			"div.DefaultStickyWin div.bottom_lr{background:url({%baseHref%}full.png) bottom right; position:relative; left:0px !important; left:-4px; margin:0px 0px 0px 15px !important; margin-right:-4px; height:15px}"+
			"div.DefaultStickyWin div.closeButtons{text-align: center; background:url({%baseHref%}body.png) top right repeat-y; padding: 0px 30px 8px 0px; margin-left:5px; position:relative; right:-20px}"+
			"div.DefaultStickyWin a.button:hover{background:url({%baseHref%}big_button_over.gif) repeat-x}"+
			"div.DefaultStickyWin a.button {background:url({%baseHref%}big_button.gif) repeat-x; margin: 2px 8px 2px 8px; padding: 2px 12px; cursor:pointer; border: 1px solid #999 !important; text-decoration:none; color: #000 !important;}"+
			"div.DefaultStickyWin div.closeButton{width:13px; height:13px; background:url({%baseHref%}closebtn.gif) no-repeat; position: absolute; right: 0px; margin:10px 15px 0px 0px !important; cursor:pointer}"+
			"div.DefaultStickyWin div.dragHandle {	width: 11px;	height: 25px;	position: relative;	top: 5px;	left: -3px;	cursor: move;	background: url({%baseHref%}drag_corner.gif); float: left;}",
		cornerHandle: false,
		cssClass: '',
		baseHref: 'templates/'+DESIGN+'/images/javascripts/stickyWinHTML/',
		buttons: []
/*	These options are deprecated:		
		closeTxt: false,
		onClose: $empty,
		confirmTxt: false,
		onConfirm: $empty	*/
        }, options);
        //legacy support
        if(options.confirmTxt) options.buttons.push({text: options.confirmTxt, onClick: options.onConfirm || $empty});
        if(options.closeTxt) options.buttons.push({text: options.closeTxt, onClick: options.onClose || $empty});


        new StyleWriter().createStyle(options.css.substitute({baseHref: options.baseHref}, /\\?\{%([^}]+)%\}/g), 'defaultStickyWinStyle');
        caption = $pick(caption, '%caption%');
        body = $pick(body, '%body%');
        var container = new Element('div').setStyle('width', options.width).addClass('DefaultStickyWin');
        if(options.cssClass) container.addClass(options.cssClass);
        //header
        var h1Caption = new Element('h1').addClass('caption').setStyle('width', (options.width.toInt()-(options.cornerHandle?70:60)));


        if($(caption)) h1Caption.adopt(caption);
        else h1Caption.set('html', caption);
        
        var bodyDiv = new Element('div').addClass('body');
        if($(body)) bodyDiv.adopt(body);
        else bodyDiv.set('html', body);
        
        var top_ur = new Element('div').addClass('top_ur').adopt(
                        new Element('div').addClass('closeButton').addClass('closeSticky')
                ).adopt(h1Caption);
        if(options.cornerHandle) new Element('div').addClass('dragHandle').inject(top_ur, 'top');
        else h1Caption.addClass('dragHandle');
        container.adopt(
                new Element('div').addClass('top').adopt(
                                new Element('div').addClass('top_ul')
                        ).adopt(top_ur)
        );
        //body
        container.adopt(new Element('div').addClass('middle').adopt(bodyDiv));
        //close buttons
        if(options.buttons.length > 0){
                var closeButtons = new Element('div').addClass('closeButtons');
                options.buttons.each(function(button){
                        if(button.properties && button.properties.className){
                                button.properties['class'] = button.properties.className;
                                delete button.properties.className;
                        }
                        var properties = $merge({'class': 'closeSticky'}, button.properties);
                        new Element('a').addEvent('click',
                                button.onClick || $empty).appendText(
                                button.text).inject(closeButtons).setProperties(properties).addClass('button');
                });
                container.adopt(new Element('div').addClass('closeBody').adopt(closeButtons));
        }
        //footer
        container.adopt(
                new Element('div').addClass('bottom').adopt(
                                new Element('div').addClass('bottom_ll')
                        ).adopt(
                                new Element('div').addClass('bottom_lr')
                )
        );
        return container;
};


/*
Script: Waiter.js

Adds a semi-transparent overlay over a dom element with a spinnin ajax icon.

License:
	http://clientside.cnet.com/wiki/cnet-libraries#license
*/
var Waiter = new Class({
	Implements: [Options, Events, Chain],
	options: {
		baseHref: 'templates/'+DESIGN+'/images/javascripts/waiter/',
		containerProps: {
			styles: {
				position: 'absolute',
				'text-align': 'center'
			},
			'class':'waiterContainer'
		},
		containerPosition: {},
		msg: false,
		msgProps: {
			styles: {
				'text-align': 'center',
				fontWeight: 'bold'
			},
			'class':'waiterMsg'
		},
		img: {
			src: 'waiter.gif',
			styles: {
				width: 24,
				height: 24
			},
			'class':'waiterImg'
		},
		layer:{
			styles: {
				width: 0,
				height: 0,
				position: 'absolute',
				zIndex: 999,
				display: 'none',
				opacity: 0.5,
				background: '#fff'
			},
			'class': 'waitingDiv'
		},
		useIframeShim: true,
		fxOptions: {}
//	iframeShimOptions: {},
//	onShow: $empty
//	onHide: $empty
	},
	initialize: function(target, options){
		this.target = $(target)||$(document.body);
		this.setOptions(options);
		this.waiterContainer = new Element('div', this.options.containerProps).inject(document.body);
		if (this.options.msg) {
			this.msgContainer = new Element('div', this.options.msgProps);
			this.waiterContainer.adopt(this.msgContainer);
			if (!$(this.options.msg)) this.msg = new Element('p').appendText(this.options.msg);
			else this.msg = $(this.options.msg);
			this.msgContainer.adopt(this.msg);
		}
		if (this.options.img) this.waiterImg = $(this.options.img.id) || new Element('img').injectInside(this.waiterContainer);
		this.waiterOverlay = $(this.options.layer.id) || new Element('div').injectInside(document.body).adopt(this.waiterContainer);
		
		try {
			if (this.options.useIframeShim) this.shim = new IframeShim(this.waiterOverlay, this.options.iframeShimOptions);
		} catch(e) {
			dbug.log("Waiter attempting to use IframeShim but failed; did you include IframeShim? Error: ", e);
			this.options.useIframeShim = false;
		}
		this.waiterFx = this.waiterFx || new Fx.Elements($$(this.waiterContainer, this.waiterOverlay), this.options.fxOptions);
	},
	toggle: function(element, show) {
		//the element or the default
		element = $(element) || $(this.active) || $(this.target);
		if (!$(element)) return this;
		if (this.active && element != this.active) return this.stop(this.start.bind(this, element));
		//if it's not active or show is explicit
		//or show is not explicitly set to false
		//start the effect
		if((!this.active || show) && show !== false) this.start(element);
		//else if it's active and show isn't explicitly set to true
		//stop the effect
		else if(this.active && !show) this.stop();
		return this;
	},
	reset: function(){
		this.waiterFx.cancel().set({
			0: { opacity:[0]},
			1: { opacity:[0]}
		});
	},
	start: function(element){
		this.reset();
		element = $(element) || $(this.target);
		if (this.options.img) {
			this.waiterImg.set($merge(this.options.img, {
				src: this.options.baseHref + this.options.img.src
			}));
		}
		this.waiterOverlay.set(this.options.layer);
		
		var start = function() {
			var dim = element.getComputedSize();
			this.active = element;
			this.waiterOverlay.setStyles({
				width: this.options.layer.width||dim.totalWidth,
				height: this.options.layer.height||dim.totalHeight,
				display: 'block'
			}).setPosition({
				relativeTo: element,
				position: 'upperLeft'
			});
			this.waiterContainer.setPosition({
				relativeTo: this.waiterOverlay
			});
			if (this.options.useIframeShim) this.shim.show();
			this.waiterFx.start({
				0: { opacity:[1] },
				1: { opacity:[this.options.layer.styles.opacity]}
			}).chain(function(){
				if (this.active == element) this.fireEvent('onShow', element);
				this.callChain();
			}.bind(this));
		}.bind(this);

		if (this.active && this.active != element) this.stop(start);
		else start();
		
		return this;
	},
	stop: function(callback){
		if (!this.active) {
			if ($type(callback) == "function") callback.attempt();
			return this;
		}
		this.waiterFx.cancel();
		this.waiterFx.clearChain();
		//fade the waiter out
		this.waiterFx.start({
			0: { opacity:[0]},
			1: { opacity:[0]}
		}).chain(function(){
			this.active = null;
			this.waiterOverlay.hide();
			if (this.options.useIframeShim) this.shim.hide();
			this.fireEvent('onHide', this.active);
			this.callChain();
			this.clearChain();
			if ($type(callback) == "function") callback.attempt();
		}.bind(this));
		return this;
	}
});

if (typeof Request != "undefined" && Request.HTML) {
	Request.HTML = new Class({
		Extends: Request.HTML,
		options: {
			useWaiter: false,
			waiterOptions: {},
			waiterTarget: false
		},
		initialize: function(options){
			this._send = this.send;
			this.send = function(options){
				if(this.waiter) this.waiter.start().chain(this._send.bind(this, options));
				else this._send(options);
				return this;
			};
			this.parent(options);
			if (this.options.useWaiter && ($(this.options.update) || $(this.options.waiterTarget))) {
				this.waiter = new Waiter(this.options.waiterTarget || this.options.update, this.options.waiterOptions);
				['onComplete', 'onException', 'onCancel'].each(function(event){
					this.addEvent(event, this.waiter.stop.bind(this.waiter));
				}, this);
			}
		}
	});
}

/*
Script: HtmlTable.js

Builds table elements with methods to add rows quickly.

License:
	http://clientside.cnet.com/wiki/cnet-libraries#license
*/
var HtmlTable = new Class({
	Implements: [Options],
	options: {
		properties: {
			cellpadding: 0,
			cellspacing: 0,
			border: 0
		},
		rows: []
	},
	initialize: function(options) {
		this.setOptions(options);
		this.table = new Element('table').setProperties(this.options.properties);
		this.table.store('HtmlTable', this);
		this.tbody = new Element('tbody').inject(this.table);
		this.options.rows.each(this.push.bind(this));
		["adopt", "inject", "wraps", "grab", "replaces", "empty", "dispose"].each(function(method){
				this[method] = this.table[method].bind(this.table);
		}, this);
	},
	toElement: function(){
		return this.table;
	},
	push: function(row) {
		var tr = new Element('tr').inject(this.tbody);
		var tds = row.map(function (tdata) {
			var td = new Element('td').inject(tr);
			if(tdata.properties) td.setProperties(tdata.properties);
			function setContent(content){
				if($(content)) td.adopt($(content));
				else td.set('html', content);
			};
			if(tdata.content) setContent(tdata.content);
			else setContent(tdata);
			return td;
		}, this);
		return {tr: tr, tds: tds};
	}
});

/*
Script: MultipleOpenAccordion.js

Creates a Mootools Fx.Accordion that allows the user to open more than one element.

License:
	http://clientside.cnet.com/wiki/cnet-libraries#license
*/
var MultipleOpenAccordion = new Class({
	Implements: [Options, Events, Chain],
	options: {
		togglers: [],
		elements: [],
		openAll: true,
		firstElementsOpen: [0],
		fixedHeight: false,
		fixedWidth: false,
		height: true,
		opacity: true,
		width: false
//	onActive: $empty,
//	onBackground: $empty
	},
	togglers: [],
	elements: [],
	initialize: function(container, options){
		this.setOptions(options);
		this.container = $(container);
		elements = $$(options.elements);
		$$(options.togglers).each(function(toggler, idx){
			this.addSection(toggler, elements[idx], idx);
		}, this);
		if (this.togglers.length) {
			if (this.options.openAll) this.showAll();
			else this.openSections(this.options.firstElementsOpen);
		}
	},
	addSection: function(toggler, element, pos){
		toggler = $(toggler);
		element = $(element);
		var test = this.togglers.contains(toggler);
		var len = this.togglers.length;
		this.togglers.include(toggler);
		this.elements.include(element);
		if (len && (!test || pos)){
			pos = $pick(pos - 1, len - 1);
			toggler.inject(this.elements[pos], 'after');
			element.inject(toggler, 'after');
		} else if (this.container && !test){
			toggler.inject(this.container);
			element.inject(this.container);
		}
		var idx = this.togglers.indexOf(toggler);
		toggler.addEvent('click', this.toggleSection.bind(this, idx));
		var mode;
		if (this.options.height && this.options.width) mode = "both";
		else mode = (this.options.height)?"vertical":"horizontal";
		element.store('reveal', new Fx.Reveal(element, {
			transitionOpacity: this.options.opacity,
			mode: mode,
			heightOverride: this.options.fixedHeight,
			widthOverride: this.options.fixedWidth
		}));
		return this;
	},
	onComplete: function(idx, callChain){
		this.fireEvent(this.elements[idx].isVisible()?'onActive':'onBackground', [this.togglers[idx], this.elements[idx]]);
		this.callChain();
		return this;
	},
	showSection: function(idx, useFx){
		this.toggleSection(idx, useFx, true);
	},
	hideSection: function(idx, useFx){
		this.toggleSection(idx, useFx, false);
	},
	toggleSection: function(idx, useFx, show, callChain){
		var method = show?'reveal':$defined(show)?'dissolve':'toggle';
		callChain = $pick(callChain, true);
		if($pick(useFx, true)) {
			this.elements[idx].retrieve('reveal')[method]().chain(
				this.onComplete.bind(this, [idx, callChain])
			);
		} else {
				if (method == "toggle") el.togglek();
				else el[method == "reveal"?'show':'hide']();
				this.onComplete(idx, callChain);
		}
		return this;
	},
	toggleAll: function(useFx, show){
		var method = show?'reveal':$chk(show)?'disolve':'toggle';
		var last = this.elements.getLast();
		this.elements.each(function(el, idx){
			this.toggleSection(idx, useFx, show, el == last);
		}, this);
		return this;
	},
	toggleSections: function(sections, useFx, show) {
		last = sections.getLast();
		this.elements.each(function(el,idx){
			this.toggleSection(idx, useFx, sections.contains(idx), show, idx == last);
		}, this);
		return this;
	},
	openSections: function(sections, useFx){
		this.toggleSections(sections, useFx, true);
	},
	closeSections: function(sections, useFx){
		this.toggleSections(sections, useFx, false);
	},
	showAll: function(useFx){
		return this.toggleAll(useFx, true);
	},
	hideAll: function(useFx){
		return this.toggleAll(useFx, false);
	}
});


/*
Script: MooScroller.js

Recreates the standard scrollbar behavior for elements with overflow but using DOM elements so that the scroll bar elements are completely styleable by css.

License:
	http://clientside.cnet.com/wiki/cnet-libraries#license
*/
var MooScroller = new Class({
	Implements: [Options, Events],
	options: {
		maxThumbSize: 10,
		mode: 'vertical',
		width: 0, //required only for mode: horizontal
		scrollSteps: 10,
		wheel: true,
		scrollLinks: {
			forward: 'scrollForward',
			back: 'scrollBack'
		}
//		onScroll: $empty,
//		onPage: $empty
	},

	initialize: function(content, knob, options){
		this.setOptions(options);
		this.horz = (this.options.mode == "horizontal");

		this.content = $(content).setStyle('overflow', 'hidden');
		this.knob = $(knob);
		this.track = this.knob.getParent();
		this.setPositions();
		
		if(this.horz && this.options.width) {
			this.wrapper = new Element('div');
			this.content.getChildren().each(function(child){
				this.wrapper.adopt(child);
			});
			this.wrapper.inject(this.content).setStyle('width', this.options.width);
		}
		

		this.bound = {
			'start': this.start.bind(this),
			'end': this.end.bind(this),
			'drag': this.drag.bind(this),
			'wheel': this.wheel.bind(this),
			'page': this.page.bind(this)
		};

		this.position = {};
		this.mouse = {};
		this.update();
		this.attach();
		
		var clearScroll = function (){
			$clear(this.scrolling);
		}.bind(this);
		['forward','back'].each(function(direction) {
			var lnk = $(this.options.scrollLinks[direction]);
			if(lnk) {
				lnk.addEvents({
					mousedown: function() {
						this.scrolling = this[direction].periodical(50, this);
					}.bind(this),
					mouseup: clearScroll.bind(this),
					click: clearScroll.bind(this)
				});
			}
		}, this);
		this.knob.addEvent('click', clearScroll.bind(this));
		window.addEvent('domready', function(){
			try {
				$(document.body).addEvent('mouseup', clearScroll.bind(this));
			}catch(e){}
		}.bind(this));
	},
	setPositions: function(){
		[this.track, this.knob].each(function(el){
			if (el.getStyle('position') == 'static') el.setStyle('position','relative');
		});

	},
	toElement: function(){
		return this.content;
	},
	update: function(){
		var plain = this.horz?'Width':'Height';
		this.contentSize = this.content['offset'+plain];
		this.contentScrollSize = this.content['scroll'+plain];
		this.trackSize = this.track['offset'+plain];

		this.contentRatio = this.contentSize / this.contentScrollSize;

		this.knobSize = (this.trackSize * this.contentRatio).limit(this.options.maxThumbSize, this.trackSize);

		this.scrollRatio = this.contentScrollSize / this.trackSize;
		this.knob.setStyle(plain.toLowerCase(), this.knobSize);

		this.updateThumbFromContentScroll();
		this.updateContentFromThumbPosition();
	},

	updateContentFromThumbPosition: function(){
		this.content[this.horz?'scrollLeft':'scrollTop'] = this.position.now * this.scrollRatio;
	},

	updateThumbFromContentScroll: function(){
		this.position.now = (this.content[this.horz?'scrollLeft':'scrollTop'] / this.scrollRatio).limit(0, (this.trackSize - this.knobSize));
		this.knob.setStyle(this.horz?'left':'top', this.position.now);
	},

	attach: function(){
		this.knob.addEvent('mousedown', this.bound.start);
		if (this.options.scrollSteps) this.content.addEvent('mousewheel', this.bound.wheel);
		this.track.addEvent('mouseup', this.bound.page);
	},

	wheel: function(event){
		this.scroll(-(event.wheel * this.options.scrollSteps));
		this.updateThumbFromContentScroll();
		event.stop();
	},

	scroll: function(steps){
		steps = steps||this.options.scrollSteps;
		this.content[this.horz?'scrollLeft':'scrollTop'] += steps;
		this.updateThumbFromContentScroll();
		this.fireEvent('onScroll', steps);
	},
	forward: function(steps){
		this.scroll(steps);
	},
	back: function(steps){
		steps = steps||this.options.scrollSteps;
		this.scroll(-steps);
	},

	page: function(event){
		var axis = this.horz?'x':'y';
		var forward = (event.page[axis] > this.knob.getPosition()[axis]);
		this.scroll((forward?1:-1)*this.content['offset'+(this.horz?'Width':'Height')]);
		this.updateThumbFromContentScroll();
		this.fireEvent('onPage', forward);
		event.stop();
	},

	
	start: function(event){
		var axis = this.horz?'x':'y';
		this.mouse.start = event.page[axis];
		this.position.start = this.knob.getStyle(this.horz?'left':'top').toInt();
		document.addEvent('mousemove', this.bound.drag);
		document.addEvent('mouseup', this.bound.end);
		this.knob.addEvent('mouseup', this.bound.end);
		event.stop();
	},

	end: function(event){
		document.removeEvent('mousemove', this.bound.drag);
		document.removeEvent('mouseup', this.bound.end);
		this.knob.removeEvent('mouseup', this.bound.end);
		event.stop();
	},

	drag: function(event){
		var axis = this.horz?'x':'y';
		this.mouse.now = event.page[axis];
		this.position.now = (this.position.start + (this.mouse.now - this.mouse.start)).limit(0, (this.trackSize - this.knobSize));
		this.updateContentFromThumbPosition();
		this.updateThumbFromContentScroll();
		event.stop();
	}

});


/*
Script: SimpleCarousel.js

Builds a carousel object that manages the basic functions of a generic carousel (a carousel	here being a collection of "slides" that play from one to the next, with a collection of "buttons" that reference each slide).

License:
	http://clientside.cnet.com/wiki/cnet-libraries#license
*/
var SimpleCarousel = new Class({
	Implements: [Options, Events],
	options: {
//		onRotate: $empty,
//		onStop: $empty,
//		onAutoPlay: $empty,
//		onShowSlide: $empty,
		slideInterval: 4000,
		transitionDuration: 700,
		startIndex: 0,
		buttonOnClass: "selected",
		buttonOffClass: "off",
		rotateAction: "none",
		rotateActionDuration: 100,
		autoplay: true
	},
	initialize: function(container, slides, buttons, options){
		this.container = $(container);
		if(this.container.hasClass('hasCarousel')) return false;
		this.setOptions(options);
		this.container.addClass('hasCarousel');
		this.slides = $$(slides);
		this.buttons = $$(buttons);
		this.createFx();
		this.showSlide(this.options.startIndex);
		if(this.options.autoplay) this.autoplay();
		if(this.options.rotateAction != 'none') this.setupAction(this.options.rotateAction);
		return this;
	},
	toElement: function(){
		return this.container;
	},
	setupAction: function(action) {
		this.buttons.each(function(el, idx){
			$(el).addEvent(action, function() {
				this.slideFx.setOptions(this.slideFx.options, {duration: this.options.rotateActionDuration});
				if(this.currentSlide != idx) this.showSlide(idx);
				this.stop();
			}.bind(this));
		}, this);
	},
	createFx: function(){
		if (!this.slideFx) this.slideFx = new Fx.Elements(this.slides, {duration: this.options.transitionDuration});
		this.slides.each(function(slide){
			slide.setStyle('opacity',0);
		});
	},
	showSlide: function(slideIndex){
		var action = {};
		this.slides.each(function(slide, index){
			if(index == slideIndex && index != this.currentSlide){ //show
				$(this.buttons[index]).swapClass(this.options.buttonOnClass, this.options.buttonOffClass);
				action[index.toString()] = {
					opacity: 1
				};
			} else {
				$(this.buttons[index]).swapClass(this.options.buttonOffClass, this.options.buttonOnClass);
				action[index.toString()] = {
					opacity:0
				};
			}
		}, this);
		this.fireEvent('onShowSlide', slideIndex);
		this.currentSlide = slideIndex;
		this.slideFx.start(action);
		return this;
	},
	autoplay: function(){
		this.slideshowInt = this.rotate.periodical(this.options.slideInterval, this);
		this.fireEvent('onAutoPlay');
		return this;
	},
	stop: function(){
		$clear(this.slideshowInt);
		this.fireEvent('onStop');
		return this;
	},
	rotate: function(){
		current = this.currentSlide;
		next = (current+1 >= this.slides.length) ? 0 : current+1;
		this.showSlide(next);
		this.fireEvent('onRotate', next);
		return this;
	}
});

/*
Script: SimpleSlideShow.js

Makes a very, very simple slideshow gallery with a collection of dom elements and previous and next buttons.

License:
	http://clientside.cnet.com/wiki/cnet-libraries#license
*/
	var SimpleSlideShow = new Class({
		Implements: [Events, Options, Chain],
		options: {
			startIndex: 0,
			slides: [],
			currentSlideClass: 'currentSlide',
			currentIndexContainer: false,
			maxContainer: false,
			nextLink: false,
			prevLink: false,
			wrap: true,
			disabledLinkClass: 'disabled',
//			onNext: $empty,
//			onPrev: $empty,
//			onSlideClick: $empty,
			crossFadeOptions: {}
		},
		initialize: function(options){
			this.setOptions(options);
			this.slides = this.options.slides;
			this.makeSlides();
			this.setCounters();
			this.setUpNav();
			this.now = this.options.startIndex;
			if(this.slides.length > 0) this.show(this.now);
		},
		setCounters: function(){
			if($(this.options.currentIndexContainer))$(this.options.currentIndexContainer).set('html', this.now+1);
			if($(this.options.maxContainer))$(this.options.maxContainer).set('html', this.slides.length);
		},
		makeSlides: function(){
			//hide them all
			this.slides.each(function(slide, index){
				if(index != this.now) slide.setStyle('display', 'none');
				else slide.setStyle('display', 'block');
				this.makeSlide(slide);
			}, this);
		},
		makeSlide: function(slide){
			slide.addEvent('click', function(){ this.fireEvent('onSlideClick'); }.bind(this));
		},
		setUpNav: function(){	
			if($(this.options.nextLink)) $(this.options.nextLink).addEvent('click', function(){
					this.forward();
				}.bind(this));
			if($(this.options.prevLink)) $(this.options.prevLink).addEvent('click', function(){
					this.back();
				}.bind(this));
		},
		forward: function(){
			var fireEvent = false;
			if($type(this.now) && this.now < this.slides.length-1) fireEvent = this.show(this.now+1);
			else if($type(this.now) && this.options.wrap) fireEvent = this.show(0);
			else if(!$type(this.now)) fireEvent = this.show(this.options.startIndex);
			if (fireEvent) this.fireEvent('onNext');
			if(this.now == this.slides.length && !this.options.wrap && $(this.options.nextLink))
				$(this.options.nextLink).addClass(this.options.disabledLinkClass);
			else if ($(this.options.nextLink)) $(this.options.nextLink).removeClass(this.options.disabledLinkClass);
			return this;
		},
		back: function(){
			if(this.now > 0) {
				this.show(this.now-1);
				this.fireEvent('onPrev');
			} else if(this.options.wrap && this.slides.length > 1) {
				this.show(this.slides.length-1);
				this.fireEvent('onPrev');
			}
			if(this.now == 0 && !this.options.wrap && $(this.options.prevSlide))
				$(this.options.prevSlide).addClass(this.options.disabledLinkClass);
			else if ($(this.options.prevSlide)) 
				$(this.options.prevSlide).removeClass(this.options.disabledLinkClass);
			return this;
		},
		show: function(index){
			if (this.showing) return this.chain(this.show.bind(this, index));
			var now = this.now;
			var s = this.slides[index]; //saving bytes
			function fadeIn(s, resetOpacity){
				s.setStyle('display','block');
				if(s.fxOpacityOk()) {
					if(resetOpacity) s.setStyle('opacity', 0);
					s.set('tween', this.options.crossFadeOptions).get('tween').start('opacity', 1).chain(function(){
						this.showing = false;
						this.callChain();
					}.bind(this));
				}
			};
			if(s) {
				if($type(this.now) && this.now != index){
					if(s.fxOpacityOk()) {
						var fx = this.slides[this.now].get('tween');
						fx.setOptions(this.options.crossFadeOptions);
						this.showing = true;
						fx.start('opacity', 0).chain(function(){
							this.slides[now].setStyle('display','none');
							s.addClass(this.options.currentSlideClass);
							fadeIn.run([s, true], this);
						}.bind(this));
					} else {
						this.slides[this.now].setStyle('display','none');
						fadeIn.run(s, this);
					}
				} else fadeIn.run(s, this);
				this.now = index;
				this.setCounters();
			}
		},
		slideClick: function(){
			this.fireEvent('onSlideClick', [this.slides[this.now], this.now]);
		}
	});

	var SimpleImageSlideShow = new Class({
		Extends: SimpleSlideShow,
		options: {
			imgUrls: [],
			imgClass: 'screenshot',
			container: false
		},
		initialize: function(options){
			this.parent(options);
			this.options.imgUrls.each(function(url){
				this.addImg(url);
			}, this);
			this.show(this.options.startIndex);
		},
		addImg: function(url){
			if($(this.options.container)) {
				var img = new Element('img', {
					'src': url,
					'id': this.options.imgClass+this.slides.length
				}).addClass(this.options.imgClass).setStyle(
					'display', 'none').inject($(this.options.container)).addEvent(
					'click', this.slideClick.bind(this));
				this.slides.push(img);
				this.makeSlide(img);
				this.setCounters();
			}
			return this;
		}
	});


/*
Script: TabSwapper.js

Handles the scripting for a common UI layout; the tabbed box.

License:
	http://clientside.cnet.com/wiki/cnet-libraries#license
*/
var TabSwapper = new Class({
	Implements: [Options, Events],
	options: {
		selectedClass: 'tabSelected',
		mouseoverClass: 'tabOver',
		deselectedClass: '',
		rearrangeDOM: true,
		initPanel: 0, 
		smooth: false, 
		smoothSize: false,
		maxSize: null,
		effectOptions: {
			duration: 500
		},
		cookieName: null, 
		cookieDays: 999
//	onActive: $empty,
//	onActiveAfterFx: $empty,
//	onBackground: $empty
	},
	tabs: [],
	sections: [],
	clickers: [],
	sectionFx: [],
	initialize: function(options){
		this.setOptions(options);
		this.setup();
		if(this.options.cookieName && this.recall()) this.show(this.recall().toInt());
		else this.show(this.options.initPanel);
	},
	setup: function(){
		var opt = this.options;
		sections = $$(opt.sections);
		tabs = $$(opt.tabs);
		clickers = $$(opt.clickers);
		tabs.each(function(tab, index){
			this.addTab(tab, sections[index], clickers[index], index);
		}, this);
		return this;
	},
	addTab: function(tab, section, clicker, index){
		tab = $(tab); clicker = $(clicker); section = $(section);
		//if the tab is already in the interface, just move it
		if(this.tabs.indexOf(tab) >= 0 && tab.retrieve('tabbered') 
			 && this.tabs.indexOf(tab) != index && this.options.rearrangeDOM) {
			this.moveTab(this.tabs.indexOf(tab), index);
			return this;
		}
		//if the index isn't specified, put the tab at the end
		if(!$defined(index)) index = this.tabs.length;
		//if this isn't the first item, and there's a tab
		//already in the interface at the index 1 less than this
		//insert this after that one
		if(index > 0 && this.tabs[index-1] && this.options.rearrangeDOM) {
			tab.inject(this.tabs[index-1], 'after');
			section.inject(this.tabs[index-1].retrieve('section'), 'after');
		}
		this.tabs.splice(index, 0, tab);
		clicker = clicker || tab;

		tab.addEvents({
			mouseout: function(){
				tab.removeClass(this.options.mouseoverClass);
			}.bind(this),
			mouseover: function(){
				tab.addClass(this.options.mouseoverClass);
			}.bind(this)
		});

		clicker.addEvent('click', function(){
			this.show(index);
		}.bind(this));

		tab.store('tabbered', true);
		tab.store('section', section);
		tab.store('clicker', clicker);
		this.hideSection(index);
		return this;
	},
	removeTab: function(index){
		var now = this.tabs[this.now];
		if(this.now == index){
			if(index > 0) this.show(index - 1);
			else if (index < this.tabs.length) this.show(index + 1);
		}
		this.now = this.tabs.indexOf(now);
		return this;
	},
	moveTab: function(from, to){
		var tab = this.tabs[from];
		var clicker = tab.retrieve('clicker');
		var section = tab.retrieve('section');
		
		var toTab = this.tabs[to];
		var toClicker = toTab.retrieve('clicker');
		var toSection = toTab.retrieve('section');
		
		this.tabs.erase(tab).splice(to, 0, tab);

		tab.inject(toTab, 'before');
		clicker.inject(toClicker, 'before');
		section.inject(toSection, 'before');
		return this;
	},
	show: function(i){
		if (!$chk(this.now)) {
			this.tabs.each(function(tab, idx){
				if (i != idx) 
					this.hideSection(idx)
			}, this);
		}
		this.showSection(i).save(i);
		return this;
	},
	save: function(index){
		if(this.options.cookieName) 
			Cookie.write(this.options.cookieName, index, {duration:this.options.cookieDays});
		return this;
	},
	recall: function(){
		return (this.options.cookieName)?$pick(Cookie.read(this.options.cookieName), false): false;
	},
	hideSection: function(idx) {
		var tab = this.tabs[idx];
		if (!tab) return this;
		var sect = tab.retrieve('section');
		if (!sect) return this;
		if (sect.getStyle('display') != 'none') {
			this.lastHeight = sect.getSize().y;
			sect.setStyle('display', 'none');
			tab.swapClass(this.options.selectedClass, this.options.deselectedClass);
			this.fireEvent('onBackground', [idx, sect, tab]);
		}
		return this;
	},
	showSection: function(idx) {
		var tab = this.tabs[idx];
		if (!tab) return this;
		var sect = tab.retrieve('section');
		if (!sect) return this;
		var smoothOk = this.options.smooth && (!Browser.Engine.trident4 
										|| (Browser.Engine.trident4 && sect.fxOpacityOk()));
		if(this.now != idx) {
			if (!tab.retrieve('tabFx')) 
				tab.store('tabFx', new Fx.Morph(sect, this.options.effectOptions));
			var start = {
				display:'block',
				overflow: 'hidden'
			};
			if (smoothOk) start.opacity = 0;
			var effect = false;
			if(smoothOk) {
				effect = {opacity: 1};
			} else if (sect.getStyle('opacity').toInt() < 1) {
				sect.setStyle('opacity', 1);
				if (!this.options.smoothSize) 
					this.fireEvent('onActiveAfterFx', [idx, sect, tab]);
			}
			if (this.options.smoothSize) {
				var size = sect.getDimensions().height;
				if ($chk(this.options.maxSize) && this.options.maxSize < size) 
					size = this.options.maxSize;
				if (!effect) effect = {};
				effect.height = size;
			}
			if ($chk(this.now)) this.hideSection(this.now);
			if (this.options.smoothSize && this.lastHeight) start.height = this.lastHeight;
			sect.setStyles(start);
			if (effect) {
				tab.retrieve('tabFx').start(effect).chain(function(){
					this.fireEvent('onActiveAfterFx', [idx, sect, tab]);
					sect.setStyle("height", "auto");
				}.bind(this));
			}
			this.now = idx;
			this.fireEvent('onActive', [idx, sect, tab]);
		}
		tab.swapClass(this.options.deselectedClass, this.options.selectedClass);
		return this;
	}
});


/*
Script: Clipboard.js
	Provides access to the OS clipboard so that data can be copied to it (using a flash plugin).

License:
	http://clientside.cnet.com/wiki/cnet-libraries#license
*/
var Clipboard = {
	swfLocation: 'templates/'+DESIGN+'/images/javascripts/clipboard/_clipboard.swf',
	copyFromElement: function(element) {
		element = $(element);
		if(!element) return null;
		if (Browser.Engine.trident) {
			try {
				window.addEvent('domready', function() {
					var range = element.createTextRange();
					if(range) range.execCommand('Copy');
				});
			}catch(e){
				dbug.log('cannot copy to clipboard: %s', o)
			}
		} else {
			var text = (element.getSelectedText)?element.getSelectedText():element.get('value');
			if (text) Clipboard.copy(text);
		}
		return element;
	},
	copy: function(text) {
		if(Browser.Engine.trident){
			window.addEvent('domready', function() {
				var cb = new Element('textarea', {styles: {display: 'none'}}).inject(document.body);
				cb.set('value', text).select();
				Clipboard.copyFromElement(cb);
				cb.dispose();
			});
		} else {
			var swf = ($('flashcopier'))?$('flashcopier'):new Element('div', {
				id: 'flashcopier'
			}).inject(document.body);
			swf.empty();
			swf.set('html', '<embed src="'+this.swfLocation+'" FlashVars="clipboard='+escape(text)+'" width="0" height="0" type="application/x-shockwave-flash"></embed>');
		}
	}
};


/*
Script: Confirmer.js
	Fades a message in and out for the user to tell them that some event (like an ajax save) has occurred.

License:
	http://clientside.cnet.com/wiki/cnet-libraries#license
*/
var Confirmer = new Class({
	Implements: [Options, Events],
	options: {
		reposition: true, //for elements already in the DOM
		//if position = false, just fade
		positionOptions: {
			relativeTo: false,
			position: 'upperRight', //see <Element.setPosition>
			offset: {x:-225,y:0},
			zIndex: 9999
		},
		msg: 'your changes have been saved', //string or dom element
		msgContainerSelector: '.body',
		delay: 250,
		pause: 500,
		effectOptions:{
			duration: 500
		},
		prompterStyle:{
			padding: '2px 6px',
			border: '1px solid #9f0000',
			backgroundColor: '#f9d0d0',
			fontWeight: 'bold',
			color: '#000',
			width: 210
		}
//	onComplete: $empty
	},
	initialize: function(options){
			this.setOptions(options);
			this.options.positionOptions.relativeTo = $(this.options.positionOptions.relativeTo) || document.body;
			this.prompter = ($(this.options.msg))?$(this.options.msg):this.makePrompter(this.options.msg);
			if(this.options.reposition){
				this.prompter.setStyles({
					position: 'absolute',
					display: 'none',
					zIndex: this.options.positionOptions.zIndex
				});
				if(this.prompter.fxOpacityOk()) this.prompter.setStyle('opacity',0);
			} else if(this.prompter.fxOpacityOk()) this.prompter.setStyle('opacity',0);
			else this.prompter.setStyle('visibility','hidden');
			if (!this.prompter.getParent()){
				window.addEvent('domready', function(){
					this.prompter.inject(document.body);
				}.bind(this));
			}
		try {
			this.msgHolder = this.prompter.getElement(this.options.msgContainerSelector);
			if(!this.msgHolder) this.msgHolder = this.prompter;
		} catch(e){dbug.log(e)}
	},
	makePrompter: function(msg){
		return new Element('div').setStyles(this.options.prompterStyle).appendText(msg);
	},
	prompt: function(options){
		if(!this.paused)this.stop();
		var msg = (options)?options.msg:false;
		options = $merge(this.options, {saveAsDefault: false}, options||{});
		if ($(options.msg) && msg) this.msgHolder.empty().adopt(options.msg);
		else if (!$(options.msg) && options.msg) this.msgHolder.empty().appendText(options.msg);
		if(!this.paused) {
			if(options.reposition) this.position(options.positionOptions);
			(function(){
				this.timer = this.fade(options.pause);
			}).delay(options.delay, this);
		}
		if(options.saveAsDefault) this.setOptions(options);
		return this;
	},
	fade: function(pause){
		this.paused = true;
		pause = $pick(pause, this.options.pause);
		if(!this.fx && this.prompter.fxOpacityOk())
			this.fx = new Fx.Tween(this.prompter, $merge({property: 'opacity'}, this.options.effectOptions));
		if(this.options.reposition) this.prompter.setStyle('display','block');
		if(this.prompter.fxOpacityOk()){
			this.prompter.setStyle('visibility','visible');
			this.fx.start(0,1).chain(function(){
				this.timer = (function(){
					this.fx.start(0).chain(function(){
						if(this.options.reposition) this.prompter.hide();
						this.paused = false;
					}.bind(this));
				}).delay(pause, this);
			}.bind(this));
		} else {
			this.prompter.setStyle('visibility','visible');
			this.timer = (function(){
				this.prompter.setStyle('visibility','hidden');
				this.fireEvent('onComplete');
				this.paused = false;
			}).delay(pause+this.options.effectOptions.duration, this);
		}
		return this;
	},
	stop: function(){	
		this.paused = false;
		$clear(this.timer);
		if(this.fx) this.fx.set(0);
		if(this.options.reposition) this.prompter.hide();
		return this;
	},
	position: function(positionOptions){
		this.prompter.setPosition($merge(this.options.positionOptions, positionOptions));
		return this;
	}
});


/*
Script: DatePicker.js
	Allows the user to enter a date in many popuplar date formats or choose from a calendar.

License:
	http://clientside.cnet.com/wiki/cnet-libraries#license
*/
var DatePicker = new Class({
	Implements: [Options, Events, StyleWriter],
	options: {
		format: "%x",
		defaultCss: 'div.calendarHolder {height:177px;position: absolute;top: -21px !important;top: -27px;left: -3px;}'+
			'div.calendarHolder table.cal {margin-right: 15px !important;margin-right: 8px;width: 205px;}'+
			'div.calendarHolder td {text-align:center;}'+
			'div.calendarHolder tr.dayRow td {padding: 2px;width: 22px;cursor: pointer;}'+
			'div.calendarHolder table.datePicker * {font-size:11px;line-height:16px;}'+
			'div.calendarHolder table.datePicker {margin: 0;padding:0 5px;float: left;}'+
			'div.calendarHolder table.datePicker table.cal td {cursor:pointer;}'+
			'div.calendarHolder tr.dateNav {font-weight: bold;height:22px;margin-top:8px;}'+
			'div.calendarHolder tr.dayNames {height: 23px;}'+
			'div.calendarHolder tr.dayNames td {color:#666;font-weight:700;border-bottom:1px solid #ddd;}'+
			'div.calendarHolder table.datePicker tr.dayRow td:hover {background:#ccc;}'+
			'div.calendarHolder table.datePicker tr.dayRow td {margin: 1px;}'+
			'div.calendarHolder td.today {color:#bb0904;}'+
			'div.calendarHolder td.otherMonthDate {border:1px solid #fff;color:#ccc;background:#f3f3f3 !important;margin: 0px !important;}'+
			'div.calendarHolder td.selectedDate {border: 1px solid #20397b;background:#dcddef;margin: 0px !important;}'+
			'div.calendarHolder a.leftScroll, div.calendarHolder a.rightScroll {cursor: pointer;}'+
			'div.datePickerSW div.body {height: 139px !important;height: 149px;}'+
			'div.datePickerSW .clearfix:after {content: ".";display: block;height: 0;clear: both;visibility: hidden;}'+
			'div.datePickerSW .clearfix {display: inline-table;}'+
			'* html div.datePickerSW .clearfix {height: 1%;}'+
			'div.datePickerSW .clearfix {display: block;}',
		calendarId: false,
		stickyWinOptions: {
			draggable: true,
			dragOptions: {},
			position: "bottomLeft",
			offset: {x:10, y:10},
			fadeDuration: 400
		},
		updateOnBlur: true,
		additionalShowLinks: [],
		showOnInputFocus: true,
		useDefaultCss: true,
		hideCalendarOnPick: true
/*	onPick: $empty,
		onShow: $empty,
		onHide: $empty */
	},
		
	initialize: function(input, options){
		if ($(input)) this.inputs = $H({start: $(input)});
    	this.today = new Date();
		var StickyWinToUse = (typeof StickyWinFx == "undefined")?StickyWin:StickyWinFx;
		this.setOptions({
			stickyWinToUse: StickyWinToUse
		}, options);
		this.whens = this.whens || ['start'];
		if(!this.calendarId) this.calendarId = "popupCalendar" + new Date().getTime();
		if(this.options.useDefaultCss)
			this.createStyle(this.options.defaultCss, 'datePickerStyle');
		this.setUpObservers();
		this.getCalendar();
		this.formValidatorInterface();
	},
	formValidatorInterface: function(){
		this.inputs.each(function(input){
			var props;
			if(input.get('validatorProps')){
				try {
					props = JSON.decode(input.get('validatorProps'));
				}catch(e){}
			}
			if (props && props.dateFormat) {
				dbug.log('using date format specified in validatorProps property of element to play nice with FormValidator');
				this.setOptions({ format: props.dateFormat });
			} else {
				if (!props) props = {};
				props.dateFormat = this.options.format;
				input.set('validatorProps', JSON.encode(props));
			}
		}, this);
	},
	calWidth: 260,
	inputDates: {},
	selectedDates: {},
	setUpObservers: function(){
		this.inputs.each(function(input) {
			if (this.options.showOnInputFocus) {
				input.addEvent('focus', this.show.bind(this));
				input.addEvent('blur', function(e){
					if (e) {
						this.selectedDates = this.getDates(null, true);
						this.fillCalendar(this.selectedDates.start);
						if (this.options.updateOnBlur) this.updateInput();
					}
				}.bind(this));
			}
		}, this);
		this.options.additionalShowLinks.each(function(lnk){
			$(lnk).addEvent('click', this.show.bind(this))
		}, this);
	},
	getDates: function(dates, getFromInputs){
		var d = {};
		if (!getFromInputs) dates = dates||this.selectedDates;
		var getFromInput = function(when){
			var input = this.inputs.get(when);
			if (input) d[when] = this.validDate(input.get('value'));
		}.bind(this);
		this.whens.each(function(when) {
			switch($type(dates)){
				case "object":
					if (dates) d[when] = dates[when]?dates[when]:dates;
					if (!d[when] && !d[when].format) getFromInput(when);
					break;
				default:
					getFromInput(when);
					break;
			}
			if (!d[when]) d[when] = this.selectedDates[when]||new Date();
		}, this);
		return d;
	},
	updateInput: function(){
		var d = {};
		$each(this.getDates(), function(value, key){
			var input = this.inputs.get(key);
			if (!input) return;
			input.set('value', (value)?this.formatDate(value)||"":"");
		}, this);
		return this;
	},
	validDate: function(val) {
		if (!$chk(val)) return null;
		var date = Date.parse(val.trim());
		return isNaN(date)?null:date;
	},
	formatDate: function (date) {
		return date.format(this.options.format);
	},
	getCalendar: function() {
		if(!this.calendar) {
			var cal = new Element("table", {
				'id': this.options.calendarId,
				'border':'0',
				'cellpadding':'0',
				'cellspacing':'0'
			}).addClass('datePicker');
			var tbody = new Element('tbody').inject(cal);
			var rows = [];
			(8).times(function(i){
				var row = new Element('tr').inject(tbody);
				(7).times(function(i){
					var td = new Element('td').inject(row).set('html', '&nbsp;');
				});
			});
			var rows = tbody.getElements('tr');
			rows[0].addClass('dateNav');
			rows[1].addClass('dayNames');
			(6).times(function(i){
				rows[i+1].addClass('dayRow');
			});
			this.rows = rows;
			var dayCells = rows[1].getElements('td');
			dayCells.each(function(cell, i){
				cell.firstChild.data = Date.$days[i].substring(0,3);
			});
			[6,5,4,3].each(function(i){ rows[0].getElements('td')[i].dispose() });
			this.prevLnk = rows[0].getElement('td').setStyle('text-align', 'right');
			if(!Browser.Engine.trident4) this.prevLnk.adopt(new Element('a').set('html', String.fromCharCode(9668)).addClass('rightScroll'));
			else this.prevLnk.adopt(new Element('a').set('html', '&lt;').addClass('rightScroll'));
			this.month = rows[0].getElements('td')[1];
			this.month.set('colspan', 5);
			this.nextLnk = rows[0].getElements('td')[2].setStyle('text-align', 'left');
			if(!Browser.Engine.trident4) this.nextLnk.adopt(new Element('a').set('html', String.fromCharCode(9658)).addClass('leftScroll'));
			else this.nextLnk.adopt(new Element('a').set('html', '&gt;').addClass('leftScroll'));
			cal.addEvent('click', this.clickCalendar.bind(this));
			this.calendar = cal;
			this.container = new Element('div').adopt(cal).addClass('calendarHolder');
			this.content = StickyWin.ui('', this.container, {
				cornerHandle: this.options.stickyWinOptions.draggable,
				width: this.calWidth
			});
			//make stickywin
			var opts = $merge(this.options.stickyWinOptions, {
				content: this.content,
				className: 'datePickerSW',
				allowMultipleByClass: true,
				showNow: false,
				relativeTo: this.inputs.get('start')
			});
			this.stickyWin = new this.options.stickyWinToUse(opts);
			var closer = this.content.getElement('div.closeButton');
			if (closer)closer.setStyle('z-index', this.stickyWin.win.getStyle('z-index').toInt()+2);
		}
		return this.calendar;
	},
	hide: function(){
		this.stickyWin.hide();
		this.fireEvent('onHide');
		return this;
	},
	show: function(){
		this.selectedDates = {};
		var dates = this.getDates(null, true);
		this.whens.each(function(when){
			this.inputDates[when] = dates[when]?dates[when].clone():dates.start?dates.start.clone():this.today;
	    this.selectedDates[when] = !this.inputDates[when] || isNaN(this.inputDates[when]) 
					? this.today 
					: this.inputDates[when].clone();
			this.getCalendar(when);
		}, this);
    this.fillCalendar(this.selectedDates.start);
		this.stickyWin.show();
		this.fireEvent('onShow');
		return this;
	},
	handleScroll: function(e){
		if (e.target.hasClass('rightScroll')||e.target.hasClass('leftScroll')) {
			var newRef = e.target.hasClass('rightScroll')
				?this.rows[2].getElement('td').refDate - Date.$units.day()
				:this.rows[7].getElements('td')[6].refDate + Date.$units.day();
			this.fillCalendar(new Date(newRef));
			return true;
		}
		return false;
	},
	setSelectedDates: function(e, newDate){
		this.selectedDates.start = newDate;
	},
	onPick: function(){
		this.updateSelectors();
		this.inputs.each(function(input) {
			input.fireEvent("change");
			input.fireEvent("blur");
		});
		this.fireEvent('onPick');
		if(this.options.hideCalendarOnPick) this.hide();
	},
	clickCalendar: function(e) {
		if (this.handleScroll(e)) return;
		if (!e.target.firstChild || !e.target.firstChild.data) return;
		var val = e.target.firstChild.data;
		if (e.target.refDate) {
			var newDate = new Date(e.target.refDate);
			this.setSelectedDates(e, newDate);
			/* trip onchange events in text field */
			this.updateInput();
			this.onPick();
		}
	},
	fillCalendar: function (date) {
		if ($type(date) == "string") date = new Date(date);
		var startDate = (date)?new Date(date.getTime()):new Date();
		startDate.setDate(1);
		startDate.setTime(startDate.getTime() - (Date.$units.day() * startDate.getDay()));
		this.rows[0].getElements('td')[1].firstChild.data = Date.$months[date.getMonth()] + " " + date.getFullYear();
		var atDate = startDate.clone();
		this.rows.each(function(row, i){
			if(i < 2) return;
			row.getElements('td').each(function(td){
				td.firstChild.data = atDate.getDate();
				td.refDate = atDate.getTime();
				atDate.setTime(atDate.getTime() + Date.$units.day());
			}, this);
		}, this);
		this.updateSelectors();
	},
	updateSelectors: function(){
		var atDate;
		var month = new Date(this.rows[5].getElement('td').refDate).getMonth();
		this.rows.each(function(row, i){
			if(i < 2) return;
			row.getElements('td').each(function(td){
				td.className = '';
				atDate = new Date(td.refDate);
				if(atDate.format("%x") == this.today.format("%x")) td.addClass('today');
				this.whens.each(function(when){
					var date = this.selectedDates[when];
					if(date && atDate.format("%x") == date.format("%x")) {
						td.addClass('selectedDate');
						this.fireEvent('selectedDateMatch', [td, when]);
					}
				}, this);
				this.fireEvent('rowDateEvaluated', [atDate, td]);
				if(atDate.getMonth() != month) td.addClass('otherMonthDate');
				atDate.setTime(atDate.getTime() + Date.$units.day());
			}, this);
		}, this);
	}
});

/*
Script: DatePicker.Extras.js
	Extends DatePicker to allow for range selection and time entry.

License:
	http://clientside.cnet.com/wiki/cnet-libraries#license
*/
DatePicker = new Class({
	Extends: DatePicker,
	options:{
		extraCSS: 'a.finish {position: relative;height: 13px !important;top: -31px !important;left: 85px !important;top: -34px;left: 77px;height: 16px;display:block;float: left;padding: 1px 12px 3px !important;}'+
			'div.calendarHolder div.time {border: #999 1px solid;width: 55px;position: relative;left: 3px;height: 17px;}'+
			'div.calendarHolder td.timeTD {width: 140px;} div.calendarHolder td.label{width:35px; text-align:right}'+
			'div.calendarHolder div.time select {font-size: 10px !important; font-size: 15px;padding: 0px;left:60px;position:absolute;top:-1px !important;}'+
			'div.calendarHolder div.time input {width: 16px !important;width: 12px;padding: 2px;height: 13px;border: none !important;border: 1px solid #fff;}'+
			'div.calendarHolder div.timeSub {clear:both;position: relative;width: 65px;}'+
			'div.calendarHolder div.timeSub span {text-align: center;color: #999;margin: 5px;}'+
			'div.calendarHolder span.seperator {position:relative;top:-3px;}'+
			'div.calendarHolder table.stamp {position:relative;top: 35px !important;top: 50px;left: 0px;}'+
			'div.calendarHolder table.stamp a {left:123px;position:relative;top:9px;}'+
			'div.calendarHolder td.selected_end {border-width: 1px 1px 1px 0px !important;margin: 0px 0px 0px 1px !important;}'+
			'div.calendarHolder td.selected_start {border-width: 1px 0px 1px 1px !important;margin: 0px 1px 0px 0px !important;}'+
			'div.calendarHolder table.datePicker td.range {background: #dcddef;border: solid #20397b;border-width: 1px 0px;margin: 0px 1px !important;}',
		range: false,
		time: false
	},
	initialize: function(inputs, options){
		if (options && (options.range || options.time)) {
			options = $merge({
				hideCalendarOnPick: false
			}, options);
		}
		if (options.time && !options.format) {
			options.format = "%x %X";
		}
		this.setOptions(options);
		this.whens = (this.options.range)?['start', 'end']:['start'];
		if ($type(inputs) == 'object') {
			this.inputs = $H(inputs);
		} else if ($type($(inputs)) == "element") {
			this.inputs = $H({'start': $(inputs)});
		} else if ($type(inputs) == "array"){
			inputs = $$(inputs);
			this.inputs = $H({});
			this.whens.each(function(when, i){
				this.inputs.set(when, inputs[i]);
			}, this);
		}
		if (this.options.time) this.calWidth = 460;
		this.parent(inputs, this.options);
		this.createStyle(this.options.extraCSS, 'datePickerPlusStyle');
		this.addEvent('rowDateEvaluated', function(atDate, td){
			if (this.options.range && this.selectedDates.start.diff(atDate, 'minute') > 0 
					&& this.selectedDates.end.diff(atDate, 'minute') < 0) td.addClass('range');
		}.bind(this));
		this.addEvent('selectedDateMatch', function(td, when){
			if(this.options.range) td.addClass('selected_'+when);
		}.bind(this));
	},
	updateInput: function(){
		this.parent();
		if (this.options.time) this.updateView();
	},
	updateView: function() {
		this.whens.each(function(when){
			var stamp = this.stamps[when];
			var date = this.getDates()[when];
			stamp.date.set('html', date?date.format("%b. %d, %Y"):"");
			if (stamp.hr) {
				stamp.hr.set('value', date?date.format("%I"):"");
				stamp.min.set('value', date?date.format("%M"):"");
			}
		}, this);
	},
	stamps: {},
	setupWideView: function(){
		var timeStampMap = {
			hr: '%I',
			'min': '%M'
		};
		timeSetMap = {
			hr: 'setHours',
			'min':'setMinutes'
		};
		var dates = this.getDates();
	
		if (!this.options.range && !this.options.time) return;
		this.stamps.table = new Element('table', {
			'class':'stamp'
		}).inject(this.container);
		this.stamps.tbody = new Element('tbody').inject(this.stamps.table);
		this.whens.each(function(when){
			this.stamps[when] = {};
			var s = this.stamps[when]; //saving some bytes
			s.container = new Element('tr').addClass(when+'_stamp').inject(this.stamps.tbody);
			s.label = new Element('td').inject(s.container).addClass('label');
			if(this.whens.length == 1) {
				s.label.set('html', 'date:');
			} else {
				s.label.set('html', when=="start"?"from:":"to:");
			}
			s.date = new Element('td').inject(s.container);
			if (this.options.time) {
				currentWhen = dates[when]||new Date();
				s.time = new Element('tr').inject(this.stamps.tbody);
				new Element('td').inject(s.time);
				s.timeTD = new Element('td').inject(s.time);
				s.timeInputs = new Element('div').addClass('time clearfix').inject(s.timeTD);
				s.timeSub = new Element('div', {'class':'timeSub'}).inject(s.timeTD);
				['hr','min'].each(function(t, i){
					s[t] = new Element('input', {
						type: 'text',
						'class': t,
						name: t,
						events: {
							focus: function(){
								this.select();
							},
							change: function(){
								this.selectedDates[when][timeSetMap[t]](s[t].get('value'));
								this.selectedDates[when].setAMPM(s.ampm.get('value'));
								this.updateInput();
							}.bind(this)
						}
					}).inject(s.timeInputs);
					s[t].set('value', currentWhen.format(timeStampMap[t]));
					if (i < 1) s.timeInputs.adopt(new Element('span', {'class':'seperator'}).set('html', ":"));
					new Element('span', {
						'class': t
					}).set('html', t).inject(s.timeSub);
				}, this);
				s.ampm = new Element('select').inject(s.timeInputs);
				['AM','PM'].each(function(ampm){
					var opt = new Element('option', {
						value: ampm,
						text: ampm.toLowerCase()
					}).set('html', ampm).inject(s.ampm);
					if (ampm == currentWhen.format("%p")) opt.selected = true;
				});
				s.ampm.addEvent('change', function(){
					var date = this.getDates()[when];
					var ampm = s.ampm.get('value');
					if (ampm != date.format("%p")) {
						date.setAMPM(ampm);
						this.updateInput();
					}
				}.bind(this));
			}
		}, this);
		new Element('tr').inject(this.stamps.tbody).adopt(new Element('td', {colspan: 2}).adopt(new Element('a', {
			'class':'closeSticky button',
			events: {
				click: function(){
					this.hide();
				}.bind(this)
			}
		}).set('html', 'Ok')));
	},
	show: function(){
		this.parent();
		if (this.options.time) {
			if (!this.stamps.table) this.setupWideView();
			this.updateView();
		}
	},
	startSet: false,
	onPick: function(){
		if((this.options.range && this.selectedDates.start && this.selectedDates.end) || !this.options.range) {
			this.parent();
		}
	},
	setSelectedDates: function(e, newDate) {
		if(this.options.range) {
			if (this.selectedDates.start && this.startSet) {
				if (this.selectedDates.start.getTime() > newDate.getTime()){
					this.selectedDates.end = new Date(this.selectedDates.start);
					this.selectedDates.start = newDate;
				} else {
					this.selectedDates.end = newDate;
				}
				this.startSet = false;
			} else {
				this.selectedDates.start = newDate;
				if (this.selectedDates.end && this.selectedDates.start.getTime() > this.selectedDates.end.getTime())
					this.selectedDates.end = new Date(newDate);
				this.startSet = true;
			}
		} else {
			this.parent(e, newDate);
		}
		if(this.options.time) {
			this.whens.each(function(when){
				var hr = this.stamps[when].hr.get('value').toInt();
				if (this.stamps[when].ampm.get('value') == "PM" && hr < 12) hr += 12;
				this.selectedDates[when].setHours(hr);
				this.selectedDates[when].setMinutes(this.stamps[when]['min'].get('value')||"0");
				this.selectedDates[when].setAMPM(this.stamps[when].ampm.get('value')||"AM");
			}, this);
		}
	}
});

/*
Script: FormValidator.js
	A css-class based form validation system.

License:
	http://clientside.cnet.com/wiki/cnet-libraries#license
*/
var InputValidator = new Class({
	Implements: [Options],
	initialize: function(className, options){
		this.setOptions({
			errorMsg: 'Validation failed.',
			test: function(field){return true}
		}, options);
		this.className = className;
	},
	test: function(field){
		if($(field)) return this.options.test($(field), this.getProps(field));
		else return false;
	},
	getError: function(field){
		var err = this.options.errorMsg;
		if($type(err) == "function") err = err($(field), this.getProps(field));
		return err;
	},
	getProps: function(field){
		if($(field) && $(field).get('validatorProps')){
			try {
				return JSON.decode($(field).get('validatorProps'));
			}catch(e){ return {}}
		} else {
			return {}
		}
	}
});

var FormValidator = new Class({
	Implements:[Options, Events],
	options: {
		fieldSelectors:"input, select, textarea",
		useTitles:false,
		evaluateOnSubmit:true,
		evaluateFieldsOnBlur: true,
		evaluateFieldsOnChange: true,
		serial: false,
		warningPrefix: "",
		errorPrefix: ""
//	onFormValidate: function(isValid, form){},
//	onElementValidate: function(isValid, field){}
	},
	initialize: function(form, options){
		this.setOptions(options);
		this.form = $(form);
		this.form.store('validator', this);
		if(this.options.evaluateOnSubmit) this.form.addEvent('submit', this.onSubmit.bind(this));
		if(this.options.evaluateFieldsOnBlur) this.watchFields();
	},
	toElement: function(){
		return this.form;
	},
	getFields: function(){
		return this.fields = this.form.getElements(this.options.fieldSelectors)
	},
	watchFields: function(){
		this.getFields().each(function(el){
				el.addEvent('blur', this.validateField.pass([el, false], this));
			if(this.options.evaluateFieldsOnChange)
				el.addEvent('change', this.validateField.pass([el, true], this));
		}, this);
	},
	onSubmit: function(event){
		if(!this.validate(event) && event) event.stop();
		else {
			this.stop();
			this.reset();
		}
	},
	reset: function() {
		this.getFields().each(this.resetField, this);
		return this;
	}, 
	validate: function(event) {
		var result = this.getFields().map(function(field) { return this.validateField(field, true); }, this);
		result = result.every(function(val){
			return val;
		});
		this.fireEvent('onFormValidate', [result, this.form, event]);
		return result;
	},
	validateField: function(field, force){
		if(this.paused) return true;
		field = $(field);
		var result = true;
		var failed = this.form.getElement('.validation-failed');
		var warned = this.form.getElement('.warning');
		if(field && (!failed || force || field == failed || (failed && !this.options.serial))){
			var validators = field.className.split(" ").some(function(cn){
				return this.getValidator(cn);
			}, this);
			result = field.className.split(" ").map(function(className){
				return this.test(className,field);
			}, this).every(function(val){
				return val;
			});
			if (validators && !field.hasClass('warnOnly')){
				if(result) field.addClass('validation-passed').removeClass('validation-failed');
				else field.addClass('validation-failed').removeClass('validation-passed');
			}
			if(!warned || force || (warned && !this.options.serial)) {
				var warnings = field.className.split(" ").some(function(cn){
					if(cn.test('^warn-') || field.hasClass('warnOnly')) 
						return this.getValidator(cn.replace(/^warn-/,""));
					else return null;
				}, this);
				field.removeClass('warning');
				var warnResult = field.className.split(" ").map(function(cn){
					if(cn.test('^warn-') || field.hasClass('warnOnly')) 
						return this.test(cn.replace(/^warn-/,""), field, true);
					else return null;
				}, this);
			}
		}
		return result;
	},
	getPropName: function(className){
		return '__advice'+className;
	},
	test: function(className, field, warn){
		field = $(field);
		if(field.hasClass('ignoreValidation')) return true;
		warn = $pick(warn, false);
		if(field.hasClass('warnOnly')) warn = true;
		var isValid = true;
		if(field) {
			var validator = this.getValidator(className);
			if(validator && this.isVisible(field)) {
				isValid = validator.test(field);
				if(!isValid && validator.getError(field)){
					if(warn) field.addClass('warning');
					var advice = this.makeAdvice(className, field, validator.getError(field), warn);
					this.insertAdvice(advice, field);
					this.showAdvice(className, field);
				} else this.hideAdvice(className, field);
				this.fireEvent('onElementValidate', [isValid, field]);
			}
		}
		if(warn) return true;
		return isValid;
	},
	showAdvice: function(className, field){
		var advice = this.getAdvice(className, field);
		if(advice && !field[this.getPropName(className)] 
			 && (advice.getStyle('display') == "none" 
			 || advice.getStyle('visiblity') == "hidden" 
			 || advice.getStyle('opacity')==0)){
			field[this.getPropName(className)] = true;
			if(advice.reveal) advice.reveal();
			else advice.setStyle('display','block');
		}
	},
	hideAdvice: function(className, field){
		var advice = this.getAdvice(className, field);
		if(advice && field[this.getPropName(className)]) {
			field[this.getPropName(className)] = false;
			//if element.cnet.js is present, transition the advice out
			if(advice.dissolve) advice.dissolve();
			else advice.setStyle('display','none');
		}
	},
	isVisible : function(field) {
		while(field != document.body) {
			if($(field).getStyle('display') == "none") return false;
			field = field.getParent();
		}
		return true;
	},
	getAdvice: function(className, field) {
		return $('advice-' + className + '-' + this.getFieldId(field))
	},
	makeAdvice: function(className, field, error, warn){
		var errorMsg = (warn)?this.options.warningPrefix:this.options.errorPrefix;
				errorMsg += (this.options.useTitles) ? $pick(field.title, error):error;
		var advice = this.getAdvice(className, field);
		if(!advice){
			var cssClass = (warn)?'warning-advice':'validation-advice';
			advice = new Element('div', {
				text: errorMsg,
				styles: { display: 'none' },
				id: 'advice-'+className+'-'+this.getFieldId(field)
			}).addClass(cssClass);
			advice.set('html', errorMsg);
		} else{
			advice.set('html', errorMsg);
		}
		return advice;
	},
	insertAdvice: function(advice, field){
		switch (field.type.toLowerCase()) {
			case 'radio':
				var p = $(field.parentNode);
				if(p) {
					p.adopt(advice);
					break;
				}
			default: advice.inject($(field), 'after');
	  };
	},
	getFieldId : function(field) {
		return field.id ? field.id : field.id = "input_"+field.name;
	},
	resetField: function(field) {
		field = $(field);
		if(field) {
			var cn = field.className.split(" ");
			cn.each(function(className) {
				if(className.test('^warn-')) className = className.replace(/^warn-/,"");
				var prop = this.getPropName(className);
				if(field[prop]) this.hideAdvice(className, field);
				field.removeClass('validation-failed');
				field.removeClass('warning');
				field.removeClass('validation-passed');
			}, this);
		}
		return this;
	},
	stop: function(){
		this.paused = true;
		return this;
	},
	start: function(){
		this.paused = false;
		return this;
	},
	ignoreField: function(field, warn){
		field = $(field);
		if(field){
			this.enforceField(field);
			if(warn) field.addClass('warnOnly');
			else field.addClass('ignoreValidation');
		}
		return this;
	},
	enforceField: function(field){
		field = $(field);
		if(field) field.removeClass('warnOnly').removeClass('ignoreValidation');
		return this;
	}
});

FormValidator.adders = {
	validators:{},
	add : function(className, options) {
		this.validators[className] = new InputValidator(className, options);
		//if this is a class
		//extend these validators into it
		if(!this.initialize){
			this.implement({
				validators: this.validators
			});
		}
	},
	addAllThese : function(validators) {
		$A(validators).each(function(validator) {
			this.add(validator[0], validator[1]);
		}, this);
	},
	getValidator: function(className){
		return this.validators[className];
	}
};
$extend(FormValidator, FormValidator.adders);
FormValidator.implement(FormValidator.adders);

FormValidator.add('IsEmpty', {
	errorMsg: false,
	test: function(element) { 
		if(element.type == "select-one"||element.type == "select")
			return !(element.selectedIndex >= 0 && element.options[element.selectedIndex].value != "");
		else
			return ((element.get('value') == null) || (element.get('value').length == 0));
	}
});


FormValidator.addAllThese([
	['required', {
		errorMsg: function(element){return INPUT_MUST_FILL}, 
		test: function(element) { 
			return !FormValidator.getValidator('IsEmpty').test(element); 
		}
	}],
	['minLength', {
		errorMsg: function(element, props){
			if($type(props.minLength))
				return STRING_MIN_LENGTH.replaceAll('{gesamt}', props.minLength).replace('{anzahl}', element.get('value').length);
			else return '';
		}, 
		test: function(element, props) {
			if($type(props.minLength)) return (element.get('value').length >= $pick(props.minLength, 0));
			else return true;
		}
	}],
	['maxLength', {
		errorMsg: function(element, props){
			//props is {maxLength:10}
			if($type(props.maxLength))
				return STRING_MAX_LENGTH.replaceAll('{gesamt}', props.minLength).replace('{anzahl}', element.get('value').length);
			else return '';
		}, 
		test: function(element, props) {
			//if the value is <= than the maxLength value, element passes test
			return (element.get('value').length <= $pick(props.maxLength, 10000));
		}
	}],
	['validate-integer', {
		errorMsg: NUMBERS_ONLY,
		test: function(element) {
				return FormValidator.getValidator('IsEmpty').test(element) || /^-{0,1}\d+$/.test(element.get('value'));
		}
	}],
	['validate-numeric', {
		errorMsg: NUMBERS_DOT_ONLY, 
		test: function(element) {
			return FormValidator.getValidator('IsEmpty').test(element) || /^-{0,1}\d*\.{0,1}\d+$/.test(element.get('value'));
		}
	}],
	['validate-numberdot', {
		errorMsg: NUMBERS_DOT_ONLY, 
		test: function(element) {
			return FormValidator.getValidator('IsEmpty').test(element) || /^-{0,1}\d*\.{0,1}\d+$/.test(element.get('value'));
		}
	}],	
	['validate-digits', {
		errorMsg: NUMBERS_ONLY, 
		test: function(element) {
			return FormValidator.getValidator('IsEmpty').test(element) || 
				(/[^a-zA-Z]/.test(element.get('value')) && /[\d]/.test(element.get('value')));
		}
	}],
	['validate-alpha', {
		errorMsg: ONLY_CHARS, 
		test: function (element) {
			return FormValidator.getValidator('IsEmpty').test(element) ||  /^[a-zA-Z]+$/.test(element.get('value'))
		}
	}],
	['validate-alphanum', {
		errorMsg: ONLY_CHARS_NUMBER, 
		test: function(element) {
			return FormValidator.getValidator('IsEmpty').test(element) || !/\W/.test(element.get('value'))
		}
	}],
	['validate-date', {
		errorMsg: DATE_VALID_EU,
		test: function(element) {
			if(FormValidator.getValidator('IsEmpty').test(element)) return true;
	    var regex = /^(\d{4})-(\d{2})-(\d{2}) (\d{2}):(\d{2}):(\d{2})$/;
	    if(!regex.test(element.get('value'))) return false;
	    var d = new Date(element.get('value').replace(regex,  '$2/$3/$1'));
	    return (parseInt(RegExp.$2, 10) == (1+d.getMonth())) && 
        (parseInt(RegExp.$3, 10) == d.getDate()) && 
        (parseInt(RegExp.$1, 10) == d.getFullYear() );
		}
	}],
	['validate-email', {
		errorMsg: VALID_EMAIL, 
		test: function (element) {
			return FormValidator.getValidator('IsEmpty').test(element) || /\w{1,}[@][\w\-]{1,}([.]([\w\-]{1,})){1,3}$/.test(element.get('value'));
		}
	}],
	['validate-url', {
		errorMsg: VALID_URL, 
		test: function (element) {
			return FormValidator.getValidator('IsEmpty').test(element) || /^(http|https|ftp|rmtp|mms):\/\/(([A-Z0-9][A-Z0-9_-]*)(\.[A-Z0-9][A-Z0-9_-]*)+)(:(\d+))?\/?/i.test(element.get('value'));
		}
	}],
	['validate-currency-dollar', {
		errorMsg: 'Please enter a valid $ amount. For example $100.00 .', 
		test: function(element) {
			// [$]1[##][,###]+[.##]
			// [$]1###+[.##]
			// [$]0.##
			// [$].##
			return FormValidator.getValidator('IsEmpty').test(element) ||  /^\$?\-?([1-9]{1}[0-9]{0,2}(\,[0-9]{3})*(\.[0-9]{0,2})?|[1-9]{1}\d*(\.[0-9]{0,2})?|0(\.[0-9]{0,2})?|(\.[0-9]{1,2})?)$/.test(element.get('value'));
		}
	}],
	['validate-one-required', {
		errorMsg: VALID_CHOOSE, 
		test: function (element) {
			var p = element.parentNode;
			var options = p.getElements('input');
			return $A(options).some(function(el) {
				if (el.get('type') == 'checkbox') return el.get('checked');
				return el.get('value');
			});
		}
	}],
	['validate-selection', {errorMsg: VALID_CHOOSE, test: function(v){
				return v.options ? v.selectedIndex > 0 : !FormValidator.getValidator('IsEmpty').test(v);
			}}]	
]);

/*
Script: OverText.js
        Shows text over an input that disappears when the user clicks into it. The text remains hidden if the user adds a value.


License:
        http://www.clientcide.com/wiki/cnet-libraries#license
*/
//returns a collection given an id or a selector
$G = function(elements) {
        return $splat($(elements)||$$(elements));
};
var OverText = new Class({
        Implements: [Options, Events],
        options: {
//      textOverride: null,
                positionOptions: {
                        position:"upperLeft",
                        edge:"upperLeft",
                        offset: {
                                x: 4,
                                y: 2
                        }
                },
                poll: false,
                pollInterval: 250
//      onTextHide: $empty,
//      onTextShow: $empty
        },
        overTxtEls: [],
        initialize: function(inputs, options) {
                this.setOptions(options);
                $G(inputs).each(this.addElement, this);
                OverText.instances.push(this);
                if (this.options.poll) this.poll();
        },
        addElement: function(el){
                if (el.retrieve('OverText')) return;
                var val = this.options.textOverride || el.get('alt') || el.get('title');
                if (!val) return;
                this.overTxtEls.push(el);
                var txt = new Element('div', {
                  'class': 'overTxtDiv',
                        styles: {
                                lineHeight: 'normal',
                                position: 'absolute'
                        },
                  html: val,
                  events: {
                    click: this.hideTxt.pass([el, true], this)
                  }
                }).inject(el, 'after');
                el.addEvents({
                        focus: this.hideTxt.pass([el, true], this),
                        blur: this.testOverTxt.pass(el, this),
                        change: this.testOverTxt.pass(el, this)
                }).store('OverTextDiv', txt).store('OverText', this);
                window.addEvent('resize', this.repositionAll.bind(this));
                this.testOverTxt(el);
                this.repositionOverTxt(el);
        },
        startPolling: function(){
                this.pollingPaused = false;
                return this.poll();
        },
        poll: function(stop) {
                //start immediately
                //pause on focus
                //resumeon blur
                if (this.poller && !stop) return this;
                var test = function(){
                        if (this.pollingPaused == true) return;
                        this.overTxtEls.each(function(el){
                                if (el.retrieve('ot_paused')) return;
                                this.testOverTxt(el);
                        }, this);
                }.bind(this);
                if (stop) $clear(this.poller);
                else this.poller = test.periodical(this.options.pollInterval, this);
                return this;
        },
        stopPolling: function(){
                this.pollingPaused = true;
                return this.poll(true);
        },
        hideTxt: function(el, focus){
                var txt = el.retrieve('OverTextDiv');
                if (txt && txt.isVisible() && !el.get('disabled')) {
                        txt.hide(); 
                        try {
                                if (focus) el.fireEvent('focus').focus();
                        } catch(e){} //IE barfs if you call focus on hidden elements
                        this.fireEvent('onTextHide', [txt, el]);
                        el.store('ot_paused', true);
                }
                return this;
        },
        showTxt: function(el){
                var txt = el.retrieve('OverTextDiv');
                if (txt && !txt.isVisible()) {
                        txt.show();
                        this.fireEvent('onTextShow', [txt, el]);
                        el.store('ot_paused', false);
                }
                return this;
        },
        testOverTxt: function(el){
                if (el.get('value')) this.hideTxt(el);
                else this.showTxt(el);  
        },
        repositionAll: function(){
                this.overTxtEls.each(this.repositionOverTxt.bind(this));
                return this;
        },
        repositionOverTxt: function (el){
                if (!el) return;
                try {
                        var txt = el.retrieve('OverTextDiv');
                        if (!txt || !el.getParent()) return;
                        this.testOverTxt(el);
                        txt.setPosition($merge(this.options.positionOptions, {relativeTo: el}));
                        if (el.offsetHeight) this.testOverTxt(el);
                        else this.hideTxt(el);		
                } catch(e){
                        dbug.log('overTxt error: ', e);
                }
                return this;
        }
});
OverText.instances = [];
OverText.update = function(){
        return OverText.instances.map(function(ot){
                return ot.repositionAll();
        });
};
/*
Script: ProductPicker.js
	Allows the user to pick a product from a data source.

License:
	http://clientside.cnet.com/wiki/cnet-libraries#license
*/
var Picklet = new Class({
	Implements: [Options, Events],
/* options: {
		onShow: $empty
	 }, */ 
	inputElements : {},
	initialize: function(className, options){
		this.setOptions(options);
		this.className = className;
		this.getQuery = this.options.getQuery;
	}
});
var ProductPicker = new Class({
	Implements: [Options, Events, StyleWriter],
	options: {
//	onShow: $empty,
//	onPick: $empty,
		title: 'Product picker',
		showOnFocus: true,
		additionalShowLinks: [],
		stickyWinToUse: StickyWinFx,
		stickyWinOptions: {
			fadeDuration : 200,
			draggable : true
		},
		moveIntoView: true,
		baseHref: 'templates/'+DESIGN+'/images/javascripts/Picker',
		css: "div.productPickerProductDiv div.results { overflow: 'auto'; width: 100%; margin-top: 4px }"+
			"div.productPickerProductDiv select { margin: 4px 0px 4px 0px}"+
			"div.pickerPreview div.sliderContent img {border: 1px solid #000}"+
			"div.pickerPreview div.sliderContent a {color: #0d63a0}" + 
			"div.productPickerProductDiv * {color: #000}" +
			".tool-tip { color: #fff; width: 172px; z-index: 13000; }" +
			".tool-title { font: Verdana, Arial, Helvetica, sans-serif; font-weight: bold; font-size: 11px; margin: 0; padding: 8px 8px 4px; background: url(%tipsArt%/bubble.png) top left !important; background: url(%tipsArt%/bubble.gif) top left; }" +
			".tool-text {font-size: 11px; margin: 0px; padding: 4px 8px 8px; background: url(%tipsArt%/bubble.png) bottom right !important; background: url(%tipsArt%/bubble.gif) bottom right; }"
	},
	initialize: function(input, picklets, options){
		this.setOptions(options);
		this.input = $(input);
		this.picklets = picklets;
		this.setUpObservers();
		this.writeCss();
	},
	writeCss: function(){
		var art = this.options.baseHref;
		var tipsArt = art.replace("Picker", "tips");
		this.createStyle(this.options.css.replace("%tipsArt%", tipsArt, "g"), 'pickerStyles');
	},
	getPickletList: function(){
		if(this.picklets.length>1) {
			var selector = new Element('select').setStyle('width', 399);
			this.picklets.each(function(picklet, index){
				var opt = new Element('option').set('value',index);
				opt.text = picklet.options.descriptiveName;
				selector.adopt(opt);
			}, this);
			selector.addEvent('change', function(){
				this.showForm(this.picklets[selector.getSelected()[0].get('value')]);
				this.focusInput(true);
			}.bind(this));
			return selector;
		} else return false;
	},
	buildPicker: function(picklet){
		var contents = new Element('div');
		this.formBody = new Element('div');
		this.pickletList = this.getPickletList();
		if(this.pickletList) contents.adopt(this.pickletList);
		contents.adopt(this.formBody);
		var body = StickyWin.ui(this.options.title, contents, {
			width: 450,
			closeTxt: 'close'
		}).addClass('productPickerProductDiv');
		this.showForm();
		return body;
	},
	showForm: function(picklet){
		this.form = this.makeSearchForm(picklet || this.picklets[0]);
		this.formBody.empty().adopt(this.form);
		(picklet || this.picklets[0]).fireEvent('onShow');
		this.results = new Element('div').addClass('results');
		this.formBody.adopt(this.results);
		this.sliderFx = null;
		this.fireEvent("onShow");
	},
	makeSlider: function(){
		var png = (Browser.Engine.trident)?'gif':'png';
		this.slider = new Element('div', {
			styles: {
				background:'url('+this.options.baseHref+'/slider.'+png+') top right no-repeat',
				display: 'none',
				height:250,
				left:439,
				position:'absolute',
				top:25,
				width:0,
				overflow: 'hidden'
			}
		}).addClass('pickerPreview').inject(this.swin.win).addEvents({
			mouseover: function(){
				this.previewHover = true;
			}.bind(this),
			mouseout: function(){
				this.previewHover = false;
				(function(){if (!this.previewHover) this.hidePreview()}).delay(400, this);
			}.bind(this)
		});
		this.sliderContent = new Element('div', {
			styles: {
				width: 130,
				height: 200,
				padding: 10,
				margin: '10px 10px 0px 0px',
				overflow: 'auto',
				cssFloat: 'right'
			}
		}).inject(this.slider).addClass('sliderContent');
	},
	makeSearchForm: function(picklet){
		this.currentPicklet = picklet;
		var formTable = new Element('table', {
			styles: {
				width: "100%",
				cellpadding: '0',
				cellspacing: '0'
			}
		});
		var tBody = new Element('tbody').inject(formTable);
		var form = new Element('form').addEvent('submit', function(e){
			this.getResults(e.target, picklet);
		}.bind(this)).adopt(formTable).set('action','javascript:void(0);');
		$each(picklet.options.inputs, function(val, name){
			var ins = this.getSearchInputTr(val, name);
			tBody.adopt(ins.holder);
			picklet.inputElements[name] = ins.input;
		}, this);
		return form;
	},
	getSearchInputTr: function(val, name){
		try{
			var style = ($type(val.style))?val.style:{};
			//create the input object
			//this is I.E. hackery, because IE does not let you set the name of a DOM element.
			//thanks MSFT.
			var input = (Browser.Engine.trident)?new Element('<' + val.tagName + ' name="' + name + '" />'):
					new Element(val.tagName, {name: name});
			input.setStyles(style);
			if(val.type)input.set('type', val.type);
			if(val.tip && Tips){
				input.set('title', val.tip);
				new Tips([input], {
					onShow: function(tip){
						this.shown = true;
						(function(){
              if(!this.shown) return;
              $(tip).setStyles({ display:'block', opacity: 0 });
              new Fx.Tween(tip, {property: 'opacity', duration: 300 }).start(0,.9);
            }).delay(500, this);
          },
          onHide: function(tip){
            tip.setStyle('visibility', 'hidden');
            this.shown = false;
          }
        });
      }
      if(val.getTag() == "select"){
        val.value.each(function(option, index){
          var opt = new Element('option',{ value: option });
          opt.text = (val.optionNames && val.optionNames[index])?$pick(val.optionNames[index], option):option;
          input.adopt(opt);
        });
      } else {
				input.set('value', $pick(val.value,""));
			}
      var holder = new Element('tr');
			var colspan=0;
			if (val.instructions) holder.adopt(new Element('td').set('html', val.instructions));
			else colspan=2;
			var inputTD = new Element('td').adopt(input);
			if (colspan) inputTD.set('colspan', colspan);
			holder.adopt(inputTD);
			return { holder : holder, input : input};
		}catch(e){dbug.log(e); return false;}
	},
	getResults: function(form, picklet){
		if(form.getTag() != "form") form = $$('form').filter(function(fm){ return fm.hasChild(form) })[0];
		if(!form) {
			dbug.log('error computing form');
			return null;
		}
		var query = picklet.getQuery(unescape(form.toQueryString()).parseQuery());
		query.addEvent('onComplete', this.showResults.bind(this));
		query.request();
		return this;
	},
	showResults: function(data){
		var empty = false;
		if(this.results.innerHTML=='') {
			empty = true;
			this.results.setStyles({
				height: 0,
				border: '1px solid #666',
				padding: 0,
				overflow: 'auto',
				opacity: 0
			});
		} else this.results.empty();
		this.items = this.currentPicklet.options.resultsList(data);
		if(this.items && this.items.length > 0) {
			this.items.each(function(item, index){
				var name = this.currentPicklet.options.listItemName(item);
				var value = this.currentPicklet.options.listItemValue(item);
				this.results.adopt(this.makeProductListEntry(name, value, index));
			}, this);
		} else {
			this.results.set('html', "Sorry, there don't seem to be any items for that search");
		}
		this.results.morph({ height: 200, opacity: 1 });
		this.listStyles();
		this.getOnScreen.delay(500, this);
	},
	getOnScreen: function(){
		if(document.compatMode == "BackCompat") return;
		var s = this.swin.win.getCoordinates();
		if(s.top < window.getScroll().y) {
			this.swin.win.tween('top', window.getScroll().y+50);
			return;
		}
		if(s.top+s.height > window.getScroll().y+window.getSize().y && window.getSize().y>s.height) {
			this.swin.win.tween('top', window.getScroll().y+window.getSize().y-s.height-100);
			return;
		}
		try{this.swin.shim.show.delay(500, this.swin.shim);}catch(e){}
		return;
	},
	listStyles: function(){
		var defaultStyle = {
			cursor: 'pointer',
			borderBottom: '1px solid #ddd',
			padding: '2px 8px 2px 8px',
			backgroundColor:'#fff',
			color: '#000',
			fontWeight: 'normal'
		};
		var hoverStyle = {
			backgroundColor:'#fcfbd1',
			color: '#d56a00'
		};
		var selectedStyle = $extend(defaultStyle, {
			color: '#D00000',
			fontWeight: 'bold',
			backgroundColor: '#eee'
		});
		this.results.getElements('div.productPickerProductDiv').each(function(p){
			var useStyle = (this.input.value.toInt() == p.get('val').toInt())?selectedStyle:defaultStyle;
			p.setStyles(useStyle);
			if(!Browser.Engine.trident) {//ie doesn't like these mouseover behaviors...
				p.addEvent('mouseover', function(){ p.setStyles(hoverStyle); }.bind(this));
				p.addEvent('mouseout', function(){ p.setStyles(useStyle); });
			}
		}, this);
	},
	makeProductListEntry: function(name, value, index){
		var pDiv = new Element("div").addClass('productPickerProductDiv').adopt(
				new Element("div").set('html', name)
			).set('val', value);
		pDiv.addEvent('mouseenter', function(e){
			this.preview = true;
			this.sliderContent.empty();
			var content = this.getPreview(index);
			if($type(content)=="string") this.sliderContent.set('html', content);
			else if($(content)) this.sliderContent.adopt(content);
			this.showPreview.delay(200, this);
		}.bind(this));
		pDiv.addEvent('mouseleave', function(e){
			this.preview = false;
			(function(){if(!this.previewHover) this.hidePreview();}).delay(400, this);
		}.bind(this));
		pDiv.addEvent('click', function(){
			this.currentPicklet.options.updateInput(this.input, this.items[index]);
			this.fireEvent('onPick', [this.input, this.items[index], this]);
			this.hide();
			this.listStyles.delay(200, this);
		}.bind(this));
		return pDiv;
	},
	makeStickyWin: function(){
		if(document.compatMode == "BackCompat") this.options.stickyWinOptions.relativeTo = this.input;
		this.swin = new this.options.stickyWinToUse($merge(this.options.stickyWinOptions, {
			draggable: true,
			content: this.buildPicker()
		}));
	},
	focusInput: function(force){
		if ((!this.focused || $pick(force,false)) && this.form.getElement('input')) {
			this.focused = true;
			try { this.form.getElement('input').focus(); } catch(e){}
		}
	},
	show: function(){
		if (!this.swin) this.makeStickyWin();
		if (!this.slider) this.makeSlider();
		if (!this.swin.visible) this.swin.show();
		this.focusInput();
		return this;
	},
	hide: function(){
		$$('.tool-tip').hide();
		this.swin.hide();
		this.focused = false;
		return this;
	},
	setUpObservers: function(){
		try {
			if(this.options.showOnFocus) this.input.addEvent('focus', this.show.bind(this));
			if(this.options.additionalShowLinks.length>0) {
				this.options.additionalShowLinks.each(function(lnk){
					$(lnk).addEvent('click', this.show.bind(this));
				}, this);
			}
		}catch(e){dbug.log(e);}
	},
	showPreview: function(index){
		width = this.currentPicklet.options.previewWidth || 150;
		this.sliderContent.setStyle('width', (width-30));
		if(!this.sliderFx) this.sliderFx = new Fx.Elements([this.slider, this.swin.win]);
		this.sliderFx.clearChain();
		this.sliderFx.setOptions({
			duration: 1000, 
			transition: 'elastic:out'
		});
		if(this.preview && this.slider.getStyle('width').toInt() < width-5) {
			this.slider.show('block');
			this.sliderFx.start({
				'0':{//the slider
					'width':width
				},
				'1':{//the popup window (for ie)
					'width':width+450
				}
			});
		}
	},
	hidePreview: function(){
		if(!this.preview) {
			this.sliderFx.setOptions({
				duration: 250, 
				transition: 'back:in'
			});
			this.sliderFx.clearChain();
			this.sliderFx.start({
				'0':{//the slider
					'width':[this.slider.getStyle('width').toInt(),0]
				},
				'1':{//the popup window (for ie)
					'width':[this.swin.win.getStyle('width').toInt(), 450]
				}
			}).chain(function(){
				this.slider.hide();
			}.bind(this));
		}
	},
	getPreview: function(index){
		return this.currentPicklet.options.previewHtml(this.items[index]);
	}
});


$extend(ProductPicker, {
	picklets: [],
	add: function(picklet){
		if(! picklet.className) {
			dbug.log('error: cannot add Picklet %o; missing className: %s', picklet, picklet.className);
			return;
		}
		this.picklets[picklet.className] = picklet;
	},
	addAllThese: function(picklets){
		picklets.each(function(picklet){
			this.add(picklet);
		}, this);
	},
	getPicklet: function(className){
		return ProductPicker.picklets[className]||false;
	}
});

var FormPickers = new Class({
	Implements: [Options],
	options: {
		inputs: 'input',
		additionalShowLinkClass: 'openPicker',
		pickletOptions: {}
	},
	initialize: function(form, options){
		this.setOptions(options);
		this.form = $(form);
		this.inputs = this.form.getElements(this.options.inputs);
		this.setUpInputs();
	},
	setUpInputs: function(inputs){
		inputs = $pick(inputs, this.inputs);
		inputs.each(this.addPickers.bind(this));
	},
	addPickers: function(input){
		var picklets = [];
		input.className.split(" ").each(function(clss){
			if(ProductPicker.getPicklet(clss)) picklets.push(ProductPicker.getPicklet(clss));
		}, this);
		if(input.getNext() && input.getNext().hasClass(this.options.additionalShowLinkClass))
			this.options.pickletOptions.additionalShowLinks = [input.getNext()];
		if(picklets.length>0)	new ProductPicker(input, picklets, this.options.pickletOptions);
	}
});

/*
Script: SimpleEditor.js
	A simple html editor for wrapping text with links and whatnot.

License:
	http://clientside.cnet.com/wiki/cnet-libraries#license
*/
var SimpleEditor = new Class({
	initialize: function(input, buttons, commands){
		this.commands = new Hash($extend(SimpleEditor.commands, commands||{}));
		this.input = $(input);
		this.buttons = $$(buttons);
		this.buttons.each(function(button){
			button.addEvent('click', function() {
				this.exec(button.get('rel'));
			}.bind(this));
		}.bind(this));
		this.input.addEvent('keydown', function(e){
			if (e.control) {
				var key = this.shortCutToKey(e.key);
				if(key) {
					e.stop();
					this.exec(key);
				}
			}
		}.bind(this));
		this.input.store('editor', this);
	},
	toElement: function(){
		return this.input;
	},
	shortCutToKey: function(shortcut){
		var returnKey = false;
		this.commands.each(function(value, key){
			if(value.shortcut == shortcut) returnKey = key;
		});
		return returnKey;
	},
	addCommand: function(key, command, shortcut){
		this.commands.set(key, {
			command: command,
			shortcut: shortcut
		});
	},
	addCommands: function(commands){
		this.commands.extend(commands);
	},
	exec: function(key){
		var currentScrollPos; 
		if (this.input.scrollTop || this.input.scrollLeft) {
			currentScrollPos = {
				scrollTop: this.input.getScroll().y,
				scrollLeft: this.input.getScroll().x
			};
		}
		if(this.commands.has(key)) this.commands.get(key).command(this.input);
		if(currentScrollPos) {
			this.input.set('scrollTop', currentScrollPos.getScroll().y);
			this.input.set('scrollLeft', currentScrollPos.getScroll().x);
		}
	}
});
$extend(SimpleEditor, {
	commands: {},
	addCommand: function(key, command, shortcut){
		SimpleEditor.commands[key] = {
			command: command,
			shortcut: shortcut
		}
	},
	addCommands: function(commands){
		$extend(SimpleEditor.commands, commands);
	}
});
SimpleEditor.addCommands({
	bold: {
		shortcut: 'b',
		command: function(input){
			input.insertAroundCursor({before:'<b>',after:'</b>'});
		}
	},
	underline: {
		shortcut: 'u',
		command: function(input){
			input.insertAroundCursor({before:'<u>',after:'</u>'});
		}
	},
	anchor: {
		shortcut: 'l',
		command: function(input){
			function simpleLinker(){
				if(window.TagMaker){
					if(!this.linkBuilder) this.linkBuilder = new TagMaker.anchor();
					this.linkBuilder.prompt(input);
				} else {
					var href = window.prompt('The URL for the link');
					var opts = {before: '<a href="'+href+'">', after:'</a>'};
					if (!input.getSelectedText()) opts.defaultMiddle = window.prompt('The link text');
					input.insertAroundCursor(opts);
				}
			}
			try {
				if(Trinket) {
					if(!this.linkBulder){
						var lb = Trinket.available.filter(function(trinket){
							return trinket.name == 'Link Builder';
						});
						this.linkBuilder = (lb.length)?lb[0]:new Trinket.LinkBuilder({
							context: 'default'
						});
						this.linkBuilder.clickPrompt(input);
					}
				} else simpleLinker();
			} catch(e){ simpleLinker(); }
		}
	},
	copy: {
		shortcut: false,
		command: function(input){
			if(Clipboard) Clipboard.copyFromElement(input);
			else simpleErrorPopup('Woops', 'Sorry, this function doesn\'t work here; use ctrl+c.');
			input.focus();
		}
	},
	cut: {
		shortcut: false,
		command: function(input){
			if(Clipboard) {
				Clipboard.copyFromElement(input);
				input.insertAtCursor('');
			} else simpleErrorPopup('Woops', 'Sorry, this function doesn\'t work here; use ctrl+x.');
		}
	},
	hr: {
		shortcut: '-',
		command: function(input){
			input.insertAtCursor('\n<hr/>\n');
		}
	},
	img: {
		shortcut: 'g',
		command: function(input){
			if(window.TagMaker) {
				if(!this.anchorBuilder) this.anchorBuilder = new TagMaker.image();
				this.anchorBuilder.prompt(input);
			} else {
				input.insertAtCursor('<img src="'+window.prompt('The url to the image')+'" />');
			}
		}
	},
	stripTags: {
		shortcut: '\\',
		command: function(input){
			input.insertAtCursor(input.getSelectedText().stripTags());
		}
	},
	sup: {
		shortcut: false,
		command: function(input){
			input.insertAroundCursor({before:'<sup>', after: '</sup>'});
		}
	},
	sub: {
		shortcut: false,
		command: function(input){
			input.insertAroundCursor({before:'<sub>', after: '</sub>'});
		}
	},
	paragraph: {
		shortcut: 'enter',
		command: function(input){
			input.insertAroundCursor({before:'\n<p>\n', after: '\n</p>\n'});
		}
	},
	strike: {
		shortcut: 'k',
		command: function(input){
			input.insertAroundCursor({before:'<strike>',after:'</strike>'});
		}
	},
	italics: {
		shortcut: 'i',
		command: function(input){
			input.insertAroundCursor({before:'<i>',after:'</i>'});
		}
	},
	bullets: {
		shortcut: '8',
		command: function(input){
			input.insertAroundCursor({before:'<ul>\n	<li>',after:'</li>\n</ul>'});
		}
	},
	numberList: {
		shortcut: '=',
		command: function(input){
			input.insertAroundCursor({before:'<ol>\n	<li>',after:'</li>\n</ol>'});
		}
	},
	clean: {
		shortcut: false,
		command: function(input){
			input.tidy();
		}
	},
	preview: {
		shortcut: false,
		command: function(input){
			try {
				if(!this.container){
					this.container = new Element('div', {
						styles: {
							border: '1px solid black',
							padding: 8,
							height: 300,
							overflow: 'auto'
						}
					});
					this.preview = new StickyWinModal({
						content: StickyWin.ui("preview", this.container, {
							width: 600,
							buttons: [{
								text: 'close',
								onClick: function(){
									this.container.empty();
								}.bind(this)
							}]
						}),
						showNow: false
					});
				}
				this.container.set('html', input.get('value'));
				this.preview.show();
			} catch(e){dbug.log('you need StickyWinModal and StickyWin.ui')}
		}
	}
});

/*
Script: TagMaker.js
	Prompts the user to fill in the gaps to create an html tag output.

License:
	http://clientside.cnet.com/wiki/cnet-libraries#license
*/
var TagMaker = new Class({
	Implements: [Options, Events, StyleWriter],
	options: {
		name: "Tag Builder",
		output: '',
		picklets: {},
		help: {},
		example: {},
		'class': {},
		selectLists: {},
		width: 400,
		maxHeight: 500,
		clearOnPrompt: true,
		baseHref: "templates/"+DESIGN+"/images/javascripts/tips", 
		css: "table.trinket {	width: 98%;	margin: 0px auto;	font-size: 10px; }"+
					"table.trinket td {	vertical-align: top;	padding: 4px;}"+
					"table.trinket td a.button {	position: relative;	top: -2px;}"+
					"table.trinket td.example {	font-size: 9px;	color: #666;	text-align: right;	border-bottom: 1px solid #ddd;"+
						"padding-bottom: 6px;}"+
					"table.trinket div.validation-advice {	background-color: #a36565;	font-weight: bold;	color: #fff;	padding: 4px;"+
						"margin-top: 3px;}"+
					"table.trinket input.text {width: 100%;}"+
					".tagMakerTipElement { 	cursor: help; }"+
					".tagMaker .tip {	color: #fff;	width: 172px;	z-index: 13000; }"+
					".tagMaker .tip-title {	font-weight: bold;	font-size: 11px;	margin: 0;	padding: 8px 8px 4px;"+
							"background: url(%baseHref%/bubble.png) top left;}"+
					".tagMaker .tip-text { font-size: 11px; 	padding: 4px 8px 8px; "+
							"background: url(%baseHref%/bubble.png) bottom right; }"
//	onPrompt: $empty,
//	onChoose: $empty
	},
	initialize: function(options){
		this.setOptions(options);
		this.buttons = [
			{
				text: 'Copy',
				onClick: this.copyToClipboard.bind(this),
				properties: {
					'class': 'closeSticky tip',
					title: 'Copy::Copy the html to your OS clipboard (like hitting Ctrl+C)'
				}
			},
			{
				text: 'Paste',
				onClick: function(){
					if(this.validator.validate()) this.insert();
				}.bind(this),
				properties: {
					'class': 'tip',
					title: 'Paste::Insert the html into the field you are editing'
				}
			},
			{
				text: 'Close',
				properties: {
					'class': 'closeSticky tip',
					title: 'Close::Close this popup'
				}
			}
		];
		this.createStyle(this.options.css.replace("%baseHref%", this.options.baseHref, "g"), 'defaultTagBuilderStyle');
	},
	prompt: function(target){
		this.target = $(target);
		var content = this.getContent();
		if (this.options.clearOnPrompt) this.clear();
		if(content) {
				var relativeTo = (document.compatMode == "BackCompat" && this.target)?this.target:document.body;
				if(!this.win) {
					this.win = new StickyWinFx({
						content: content,
						draggable: true,
						relativeTo: relativeTo,
						onClose: function(){
							$$('.tagMaker-tip').hide();
						}
					});
				}
				if(!this.win.visible) this.win.show();
		}
		var innerText = this.getInnerTextInput();
		this.range = target.getSelectedRange();
		if(innerText) innerText.set('value', target.getTextInRange(this.range.start, this.range.end)||"");
		return this.fireEvent('onPrompt');
	},
	clear: function(){
		this.body.getElements('input').each(function(input){
			input.erase('value');
		});
	},
	getKeys: function(text) {
		return text.split('%').filter(function(inputKey, index){
				return index%2;
		});
	},
	getInnerTextInput: function(){
		return this.body.getElement('input[name=Inner-Text]');
	},
	getContent: function(){
		var opt = this.options; //save some bytes
		if(!this.form) { //if the body hasn't been created, create it
			this.form = new Element('form');
				var table = new HtmlTable({properties: {'class':'trinket'}});
				this.getKeys(opt.output).each(function(inputKey) {
					if(this.options.selectLists[inputKey]){
						var input = new Element('select').setProperties({
							name: inputKey.replace(' ', '-', 'g')
						}).addEvent('change', this.createOutput.bind(this));
						this.options.selectLists[inputKey].each(function(opt){
							var option = new Element('option').inject(input);
							if(opt.selected) option.set('selected', true);
							option.set('value', opt.value);
							option.set('text', opt.key);
						}, this);
						table.push([inputKey, input]);
					} else {
						var input = new Element('input', {
							type: 'text',
							name: inputKey.replace(/ /g, '-'),
							title: inputKey+'::'+opt.help[inputKey],
							'class': 'text tip ' + ((opt['class'])?opt['class'][inputKey]||'':''),
							events: {
								keyup: this.createOutput.bind(this),
								focus: function(){this.select()},
								change: this.createOutput.bind(this)
							}
						});
						if(opt.picklets[inputKey]) {
							var a = new Element('a').addClass('button').set('html', 'choose');
							var div = new Element('div').adopt(input.setStyle('width',160)).adopt(a);
							var picklets = ($type(opt.picklets[inputKey]) == "array")?opt.picklets[inputKey]:[opt.picklets[inputKey]];
							new ProductPicker(input, picklets, {
								showOnFocus: false, 
								additionalShowLinks: [a],
								onPick: function(input, data, picker){
									try {
										var ltInput = this.getInnerTextInput();
										if(ltInput && !ltInput.get('value')) {
											try {
												ltInput.set('value', picker.currentPicklet.options.listItemName(data));
											}catch (e){dbug.log('set value error: ', e);}
										}
										var val = input.value;
										if(inputKey == "Full Path" && val.test(/^http:/))
												input.set('value', val.substring(val.indexOf('/', 7), val.length));
										this.createOutput();
									} catch(e){dbug.log(e)}
								}.bind(this)
							});
							table.push([inputKey, div]);
						} else table.push([inputKey, input]);
					}
					//[{content: <content>, properties: {colspan: 2, rowspan: 3, 'class': "cssClass", style: "border: 1px solid blue"}]
					if(this.options.example[inputKey]) 
						table.push([{content: 'eg. '+this.options.example[inputKey], properties: {colspan: 2, 'class': 'example'}}]);
				}, this);
				this.resultInput = new Element('input', {
						type: 'text',
						title: 'HTML::This is the resulting tag html.',
						'class': 'text result tip'
					}).addEvent('focus', function(){this.select()});
				table.push(['HTML', this.resultInput]).tr;

			this.form = table.table;
			this.body = new Element('div', {
				styles: {
					overflow:'auto',
					maxHeight: this.options.maxHeight
				}
			}).adopt(this.form);
			this.validator = new FormValidator(this.form);
			this.validator.insertAdvice = function(advice, field){
				var p = $(field.parentNode);
				if(p) p.adopt(advice);
			};
		}

		if(!this.content) {
			this.content = StickyWin.ui(this.options.name, this.body, {
				buttons: this.buttons,
				width: this.options.width.toInt()
			});
			new Tips(this.content.getElements('.tip'), {
				showDelay: 700,
				maxTitleChars: 50, 
				maxOpacity: .9,
				className: 'tagMaker'
			});
		}
		return this.content;

	},
	createOutput: function(){
		var inputs = this.form.getElements('input, select');
		var html = this.options.output;
		inputs.each(function(input) {
			if(!input.hasClass('result')) {
				html = html.replace(new RegExp('%'+input.get('name').replace('-', ' ', 'g').toLowerCase()+'%', 'ig'),
					(input.get('tag')=='select'?input.getSelected()[0]:input).get('value'));
				html = html.replace(/\s\w+\=""/g, "");
			}
		});
		return this.resultInput.value = html;
	},
	copyToClipboard: function(){
		var inputs = this.form.getElements('input');
		var result = inputs[inputs.length-1];
		result.select();
		Clipboard.copyFromElement(result);
		$$('.tagMaker-tip').hide();
		this.win.hide();
		this.fireEvent('onChoose');
	},
	insert: function(){
		if(!this.target) {
			simpleErrorPopup('Cannot Paste','This tag builder was not launched with a target input specified; you\'ll have to copy the tag yourself. Sorry!');
			return;
		}
		var value = (this.target)?this.target.value:this.target;
		var output = this.body.getElement("input.result");
		
		var currentScrollPos; 
		if (this.target.scrollTop || this.target.scrollLeft) {
			currentScrollPos = {
				scrollTop: this.target.getScroll().y,
				scrollLeft: this.target.getScroll().x
			};
		}
		this.target.value = value.substring(0, this.range.start) + output.value + value.substring((this.range.end-this.range.start) + this.range.start, value.length);
		if(currentScrollPos) {
			this.target.scrollTop = currentScrollPos.getScroll().y;
			this.target.scrollLeft = currentScrollPos.getScroll().x;
		}

		this.target.selectRange(this.range.start, output.value.length + this.range.start);
		this.fireEvent('onChoose');
		$$('.tagMaker-tip').hide();
		this.win.hide();
		return;
	}
});


TagMaker.image = new Class({
	Extends: TagMaker,
	options: {
		name: "Image Builder",
		output: '<img src="%Full Url%" width="%Width%" height="%Height%" alt="%Alt Text%" style="%Alignment%"/>',
		help: {
			'Full Url':'Enter the external URL (http://...) to the image',
			'Width':'Enter the width in pixels.',
			'Height':'Enter the height in pixels.',
			'Alt Text':'Enter the alternate text for the image.',
			'Alignment':'Choose how to float the image.'
		},
		example: {
			'Full Url':'http://i.i.com.com/cnwk.1d/i/hdft/redball.gif'
		},
		'class': {
			'Full Url':'validate-url required',
			'Width':'validate-digits',
			'Height':'validate-digits',
			'Alt Text':''
		},
		selectLists: {
			Alignment: [
				{
					key: 'left',
					value: 'float: left'
				},
				{
					key: 'right',
					value: 'float: right'
				},
				{
					key: 'none',
					value: 'float: none',
					selected: true
				},
				{
					key: 'center',
					value: 'margin-left: auto; margin-right: auto;'
				}
			]		
		}
	}
});

var TMPicklets = [];
if(typeof CNETProductPicker_ReviewPath != "undefined") TMPicklets.push(CNETProductPicker_ReviewPath);
if(typeof CNETProductPicker_PricePath != "undefined") TMPicklets.push(CNETProductPicker_PricePath);
if(typeof NewsStoryPicker_Path != "undefined") TMPicklets.push(NewsStoryPicker_Path);
TagMaker.anchor = new Class({
	Extends: TagMaker,
	options: {
		name: "Anchor Builder",
		output: '<a href="%Full Url%">%Inner Text%</a>',
		picklets: {
			'Full Url': (TMPicklets.length)?TMPicklets:false
		},
		help: {
			'Full Url':'Enter the external URL (http://...)',
			'Inner Text':'Enter the text for the link body'
		},
		example: {
			'Full Url':'http://www.microsoft.com',
			'Inner Text':'Microsoft'
		},
		'class': {
			'Full Url':'validate-url'
		}
	}
});

TagMaker.cnetVideo = new Class({
	Extends: TagMaker,
	options: {
		name: "CNET Video Embed Tag",
		output: '<cnet:video ssaVideoId="%Video Id%" float="%Alignment%"/>',
		help: {
			'Video Id':'The id of the video to embed'
		},
		'class':{
			'Video Id':'validate-digits required'
		},
		selectLists: {
			Alignment: [
				{
					key: 'left',
					value: 'left'
				},
				{
					key: 'right',
					value: 'right'
				},
				{
					key: 'none',
					value: '',
					selected: true
				}
			]		
		}
	}
});

/**
 * Autocompleter
 *
 * @version		1.1.1
 *
 * @todo: Caching, no-result handling!
 *
 *
 * @license		MIT-style license
 * @author		Harald Kirschner <mail [at] digitarald.de>
 * @copyright	Author
 */
var Autocompleter = {};

var OverlayFix = IframeShim;

Autocompleter.Base = new Class({
	
	Implements: [Options, Events],
	
	options: {
		minLength: 1,
		markQuery: true,
		width: 'inherit',
		maxChoices: 10,
//	injectChoice: null,
//	customChoices: null,
		className: 'autocompleter-choices',
		zIndex: 42,
		delay: 400,
		observerOptions: {},
		fxOptions: {},
//	onSelection: $empty,
//	onShow: $empty,
//	onHide: $empty,
//	onBlur: $empty,
//	onFocus: $empty,

		autoSubmit: false,
		overflow: false,
		overflowMargin: 25,
		selectFirst: false,
		filter: null,
		filterCase: false,
		filterSubset: false,
		forceSelect: false,
		selectMode: true,
		choicesMatch: null,

		multiple: false,
		separator: ', ',
		separatorSplit: /\s*[,;]\s*/,
		autoTrim: true,
		allowDupes: false,

		cache: true,
		relative: false
	},

	initialize: function(element, options) {
		this.element = $(element);
		this.setOptions(options);
		this.build();
		this.observer = new Observer(this.element, this.prefetch.bind(this), $merge({
			'delay': this.options.delay
		}, this.options.observerOptions));
		this.queryValue = null;
		if (this.options.filter) this.filter = this.options.filter.bind(this);
		var mode = this.options.selectMode;
		this.typeAhead = (mode == 'type-ahead');
		this.selectMode = (mode === true) ? 'selection' : mode;
		this.cached = [];
	},

	/**
	 * build - Initialize DOM
	 *
	 * Builds the html structure for choices and appends the events to the element.
	 * Override this function to modify the html generation.
	 */
	build: function() {
		if ($(this.options.customChoices)) {
			this.choices = this.options.customChoices;
		} else {
			this.choices = new Element('ul', {
				'class': this.options.className,
				'styles': {
					'zIndex': this.options.zIndex
				}
			}).inject(document.body);
			this.relative = false;
			if (this.options.relative || this.element.getOffsetParent() != document.body) {
				this.choices.inject(this.element, 'after');
				this.relative = this.element.getOffsetParent();
			}
			this.fix = new OverlayFix(this.choices);
		}
		if (!this.options.separator.test(this.options.separatorSplit)) {
			this.options.separatorSplit = this.options.separator;
		}
		this.fx = (!this.options.fxOptions) ? null : new Fx.Tween(this.choices, $merge({
			'property': 'opacity',
			'link': 'cancel',
			'duration': 200
		}, this.options.fxOptions)).addEvent('onStart', Chain.prototype.clearChain).set(0);
		this.element.setProperty('autocomplete', 'off')
			.addEvent((Browser.Engine.trident || Browser.Engine.webkit) ? 'keydown' : 'keypress', this.onCommand.bind(this))
			.addEvent('click', this.onCommand.bind(this, [false]))
			.addEvent('focus', this.toggleFocus.create({bind: this, arguments: true, delay: 100}))
			.addEvent('blur', this.toggleFocus.create({bind: this, arguments: false, delay: 100}));
	},

	destroy: function() {
		if (this.fix) this.fix.dispose();
		this.choices = this.selected = this.choices.destroy();
	},

	toggleFocus: function(state) {
		this.focussed = state;
		if (!state) this.hideChoices(true);
		this.fireEvent((state) ? 'onFocus' : 'onBlur', [this.element]);
	},

	onCommand: function(e) {
		if (!e && this.focussed) return this.prefetch();
		if (e && e.key && !e.shift) {
			switch (e.key) {
				case 'enter':
					if (this.element.value != this.opted) return true;
					if (this.selected && this.visible) {
						this.choiceSelect(this.selected);
						return !!(this.options.autoSubmit);
					}
					break;
				case 'up': case 'down':
					if (!this.prefetch() && this.queryValue !== null) {
						var up = (e.key == 'up');
						this.choiceOver((this.selected || this.choices)[
							(this.selected) ? ((up) ? 'getPrevious' : 'getNext') : ((up) ? 'getLast' : 'getFirst')
						](this.options.choicesMatch), true);
					}
					return false;
				case 'esc': case 'tab':
					this.hideChoices(true);
					break;
			}
		}
		return true;
	},

	setSelection: function(finish) {
		var input = this.selected.inputValue, value = input;
		var start = this.queryValue.length, end = input.length;
		if (input.substr(0, start).toLowerCase() != this.queryValue.toLowerCase()) start = 0;
		if (this.options.multiple) {
			var split = this.options.separatorSplit;
			value = this.element.value;
			start += this.queryIndex;
			end += this.queryIndex;
			var old = value.substr(this.queryIndex).split(split, 1)[0];
			value = value.substr(0, this.queryIndex) + input + value.substr(this.queryIndex + old.length);
			if (finish) {
				var space = /[^\s,]+/;
				var tokens = value.split(this.options.separatorSplit).filter(space.test, space);
				if (!this.options.allowDupes) tokens = [].combine(tokens);
				var sep = this.options.separator;
				value = tokens.join(sep) + sep;
				end = value.length;
			}
		}
		this.observer.setValue(value);
		this.opted = value;
		if (finish || this.selectMode == 'pick') start = end;
		this.element.selectRange(start, end);
		this.fireEvent('onSelection', [this.element, this.selected, value, input]);
	},

	showChoices: function() {
		var match = this.options.choicesMatch, first = this.choices.getFirst(match);
		this.selected = this.selectedValue = null;
		if (this.fix) {
			var pos = this.element.getCoordinates(this.relative), width = this.options.width || 'auto';
			this.choices.setStyles({
				'left': pos.left,
				'top': pos.bottom,
				'width': (width === true || width == 'inherit') ? pos.width : width
			});
		}
		if (!first) return;
		if (!this.visible) {
			this.visible = true;
			this.choices.setStyle('display', '');
			if (this.fx) this.fx.start(1);
			this.fireEvent('onShow', [this.element, this.choices]);
		}
		if (this.options.selectFirst || this.typeAhead || first.inputValue == this.queryValue) this.choiceOver(first, this.typeAhead);
		var items = this.choices.getChildren(match), max = this.options.maxChoices;
		var styles = {'overflowY': 'hidden', 'height': ''};
		this.overflown = false;
		if (items.length > max) {
			var item = items[max - 1];
			styles.overflowY = 'scroll';
			styles.height = item.getCoordinates(this.choices).bottom;
			this.overflown = true;
		};
		this.choices.setStyles(styles);
		this.fix.show();
	},

	hideChoices: function(clear) {
		if (clear) {
			var value = this.element.value;
			if (this.options.forceSelect) value = this.opted;
			if (this.options.autoTrim) {
				value = value.split(this.options.separatorSplit).filter($arguments(0)).join(this.options.separator);
			}
			this.observer.setValue(value);
		}
		if (!this.visible) return;
		this.visible = false;
		this.observer.clear();
		var hide = function(){
			this.choices.setStyle('display', 'none');
			this.fix.hide();
		}.bind(this);
		if (this.fx) this.fx.start(0).chain(hide);
		else hide();
		this.fireEvent('onHide', [this.element, this.choices]);
	},

	prefetch: function() {
		var value = this.element.value, query = value;
		if (this.options.multiple) {
			var split = this.options.separatorSplit;
			var values = value.split(split);
			var index = this.element.getCaretPosition();
			var toIndex = value.substr(0, index).split(split);
			var last = toIndex.length - 1;
			index -= toIndex[last].length;
			query = values[last];
		}
		if (query.length < this.options.minLength) {
			this.hideChoices();
		} else {
			if (query === this.queryValue || (this.visible && query == this.selectedValue)) {
				if (this.visible) return false;
				this.showChoices();
			} else {
				this.queryValue = query;
				this.queryIndex = index;
				if (!this.fetchCached()) this.query();
			}
		}
		return true;
	},

	fetchCached: function() {
		return false;
		if (!this.options.cache
			|| !this.cached
			|| !this.cached.length
			|| this.cached.length >= this.options.maxChoices
			|| this.queryValue) return false;
		this.update(this.filter(this.cached));
		return true;
	},

	update: function(tokens) {
		this.choices.empty();
		this.cached = tokens;
		if (!tokens || !tokens.length) {
			this.hideChoices();
		} else {
			if (this.options.maxChoices < tokens.length && !this.options.overflow) tokens.length = this.options.maxChoices;
			tokens.each(this.options.injectChoice || function(token){
				var choice = new Element('li', {'html': this.markQueryValue(token)});
				choice.inputValue = token;
				this.addChoiceEvents(choice).inject(this.choices);
			}, this);
			this.showChoices();
		}
	},

	choiceOver: function(choice, selection) {
		if (!choice || choice == this.selected) return;
		if (this.selected) this.selected.removeClass('autocompleter-selected');
		this.selected = choice.addClass('autocompleter-selected');
		this.fireEvent('onSelect', [this.element, this.selected, selection]);
		if (!selection) return;
		this.selectedValue = this.selected.inputValue;
		if (this.overflown) {
			var coords = this.selected.getCoordinates(this.choices), margin = this.options.overflowMargin,
				top = this.choices.scrollTop, height = this.choices.offsetHeight, bottom = top + height;
			if (coords.top - margin < top && top) this.choices.scrollTop = Math.max(coords.top - margin, 0);
			else if (coords.bottom + margin > bottom) this.choices.scrollTop = Math.min(coords.bottom - height + margin, bottom);
		}
		if (this.selectMode) this.setSelection();
	},

	choiceSelect: function(choice) {
		if (choice) this.choiceOver(choice);
		this.setSelection(true);
		this.queryValue = false;
		this.hideChoices();
	},

	filter: function(tokens) {
		var regex = new RegExp(((this.options.filterSubset) ? '' : '^') + this.queryValue.escapeRegExp(), (this.options.filterCase) ? '' : 'i');
		return (tokens || this.tokens).filter(regex.test, regex);
	},

	/**
	 * markQueryValue
	 *
	 * Marks the queried word in the given string with <span class="autocompleter-queried">*</span>
	 * Call this i.e. from your custom parseChoices, same for addChoiceEvents
	 *
	 * @param		{String} Text
	 * @return		{String} Text
	 */
	markQueryValue: function(str) {
		return (!this.options.markQuery || !this.queryValue) ? str
			: str.replace(new RegExp('(' + ((this.options.filterSubset) ? '' : '^') + this.queryValue.escapeRegExp() + ')', (this.options.filterCase) ? '' : 'i'), '<span class="autocompleter-queried">$1</span>');
	},

	/**
	 * addChoiceEvents
	 *
	 * Appends the needed event handlers for a choice-entry to the given element.
	 *
	 * @param		{Element} Choice entry
	 * @return		{Element} Choice entry
	 */
	addChoiceEvents: function(el) {
		return el.addEvents({
			'mouseover': this.choiceOver.bind(this, [el]),
			'click': this.choiceSelect.bind(this, [el])
		});
	}
});


/**
 * Autocompleter.Remote
 *
 * @version		1.1.1
 *
 * @todo: Caching, no-result handling!
 *
 *
 * @license		MIT-style license
 * @author		Harald Kirschner <mail [at] digitarald.de>
 * @copyright	Author
 */

Autocompleter.Ajax = {};

Autocompleter.Ajax.Base = new Class({

	Extends: Autocompleter.Base,

	options: {
		postVar: 'value',
		postData: {},
		ajaxOptions: {},
		onRequest: $empty,
		onComplete: $empty
	},

	initialize: function(element, options) {
		this.parent(element, options);
		var indicator = $(this.options.indicator);
		if (indicator) {
			this.addEvents({
				'onRequest': indicator.show.bind(indicator),
				'onComplete': indicator.hide.bind(indicator)
			}, true);
		}
	},

	query: function(){
		var data = $unlink(this.options.postData);
		data[this.options.postVar] = this.queryValue;
		this.fireEvent('onRequest', [this.element, this.request, data, this.queryValue]);
		this.request.send({'data': data});
	},

	/**
	 * queryResponse - abstract
	 *
	 * Inherated classes have to extend this function and use this.parent(resp)
	 *
	 * @param		{String} Response
	 */
	queryResponse: function() {
		this.fireEvent('onComplete', [this.element, this.request, this.response]);
	}

});

Autocompleter.Ajax.Json = new Class({

	Extends: Autocompleter.Ajax.Base,

	initialize: function(el, url, options) {
		this.parent(el, options);
		this.request = new Request.JSON($merge({
			'url': url,
			'link': 'cancel'
		}, this.options.ajaxOptions)).addEvent('onComplete', this.queryResponse.bind(this));
	},

	queryResponse: function(response) {
		this.parent();
		this.update(response);
	}

});

Autocompleter.Ajax.Xhtml = new Class({

	Extends: Autocompleter.Ajax.Base,

	initialize: function(el, url, options) {
		this.parent(el, options);
		this.request = new Request.HTML($merge({
			'url': url,
			'link': 'cancel',
			'update': this.choices
		}, this.options.ajaxOptions)).addEvent('onComplete', this.queryResponse.bind(this));
	},

	queryResponse: function(tree, elements) {
		this.parent();
		if (!elements || !elements.length) {
			this.hideChoices();
		} else {
			this.choices.getChildren(this.options.choicesMatch).each(this.options.injectChoice || function(choice) {
				var value = choice.innerHTML;
				choice.inputValue = value;
				this.addChoiceEvents(choice.set('html', this.markQueryValue(value)));
			}, this);
			this.showChoices();
		}

	}

});


/*
Script: Autocompleter.JsonP.js
	Implements JsonP support for the Autocompleter class.

License:
	http://clientside.cnet.com/wiki/cnet-libraries#license
*/

Autocompleter.JsonP = new Class({

	Extends: Autocompleter.Ajax.Json,

	options: {
		postVar: 'query',
		jsonpOptions: {},
//	onRequest: $empty,
//	onComplete: $empty,
//	filterResponse: $empty
		minLength: 1
	},

	initialize: function(el, url, options) {
		this.url = url;
		this.setOptions(options);
		this.parent(el, options);
	},

	query: function(){
		var data = $unlink(this.options.jsonpOptions.data||{});
		data[this.options.postVar] = this.queryValue;
		this.jsonp = new JsonP(this.url, $merge({data: data},	this.options.jsonpOptions));
		this.jsonp.addEvent('onComplete', this.queryResponse.bind(this));
		this.fireEvent('onRequest', [this.element, this.jsonp, data, this.queryValue]);
		this.jsonp.request();
	},

	
/*	Property: queryResponse
		Inherated classes have to extend this function and use this.parent(resp)
		
		Arguments:
		resp - (String) the response from the JsonP query.
*/

	queryResponse: function() {
		this.fireEvent('onComplete', [this.element, this.request, this.response]);
	},
	
	queryResponse: function(response) {
		this.parent();
		var data = (this.options.filterResponse)?this.options.filterResponse.run([response], this):response;
		this.update(data);
	}

});

/**
 * Observer - Observe formelements for changes
 *
 * @version		1.0rc3
 *
 * @license		MIT-style license
 * @author		Harald Kirschner <mail [at] digitarald.de>
 * @copyright	Author
 */
var Observer = new Class({

	Implements: [Options, Events],

	options: {
		periodical: false,
		delay: 1000
	},

	initialize: function(el, onFired, options){
		this.setOptions(options);
		this.addEvent('onFired', onFired);
		this.element = $(el) || $$(el);
		/* CNET change */
		this.boundChange = this.changed.bind(this);
		this.resume();
	},

	changed: function() {
		var value = this.element.get('value');
		if ($equals(this.value, value)) return;
		this.clear();
		this.value = value;
		this.timeout = this.onFired.delay(this.options.delay, this);
	},

	setValue: function(value) {
		this.value = value;
		this.element.set('value', value);
		return this.clear();
	},

	onFired: function() {
		this.fireEvent('onFired', [this.value, this.element]);
	},

	clear: function() {
		$clear(this.timeout || null);
		return this;
	},
	/* CNET change */
	pause: function(){
		$clear(this.timeout);
		$clear(this.timer);
		this.element.removeEvent('keyup', this.boundChange);
		return this;
	},
	resume: function(){
		this.value = this.element.get('value');
		if (this.options.periodical) this.timer = this.changed.periodical(this.options.periodical, this);
		else this.element.addEvent('keyup', this.boundChange);
		return this;
	}

});

var $equals = function(obj1, obj2) {
	return (obj1 == obj2 || JSON.encode(obj1) == JSON.encode(obj2));
};

/*
Script: Slimbox.js
	A lightbox clone for MooTools.

* Christophe Beyls (http://www.digitalia.be); MIT-style license.
* Inspired by the original Lightbox v2 by Lokesh Dhakar: http://www.huddletogether.com/projects/lightbox2/.
* Refactored by Aaron Newton 

*/
var Lightbox = new Class({
	Implements: [Options, Events],
	options: {
//	anchors: null,
		resizeDuration: 400,
//	resizeTransition: false,	// default transition
		initialWidth: 250,
		initialHeight: 250,
		zIndex: 10,
		animateCaption: true,
		showCounter: true,
		autoScanLinks: true,
		relString: 'lightbox',
		useDefaultCss: true,
		assetBaseUrl: 'templates/'+DESIGN+'/images/javascripts/slimbox/',
		overlayStyles: {}
//	onImageShow: $empty,
//	onDisplay: $empty,
//	onHide: $empty,
	},

	initialize: function(options){
		this.setOptions(options);
		this.anchors = this.options.anchors || arguments[1];
		if (this.options.autoScanLinks && !this.anchors) {
			this.anchors = [];
			$$('a[rel^='+this.options.relString+']').each(function(el){
				if(!el.retrieve('lightbox')) this.anchors.push(el);
			}, this);
		}
		if(!$$(this.anchors).length) return; //no links!
		if(this.options.useDefaultCss) this.addCss();
		$$(this.anchors).each(function(el){
			if(!el.retrieve('lightbox')) {
				el.store('lightbox', this);
				el.addEvent('click', function(e){
					e.stop();
					this.click(el);
				}.bind(this));
			}
		}.bind(this));
		this.eventKeyDown = this.keyboardListener.bind(this);
		this.eventPosition = this.position.bind(this);
		window.addEvent('domready', this.addHtmlElements.bind(this));
	},

	addHtmlElements: function(){
		this.overlay = new Element('div', {
			'class': 'lbOverlay',
			styles: {
				zIndex:this.options.zIndex
			}
		});
		this.overlay.inject(document.body).setStyles(this.options.overlayStyles);
		this.center = new Element('div', {
			styles: {	
				width: this.options.initialWidth, 
				height: this.options.initialHeight, 
				marginLeft: (-(this.options.initialWidth/2)),
				display: 'none',
				zIndex:this.options.zIndex+1
			}
		}).inject(document.body).addClass('lbCenter');
		this.image = new Element('div', {
			'class': 'lbImage'
		}).inject(this.center);
		
		this.prevLink = new Element('a', {
			'class': 'lbPrevLink', 
			href: 'javascript:void(0);', 
			styles: {'display': 'none'}
		}).inject(this.image);
		this.nextLink = this.prevLink.clone().removeClass('lbPrevLink').addClass('lbNextLink').inject(this.image);
		this.prevLink.addEvent('click', this.previous.bind(this));
		this.nextLink.addEvent('click', this.next.bind(this));

		this.bottomContainer = new Element('div', {
			'class': 'lbBottomContainer', 
			styles: {
				display: 'none', 
				zIndex:this.options.zIndex+1
		}}).inject(document.body);
		this.bottom = new Element('div', {'class': 'lbBottom'}).inject(this.bottomContainer);
		new Element('a', {
			'class': 'lbCloseLink', 
			href: 'javascript:void(0);'
		}).inject(this.bottom).addEvent('click', this.close.bind(this));
		this.overlay.addEvent('click', this.close.bind(this));
		this.caption = new Element('div', {'class': 'lbCaption'}).inject(this.bottom);
		this.number = new Element('div', {'class': 'lbNumber'}).inject(this.bottom);
		new Element('div', {'styles': {'clear': 'both'}}).inject(this.bottom);

		var nextEffect = this.nextEffect.bind(this);
		this.fx = {
			overlay: new Fx.Tween(this.overlay, {property: 'opacity', duration: 500}).set(0),
			resize: new Fx.Morph(this.center, $extend({
				duration: this.options.resizeDuration, 
				onComplete: nextEffect}, 
				this.options.resizeTransition ? {transition: this.options.resizeTransition} : {})),
			image: new Fx.Tween(this.image, {property: 'opacity', duration: 500, onComplete: nextEffect}),
			bottom: new Fx.Tween(this.bottom, {property: 'margin-top', duration: 400, onComplete: nextEffect})
		};

		this.preloadPrev = new Element('img');
		this.preloadNext = new Element('img');
	},
	
	addCss: function(){
		window.addEvent('domready', function(){
			if($('SlimboxCss')) return;
			new Element('link', {
				rel: 'stylesheet', 
				media: 'screen', 
				type: 'text/css', 
				href: this.options.assetBaseUrl + 'slimbox.css',
				id: 'SlimboxCss'
			}).inject(document.head);
		}.bind(this));
	},

	click: function(link){
		link = $(link);
		var rel = link.get('rel')||this.options.relString;
		if (rel == this.options.relString) return this.show(link.get('href'), link.get('title'));

		var j, imageNum, images = [];
		this.anchors.each(function(el){
			if (el.get('rel') == link.get('rel')){
				for (j = 0; j < images.length; j++) if(images[j][0] == el.get('href')) break;
				if (j == images.length){
					images.push([el.get('href'), el.get('title')]);
					if (el.get('href') == link.get('href')) imageNum = j;
				}
			}
		}, this);
		return this.open(images, imageNum);
	},

	show: function(url, title){
		return this.open([[url, title]], 0);
	},

	open: function(images, imageNum){
		this.fireEvent('onDisplay');
		this.images = images;
		this.position();
		this.setup(true);
		this.top = (window.getScroll().y + (window.getSize().y / 15)).toInt();
		this.center.setStyles({
			top: this.top,
			display: ''
		});
		this.fx.overlay.start(0.8);
		return this.changeImage(imageNum);
	},

	position: function(){
		this.overlay.setStyles({
			'top': window.getScroll().y, 
			'height': window.getSize().y
		});
	},

	setup: function(open){
		var elements = $$('object, iframe');
		elements.extend($$(Browser.Engine.trident ? 'select' : 'embed'));
		elements.each(function(el){
			if (open) el.store('lbBackupStyle', el.getStyle('visibility'));
			var vis = (open ? 'hidden' : el.retrieve('lbBackupStyle'));
			el.setStyle('visibility', vis);
		});
		var fn = open ? 'addEvent' : 'removeEvent';
		window[fn]('scroll', this.eventPosition)[fn]('resize', this.eventPosition);
		document[fn]('keydown', this.eventKeyDown);
		this.step = 0;
	},

	keyboardListener: function(event){
		switch (event.code){
			case 27: case 88: case 67: this.close(); break;
			case 37: case 80: this.previous(); break;	
			case 39: case 78: this.next();
		}
	},

	previous: function(){
		return this.changeImage(this.activeImage-1);
	},

	next: function(){
		return this.changeImage(this.activeImage+1);
	},

	changeImage: function(imageNum){
		this.fireEvent('onImageShow', [imageNum, this.images[imageNum]]);
		if (this.step || (imageNum < 0) || (imageNum >= this.images.length)) return false;
		this.step = 1;
		this.activeImage = imageNum;

		this.center.setStyle('backgroundColor', '');
		this.bottomContainer.setStyle('display', 'none');
		this.prevLink.setStyle('display', 'none');
		this.nextLink.setStyle('display', 'none');
		this.fx.image.set(0);
		this.center.addClass('lbLoading');
		this.preload = new Element('img', {
			events: {
				load: function(){
					this.nextEffect.delay(100, this)
				}.bind(this)
			}
		});
		this.preload.set('src', this.images[imageNum][0]);
		return false;
	},

	nextEffect: function(){
		switch (this.step++){
		case 1:
			this.image.setStyle('backgroundImage', 'url('+this.images[this.activeImage][0]+')');
			this.image.setStyle('width', this.preload.width);
			this.bottom.setStyle('width',this.preload.width);
			this.image.setStyle('height', this.preload.height);
			this.prevLink.setStyle('height', this.preload.height);
			this.nextLink.setStyle('height', this.preload.height);

			this.caption.set('html',this.images[this.activeImage][1] || '');
			this.number.set('html',(!this.options.showCounter || (this.images.length == 1)) ? '' : 'Image '+(this.activeImage+1)+' of '+this.images.length);

			if (this.activeImage) $(this.preloadPrev).set('src', this.images[this.activeImage-1][0]);
			if (this.activeImage != (this.images.length - 1)) 
				$(this.preloadNext).set('src',  this.images[this.activeImage+1][0]);
			if (this.center.clientHeight != this.image.offsetHeight){
				this.fx.resize.start({height: this.image.offsetHeight});
				break;
			}
			this.step++;
		case 2:
			if (this.center.clientWidth != this.image.offsetWidth){
				this.fx.resize.start({width: this.image.offsetWidth, marginLeft: -this.image.offsetWidth/2});
				break;
			}
			this.step++;
		case 3:
			this.bottomContainer.setStyles({
				top: (this.top + this.center.getSize().y), 
				height: 0, 
				marginLeft: this.center.getStyle('margin-left'), 
				display: ''
			});
			this.fx.image.start(1);
			break;
		case 4:
			this.center.style.backgroundColor = '#000';
			if (this.options.animateCaption){
				this.fx.bottom.set(-this.bottom.offsetHeight);
				this.bottomContainer.setStyle('height', '');
				this.fx.bottom.start(0);
				break;
			}
			this.bottomContainer.style.height = '';
		case 5:
			if (this.activeImage) this.prevLink.setStyle('display', '');
			if (this.activeImage != (this.images.length - 1)) this.nextLink.setStyle('display', '');
			this.step = 0;
		}
	},

	close: function(){
		this.fireEvent('onHide');
		if (this.step < 0) return;
		this.step = -1;
		if (this.preload) this.preload.destroy();
		for (var f in this.fx) this.fx[f].cancel();
		this.center.setStyle('display', 'none');
		this.bottomContainer.setStyle('display', 'none');
		this.fx.overlay.chain(this.setup.pass(false, this)).start(0);
		return;
	}
});
window.addEvent('domready', function(){if($(document.body).get('html').match(/rel=?.lightbox/i)) new Lightbox()});
/**
 * Swiff.Uploader - Flash FileReference Control
 *
 * @version		1.2
 *
 * @license		MIT License
 *
 * @author		Harald Kirschner <mail [at] digitarald [dot] de>
 * @copyright	Authors
 */

Swiff.Uploader = new Class({

	Extends: Swiff,

	Implements: Events,

	options: {
		path: 'Swiff.Uploader.swf',
		multiple: true,
		queued: true,
		typeFilter: null,
		url: null,
		method: 'post',
		data: null,
		fieldName: 'Filedata',
		target: null,
		height: '100%',
		width: '100%',
		callBacks: null
	},

	initialize: function(options){
		if (Browser.Plugins.Flash.version < 9) return false;
		this.setOptions(options);

		var callBacks = this.options.callBacks || this;
		if (callBacks.onLoad) this.addEvent('onLoad', callBacks.onLoad);
		if (!callBacks.onBrowse) {
			callBacks.onBrowse = function() {
				return this.options.typeFilter;
			}
		}

		var prepare = {}, self = this;
		['onBrowse', 'onSelect', 'onAllSelect', 'onCancel', 'onBeforeOpen', 'onOpen', 'onProgress', 'onComplete', 'onError', 'onAllComplete'].each(function(index) {
			var fn = callBacks[index] || $empty;
			prepare[index] = function() {
				self.fireEvent(index, arguments, 10);
				return fn.apply(self, arguments);
			};
		});

		prepare.onLoad = this.load.create({delay: 10, bind: this});
		this.options.callBacks = prepare;

		var path = this.options.path;
		if (!path.contains('?')) path += '?noCache=' + $time(); // quick fix

		this.parent(path);

		var scroll = window.getScroll();
		this.box = new Element('div', {
			styles: {
				position: 'absolute',
				visibility: 'visible',
				zIndex: 9999999,
				overflow: 'hidden',
				height: 15, width: 15,
				top: scroll.y, left: scroll.x
			}
		});
		this.inject(this.box);
		this.box.inject($(this.options.container) || document.body);

		return this;
	},

	load: function(){
		this.remote('register', this.instance, this.options.multiple, this.options.queued);
		this.fireEvent('onLoad');

		this.target = $(this.options.target);
		if (Browser.Plugins.Flash.version >= 10 && this.target) {
			this.reposition();
			window.addEvent('resize', this.reposition.bind(this));
		}
	},

	reposition: function() {
		var pos = this.target.getCoordinates(this.box.getOffsetParent());
		this.box.setStyles(pos);
	},

	/*
	Method: browse
		Open the file browser.
	*/

	browse: function(typeFilter){
		this.options.typeFilter = $pick(typeFilter, this.options.typeFilter);
		return this.remote('browse');
	},

	/*
	Method: upload
		Starts the upload of all selected files.
	*/

	upload: function(options){
		var current = this.options;
		options = $extend({data: current.data, url: current.url, method: current.method, fieldName: current.fieldName}, options);
		if ($type(options.data) == 'element') options.data = $(options.data).toQueryString();
		return this.remote('upload', options);
	},

	/*
	Method: removeFile
		For multiple uploads cancels and removes the given file from queue.

	Arguments:
		name - (string) Filename
		name - (string) Filesize in byte
	*/

	removeFile: function(file){
		if (file) file = {name: file.name, size: file.size};
		return this.remote('removeFile', file);
	},

	/*
	Method: getFileList
		Returns one Array with with arrays containing name and size of the file.

	Returns:
		(array) An array with files
	*/

	getFileList: function(){
		return this.remote('getFileList');
	}

});
/**
 * Fx.ProgressBar
 *
 * @version		1.0
 *
 * @license		MIT License
 *
 * @author		Harald Kirschner <mail [at] digitarald [dot] de>
 * @copyright	Authors
 */

Fx.ProgressBar = new Class({

	Extends: Fx,

	options: {
		text: null,
		transition: Fx.Transitions.Circ.easeOut,
		link: 'cancel'
	},

	initialize: function(element, options) {
		this.element = $(element);
		this.parent(options);
		this.text = $(this.options.text);
		this.set(0);
	},

	start: function(to, total) {
		return this.parent(this.now, (arguments.length == 1) ? to.limit(0, 100) : to / total * 100);
	},

	set: function(to) {
		this.now = to;
		this.element.setStyle('backgroundPosition', (100 - to) + '% 0px');
		if (this.text) this.text.set('text', Math.round(to) + '%');
		return this;
	}

});
/**
 * FancyUpload - Flash meets Ajax for powerful and elegant uploads.
 *
 * @version		2.1
 *
 * @license		MIT License
 *
 * @author		Harald Kirschner <mail [at] digitarald [dot] de>
 * @copyright	Authors
 */

var FancyUpload2 = new Class({

	Extends: Swiff.Uploader,

	options: {
		limitSize: false,
		limitFiles: 5,
		instantStart: false,
		allowDuplicates: false,
		validateFile: $lambda(true), // provide a function that returns true for valid and false for invalid files.
		debug: false,

		fileInvalid: null, // called for invalid files with error stack as 2nd argument
		fileCreate: null, // creates file element after select
		fileUpload: null, // called when file is opened for upload, allows to modify the upload options (2nd argument) for every upload
		fileComplete: null, // updates the file element to completed state and gets the response (2nd argument)
		fileRemove: null // removes the element
		/**
		 * Events:
		 * onBrowse, onSelect, onAllSelect, onCancel, onBeforeOpen, onOpen, onProgress, onComplete, onError, onAllComplete
		 */
	},

	initialize: function(status, list, options) {
		this.status = $(status);
		this.list = $(list);

		this.files = [];

		if (options.callBacks) {
			this.addEvents(options.callBacks);
			options.callBacks = null;
		}
		this.options.error = false;
		this.parent(options);
		this.render();
	},

	render: function() {
		this.overallTitle = this.status.getElement('.overall-title');
		this.currentTitle = this.status.getElement('.current-title');
		this.currentText = this.status.getElement('.current-text');

		var progress = this.status.getElement('.overall-progress');
		this.overallProgress = new Fx.ProgressBar(progress, {
			text: new Element('span', {'class': 'progress-text'}).inject(progress, 'after')
		});
		progress = this.status.getElement('.current-progress')
		this.currentProgress = new Fx.ProgressBar(progress, {
			text: new Element('span', {'class': 'progress-text'}).inject(progress, 'after')
		});
	},

	onLoad: function() {
		this.log('Uploader ready!');
	},

	onBeforeOpen: function(file, options) {
		this.log('Initialize upload for "{name}".', file);
		var fn = this.options.fileUpload;
		var obj = (fn) ? fn.call(this, this.getFile(file), options) : options;
		return obj;
	},

	onOpen: function(file, overall) {
		this.log('Starting upload "{name}".', file);
		file = this.getFile(file);
		file.element.addClass('file-uploading');
		this.currentProgress.cancel().set(0);
		this.currentTitle.set('html', 'File Progress "{name}"'.substitute(file) );
	},

	onProgress: function(file, current, overall) {
		this.overallProgress.start(overall.bytesLoaded, overall.bytesTotal);
		this.currentText.set('html', 'Upload with {rate}/s. Time left: ~{timeLeft}'.substitute({
			rate: (current.rate) ? this.sizeToKB(current.rate) : '- B',
			timeLeft: Date.fancyDuration(current.timeLeft || 0)
		}));
		this.currentProgress.start(current.bytesLoaded, current.bytesTotal);
	},

	onSelect: function(file, index, length) {
		this.options.error = false;
		var errors = [];
		if (this.options.limitSize && (file.size > this.options.limitSize)) errors.push('size');
		if (this.options.limitFiles && (this.countFiles() >= this.options.limitFiles)) errors.push('length');
		if (!this.options.allowDuplicates && this.getFile(file)) errors.push('duplicate');
		if (!this.options.validateFile.call(this, file, errors)) errors.push('custom');
		if (errors.length) {
			var fn = this.options.fileInvalid;
			if (fn) fn.call(this, file, errors);
			return false;
		}
		(this.options.fileCreate || this.fileCreate).call(this, file);
		this.files.push(file);
		return true;
	},

	onAllSelect: function(files, current, overall) {
		this.log('Added ' + files.length + ' files, now we have (' + current.bytesTotal + ' bytes).', arguments);
		this.updateOverall(current.bytesTotal);
		this.status.removeClass('status-browsing');
		if (this.files.length && this.options.instantStart) this.upload.delay(10, this);
	},

	onComplete: function(file, response) {
		this.log('Completed upload "' + file.name + '".', arguments);
		this.currentText.set('html', 'Upload complete!');
		this.currentProgress.start(100);
		(this.options.fileComplete || this.fileComplete).call(this, this.finishFile(file), response);
	},

	onError: function(file, error, info) {
		this.log('Upload "' + file.name + '" failed. "{1}": "{2}".', arguments);
		(this.options.fileError || this.fileError).call(this, this.finishFile(file), error, info);
	},

	onCancel: function() {
		this.log('Filebrowser cancelled.', arguments);
		this.status.removeClass('file-browsing');
	},

	onAllComplete: function(current) {
		this.log('Completed all files, ' + current.bytesTotal + ' bytes.', arguments);
		this.updateOverall(current.bytesTotal);
		this.overallProgress.start(100);
		this.status.removeClass('file-uploading');
	},

	browse: function(fileList) {
		var ret = this.parent(fileList);
		if (ret !== true){
			if (ret) this.log('An error occured: ' + ret);
			else this.log('Browse in progress.');
		} else {
			this.log('Browse started.');
			this.status.addClass('file-browsing');
		}
	},

	upload: function(options) {
		var ret = this.parent(options);
		if (ret !== true) {
			this.log('Upload in progress or nothing to upload.');
			if (ret) alert(ret);
		} else {
			this.log('Upload started.');
			this.status.addClass('file-uploading');
			this.overallProgress.set(0);
		}
	},

	removeFile: function(file) {
		this.options.error = false;
		var remove = this.options.fileRemove || this.fileRemove;
		if (!file) {
			this.files.each(remove, this);
			this.files.empty();
			this.updateOverall(0);
		} else {
			if (!file.element) file = this.getFile(file);
			this.files.erase(file);
			remove.call(this, file);
			this.updateOverall(this.bytesTotal - file.size);
		}
		this.parent(file);
	},

	getFile: function(file) {
		var ret = null;
		this.files.some(function(value) {
			if ((value.name != file.name) || (value.size != file.size)) return false;
			ret = value;
			return true;
		});
		return ret;
	},

	countFiles: function() {
		var ret = 0;
		for (var i = 0, j = this.files.length; i < j; i++) {
			if (!this.files[i].finished) ret++;
		}
		return ret;
	},

	updateOverall: function(bytesTotal) {
		this.bytesTotal = bytesTotal;
		this.overallTitle.set('html', 'Overall Progress (' + this.sizeToKB(bytesTotal) + ')');
	},

	finishFile: function(file) {
		file = this.getFile(file);
		file.element.removeClass('file-uploading');
		file.finished = true;
		return file;
	},

	fileCreate: function(file) {
		file.info = new Element('span', {'class': 'file-info'});
		file.element = new Element('li', {'class': 'file'}).adopt(
			new Element('span', {'class': 'file-size', 'html': this.sizeToKB(file.size)}),
			new Element('a', {
				'class': 'file-remove',
				'href': '#',
				'html': 'Remove',
				'events': {
					'click': function() {
						this.removeFile(file);
						return false;
					}.bind(this)
				}
			}),
			new Element('span', {'class': 'file-name', 'html': file.name}),
			file.info
		).inject(this.list);
	},

	fileComplete: function(file, response) {
		this.options.processResponse || this
		var json = $H(JSON.decode(response, true));
		if (json.get('result') == 'success') {
			file.element.addClass('file-success');
			file.info.set('html', json.get('size'));
		} else {
			this.options.error = true;
			file.element.addClass('file-failed');
			file.info.set('html', json.get('error') || response);
		}
	},

	fileError: function(file, error, info) {
		file.element.addClass('file-failed');
		file.info.set('html', '<strong>' + error + '</strong><br />' + info);
	},

	fileRemove: function(file) {
		file.element.fade('out').retrieve('tween').chain(Element.destroy.bind(Element, file.element));
	},

	sizeToKB: function(size) {
		var unit = 'B';
		if ((size / 1048576) > 1) {
			unit = 'MB';
			size /= 1048576;
		} else if ((size / 1024) > 1) {
			unit = 'kB';
			size /= 1024;
		}
		return size.round(1) + ' ' + unit;
	},

	log: function(text, args) {
		if (this.options.debug && window.console) console.log(text.substitute(args || {}));
	}

});

/**
 * @todo Clean-up, into Date.js
 */
Date.parseDuration = function(sec) {
	var units = {}, conv = Date.durations;
	for (var unit in conv) {
		var value = Math.floor(sec / conv[unit]);
		if (value) {
			units[unit] = value;
			if (!(sec -= value * conv[unit])) break;
		}
	}
	return units;
};

Date.fancyDuration = function(sec) {
	var ret = [], units = Date.parseDuration(sec);
	for (var unit in units) ret.push(units[unit] + Date.durationsAbbr[unit]);
	return ret.join(', ');
};

Date.durations = {years: 31556926, months: 2629743.83, days: 86400, hours: 3600, minutes: 60, seconds: 1, milliseconds: 0.001};
Date.durationsAbbr = {
	years: 'j',
	months: 'm',
	days: 'd',
	hours: 'h',
	minutes: 'min',
	seconds: 'sec',
	milliseconds: 'ms'
};
Rating = new Class( {
	Implements:[Options],
	options: {
			bindField : null,
			maxRating : 5,
			container : null,
			imageDirectory : "templates/"+DESIGN+"/images/javascripts/rating/",
			callback : null,
			actionURL : null,
			value : 0,
			locked : false,
			useOpacityStyle : true,
			messageBox : new Hash( {
				0 : "Poor",
				1 : "Nothing special",
				2 : "Worth watching",
				3 : "Pretty cool",
				4 : "Awesome!"
			}),
			valueBox : new Hash( {
				0 : 1,
				1 : 2,
				2 : 3,
				3 : 4,
				4 : 5
			})
	},
	initialize : function(options) {
		this.setOptions(options);
		this.messageBox = $H(this.options.messageBox);
		this.valueBox = $H(this.options.valueBox);
		if (this.messageBox.getValues().length != this.options.maxRating
				|| this.valueBox.getValues().length != this.options.maxRating) {
			alert("messageBox's length and valueBox's length must be equal to maxRating");
			return;
		}		
		/**
		 * hover and empty ratings imageSrc.
		 */
		this.hoverImages = {
			EMPTY : this.options.imageDirectory + "empty.png",
			HALF : this.options.imageDirectory + "hover-half.png",
			FULL : this.options.imageDirectory + "hover.png"
		};
 
		/**
		 * preload images
		 */
		for (var x in this.hoverImages) {
			var y = document.createElement("img");
			y.src = this.hoverImages[x];
		}
 
		/**
		 * selected and empty ratings imageSrc.
		 */
		this.selectedImages = {
			EMPTY : this.options.imageDirectory + "empty.png",
			HALF : this.options.imageDirectory + "selected-half.png",
			FULL : this.options.imageDirectory + "selected.png"
		};
		/**
		 * preload images
		 */
		for (var x in this.selectedImages) {
			var y = document.createElement("img");
			y.src = this.selectedImages[x];
		}
 
		if ($defined(this.options.container)) {
			this.container = $(this.options.container);
		} else {
			this.id = "ratecontainer" + Math.random(0, 10000);
			document.write('<span id="' + this.id + '"></span>');
			this.container = $(this.id);
		}
 
		this.initialized = false;
		this.rated = false;
		this.ratings = [];
		this.value = -1;
		this.locked = this.options.locked ? true : false;
		this.useOpacityStyle = this.options.useOpacityStyle ? true : false;
 
		this.display();
		this.setValue(this.options.value);
		this.initialized = true;
	},
 
	display : function() {
		for (var i = 0;i < this.options.maxRating; i++) {
			el = new Element('img', {
				'events': { 
					'mouseover':	this.hover.bind(this),
					'click': 	this.rate.bind(this),
					'mouseout': this.clear.bind(this)
				},
				'title': this.messageBox.get(i),
				'styles': {
					'cursor': 'pointer'
				},
				'src': this.locked ? this.selectedImages.EMPTY : this.hoverImages.EMPTY 
			});			
			this.ratings.push(el);
			this.container.appendChild(el);
		}
	},
 
	setValue : function(val) {
		if (this.locked && this.initialized)
			return;
 
		// iterate on options.valueBox to search key for val.
 
		for (var i = 0;i < this.valueBox.getValues().length; i++) {
			var value = this.valueBox.get(i);
			if (value == val + .5) {
				this.value = i - .5;
				break;
			} else if (value == val) {
				this.value = i;
				break;
			}
		}
		/*
		 * this.options.valueBox.each(function(value, key) { if (value == val +
		 * .5) { this.value = key - .5; } else if (value == val) { this.value =
		 * key; } }, this);
		 */
		if (this.options.bindField) {
			$(this.options.bindField).value = val;
		}
		if (this.initialized) {
			if (this.options.actionURL) {
				// ajax submit.
				new Request({url: this.options.actionURL + val,
					onSuccess : this.options["callback"]
				}).get();
			} else if (this.options.callback) {
				this.options["callback"](val);
			}
		}
		this.clear();
	},
 
	hover : function(ev) {
		if (this.locked)
			return;
		var rating = new Event(ev).target;
		var greater = false;
 
		this.ratings.each(function(el) {
			el.src = greater ? this.hoverImages.EMPTY : this.hoverImages.FULL;
			if (rating == el) {
				greater = true;
				// TODO use opacity style, maybe more beautiful styles should be
				// added.
				if (this.useOpacityStyle) {
					var fx = new Fx.Morph(el, {
						duration : 500,
						wait : false
					});
					fx.start(.5, 1);
				}
			}
		}, this);
	},
 
	rate : function(ev) {
		if (this.locked)
			return;
		var rating = new Event(ev).target;
		this.rated = true;
 
		this.ratings.some(function(el, i) {
			if (el == rating) {
				this.setValue(this.valueBox.get(i));
				return true;
			}
		}, this);
	},
 
	clear : function(ev) {
		if (this.locked && this.initialized)
			return;
		var greater = false;
		this.ratings.each(function(el, i) {
			if (i > this.value)
				greater = true;
			if ((this.initialized && this.rated) || this.value == -1)
				el.src = greater ? (this.value + .5 == i)
						? this.hoverImages.HALF
						: this.hoverImages.EMPTY : this.hoverImages.FULL;
			else
				this.ratings[i].src = greater ? (this.value + .5 == i)
						? this.selectedImages.HALF
						: this.selectedImages.EMPTY : this.selectedImages.FULL;
		}, this)
	}
});
/**
 * Multiple file upload element (Mootools 1.2 version)
 *  by Stickman
 *  http://the-stickman.com
 *  with thanks to:
 *   Luis Torrefranca -- http://www.law.pitt.edu
 *   and
 *   Shawn Parker & John Pennypacker -- http://www.fuzzycoconut.com
 *   ...for Safari fixes in the original version
 *
 * Licence:
 *  You may use this script as you wish without limitation, however I would
 *  appreciate it if you left at least the credit and site link above in
 *  place. I accept no liability for any problems or damage encountered
 *  as a result of using this script.
 *
 * Requires:
 *  Mootools 1.1 [ http://mootools.net ]
 *  ...with at least:
 *   Window.DomReady and its dependencies
 * Supports:
 *  All browsers supported by Mootools (see Mootools site for details)
 *
 * Usage:
 *  Include this file (or the packed version) and your mootools.js release in
 *  your HTML file. To  convert a standard file input element into a multiple
 *  file input element, add the following code to your HTML:
 *
 *    window.addEvent('domready', function(){
 *      new MultiUpload( $( 'my_form' ).my_file_input_element );
 *    });
 *
 *  ...where 'my_form' is the ID of your form and 'my_file_input_element' is
 *  the name of the file input element to be converted (or use whichever other
 *  method you prefer for finding the target file input element).
 *
 *  I've also included a simple CSS file (Stickman.MultiUpload.css) which
 *  you can include, although it's very basic (see 'Styling the element'),
 *  below.
 *
 * Optional parameters:
 *  There are four optional parameters (null = ignore this parameter):
 *
 *  - maximum number of files (default = 0)
 *    An integer to limit the number of files that can be uploaded using the
 *    element. A value of zero means 'no limit'.
 *
 *  - File name suffix template (default '_{id}'
 *    By default, the script will take the name of the original file input
 *    element and append an underscore followed by a number to it, eg. if the
 *    input's name is 'file' then the elements will be numbered sequentially:
 *    file_0, file_1, file_2...
 *    You can change the format of the suffix by passing in a template. This
 *    can be any string, but the sequence '{id}' will be replaced by the
 *    sequential ID of the element. So if the element is called 'file' and you
 *    pass in the template '[{id}]' then the elements will be named file[0],
 *    file[1], file[2]...
 *    To remove the suffix entirely, simply pass an empty string.
 *
 *  - Remove file path (default = false)
 *    By default, the entire path of the file is shown in the list of files.
 *    If you would prefer to show only the file name, set this option to
 *    'true'.
 *
 *  - Remove empty file input element (default = false)
 *    Because an extra (empty) element is created every time a file is
 *    chosen, this means that there will always be one empty file input
 *    element when the form is submitted. By default this is submitted with
 *    the form (exactly as it would be with a 'normal' file input element, in
 *    most browsers) but setting this option to 'true' will cause the element
 *    to be disabled  (and therefore ignored) when the form is submitted.
 *
 * Styling the element
 *  I didn't spend a lot of time making this look pretty. I've included an
 *  example CSS file (Stickman.MultiUpload.css) which is very basic but shows
 *  the parts that make up the element. These are:
 *   - div.multiupload
 *     When instaniated, the script places a container DIV around the file
 *     element, which also includes the files list
 *   - div.list
 *     Container DIV for the list of files
 *   - div.item
 *     Each item in the files list
 *   - div.item img
 *     The delete button image
 *  If changing the appearance of the element is not enough, you can alter the
 *  structure of the container and list elements in the initialize() method,
 *  or the file list elements in the addRow() method.
 *
 * Handling the uploaded files
 *  This is purely a client-side script -- I have not included any code for
 *  handling the uploaded files when they reach your server. This is because
 *  I don't know what platform you're using, or what you want to do with the
 *  files. When I posted the original version of this script, a lot of people
 *  went on to submit support code for various platforms. So you might find
 *  what you need in the comments one of these pages:
 *   http://tinyurl.com/8yp53
 *   http://tinyurl.com/wrc8p
 *
 * Other notes
 *  Because it's not possible to  set the value of a file input element
 *  dynamically (for good security reasons), this script works by hiding the
 *  file input element when a file is selected, then immediately replacing
 *  it with a new, empty one. This happens so quickly that it looks as if
 *  there's only ever one file input element.
 *  Although ideally the extra elements would be hidden using the CSS setting
 *  'display:none', this causes Safari to ignore the element completely when
 *  the form is submitted. So instead, elements are moved to a position
 *  off-screen.
 *  And no, it's not 'Ajax' -- it doesn't upload the files in the background
 *  or anything clever like that. Its sole purpose is cosmetic: to remove the
 *  need for multiple file input elements in a form.
 */

var MultiUpload = new Class(
{

	/**
	 * Constructor
	 * @param		HTMLInputElement		input_element				The file input element
	 * @param		int						max							[Optional] Max number of elements (default = 0 = unlimited)
	 * @param		string					name_suffix_template		[Optional] Template for appending to file name. Use {id} to insert row number (default = '_{id}')
	 * @param		boolean					show_filename_only			[Optional] Whether to strip path info from file name when displaying in list (default = false)
	 * @param		boolean					remove_empty_element		[Optional] Whether or not to remove the (empty) 'extra' element when the form is submitted (default = true)
	 */
	initialize:function( input_element, max, name_suffix_template, show_filename_only, remove_empty_element ){

		// Sanity check -- make sure it's  file input element
		if( !( input_element.tagName == 'INPUT' && input_element.type == 'file' ) ){
			alert( 'Error: not a file input element' );
			return;
		}

		// List of elements
		this.elements = [];
		// Lookup for row ID => array ID
		this.uid_lookup = {};
		// Current row ID
		this.uid = 0;

		// Maximum number of selected files (default = 0, ie. no limit)
		// This is optional
		if( $defined( max ) ){
			this.max = max;
		} else {
			this.max = 0;
		}

		// Template for adding id to file name
		// This is optional
		if( $defined( name_suffix_template ) ){
			this.name_suffix_template = name_suffix_template;
		} else {
			this.name_suffix_template= '_{id}';
		}

		// Show only filename (i.e. remove path)
		// This is optional
		if( $defined( show_filename_only ) ){
			this.show_filename_only = show_filename_only;
		} else {
			this.show_filename_only = false;
		}

		// Remove empty element before submitting form
		// This is optional
		if( $defined( remove_empty_element ) ){
			this.remove_empty_element = remove_empty_element;
		} else {
			this.remove_empty_element = false;
		}

		// Add element methods
		$( input_element );

		// Base name for input elements
		this.name = input_element.name;
		// Set up element for multi-upload functionality
		this.initializeElement( input_element );

		// Files list
		var container = new Element(
			'div',
			{
				'class':'multiupload'
			}
		);
		this.list = new Element(
			'div',
			{
				'class':'list'
			}
		);
		container.injectAfter( input_element );
		container.adopt( input_element );
		container.adopt( this.list );

		// Causes the 'extra' (empty) element not to be submitted
		if( this.remove_empty_element){
			input_element.form.addEvent(
				'submit',function(){
					this.elements.getLast().element.disabled = true;
				}.bind( this )
			);
		}
	},


	/**
	 * Called when a file is selected
	 */
	addRow:function(){
		if( this.max == 0 || this.elements.length <= this.max ){

			current_element = this.elements.getLast();

			// Create new row in files list
			var name = current_element.element.value;
			// Extract file name?
			if( this.show_filename_only ){
				if( name.contains( '\\' ) ){
					name = name.substring( name.lastIndexOf( '\\' ) + 1 );
				}
				if( name.contains( '//' ) ){
					name = name.substring( name.lastIndexOf( '//' ) + 1 );
				}
			}
			var item = new Element(
				'span'
			).set( 'text', name );
			var delete_button = new Element(
				'img',
				{
					'src':'templates/'+DESIGN+'/images/delete.png',
					'alt':DELETE,
					'title':DELETE,
					'events':{
						'click':function( uid ){
							this.deleteRow( uid );
						}.pass( current_element.uid, this )
					}
				}
			);
			var row_element = new Element(
				'div',
				{
					'class':'item'
				}
			).adopt( delete_button ).adopt( item );
			this.list.adopt( row_element );
			current_element.row = row_element;

			// Create new file input element
			var new_input = new Element
			(
				'input',
				{
					'type':'file',
					'disabled':( this.elements.length == this.max )?true:false
				}
			);
			// Apply multi-upload functionality to new element
			this.initializeElement(new_input);

			// Add new element to page
			current_element.element.style.position = 'absolute';
			current_element.element.style.left = '-1000px';
			new_input.injectAfter( current_element.element );
		} else {
			alert( 'You may not upload more than ' + this.max + ' files'  );
		}

	},

	/**
	 * Called when the delete button of a row is clicked
	 */
	deleteRow:function( uid ){

		// Confirm before delete
		deleted_row = this.elements[ this.uid_lookup[ uid ] ];
			this.elements.getLast().element.disabled = false;
			deleted_row.element.dispose();
			deleted_row.row.dispose();
			// Get rid of this row in the elements list
			delete(this.elements[  this.uid_lookup[ uid ] ]);

			// Rewrite IDs
			var new_elements=[];
			this.uid_lookup = {};
			for( var i = 0; i < this.elements.length; i++ ){
				if( $defined( this.elements[ i ] ) ){
					this.elements[ i ].element.name = this.name + this.name_suffix_template.replace( /\{id\}/, new_elements.length );
					this.uid_lookup[ this.elements[ i ].uid ] = new_elements.length;
					new_elements.push( this.elements[ i ] );
				}
			}
			this.elements = new_elements;
	},

	/**
	 * Apply multi-upload functionality to an element
	 *
	 * @param		HTTPFileInputElement		element		The element
	 */
	initializeElement:function( element ){

		// What happens when a file is selected
		element.addEvent(
			'change',
			function(){
				this.addRow()
			}.bind( this )
		);
		// Set the name
		element.name = this.name + this.name_suffix_template.replace( /\{id\}/, this.elements.length );

		// Store it for later
		this.uid_lookup[ this.uid ] = this.elements.length;
		this.elements.push( { 'uid':this.uid, 'element':element } );
		this.uid++;

	}
}
);