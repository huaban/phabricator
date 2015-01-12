/**
 * @provides javelin-aphlict
 * @requires javelin-install
 *           javelin-util
 *           javelin-websocket
 *           javelin-leader
 *           javelin-json
 */

/**
 * Client for the notification server. Example usage:
 *
 *   var aphlict = new JX.Aphlict('ws://localhost:22280', subscriptions)
 *     .setHandler(function(message) {
 *       // ...
 *     })
 *     .start();
 *
 */
JX.install('Aphlict', {

  construct: function(uri, subscriptions) {
    if (__DEV__) {
      if (JX.Aphlict._instance) {
        JX.$E('Aphlict object is a singleton.');
      }
    }

    this._uri = uri;
    this._subscriptions = subscriptions;
    this._setStatus('setup');

    JX.Aphlict._instance = this;
  },

  events: ['didChangeStatus'],

  members: {
    _server: null,
    _port: null,
    _subscriptions: null,
    _status: null,
    _statusCode: null,

    start: function(node, uri) {
      JX.Leader.listen('onBecomeLeader', JX.bind(this, this._lead));
      JX.Leader.listen('onReceiveBroadcast', JX.bind(this, this._receive));
      JX.Leader.start();

      JX.Leader.call(JX.bind(this, this._begin));
    },

    getStatus: function() {
      return this._status;
    },

    _begin: function() {
      JX.Leader.broadcast(
        null,
        {type: 'aphlict.getstatus'});
      JX.Leader.broadcast(
        null,
        {type: 'aphlict.subscribe', data: this._subscriptions});
    },

    _lead: function() {
      var socket = new JX.WebSocket(this._uri);
      socket.setOpenHandler(JX.bind(this, this._open));
      socket.setMessageHandler(JX.bind(this, this._message));
      socket.setCloseHandler(JX.bind(this, this._close));

      this._socket = socket;

      socket.open();
    },

    _open: function() {
      this._broadcastStatus('open');
      JX.Leader.broadcast(null, {type: 'aphlict.getsubscribers'});
    },

    _close: function() {
      this._broadcastStatus('closed');
    },

    _broadcastStatus: function(status) {
      JX.Leader.broadcast(null, {type: 'aphlict.status', data: status});
    },

    _message: function(raw) {
      var message = JX.JSON.parse(raw);
      JX.Leader.broadcast(null, {type: 'aphlict.server', data: message});
    },

    _receive: function(message, is_leader) {
      switch (message.type) {
        case 'aphlict.status':
          this._setStatus(message.data);
          break;
        case 'aphlict.getstatus':
          if (is_leader) {
            this._broadcastStatus(this.getStatus());
          }
          break;
        case 'aphlict.getsubscribers':
          JX.Leader.broadcast(
            null,
            {type: 'aphlict.subscribe', data: this._subscriptions});
          break;
        case 'aphlict.subscribe':
          if (is_leader) {
            this._write({
              command: 'subscribe',
              data: message.data
            });
          }
          break;
        case 'aphlict.server':
          var handler = this.getHandler();
          handler && handler(message.data);
          break;
      }
    },

    _setStatus: function(status) {
      this._status = status;
      this.invoke('didChangeStatus');
    },

    _write: function(message) {
      this._socket.send(JX.JSON.stringify(message));
    }

  },

  properties: {
    handler: null
  },

  statics: {
    _instance: null,

    getInstance: function() {
      var self = JX.Aphlict;
      if (!self._instance) {
        return null;
      }
      return self._instance;
    }

  }

});
