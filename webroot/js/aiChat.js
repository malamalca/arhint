$(function () {
    var $panel = $('#DashboardAIAssistant');
    if (!$panel.length) return;

    var chatUrl          = $panel.data('chat-url');
    var chatStatusUrl    = $panel.data('chat-status-url');
    var clearUrl         = $panel.data('clear-url');
    var workerStatusUrl  = $panel.data('worker-status-url');
    var csrfToken = $('meta[name="csrfToken"]').attr('content');

    $.ajaxSetup({
        headers: { 'X-CSRF-Token': csrfToken },
    });

    var $messages = $('#AIAssistantMessages');
    var $form     = $('#AIAssistantForm');
    var $input    = $('#AIAssistantInput');
    var $send     = $('#AIAssistantSend');
    var $clear    = $('#AIAssistantClear');

    function appendMessage(role, text) {
        var $msg = $('<div>').addClass('ai-message ai-message--' + role);
        var $label = $('<span>').addClass('ai-message__role').text(role === 'user' ? 'You' : 'AI');
        var $body  = $('<div>').addClass('ai-message__body');
        if (role === 'assistant') {
            $body.html(text);
        } else {
            $body.text(text);
        }
        $msg.append($label).append($body);
        $messages.append($msg);
        $messages.scrollTop($messages[0].scrollHeight);
    }

    // Worker status banner — shown above messages after the first chat message if worker is not running.
    var $workerWarning = $('<div>').addClass('ai-worker-warning').hide().text(
        'Queue worker is not running. Start it with: bin/cake queue worker'
    );
    $panel.prepend($workerWarning);
    var workerStatusChecked = false;

    function checkWorkerStatus() {
        if (!workerStatusUrl || workerStatusChecked) return;
        workerStatusChecked = true;
        $.getJSON(workerStatusUrl, function (data) {
            if (data.running === false) {
                $workerWarning.show();
            }
        });
    }

    function cancelWorkerCheck() {
        // Mark as checked so the deferred poll-3 call becomes a no-op.
        workerStatusChecked = true;
    }

    var $thinking = $('<div>').addClass('ai-message ai-message--assistant ai-message--thinking').append(
        $('<span>').addClass('ai-message__role').text('AI'),
        $('<div>').addClass('ai-message__body').append(
            $('<div>').addClass('ai-thinking').append($('<span>'), $('<span>'), $('<span>'))
        )
    );

    function showThinking() {
        $messages.append($thinking);
        $messages.scrollTop($messages[0].scrollHeight);
    }

    function hideThinking() {
        $thinking.detach();
    }

    function setLoading(loading) {
        $send.prop('disabled', loading);
        $input.prop('disabled', loading);
        if (loading) {
            $send.addClass('disabled');
            showThinking();
        } else {
            $send.removeClass('disabled');
            hideThinking();
            $input.focus();
        }
    }

    // Poll chatStatus every interval until the job is done or errors out.
    var POLL_INTERVAL_MS = 1000;
    var MAX_POLLS = 180; // Give up after ~3 minutes

    function pollJobStatus(jobId, pollCount) {
        if (pollCount >= MAX_POLLS) {
            setLoading(false);
            appendMessage('error', 'Request timed out. Please try again.');
            return;
        }

        // After a few pending polls the worker has had time to touch its heartbeat;
        // only then check whether it is actually running.
        if (pollCount === 3) {
            checkWorkerStatus();
        }

        $.ajax({
            url: chatStatusUrl,
            method: 'GET',
            dataType: 'json',
            data: { job_id: jobId },
            success: function (data) {
                if (data.status === 'pending') {
                    setTimeout(function () {
                        pollJobStatus(jobId, pollCount + 1);
                    }, POLL_INTERVAL_MS);
                    return;
                }

                setLoading(false);

                if (data.status === 'error') {
                    appendMessage('error', data.error || 'An error occurred. Please try again.');
                } else if (data.redirect) {
                    cancelWorkerCheck();
                    window.location.href = data.redirect;
                } else {
                    cancelWorkerCheck();
                    appendMessage('assistant', data.response);
                }
            },
            error: function () {
                setLoading(false);
                appendMessage('error', 'Request failed. Please try again.');
            },
        });
    }

    $form.on('submit', function (e) {
        e.preventDefault();

        var message = $input.val().trim();
        if (!message) return;

        appendMessage('user', message);
        $input.val('');
        setLoading(true);

        $.ajax({
            url: chatUrl,
            method: 'POST',
            dataType: 'json',
            data: { message: message },
            success: function (data) {
                if (data.error) {
                    setLoading(false);
                    appendMessage('error', data.error);
                    return;
                }
                pollJobStatus(data.job_id, 0);
            },
            error: function () {
                setLoading(false);
                appendMessage('error', 'Request failed. Please try again.');
            },
        });
    });

    $clear.on('click', function (e) {
        e.preventDefault();
        $.ajax({
            url: clearUrl,
            method: 'POST',
            dataType: 'json',
            success: function () {
                $messages.empty();
            },
        });
    });

    // Enter submits; Ctrl+Enter / Shift+Enter inserts a new line
    $input.on('keydown', function (e) {
        if (e.key === 'Enter' && !e.ctrlKey && !e.shiftKey && !e.metaKey) {
            e.preventDefault();
            $form.trigger('submit');
        }
    });
});
