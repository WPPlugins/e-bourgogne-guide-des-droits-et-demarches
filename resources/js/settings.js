jQuery(document).ready(function() {

	var $gdd_list = jQuery('#ebou-gdd-list'),
		$flux_select = jQuery('#ebou-gdd-flux'),
		$spinner = jQuery('#ebou-gdd-spinner'),
		$root_input = jQuery('#ebou-gdd-root'),
		$no_title_error = jQuery('#no-title-error'),
		$no_root_error = jQuery('#no-root-error'),
		$gdd_title = jQuery('#ebou-gdd-title'),
		$gdd_css = jQuery('#ebou-gdd-css'),
		$post_id = jQuery('#post_id'),
		$deleted_post_id = jQuery('#deleted_post_id'),
		$parent_root_list = jQuery('#ebou-gdd-parentroot-list'),
		prev_flux_id,
		saved_structure = {},
		saved_content = {},
		parents = {},
		display_dfd = jQuery.Deferred();

	function get_gdd_list(flux_id, root_id) {
		var url = api_base_url
				+ "?root="
				+ root_id
				+ "&flux="
				+ flux_id
				+ "&organismId="
				+ organism_id;

		var $spinner_clone = $spinner.clone();

		jQuery('li.ebou-gdd-item[data-id="' + root_id + '"]').append($spinner_clone);
		$spinner_clone.show();

		// Retrieving GDD lists
		jQuery.ajax({
			url: url,
			type: 'GET',
			beforeSend: function(xhrObj){
				xhrObj.setRequestHeader('ebou-api-key', user_api_key);
			}
		}).success(function(data) {
			saved_structure[flux_id][root_id] = data; // save data so that we only have to download it once
			display_gdd_list(root_id, saved_structure[flux_id][root_id]);
		}).fail(function() {
			// TODO: handle error
			return null;
		}).done(function() {
			$spinner_clone.remove();
		});
	}

	function display_gdd_list(root_id, datas) {
		jQuery('li.ebou-gdd-item[data-id="' + root_id + '"]').append("<ul>\n</ul>");

		var $ul = jQuery('li.ebou-gdd-item[data-id="' + root_id + '"] > ul'),
			html = "",
			data;

		// For each item, we append it to the list and register its callback for item toggling (this way each item has only one callback)
		datas.forEach(function(elt, index, array) { 
			html = "<li class=\"ebou-gdd-item\" data-id=\"" + elt['id'] + "\"" 
				+ ((elt['hasChildren']) ? " data-haschildren=\"true\"" : "")
				+ "><span"
				+ ((elt['hasChildren']) ? " class=\"bullet expand\">" : ">") 
				+ "</span>"
				+ "<span class=\"text\">"
				+ elt['text']
				+ "</span>" + "</li>\n";

			$ul.append(html);
			register_list_item_click_evts(
				jQuery('li.ebou-gdd-item[data-id="' + elt['id'] + '"]')
			);
		});

		display_dfd.resolve();
	}

	function register_list_item_click_evts(item) {
		var $item = jQuery(item),
			$bullet = $item.children('span.bullet'),
			$text = $item.children('span.text');

		$bullet.on('click', toggle_item);
		$text.on('click', toggle_selected);
	}

	function toggle_item() {
		var $item = jQuery(this);

		if($item.hasClass('expand')) {
			expand_item($item);
		} else if($item.hasClass('collapse')) {
			collapse_item($item);
		}
	}

	function toggle_selected() {
		var $item = jQuery(this).parent('li'),
			selected_item = jQuery('li.ebou-gdd-item.active');
		
		if(selected_item != undefined && selected_item.attr('data-id') != $item.attr('data-id')) { // another item is already selected
			selected_item.toggleClass('active');
		} 
		$item.toggleClass('active');

		var new_selected_item = jQuery('li.ebou-gdd-item.active');
		$root_input.val((new_selected_item != undefined) ? new_selected_item.attr('data-id') : ""); // updating the root_input value, using the new selected_item value
	}

	function expand_item($item) {
		var	flux_id = jQuery('#ebou-gdd-flux option:selected').val(),
			gdd_id = $item.parent('li').attr('data-id');

		$item.removeClass('expand').addClass('collapse');

		if(saved_structure[flux_id] == undefined) {
			saved_structure[flux_id] = {};
		}

		// If the datas have been saved, uses the save, otherwise download them
		if(saved_structure[flux_id][gdd_id] != undefined) {
			display_gdd_list(gdd_id, saved_structure[flux_id][gdd_id]);
		} else {
			get_gdd_list(flux_id, gdd_id);
		}
	}

	function expand_multiple_items(parent_root_list, index, final_root) {
		display_dfd.done(function() { // waiting for the last expand to be over (can take time when retrieving data from the API)
			display_dfd = jQuery.Deferred(); // reinitializing the deferred
			expand_item(jQuery('li.ebou-gdd-item[data-id="' + parent_root_list[index] + '"] > span.bullet')); // expand the item

			index++;
			if(index < parent_root_list.length) {
				expand_multiple_items(parent_root_list, index, final_root); // launch next item expanding if it exists
			} else {
				display_dfd.done(function() { // wait for the last expand and then select the final root
					display_dfd = jQuery.Deferred();
					jQuery('li.ebou-gdd-item[data-id="' + final_root + '"]').addClass('active');
				});
			}
		});
	}

	function collapse_item($item) {
		$item.siblings('ul').remove();
		$item.removeClass('collapse').addClass('expand');
	}

	function fill_parent_root_list_input(root) {
		var parent_root_list = [];

		for(var parent_root = jQuery('li.ebou-gdd-item[data-id="' + root + '"]').parent('ul').parent('li') ; 
				!parent_root.attr('data-id').includes("root_") ;
				parent_root = parent_root.parent('ul').parent('li')) {

			parent_root_list.unshift(parent_root.attr('data-id'));
		}

		$parent_root_list.val(parent_root_list);
	}

	function fill_gdd_form(post_id) {
		var gdd = gdd_settings[post_id],
			root_parent_list = gdd.parent_root_list.split(',');
		jQuery('#delete-directory').prop('disabled', false);

		$post_id.val(gdd.id);
		$deleted_post_id.val(gdd.id);
		$gdd_title.val(gdd.title);
		$gdd_css.val(gdd.css);
		$flux_select.val(gdd.flux).change();

		expand_item(jQuery('li.ebou-gdd-item[data-id="root_' + gdd.flux + '"] > span.bullet'));

		if(root_parent_list.length > 0) {
			expand_multiple_items(root_parent_list, 0, gdd.root); // recursive method to fill the list
		}

		$root_input.val(gdd.root);
	}

	function empty_gdd_form() {
		jQuery('#delete-directory').prop('disabled', true);
		$post_id.val("");
		$deleted_post_id.val("");
		$gdd_title.val("");
		$gdd_css.val("");
		$flux_select.val("empty");
		$gdd_list.empty();
	}

	$flux_select.on('change', function() {
		var flux_id = this.value;

		if(flux_id != "empty") {

			if(prev_flux_id != "empty") {
				saved_content[prev_flux_id] = $gdd_list.html(); // save current tree only if not empty flux type
			}

			$gdd_list.empty();

			if(saved_content[flux_id]) {
				$gdd_list.append(saved_content[flux_id]); // add saved tree
			} else {
				$gdd_list.append(
					"<ul>\n\t<li class=\"ebou-gdd-item\" data-id=\"root_" + flux_id + "\" data-haschildren=\"true\">"
					+ "<span class=\"bullet expand\"></span>&nbsp;"
					+ "<span class=\"text\">" + flux_type[flux_id] + "</span>"
					+ "</li>\n</ul>\n"
				); // append new root type
			}
			register_list_item_click_evts(
				('li.ebou-gdd-item')
			); // register callback for each item toggling and selection
		} else { // if flux type selected is 'empty', we juste empty the tree
			$gdd_list.empty();
		}

		prev_flux_id = flux_id;
	});

	jQuery('#create-gdd').on('click', function(evt) {
		$no_title_error.hide();
		$no_root_error.hide();

		jQuery('#delete-gdd').addClass('disabled');

		jQuery('#existing-gdd option:selected').prop('selected', false);
		empty_gdd_form();
	});

	jQuery('#existing-gdd').on('change', function() {
		$no_title_error.hide();
		$no_root_error.hide();

		jQuery('#delete-gdd').removeClass('disabled');
		empty_gdd_form();
		fill_gdd_form(this.value);
	});

	jQuery('#delete-gdd').on('click', function(evt) {
		evt.preventDefault();
		if(window.confirm(confirm_delete_msg)) {
			jQuery('#delete-gdd-form').submit();
		}
	});

	jQuery('#save-gdd-form').submit(function() {
		$no_title_error.hide();
		$no_root_error.hide();

		var gdd_title = $gdd_title.val(),
			root_input = $root_input.val(),
			has_error = false;

		if(gdd_title == "") {
			$no_title_error.show();
			has_error = true;
		}

		if(root_input == "") {
			$no_root_error.show();
			has_error = true;
		}

		if(!has_error) {
			fill_parent_root_list_input(root_input);
			return true;
		}

		return false;
	});
});