// Deadlines of the knowledge base in the personal calendar (templates/Projects/view.php).
// The links are rendered empty by KbRenderer::render() and filled here, so that the HTML that comes back from the
// conversation (projects-kb-chat.js) works the same way.
$(function () {
    var $content = $('#ProjectKbContent');
    if (!$content.length || !$content.data('calendar-url')) return;

    var url = $content.data('calendar-url');
    var texts = {
        add: $content.data('text-add'),
        remove: $content.data('text-remove'),
        error: $content.data('text-error'),
    };
    var headers = { 'X-CSRF-Token': $('meta[name="csrfToken"]').attr('content') };

    function label($link) {
        $link.text(String($link.attr('data-linked')) === '1' ? texts.remove : texts.add);
    }

    function labelAll() {
        $content.find('a.kb-calendar').each(function () {
            label($(this));
        });
    }

    $content.on('click', 'a.kb-calendar', function (e) {
        e.preventDefault();
        var $link = $(this);
        if ($link.hasClass('disabled')) return;

        var linked = String($link.attr('data-linked')) === '1';
        $link.addClass('disabled');
        $.ajax({
            url: url,
            method: 'POST',
            dataType: 'json',
            headers: headers,
            data: { item_id: $link.attr('data-item'), remove: linked ? '1' : '0' },
            success: function (data) {
                $link.attr('data-linked', data.linked ? '1' : '0');
                label($link);
            },
            error: function (xhr) {
                var message = xhr.responseJSON && xhr.responseJSON.error ? xhr.responseJSON.error : texts.error;
                window.alert(message);
            },
            complete: function () {
                $link.removeClass('disabled');
            },
        });
    });

    labelAll();

    // The conversation replaces the content; fill the new links when it does.
    new MutationObserver(labelAll).observe($content[0], { childList: true });
});
