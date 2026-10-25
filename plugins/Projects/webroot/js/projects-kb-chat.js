// Conversation with the AI about the knowledge base of a project (templates/Projects/view.php).
// Works like webroot/js/aiChat.js: a message is queued, the answer is polled.
$(function () {
    var $panel = $('#ProjectKbChat');
    if (!$panel.length) return;

    var urls = {
        chat: $panel.data('chat-url'),
        status: $panel.data('chat-status-url'),
        clear: $panel.data('clear-url'),
        undo: $panel.data('undo-url'),
        worker: $panel.data('worker-status-url'),
    };
    var texts = {
        undo: $panel.data('text-undo'),
        changed: $panel.data('text-changed'),
        error: $panel.data('text-error'),
        worker: $panel.data('text-worker'),
        version: String($panel.data('text-version')),
    };
    var csrfToken = $('meta[name="csrfToken"]').attr('content');
    var headers = { 'X-CSRF-Token': csrfToken };

    var $messages = $('#ProjectKbChatMessages');
    var $form = $('#ProjectKbChatForm');
    var $input = $('#ProjectKbChatInput');
    var $send = $('#ProjectKbChatSend');
    var $clear = $('#ProjectKbChatClear');

    var POLL_INTERVAL_MS = 1000;
    var MAX_POLLS = 240;

    function scrollDown() {
        $messages.scrollTop($messages[0].scrollHeight);
    }

    function addMessage(role, html, isHtml) {
        var $body = $('<div>').addClass('kb-chat-message__body');
        if (isHtml) {
            $body.html(html);
        } else {
            $body.text(html);
        }
        var $message = $('<div>').addClass('kb-chat-message kb-chat-message--' + role).append($body);
        $messages.append($message);
        scrollDown();

        return $message;
    }

    var $thinking = $('<div>').addClass('kb-chat-message kb-chat-message--assistant kb-chat-thinking').append(
        $('<div>').addClass('kb-chat-message__body').text('…')
    );

    function setLoading(loading) {
        $send.prop('disabled', loading);
        $input.prop('disabled', loading);
        if (loading) {
            $messages.append($thinking);
            scrollDown();
        } else {
            $thinking.detach();
            $input.focus();
        }
    }

    function pad(n) {
        return (n < 10 ? '0' : '') + n;
    }

    // Show the knowledge base the conversation (or its undo) left behind, without reloading the page.
    function showKnowledgeBase(html, version) {
        $('#ProjectKbContent').html(html);
        $('#ProjectKbVersion').text(texts.version.replace('{0}', version));
        var now = new Date();
        $('#ProjectKbModified').text(
            pad(now.getDate()) + '.' + pad(now.getMonth() + 1) + '.' + now.getFullYear() + ' ' +
            pad(now.getHours()) + ':' + pad(now.getMinutes())
        );
        $('#ProjectKbHeader').show();
    }

    function addChanges(data) {
        var $message = addMessage('changes', '', false);
        var $list = $('<ul>');
        $.each(data.changes, function (_, change) {
            $list.append($('<li>').text(change));
        });
        $message.find('.kb-chat-message__body')
            .append($('<strong>').text(texts.changed))
            .append($list);

        if (data.undo_revision_id) {
            var $undo = $('<a href="#">').addClass('btn-small').text(texts.undo);
            $undo.on('click', function (e) {
                e.preventDefault();
                $undo.addClass('disabled');
                $.ajax({
                    url: urls.undo,
                    method: 'POST',
                    dataType: 'json',
                    headers: headers,
                    data: { revision_id: data.undo_revision_id },
                    success: function (result) {
                        showKnowledgeBase(result.kb_html, result.kb_version);
                        $undo.remove();
                    },
                    error: function () {
                        $undo.removeClass('disabled');
                        addMessage('error', texts.error, false);
                    },
                });
            });
            $message.find('.kb-chat-message__body').append($undo);
        }
        scrollDown();
    }

    function poll(jobId, count) {
        if (count >= MAX_POLLS) {
            setLoading(false);
            addMessage('error', texts.error, false);
            return;
        }
        if (count === 3 && urls.worker) {
            $.getJSON(urls.worker, function (data) {
                if (data.running === false) {
                    addMessage('error', texts.worker, false);
                }
            });
        }

        $.ajax({
            url: urls.status,
            method: 'GET',
            dataType: 'json',
            headers: headers,
            data: { job_id: jobId },
            success: function (data) {
                if (data.status === 'pending') {
                    setTimeout(function () {
                        poll(jobId, count + 1);
                    }, POLL_INTERVAL_MS);
                    return;
                }

                setLoading(false);
                if (data.status === 'error') {
                    addMessage('error', data.error || texts.error, false);
                    return;
                }
                addMessage('assistant', data.response, true);
                if (data.changes && data.changes.length) {
                    addChanges(data);
                    showKnowledgeBase(data.kb_html, data.kb_version);
                }
            },
            error: function () {
                setLoading(false);
                addMessage('error', texts.error, false);
            },
        });
    }

    $form.on('submit', function (e) {
        e.preventDefault();

        var message = $input.val().trim();
        if (!message) return;

        addMessage('user', message, false);
        $input.val('');
        setLoading(true);

        $.ajax({
            url: urls.chat,
            method: 'POST',
            dataType: 'json',
            headers: headers,
            data: { message: message },
            success: function (data) {
                if (data.error) {
                    setLoading(false);
                    addMessage('error', data.error, false);
                    return;
                }
                poll(data.job_id, 0);
            },
            error: function () {
                setLoading(false);
                addMessage('error', texts.error, false);
            },
        });
    });

    $clear.on('click', function (e) {
        e.preventDefault();
        $.ajax({
            url: urls.clear,
            method: 'POST',
            dataType: 'json',
            headers: headers,
            success: function () {
                $messages.empty();
            },
        });
    });

    // Enter sends; Shift+Enter and Ctrl+Enter insert a new line.
    $input.on('keydown', function (e) {
        if (e.key === 'Enter' && !e.ctrlKey && !e.shiftKey && !e.metaKey) {
            e.preventDefault();
            $form.trigger('submit');
        }
    });
});
