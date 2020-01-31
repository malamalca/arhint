$(document).ready(function() {
    // dropdown users with filter line
    var searchInited = false;

    $('#filter-user').dropdown({
        closeOnClick: false,
        onOpenStart: function(e) {
            $("#dropdown-users li:hidden").prop("tabIndex", 1).show();

            if (!searchInited) {
                var li = $("<li><input id='searchbox' /></li>");

                $("input", li)
                    .on("keydown", function(e) {
                        // prevent searches but allow esc and down arrow
                        if (e.which != M.keys.ESC && e.which != M.keys.ARROW_DOWN) {
                            e.stopPropagation();
                        }
                    })
                    .on("keyup", function(e) {
                        $("#dropdown-users li:hidden").prop("tabIndex", 1).show();

                        var queryString = $(this).val();
                        if (queryString.length > 0) {
                            var searchRx = new RegExp(queryString, "i");
                            $("#dropdown-users li:gt(0)").each(function() {
                                if (!$("a", this).hasClass("department") && ($("a", this).html().search(searchRx) < 0)) {
                                    $(this).prop("tabIndex", -1).hide()
                                }
                            });
                        }
                    });

                $('#dropdown-users li:eq(0)').before(li);

                $('#dropdown-users')
                    .on('scroll', function() { $(li).css({'top': $('#dropdown-users').scrollTop()}); });
                $(li).css({'position': 'absolute', 'top': 0, 'background-color:': '#ff0000'});
            }

            searchInited = true;
        },
        onOpenEnd: function(e) {
            $('#dropdown-users li a.department').on("click", function(e) {
                e.preventDefault();
                return false;
            });

            $('#dropdown-users li:eq(0)>input').val("").focus();
        }
    });
});
