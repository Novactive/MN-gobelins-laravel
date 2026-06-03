"use strict";

exports.__esModule = true;

function _classCallCheck(instance, Constructor) { if (!(instance instanceof Constructor)) { throw new TypeError("Cannot call a class as a function"); } }

var GatewayRegistry = function () {
  function GatewayRegistry() {
    _classCallCheck(this, GatewayRegistry);

    this._containers = {};
    this._children = {};

    // Unique key for children of a gateway
    this._currentId = 0;
  }

  GatewayRegistry.prototype._renderContainer = function _renderContainer(name) {
    var _this = this;

    if (!this._containers[name] || !this._children[name]) {
      return;
    }

    this._containers[name].setState({
      children: Object.keys(this._children[name]).sort().map(function (id) {
        return _this._children[name][id];
      })
    });
  };

  GatewayRegistry.prototype.addContainer = function addContainer(name, container) {
    this._containers[name] = container;
    this._renderContainer(name);
  };

  GatewayRegistry.prototype.removeContainer = function removeContainer(name) {
    this._containers[name] = null;
  };

  GatewayRegistry.prototype.addChild = function addChild(name, gatewayId, child) {
    this._children[name][gatewayId] = child;
    this._renderContainer(name);
  };

  GatewayRegistry.prototype.clearChild = function clearChild(name, gatewayId) {
    delete this._children[name][gatewayId];
  };

  GatewayRegistry.prototype.register = function register(name, child) {
    this._children[name] = this._children[name] || {};

    var gatewayId = name + "_" + this._currentId;
    this._children[name][gatewayId] = child;
    this._currentId += 1;

    return gatewayId;
  };

  GatewayRegistry.prototype.unregister = function unregister(name, gatewayId) {
    this.clearChild(name, gatewayId);
    this._renderContainer(name);
  };

  return GatewayRegistry;
}();

exports.default = GatewayRegistry;