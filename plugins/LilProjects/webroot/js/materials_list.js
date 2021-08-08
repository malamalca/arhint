var editor = null;
jQuery.fn.MaterialsList = function(options)
{
	var default_options = {
		postUrl: "",
		reorderUrl: "",
		deleteUrl: "",
		cloneUrl: "",
		editUrl: "",
		newItemTemplate: "",
		addMaterialBarTemplate: '<div class="add-material-bar"><a href="#">Add Material</a></div>',
		checkFormulaUrl: "",
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
		modifiedMessage:	options.modifiedMessage,
		
		onShow: function() {
			$("tbody", $this).sortable("disable");
		},
		onHide: function() {
			$("tbody", $this).sortable("enable");
		},
		onUpdate: function(data, src_el) {
			$(".descript", src_el).html($this.nl2br(data.descript));
			$(".thickness", src_el).html(LilFloatFormat(parseFloat(data.thickness)));
			
			$this.calculateTotalThickness();
		},
		onAdd: function(data, src_el) {
			let rx_id = new RegExp("__id__", "ig");	

			let newRow = $(options.newItemTemplate.replace(rx_id, data.id));

			$(".descript", newRow).html($this.nl2br(data.descript));
			$(".thickness", newRow).html(LilFloatFormat(parseFloat(data.thickness)));

			$this.adjustItem(li);
			
			var nearestRow = $(src_el).closest("tr", $this);
			if ($(nearestRow).length) {
				$(nearestRow).after(newRow);
			} else {
				$($this).prepend(newRow);
			}
			$this.calculateTotalThickness();
		}
	});
	
	
	this.calculateTotalThickness = function() {
		let totalThickness = 0;
		$("td.thickness", this).each(function() {
			totalThickness += LilFloatStringToFloat($(this).html());
		});	
		
		$("th.total-thickness").html(LilFloatFormat(totalThickness, 2));
	}
	
	////////////////////////////////////////////////////////////////////////////////////////////////
	// adjust events on single row
	this.adjustItem = function(row)
	{
		// aply editor to <li>s and add links
		$("a.view-section-add-item", row).click(function() {
			var li = $(this).closest("li");
			if (editor.show(this, 
				{ Item: {
					sort_order: parseInt($("td.col-item-order span.handle", li).html()) + 1,
				}})) {
				$(this).hide();
			}
			return false;
		});
		
		$(row).click(function(e) {
			e.preventDefault();
			if (editor.show(row, $(row).attr("id").substr(3))) {
				$(row).hide();
			}
			return false;
		});

		// add item bar on hover
		$("td.descript").hover(function() {
			$("div.add-material-bar", this).show();
		}, function() {
			$("div.add-material-bar", this).hide();
		});
			
		// delete items
		$("td.actions", row).hover(function() { $("a", this).toggle(); });
		$("a.delete-material", row).click(function() { 
			$this.deleteItem(row);
			return false;
		}).hide();
		$("a.reorder-handle").hide();
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
				.replace(rx_position, position + 1);
			
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
				$("tbody", $this).sortable("cancel");
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
					$(row).remove();
					$this.calculateTotalThickness();
				}
			);
		}

		return false;
	}

	////////////////////////////////////////////////////////////////////////////////////////////////
	////////////////////////////////////////////////////////////////////////////////////////////////
	////////////////////////////////////////////////////////////////////////////////////////////////
	// apply li functionality to the first link
	$("a#additm1").click(function() {
		if (editor.show(this, { Item: { sort_order: 1 }})) {
			$(this).hide();
		}
		return false;
	});
	
	////////////////////////////////////////////////////////////////////////////////////////////////
	// apply li functionality to every item	
	$("tbody>tr", this).each(function() {
		$this.adjustItem(this);
	});
	
	////////////////////////////////////////////////////////////////////////////////////////////////
	// setup sortable for reordering items
	$("tbody", $this).sortable({
		cursor: "move",
		helper: "clone",
		handle: "a.reorder-handle",
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