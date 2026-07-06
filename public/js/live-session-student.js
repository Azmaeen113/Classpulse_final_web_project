/**
 * Student live answer: poll state, submit answer, auto-submit on timer zero, waiting state.
 */
(function () {
    'use strict';

    function qs(sel, root) {
        return (root || document).querySelector(sel);
    }

    function init() {
        var root = qs('[data-live-student]');
        if (!root || !window.ClassPulsePoller || !window.cpFetchJson) {
            return;
        }

        var stateUrl = root.getAttribute('data-poll-state-url');
        var submitUrl = root.getAttribute('data-submit-url');
        var waitingUrl = root.getAttribute('data-waiting-url');
        var resultUrl = root.getAttribute('data-result-url');

        var answering = qs('[data-state="answering"]', root);
        var waiting = qs('[data-state="waiting"]', root);
        var submitted = qs('[data-state="submitted"]', root);
        var timerEl = qs('[data-live-timer]', root);
        var promptEl = qs('[data-question-prompt]', root);
        var optionsRoot = qs('[data-options]', root);
        var shortInput = qs('[data-short-answer]', root);
        var submitBtn = qs('[data-submit-answer]', root);
        var form = qs('[data-answer-form]', root);
        var feedbackEl = qs('[data-answer-feedback]', root);
        var progressEl = qs('[data-question-progress]', root);

        var currentQuestionId = null;
        var hasSubmitted = false;
        var submitting = false;
        var selectedOptionId = null;
        var questionStartedAt = null;
        var poller = null;

        var timer = null;
        if (timerEl && window.ClassPulseLiveTimer) {
            timer = new window.ClassPulseLiveTimer({
                el: timerEl,
                onZero: function () {
                    if (!hasSubmitted && currentQuestionId) {
                        submitAnswer(true);
                    }
                },
            });
            timer.start();
        }

        function showState(name) {
            [answering, waiting, submitted].forEach(function (el) {
                if (!el) {
                    return;
                }
                el.classList.toggle('d-none', el.getAttribute('data-state') !== name);
            });
        }

        function setFeedback(text, kind) {
            if (!feedbackEl) return;
            feedbackEl.textContent = text || '';
            feedbackEl.classList.toggle('d-none', !text);
            feedbackEl.classList.remove('text-success', 'text-danger', 'cp-muted');
            if (kind === 'ok') feedbackEl.classList.add('text-success');
            else if (kind === 'err') feedbackEl.classList.add('text-danger');
            else feedbackEl.classList.add('cp-muted');
        }

        function renderOptions(question) {
            if (!optionsRoot || !question) {
                return;
            }
            var type = question.type || 'mcq';
            var options = question.options || [];

            var fillHint = document.querySelector('[data-fill-hint]');

            if (type === 'short_answer') {
                optionsRoot.innerHTML = '';
                if (shortInput) {
                    shortInput.classList.remove('d-none');
                    shortInput.disabled = false;
                    shortInput.value = '';
                    shortInput.placeholder = 'Type your answer (capitalization does not matter)';
                }
                if (fillHint) fillHint.classList.remove('d-none');
                return;
            }

            if (shortInput) {
                shortInput.classList.add('d-none');
            }
            if (fillHint) fillHint.classList.add('d-none');

            optionsRoot.innerHTML = options.map(function (opt, index) {
                var tile = 'cp-tile-' + (index % 4);
                return (
                    '<button type="button" class="cp-option ' + tile + '" data-option-id="' +
                    opt.id + '">' + escapeHtml(opt.option_text || opt.text || '') +
                    '</button>'
                );
            }).join('');

            Array.prototype.forEach.call(optionsRoot.querySelectorAll('.cp-option'), function (btn) {
                btn.addEventListener('click', function () {
                    if (hasSubmitted) {
                        return;
                    }
                    Array.prototype.forEach.call(optionsRoot.querySelectorAll('.cp-option'), function (b) {
                        b.classList.remove('is-selected');
                    });
                    btn.classList.add('is-selected');
                    selectedOptionId = btn.getAttribute('data-option-id');
                });
            });
        }

        function lockInputs() {
            if (optionsRoot) {
                Array.prototype.forEach.call(optionsRoot.querySelectorAll('.cp-option'), function (b) {
                    b.disabled = true;
                });
            }
            if (shortInput) {
                shortInput.disabled = true;
            }
            if (submitBtn) {
                submitBtn.disabled = true;
            }
        }

        function applyState(data) {
            if (!data) {
                return;
            }

            if (data.status === 'ended') {
                poller.stop();
                if (resultUrl) {
                    window.location.href = resultUrl;
                }
                return;
            }

            var question = data.current_question || data.question || null;
            var qid = data.current_question_id
                || (question && question.id)
                || null;
            var answered = !!(data.answered || data.already_answered || data.has_answered);

            if (data.status === 'paused' || data.status === 'pending' || !qid || !question) {
                showState('waiting');
                if (timer) {
                    timer.resync({
                        server_now: data.server_now,
                        question_started_at: null,
                        time_limit_seconds: 0,
                    });
                }
                return;
            }

            if (String(qid) !== String(currentQuestionId)) {
                currentQuestionId = qid;
                hasSubmitted = answered;
                selectedOptionId = null;
                questionStartedAt = data.question_started_at;
                setFeedback('', '');
                if (promptEl) {
                    promptEl.textContent = question.prompt || '';
                }
                renderOptions(question);
                if (submitBtn) {
                    submitBtn.disabled = hasSubmitted;
                }
                if (shortInput) {
                    shortInput.disabled = hasSubmitted;
                }
            } else if (answered && !hasSubmitted) {
                hasSubmitted = true;
            }

            if (progressEl) {
                var num = data.question_number;
                var total = data.question_count;
                if (num && total) {
                    progressEl.textContent = 'Question ' + num + ' of ' + total;
                } else {
                    progressEl.textContent = '';
                }
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

            if (hasSubmitted) {
                lockInputs();
                showState('submitted');
            } else {
                showState('answering');
            }
        }

        async function submitAnswer(isAuto) {
            if (submitting || hasSubmitted || !submitUrl || !currentQuestionId) {
                return;
            }

            var payload = {
                question_id: Number(currentQuestionId),
                selected_option_id: selectedOptionId ? Number(selectedOptionId) : null,
                short_answer_text: shortInput && !shortInput.classList.contains('d-none')
                    ? shortInput.value
                    : null,
                is_auto_submit: !!isAuto,
            };

            if (!isAuto && !payload.selected_option_id && !(payload.short_answer_text || '').trim()) {
                return;
            }

            submitting = true;
            if (submitBtn) {
                submitBtn.disabled = true;
            }

            try {
                var res = await window.cpFetchJson(submitUrl, {
                    method: 'POST',
                    body: JSON.stringify(payload),
                });
                hasSubmitted = true;
                lockInputs();
                showState('submitted');
                if (res && res.session_status === 'ended' && resultUrl) {
                    window.location.href = resultUrl;
                    return;
                }
                if (res && res.advanced) {
                    // Everyone present answered (or time expired) — pull next question now.
                    setFeedback('Nice! Loading the next question…', 'ok');
                    currentQuestionId = null;
                    hasSubmitted = false;
                    showState('waiting');
                    if (poller && typeof poller.tick === 'function') {
                        poller.tick();
                    }
                    return;
                }
                if (res && res.reveal_answer) {
                    if (res.is_correct) {
                        setFeedback('Correct · ' + (res.points_awarded || 0) + ' pts', 'ok');
                    } else {
                        setFeedback('Incorrect · 0 pts', 'err');
                    }
                } else {
                    setFeedback('Answer locked in. Waiting for your teacher to continue…', '');
                }
            } catch (err) {
                var msg = (err && err.data && err.data.message) || (err && err.message) || 'Could not submit.';
                if (err && err.status === 422 && /already/i.test(msg)) {
                    hasSubmitted = true;
                    lockInputs();
                    showState('submitted');
                    setFeedback('Already answered.', '');
                } else {
                    setFeedback(msg, 'err');
                    if (submitBtn && !hasSubmitted) {
                        submitBtn.disabled = false;
                    }
                }
            } finally {
                submitting = false;
            }
        }

        function escapeHtml(str) {
            return String(str)
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;');
        }

        if (form) {
            form.addEventListener('submit', function (e) {
                e.preventDefault();
                submitAnswer(false);
            });
        }
        if (submitBtn) {
            submitBtn.addEventListener('click', function (e) {
                e.preventDefault();
                submitAnswer(false);
            });
        }

        poller = new window.ClassPulsePoller({
            url: stateUrl,
            intervalMs: 2000,
            onData: applyState,
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
            if (timer) {
                timer.stop();
            }
        });

        if (!stateUrl && waitingUrl) {
            showState('waiting');
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
