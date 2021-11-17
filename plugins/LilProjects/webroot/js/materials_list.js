var editor = null;
jQuery.fn.MaterialsList = function(options)
{
	var default_options = {
		postUrl: "",
		reorderUrl: "",
		deleteUrl: "",
		cloneUrl: "",
		editUrl: "",
		addUrl: "",
		newItemTemplate: "",
		checkFormulaUrl: "",
        addFromLibraryUrl: "",
		addTplItemDialogCaption: "Add item from template",
		modifiedMessage: "Item has been modified. Exit without saving changes?",
		confirmDeleteMessage: "Are you sure you want to delete this item?",
	};
	var options = jQuery().extend({}, default_options, options);

	var $this = this;

	var item_drag_mode = 'default'; // or "clone"
	var item_start_pos = null; // this is base position of an item before reorder

	this.strip_tags = function(html) {
		let tmp = document.createElement("DIV");
		tmp.innerHTML = html;
		return tmp.textContent || tmp.innerText;
	}
	this.nl2br = function(str, is_xhtml) {
		let breakTag = (is_xhtml || typeof is_xhtml === 'undefined') ? '<br />' : '<br>';
		return (str + '').replace(/([^>\r\n]?)(\r\n|\n\r|\r|\n)/g, '$1'+ breakTag +'$2');
	}

	////////////////////////////////////////////////////////////////////////////////////////////////
	// init editor
	editor = new MaterialEditor({
		element:			"#view-section-edit-form",
		postUrl:			options.postUrl,
		editUrl:			options.editUrl,
		addUrl:				options.addUrl,
		modifiedMessage:	options.modifiedMessage,

		onShow: function() {
			$($this).sortable("disable");
		},
		onHide: function() {
			$($this).sortable("enable");
		},
		onUpdate: function(data, src_el) {
			$("div.descript", src_el).html($this.nl2br(data.descript));
			$("div.thickness", src_el).html(LilFloatFormat(parseFloat(data.thickness), 1));

			$this.calculateTotalThickness();
		},
		onAdd: function(data, src_el) {
			let rx_id = new RegExp("__id__", "ig");
			let rx_descript = new RegExp("__descript__", "ig");
			let rx_thickness = new RegExp("__thickness__", "ig");
            let rx_unit = new RegExp("__unit__", "ig");
            let rx_class = new RegExp("__class__", "ig");

			let newRow = $(options.newItemTemplate
                .replace(rx_id, data.id)
                .replace(rx_descript, "")
                .replace(rx_thickness, "")
                .replace(rx_unit, "")
                .replace(rx_class, ""));

			$("div.descript", newRow).html($this.nl2br(data.descript));

            if (data.is_group) {
                $(newRow).addClass("material-group");
            } else {
    			$("div.thickness", newRow).html(LilFloatFormat(parseFloat(data.thickness), 1));
                $("div.unit", newRow).html("cm");
            }

			$this.adjustItem(newRow);

			if (src_el) {
				$(src_el).after(newRow);
			} else {
				$($this).prepend(newRow);
			}
			$this.calculateTotalThickness();
		}
	});


	this.calculateTotalThickness = function() {
		let totalThickness = 0;
		$("div.thickness", this).each(function() {
			totalThickness += LilFloatStringToFloat($(this).html());
		});

		$("div#total-thickness").html(LilFloatFormat(totalThickness, 1));
	}

	////////////////////////////////////////////////////////////////////////////////////////////////
	// adjust events on single row
	this.adjustItem = function(row)
	{
		$("div.descript", row).click(function(e) {
			e.preventDefault();
			if (editor.show(row, $(row).attr("id").substr(3))) {
				$(row).hide();
			}
			return false;
		});

        // initialization
        $("a", $(row)).hide();
		$("div.add-material-bar", $(row)).hide();

		// actions on the left side of grid
		$(row).mouseover(function() {
			$("a", this).show();
			$("div.add-material-bar", this).show();
		});
        $(row).mouseout(function() {
			$("a", this).hide();
			$("div.add-material-bar", this).hide();
		});

		$("a.delete-material", row).click(function(e) {
			$this.deleteItem(row);
			return false;
		}).hide();
		$("a.reorder-handle", row).hide();

		$("button.add-material", row).click(function(e) {
			e.preventDefault();
			editor.show(row, null, false);
            $("div.add-material-bar", $(row)).hide();
		});

        $("button.add-group", row).click(function(e) {
			e.preventDefault();
			editor.show(row, null, true);
            $("div.add-material-bar", $(row)).hide();
		});

        $("button.add-lookup", row).modalPopup({
            "title": "Add Material From Library",
            "url": options.addFromLibraryUrl
        });
	}

	this.handleItemDragCtrlDown = function(event)
	{
		if (event.keyCode == 17 && event.ctrlKey) {
			if (item_drag_mode != "clone") {
				$(event.data.ui.item).after($(event.data.ui.item).clone().addClass("drag-copy").show());
			}

			item_drag_mode = "clone";

		}
	}

	this.handleItemDragCtrlUp = function(event)
	{
		if (event.keyCode == 17 && !event.ctrlKey) {
			if (item_drag_mode == "clone") {
				$(event.data.ui.item).next().remove();
			}
			item_drag_mode = 'default';
		}
	}

	// reorder item main function
	this.reorderItems = function(item)
	{
		if (item_start_pos != item.index()) {
			var position = item.index();
			var item_id = $(item).attr("id").substr(3);


			var rx_item = new RegExp("__id__", "i");
			var rx_position = new RegExp("__position__", "i");

			var targetUrl = options.reorderUrl
				.replace(rx_item, item_id)
				.replace(rx_position, position);

			// send new position
			$.get(targetUrl, function(data) {
				// update items below
				var i = position;
				var j = item_start_pos;
				if (item_start_pos < i) {
					j = i;
					i = item_start_pos;
				}
				$("span.handle", $this).slice(i, j+1).each(function() {
					$(this).html(i+1);
					i++;
				});
			}).error(function() {
				$($this).sortable("cancel");
			});
		}
	}

	// clone item main function
	this.cloneItem = function(item)
	{
		var position = item.index();
		var item_id = $(item).attr("id").substr(3);
		var rx_item = new RegExp("(\\%5B){2}item_id(\\%5D){2}", "i");
		var rx_position = new RegExp("(\\%5B){2}position(\\%5D){2}", "i");

		var targetUrl = options.cloneUrl
			.replace(rx_item, item_id)
			.replace(rx_position, position + 1);

			console.log(targetUrl);

		$("li", $this).eq(item_start_pos).removeClass("drag-copy");

		$.get(targetUrl, function(data) {
			// update items below
			var i = position;
			$("td.col-item-order span.handle", $this).slice(i).each(function() {
				$(this).html(i+1);
				i++;
			});

			$this.adjustItem(this);
			$this.calculateTotalThickness();
		}).error(function() {
			$this.sortable("cancel");
			$("li", $this).eq(item_start_pos).remove();
			item_drag_mode = "default"; // reset
		});
	}

	// delete item main function
	this.deleteItem = function(row)
	{
		if (confirm(options.confirmDeleteMessage)) {
			let item_id = $(row).attr("id").substr(3);
			let rx_item = new RegExp("__id__", "i");

			let targetUrl = options.deleteUrl
				.replace(rx_item, item_id);

			$.get(
				targetUrl,
				function(data) {
					// remove material line
					$(row).remove();
					$this.calculateTotalThickness();
				}
			);
		}

		return false;
	}

	////////////////////////////////////////////////////////////////////////////////////////////////
	// apply li functionality to every item
	$("li.composite-material-row", this).each(function() {
		$this.adjustItem(this);
	});

	////////////////////////////////////////////////////////////////////////////////////////////////
	// setup sortable for reordering items
	$($this).sortable({
		cursor: "move",
		helper: "clone",
		handle: "a.reorder-handle",
        items: 'li:not(:last-child)',
		start: function(event, ui) {
			$(document).on("keydown", { ui: ui }, $this.handleItemDragCtrlDown);
			$(document).on("keyup", { ui: ui }, $this.handleItemDragCtrlUp);
			item_start_pos = ui.item.index();
		},
		stop: function(event, ui) {
			if (item_drag_mode == "clone") {
				$this.cloneItem(ui.item);
			} else {
				$this.reorderItems(ui.item);
			}

			$(document).off("keydown", $this.handleItemDragCtrlDown);
			$(document).off("keyup", $this.handleItemDragCtrlUp);
		},

	});
}
