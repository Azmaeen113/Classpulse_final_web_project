/**
 * Client countdown synced to server_now + question_started_at + time_limit_seconds.
 */
(function (global) {
    'use strict';

    class LiveTimer {
        /**
         * @param {object} options
         * @param {HTMLElement|string} options.el
         * @param {function(number):void} [options.onTick]
         * @param {function():void} [options.onZero]
         * @param {number} [options.warnAt=10]
         * @param {number} [options.criticalAt=5]
         */
        constructor(options) {
            this.el = typeof options.el === 'string'
                ? document.querySelector(options.el)
                : options.el;
            this.onTick = options.onTick || function () {};
            this.onZero = options.onZero || function () {};
            this.warnAt = options.warnAt != null ? options.warnAt : 10;
            this.criticalAt = options.criticalAt != null ? options.criticalAt : 5;

            this._serverSkewMs = 0;
            this._startedAtMs = null;
            this._limitSeconds = 0;
            this._raf = null;
            this._running = false;
            this._zeroFired = false;
            this._lastShown = null;
        }

        /**
         * @param {object} sync
         * @param {string|number} sync.server_now ISO or unix seconds/ms
         * @param {string|number|null} sync.question_started_at
         * @param {number} sync.time_limit_seconds
         */
        resync(sync) {
            if (!sync) {
                return;
            }
            var serverNow = this._toMs(sync.server_now);
            if (serverNow != null) {
                this._serverSkewMs = serverNow - Date.now();
            }
            this._startedAtMs = this._toMs(sync.question_started_at);
            this._limitSeconds = Number(sync.time_limit_seconds) || 0;
            this._zeroFired = false;
            this.render();
        }

        start() {
            if (this._running) {
                return this;
            }
            this._running = true;
            var loop = function () {
                if (!this._running) {
                    return;
                }
                this.render();
                this._raf = requestAnimationFrame(loop);
            }.bind(this);
            this._raf = requestAnimationFrame(loop);
            return this;
        }

        stop() {
            this._running = false;
            if (this._raf) {
                cancelAnimationFrame(this._raf);
                this._raf = null;
            }
            return this;
        }

        remainingSeconds() {
            if (this._startedAtMs == null || !this._limitSeconds) {
                return this._limitSeconds || 0;
            }
            var now = Date.now() + this._serverSkewMs;
            var elapsed = (now - this._startedAtMs) / 1000;
            return Math.max(0, Math.ceil(this._limitSeconds - elapsed));
        }

        render() {
            var remaining = this.remainingSeconds();
            if (this._lastShown !== remaining) {
                this._lastShown = remaining;
                if (this.el) {
                    this.el.textContent = this.format(remaining);
                    this.el.classList.toggle('is-warn', remaining <= this.warnAt && remaining > this.criticalAt);
                    this.el.classList.toggle('is-critical', remaining <= this.criticalAt);
                }
                this.onTick(remaining);
            }
            if (remaining <= 0 && !this._zeroFired && this._startedAtMs != null) {
                this._zeroFired = true;
                this.onZero();
            }
        }

        format(totalSeconds) {
            var s = Math.max(0, Math.floor(totalSeconds));
            var m = Math.floor(s / 60);
            var r = s % 60;
            return String(m).padStart(2, '0') + ':' + String(r).padStart(2, '0');
        }

        _toMs(value) {
            if (value == null || value === '') {
                return null;
            }
            if (typeof value === 'number') {
                return value < 1e12 ? value * 1000 : value;
            }
            var parsed = Date.parse(value);
            return Number.isNaN(parsed) ? null : parsed;
        }
    }

    global.ClassPulseLiveTimer = LiveTimer;
})(window);
