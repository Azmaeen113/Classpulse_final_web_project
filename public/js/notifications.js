/**
 * Notification bell polling + mark read.
 */
(function () {
    'use strict';

    function qs(sel, root) {
        return (root || document).querySelector(sel);
    }

    function init() {
        var root = qs('[data-notifications]');
        if (!root || !window.ClassPulsePoller || !window.cpFetchJson) {
            return;
        }

        var listUrl = root.getAttribute('data-list-url');
        var readUrlTemplate = root.getAttribute('data-read-url');
        var readAllUrl = root.getAttribute('data-read-all-url');

        var badge = qs('[data-notify-badge]', root);
        var list = qs('[data-notify-list]', root);
        var markAllBtn = qs('[data-mark-all-read]', root);

        function render(data) {
            var items = (data && (data.notifications || data.data || data.items)) || [];
            var unread = data && data.unread_count != null
                ? Number(data.unread_count)
                : items.filter(function (n) { return !n.read_at; }).length;

            if (badge) {
                if (unread > 0) {
                    badge.textContent = unread > 99 ? '99+' : String(unread);
                    badge.classList.add('is-visible');
                } else {
                    badge.classList.remove('is-visible');
                    badge.textContent = '';
                }
            }

            if (!list) {
                return;
            }

            if (!items.length) {
                list.innerHTML = '<div class="dropdown-item-text cp-muted p-3">No notifications.</div>';
                return;
            }

            list.innerHTML = items.map(function (n) {
                var unreadClass = n.read_at ? '' : ' unread';
                return (
                    '<button type="button" class="dropdown-item cp-notify-item' + unreadClass +
                    '" data-id="' + n.id + '">' +
                    '<div class="fw-semibold">' + escapeHtml(n.title || 'Notification') + '</div>' +
                    '<div class="small cp-muted">' + escapeHtml(n.body || '') + '</div>' +
                    '</button>'
                );
            }).join('');

            Array.prototype.forEach.call(list.querySelectorAll('[data-id]'), function (btn) {
                btn.addEventListener('click', function () {
                    markRead(btn.getAttribute('data-id'));
                });
            });
        }

        function escapeHtml(str) {
            return String(str)
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;');
        }

        async function markRead(id) {
            if (!readUrlTemplate || !id) {
                return;
            }
            var url = readUrlTemplate.replace('__ID__', id).replace(':id', id);
            try {
                await window.cpFetchJson(url, { method: 'POST', body: '{}' });
                poller.tick();
            } catch (e) {
                /* ignore */
            }
        }

        async function markAllRead() {
            if (!readAllUrl) {
                return;
            }
            try {
                await window.cpFetchJson(readAllUrl, { method: 'POST', body: '{}' });
                poller.tick();
            } catch (e) {
                /* ignore */
            }
        }

        if (markAllBtn) {
            markAllBtn.addEventListener('click', function (e) {
                e.preventDefault();
                e.stopPropagation();
                markAllRead();
            });
        }

        var poller = new window.ClassPulsePoller({
            url: listUrl,
            intervalMs: 4000,
            onData: render,
        }).start();

        document.addEventListener('visibilitychange', function () {
            if (document.hidden) {
                poller.stop();
            } else {
                poller.start();
            }
        });

        window.addEventListener('beforeunload', function () {
            poller.stop();
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
