/**
 * Reusable Fetch poller with start/stop lifecycle.
 */
(function (global) {
    'use strict';

    function getCsrfToken() {
        var meta = document.querySelector('meta[name="csrf-token"]');
        return meta ? meta.getAttribute('content') : '';
    }

    function getZone() {
        return document.documentElement.getAttribute('data-zone') || '';
    }

    function zoneHeaders(extra) {
        var headers = {
            Accept: 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': getCsrfToken(),
        };
        var zone = getZone();
        if (zone && zone !== 'guest') {
            headers['X-ClassPulse-Zone'] = zone;
        }
        return Object.assign(headers, extra || {});
    }

    class Poller {
        /**
         * @param {object} options
         * @param {string|function():string} options.url
         * @param {function(any):void} options.onData
         * @param {function(Error):void} [options.onError]
         * @param {number} [options.intervalMs=3000]
         * @param {object|function():object} [options.fetchOptions]
         * @param {boolean} [options.immediate=true]
         */
        constructor(options) {
            this.url = options.url;
            this.onData = options.onData;
            this.onError = options.onError || function () {};
            this.intervalMs = options.intervalMs || 3000;
            this.fetchOptions = options.fetchOptions || {};
            this.immediate = options.immediate !== false;
            this._timer = null;
            this._running = false;
            this._inFlight = false;
            this._aborted = false;
        }

        get isRunning() {
            return this._running;
        }

        start() {
            if (this._running) {
                return this;
            }
            this._running = true;
            this._aborted = false;
            if (this.immediate) {
                this.tick();
            }
            this._timer = setInterval(this.tick.bind(this), this.intervalMs);
            return this;
        }

        stop() {
            this._running = false;
            this._aborted = true;
            if (this._timer) {
                clearInterval(this._timer);
                this._timer = null;
            }
            return this;
        }

        setIntervalMs(ms) {
            this.intervalMs = ms;
            if (this._running) {
                this.stop();
                this.start();
            }
            return this;
        }

        async tick() {
            if (!this._running || this._inFlight) {
                return;
            }
            this._inFlight = true;
            try {
                var url = typeof this.url === 'function' ? this.url() : this.url;
                var opts = typeof this.fetchOptions === 'function'
                    ? this.fetchOptions()
                    : Object.assign({}, this.fetchOptions);

                opts.headers = zoneHeaders(opts.headers || {});
                opts.credentials = opts.credentials || 'same-origin';

                var response = await fetch(url, opts);
                if (!response.ok) {
                    throw new Error('Poll failed: HTTP ' + response.status);
                }
                var data = await response.json();
                if (!this._aborted) {
                    this.onData(data);
                }
            } catch (err) {
                if (!this._aborted) {
                    this.onError(err);
                }
            } finally {
                this._inFlight = false;
            }
        }
    }

    global.ClassPulsePoller = Poller;
    global.cpCsrfToken = getCsrfToken;

    global.cpFetchJson = async function (url, options) {
        var opts = Object.assign({ method: 'GET' }, options || {});
        opts.headers = zoneHeaders(Object.assign(
            { 'Content-Type': 'application/json' },
            opts.headers || {}
        ));
        opts.credentials = opts.credentials || 'same-origin';
        var response = await fetch(url, opts);
        var data = null;
        try {
            data = await response.json();
        } catch (e) {
            data = null;
        }
        if (!response.ok) {
            var err = new Error((data && data.message) || 'Request failed');
            err.status = response.status;
            err.data = data;
            throw err;
        }
        return data;
    };
})(window);
