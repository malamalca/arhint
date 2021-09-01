MaterialEditor = function(p_options, p_anchor, p_data)
{
	var default_options = {
		original: null,
		data: {
			id:"", composite_id:"", sort_order:1, descript:"", thickness:0,
		},

		postUrl: "",
		modifiedMessage: "Material has been modified. Exit without saving changes?",

		onShow : function() {},
		onHide : function() {},
		onAdd : function() {},
		onUpdate: function() {}
	};

	var $this = this;
	var modified = false;
	var editor = null;
	var anchorRow = null;
	var options;

	var	MaterialId;
	var MaterialCompositeId;
	var	MaterialDescript;
	var	MaterialSortOrder;
	var	MaterialThickness;

	this.updateModified = function() {
		if (!anchorRow || (typeof options.original == "undefined") || !options.original) {
			modified = false;
			return;
		}

		modified =
			($(MaterialDescript).val() != options.original.descript) ||
			($(MaterialThickness).val() != options.original.thickness);
	}
	this.removeEditor = function() {
		if (options.anchorRow && modified && !confirm(options.modifiedMessage)) {
			return false;
		}

		if (!$(options.anchorRow).hasClass("add-material-row")) {
			$(options.anchorRow).show();
		}

		$(editor).remove();

		// on hide callback
		options.onHide.apply(editor);

		// remove esc handler
		$(document).off('keyup.item-editor');

		//options.anchorRow = null;
		return true;
	}
	this.sendData = function(form) {
		$("div.submit button", editor).attr("disabled", true);

		$.ajax({
			url: $("form", $(editor)).attr("action"),
			data: $("form", $(editor)).serialize(),
			type: "post",

			dataType: "json",
			success: function(data) {
				if ((typeof data.result == 'undefined') || !data.result) {
					// error
					for (var model in data.errors) {
						for (var field in data.errors[model]) {
							// check if error is from base class (Item)
							if (data.errors[model][field] instanceof Array) {
								$("[name='data["+model+"]["+field+"]']", editor).addClass("validation-error");
							} if (data.errors[model][field] instanceof Object) {
								for (var field2 in data.errors[model][field]) {
									$("[name='data["+model+"]["+field+"]["+field2+"]']", editor).addClass("validation-error");
								}
							} else {
								$("[name='data[Item]["+model+"]']", editor).addClass("validation-error");
							}
						}
					}
				} else {
					modified = false;
					$this.removeEditor();
					if (options.data.id) {
						options.onUpdate.apply(editor, [data.result, options.anchorRow]);
					} else {
						options.onAdd.apply(editor, [data.result, options.anchorRow]);
					}
				}
				return false;
			}
		});
		$("div.submit button", editor).attr("disabled", false);
		return false;
	}
	this.show = function(p_anchor, materialId) {
		this.updateModified();
		if (this.removeEditor()) {
			options.anchorRow = p_anchor;
			let rx_id = new RegExp("__id__", "ig");
			let rx_order = new RegExp("__order__", "ig");

			let targetUrl = null;
			if (materialId) {
				targetUrl = options.editUrl.replace(rx_id, materialId);
			} else {
				targetUrl = options.addUrl.replace(rx_order, $(p_anchor).index());
			}

			$.ajax({
				url: targetUrl,
				success: function(data) {
					editor = $(data).insertAfter(options.anchorRow).show();

					options.data.id = $("input#id", editor).val();
					options.data.composite_id = $("input#composite_id", editor).val();
					options.data.descript = $("input#descript", editor).val();
					options.data.thickness = $("input#thickness", editor).val();
					options.data.sort_order = $("input#sort_order", editor).val();

					options.original = options.data;
					modified = false;

					$("div.submit button", editor).on("click", function() { return $this.sendData($this); });
					$("button.cancel", editor).on("click", function() { $this.updateModified(); return $this.removeEditor(); });
					options.onShow.apply(editor);

					// cancel editing with esc
					$(document).on('keyup.item-editor', function(e) {
						if ((e.keyCode == 27)) {
							e.preventDefault();
							$this.updateModified();
							return $this.removeEditor();
						}
					});
					$("input#descript", $(editor)).focus();

					return true;
				},
				error: function() {
					return false;
				}
			});

		}
		return true;
	}

	// initialization
	options = jQuery().extend(true, {}, default_options, p_options);

	//$(MaterialDescript).autogrow();
}
