/**
 * Teacher control room: poll session state, counters, distribution, leaderboard.
 */
(function () {
    'use strict';

    function qs(sel, root) {
        return (root || document).querySelector(sel);
    }

    function qsa(sel, root) {
        return Array.prototype.slice.call((root || document).querySelectorAll(sel));
    }

    function init() {
        var root = qs('[data-live-teacher]');
        if (!root || !window.ClassPulsePoller) {
            return;
        }

        var sessionId = root.getAttribute('data-session-id');
        var urls = {
            state: root.getAttribute('data-poll-state-url'),
            counter: root.getAttribute('data-poll-counter-url'),
            distribution: root.getAttribute('data-poll-distribution-url'),
            leaderboard: root.getAttribute('data-poll-leaderboard-url'),
        };

        var timerEl = qs('[data-live-timer]', root);
        var statusEl = qs('[data-session-status]', root);
        var promptEl = qs('[data-question-prompt]', root);
        var answeredEl = qs('[data-answered-count]', root);
        var totalEl = qs('[data-total-count]', root);
        var distRoot = qs('[data-distribution]', root);
        var boardRoot = qs('[data-leaderboard]', root);
        var progressEl = qs('[data-question-progress]', root);

        var timer = null;
        if (timerEl && window.ClassPulseLiveTimer) {
            timer = new window.ClassPulseLiveTimer({ el: timerEl });
            timer.start();
        }

        function applyState(data) {
            if (!data) {
                return;
            }
            if (statusEl && data.status) {
                statusEl.textContent = String(data.status).toUpperCase();
                statusEl.classList.toggle('badge-live', data.status === 'active');
            }
            var question = data.question || data.current_question;
            if (promptEl && question) {
                promptEl.textContent = question.prompt || question.text || '';
            }
            if (timer) {
                timer.resync({
                    server_now: data.server_now,
                    question_started_at: data.question_started_at,
                    time_limit_seconds: data.time_limit_seconds
                        || (question && question.time_limit_seconds)
                        || 0,
                });
            }
            if (progressEl && data.question_number && data.question_count) {
                progressEl.textContent = 'Question ' + data.question_number + ' of ' + data.question_count;
            }
            if (data.status === 'ended') {
                pollers.forEach(function (p) { p.stop(); });
            }
        }

        function applyCounter(data) {
            if (!data) {
                return;
            }
            if (answeredEl) {
                answeredEl.textContent = data.answered != null ? data.answered : (data.count || 0);
                answeredEl.classList.add('cp-is-syncing');
                setTimeout(function () { answeredEl.classList.remove('cp-is-syncing'); }, 700);
            }
            if (totalEl) {
                totalEl.textContent = data.total != null ? data.total : (data.expected || 0);
            }
        }

        function applyDistribution(data) {
            if (!distRoot) {
                return;
            }
            var items = (data && (data.distribution || data.options || data.bars)) || [];
            if (!items.length) {
                return;
            }
            var max = items.reduce(function (m, item) {
                return Math.max(m, Number(item.count || item.responses || 0));
            }, 1);

            distRoot.classList.add('cp-is-syncing');
            distRoot.innerHTML = items.map(function (item) {
                var count = Number(item.count || item.responses || 0);
                var pct = Math.round((count / max) * 100);
                var label = item.label || item.option_text || item.text || '';
                var correctClass = item.is_correct ? ' is-correct' : '';
                return (
                    '<div class="mb-3">' +
                    '<div class="d-flex justify-content-between small mb-1">' +
                    '<span>' + escapeHtml(label) + '</span>' +
                    '<span class="cp-muted">' + count + '</span>' +
                    '</div>' +
                    '<div class="cp-bar-track"><div class="cp-bar-fill' + correctClass +
                    '" style="width:' + pct + '%"></div></div>' +
                    '</div>'
                );
            }).join('');
            setTimeout(function () { distRoot.classList.remove('cp-is-syncing'); }, 700);
        }

        function applyLeaderboard(data) {
            if (!boardRoot) {
                return;
            }
            var rows = (data && (data.leaderboard || data.rows || data.data)) || [];
            boardRoot.classList.add('cp-is-syncing');
            if (!rows.length) {
                boardRoot.innerHTML = '<div class="cp-muted p-3">No scores yet — waiting for answers.</div>';
                setTimeout(function () { boardRoot.classList.remove('cp-is-syncing'); }, 700);
                return;
            }
            boardRoot.innerHTML = rows.map(function (row, idx) {
                var rank = row.rank != null ? row.rank : idx + 1;
                var name = row.name || row.student_name || 'Student';
                var points = row.points != null ? row.points : (row.total_points || 0);
                var avg = row.avg_response_time_ms != null
                    ? Math.round(row.avg_response_time_ms) + ' ms'
                    : (row.avg_time || '');
                var top = rank <= 3 ? ' is-top' : '';
                return (
                    '<div class="cp-leaderboard-row' + top + '">' +
                    '<div class="cp-rank">#' + rank + '</div>' +
                    '<div>' + escapeHtml(name) + '</div>' +
                    '<div class="fw-bold">' + points + '</div>' +
                    '<div class="cp-avg-time cp-muted small">' + escapeHtml(String(avg)) + '</div>' +
                    '</div>'
                );
            }).join('');
            setTimeout(function () { boardRoot.classList.remove('cp-is-syncing'); }, 700);
        }

        function escapeHtml(str) {
            return String(str)
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;');
        }

        var pollers = [];

        if (urls.state) {
            pollers.push(new window.ClassPulsePoller({
                url: urls.state,
                intervalMs: 2500,
                onData: applyState,
            }).start());
        }
        if (urls.counter) {
            pollers.push(new window.ClassPulsePoller({
                url: urls.counter,
                intervalMs: 2000,
                onData: applyCounter,
            }).start());
        }
        if (urls.distribution) {
            pollers.push(new window.ClassPulsePoller({
                url: urls.distribution,
                intervalMs: 2500,
                onData: applyDistribution,
            }).start());
        }
        if (urls.leaderboard) {
            pollers.push(new window.ClassPulsePoller({
                url: urls.leaderboard,
                intervalMs: 3000,
                onData: applyLeaderboard,
            }).start());
        }

        document.addEventListener('visibilitychange', function () {
            if (document.hidden) {
                pollers.forEach(function (p) { p.stop(); });
            } else {
                pollers.forEach(function (p) { p.start(); });
            }
        });

        window.addEventListener('beforeunload', function () {
            pollers.forEach(function (p) { p.stop(); });
            if (timer) {
                timer.stop();
            }
        });

        root._cpSessionId = sessionId;
        root._cpPollers = pollers;
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
