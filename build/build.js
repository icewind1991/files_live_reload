(function e(t,n,r){function s(o,u){if(!n[o]){if(!t[o]){var a=typeof require=="function"&&require;if(!u&&a)return a(o,!0);if(i)return i(o,!0);var f=new Error("Cannot find module '"+o+"'");throw f.code="MODULE_NOT_FOUND",f}var l=n[o]={exports:{}};t[o][0].call(l.exports,function(e){var n=t[o][1][e];return s(n?n:e)},l,l.exports,e,t,n,r)}return n[o].exports}var i=typeof require=="function"&&require;for(var o=0;o<r.length;o++)s(r[o]);return s})({1:[function(require,module,exports){
'use strict';

var _LiveReload = require('./LiveReload');

OC.Plugins.register('OCA.Files.FileList', {
	attach: function attach(fileList) {
		var reloader = new _LiveReload.LiveReload(fileList);
		reloader.start();
	}
});

},{"./LiveReload":4}],2:[function(require,module,exports){
'use strict';

Object.defineProperty(exports, '__esModule', {
	value: true
});

var _createClass = (function () { function defineProperties(target, props) { for (var i = 0; i < props.length; i++) { var descriptor = props[i]; descriptor.enumerable = descriptor.enumerable || false; descriptor.configurable = true; if ('value' in descriptor) descriptor.writable = true; Object.defineProperty(target, descriptor.key, descriptor); } } return function (Constructor, protoProps, staticProps) { if (protoProps) defineProperties(Constructor.prototype, protoProps); if (staticProps) defineProperties(Constructor, staticProps); return Constructor; }; })();

function _classCallCheck(instance, Constructor) { if (!(instance instanceof Constructor)) { throw new TypeError('Cannot call a class as a function'); } }

var ListUpdater = (function () {
	function ListUpdater(fileList) {
		_classCallCheck(this, ListUpdater);

		this.modelCache = {};

		this.fileList = fileList;
	}

	_createClass(ListUpdater, [{
		key: 'isInCurrentFolder',
		value: function isInCurrentFolder(path) {
			var dir = this.fileList._currentDirectory === '/' ? '' : this.fileList._currentDirectory;
			return OC.dirname(path) === dir;
		}
	}, {
		key: 'getModelForFile',
		value: function getModelForFile(path) {
			if (!this.isInCurrentFolder(path)) {
				return null;
			}
			var name = OC.basename(path);
			if (!this.modelCache[path]) {
				this.modelCache[path] = this.fileList.getModelForFile(name);
			}
			return this.modelCache[path];
		}
	}, {
		key: 'rename',
		value: function rename(from, to, data) {
			if (this.isInCurrentFolder(from) && this.isInCurrentFolder(to)) {
				var model = this.getModelForFile(from);
				if (model) {
					model.set('name', OC.basename(to));
				}
			} else if (this.isInCurrentFolder(from)) {
				this.remove(from);
			} else if (this.isInCurrentFolder(to)) {
				console.log(data);
				data.path = to;
				this.write(data);
			}
		}
	}, {
		key: 'write',
		value: function write(data) {
			if (!this.isInCurrentFolder(data.path)) {
				return null;
			}
			console.log('put', data);
			var model = this.getModelForFile(data.path);
			if (model) {
				for (var key in data) {
					model.set(key, data[key]);
				}
			} else {
				this.fileList.add(data);
			}
		}
	}, {
		key: 'remove',
		value: function remove(path) {
			if (!this.isInCurrentFolder(path)) {
				return null;
			}
			this.fileList.remove(OC.basename(path));
		}
	}]);

	return ListUpdater;
})();

exports.ListUpdater = ListUpdater;

},{}],3:[function(require,module,exports){
'use strict';

Object.defineProperty(exports, '__esModule', {
	value: true
});

var _createClass = (function () { function defineProperties(target, props) { for (var i = 0; i < props.length; i++) { var descriptor = props[i]; descriptor.enumerable = descriptor.enumerable || false; descriptor.configurable = true; if ('value' in descriptor) descriptor.writable = true; Object.defineProperty(target, descriptor.key, descriptor); } } return function (Constructor, protoProps, staticProps) { if (protoProps) defineProperties(Constructor.prototype, protoProps); if (staticProps) defineProperties(Constructor, staticProps); return Constructor; }; })();

function _classCallCheck(instance, Constructor) { if (!(instance instanceof Constructor)) { throw new TypeError('Cannot call a class as a function'); } }

var Listener = (function () {
	function Listener(updater, eventSource) {
		_classCallCheck(this, Listener);

		this.updater = updater;
		this.eventSource = eventSource;
	}

	_createClass(Listener, [{
		key: 'listen',
		value: function listen() {
			var _this = this;

			this.eventSource.addEventListener('rename', function (_ref) {
				var data = _ref.data;

				var parsed = JSON.parse(data);
				var source = parsed.source;
				var target = parsed.target;

				_this.updater.rename(source, target, parsed);
			});

			this.eventSource.addEventListener('write', function (_ref2) {
				var data = _ref2.data;

				_this.updater.write(JSON.parse(data));
			});

			this.eventSource.addEventListener('delete', function (_ref3) {
				var data = _ref3.data;

				var _JSON$parse = JSON.parse(data);

				var path = _JSON$parse.path;

				_this.updater.remove(path);
			});
		}
	}]);

	return Listener;
})();

exports.Listener = Listener;

},{}],4:[function(require,module,exports){
'use strict';

Object.defineProperty(exports, '__esModule', {
	value: true
});

var _createClass = (function () { function defineProperties(target, props) { for (var i = 0; i < props.length; i++) { var descriptor = props[i]; descriptor.enumerable = descriptor.enumerable || false; descriptor.configurable = true; if ('value' in descriptor) descriptor.writable = true; Object.defineProperty(target, descriptor.key, descriptor); } } return function (Constructor, protoProps, staticProps) { if (protoProps) defineProperties(Constructor.prototype, protoProps); if (staticProps) defineProperties(Constructor, staticProps); return Constructor; }; })();

function _classCallCheck(instance, Constructor) { if (!(instance instanceof Constructor)) { throw new TypeError('Cannot call a class as a function'); } }

var _ListUpdater = require('./ListUpdater');

var _Listener = require('./Listener');

var LiveReload = (function () {
	_createClass(LiveReload, [{
		key: 'supportsEventSource',
		value: function supportsEventSource() {
			return typeof EventSource !== 'undefined';
		}
	}]);

	function LiveReload(fileList) {
		_classCallCheck(this, LiveReload);

		this.updater = new _ListUpdater.ListUpdater(fileList);
	}

	_createClass(LiveReload, [{
		key: 'start',
		value: function start() {
			if (!this.supportsEventSource()) {
				return;
			}
			this.eventSource = new EventSource(OC.generateUrl('/apps/files_live_reload/listen'));
			this.listener = new _Listener.Listener(this.updater, this.eventSource);
			this.listener.listen();
		}
	}]);

	return LiveReload;
})();

exports.LiveReload = LiveReload;

},{"./ListUpdater":2,"./Listener":3}]},{},[1])


//# sourceMappingURL=build.js.map