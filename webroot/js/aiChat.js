$(function () {
    var $panel = $('#DashboardAIAssistant');
    if (!$panel.length) return;

    var chatUrl  = $panel.data('chat-url');
    var clearUrl = $panel.data('clear-url');
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

    var $thinking = $('<div>').addClass('ai-message ai-message--assistant ai-message--thinking').append(
        $('<span>').addClass('ai-message__role').text('AI'),
        $('<div>').addClass('ai-message__body ai-thinking').append(
            $('<span>'), $('<span>'), $('<span>')
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
                    appendMessage('error', data.error);
                } else if (data.redirect) {
                    window.location.href = data.redirect;
                    return;
                } else {
                    appendMessage('assistant', data.response);
                }
            },
            error: function () {
                appendMessage('error', 'Request failed. Please try again.');
            },
            complete: function () {
                setLoading(false);
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
