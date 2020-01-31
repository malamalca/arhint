jQuery.fn.usersPicker = function(p_options) {

    var default_options = {
	};
	var $this       = this;
    var options     = [];

    this.toggleDepartment = function() {
        let departmentDiv = $(this).parent().parent();
        $("div.user :checkbox", departmentDiv)
            .prop("checked", $(this).prop("checked"));
    }

    this.toggleUser = function() {
        let departmentDiv = $(this).parent().parent().parent();
        if ($(this).prop("checked")) {
            let uncheckedCount = $('input.user:not(:checked)', departmentDiv).length;
            if (uncheckedCount == 0) {
                $(":checkbox.department", departmentDiv).prop("checked", true);
            }
        } else {
            $(":checkbox.department", departmentDiv).prop("checked", false);
        }
    }

    // initialization
    options = jQuery().extend(true, {}, default_options, p_options);

    // when set to off it is unchecked on history.back()
    // $(":checkbox", $this).attr("autocomplete", "off");

    $("input.department", $this).each(function() {
        $(this).on("click", $this.toggleDepartment);
    });

    $("input.user", $this).each(function() {
        $(this).on("click", $this.toggleUser);
    });
}

$(document).ready(function() {
    $(".users-picker").usersPicker();
});
