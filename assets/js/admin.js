
var dropArgs = {
		accept: "div.applet",
		drop: function(event, ui) {
			var clone = ui.draggable.clone();
			var index = jQuery('span.index').last().text();
			clone.attr('id', clone.attr('id') + '-' + index);
			jQuery(this).parents('.dropzone').find('input.drop').val(clone.attr('id'));
			jQuery(this).append(clone);
			jQuery(this).droppable('destroy');
			var id = jQuery(ui.draggable).attr('id');
			var post = jQuery('#post_ID').val();
			retrieve_applet_content(id, post, index);
		}
	};

jQuery(function($) {
	
	console.log('WP VBX Scripts Loaded');
	
	var mediaUploader;
	
	$('.ui-draggable .applet').draggable({
		helper: 'clone',
		revert: "invalid"
	});
	$('.ui-droppable').droppable(dropArgs);
	
	$('.pickup').click(function(e) {
		e.preventDefault();
		var sid = $(this).attr('data-id');
		var uid = $(this).attr('data-user-id');
		$.post(site_url, {
			action: 'connect_call',
			sid: sid,
			uid: uid
		}, function(data) {
			console.log(data);
			alert('Call connected. Your phone should ring.');			
		});
	});
	
	$('body').on('click', '.applet a.remove-button', function(event) {
		event.preventDefault();
		var zone = $(this).parents('.dropzone');
		zone.find('.drop').val('');
		var id = "#applet-" + $(this).parents('.applet').attr('id');
		remove_flow_cascading(id);
		$(this).parents('.flow').droppable(dropArgs);
		//$(this).parents('.flow').removeClass('set');
		$(this).parents('.flow').empty();
	});
	
	$('body').on('click', '.flow .applet', function(event) {
		event.preventDefault();
		//$(this).parents('.postbox').find('.toggle-indicator').trigger('click');
		var id = "#applet-" + $(this).attr('id');
		$(id).slideToggle();
	});
	
	$('.vbx-search').click(function(event) {
		event.preventDefault( );
		$(this).prop('disabled', true);
		$(this).text('Searching...');
		$.post(site_url, {
			action: 'search_numbers',
			search: $('input[name="vbx_search"]').val(),
			state: $('select[name="vbx_state"]').val()
		}, function(data) {
			$('.search-results').show().html(data);
			$('.vbx-search').text('Search');
			$('.vbx-search').prop('disabled', false);
		});
		
	});
	
	$('body').on('click', '.media-picker .select-media', function(event) {
		event.preventDefault();
		
		var thisParent = $(this).parents('.media-picker');
		
		if (mediaUploader) {
			mediaUploader = null;
		}
		
		mediaUploader = wp.media.frames.file_frame = wp.media({
			title: $(this).text(),
			button: {
				text: $(this).text()
			}, 
			multiple: false,
			library: {
				type: $(this).attr('data-type')
			}
		});
		
		mediaUploader.on('select', function() {
	    	var attachment = mediaUploader.state().get('selection').first().toJSON();
			$(thisParent).find('.media-url').val(attachment.url);
			$(thisParent).find('.media-filename').text(attachment.filename);
			$(thisParent).find('.media-display').attr('src', attachment.url);
			$(thisParent).find('.media-mime').val(attachment.type);
			$(thisParent).attr('class','').addClass('media-picker').addClass(attachment.type);
			$(thisParent).find('.media-display').load();
	    });
	    // Open the uploader dialog
	    mediaUploader.open();
		
	});
	
	$('body').on('click', '.group-container .handle', function(event) {
		event.preventDefault();
		$(this).parents('.group-container').find('.collapse').slideToggle();
	});
	
	$('body').on('click', '.menu_option_controls a.remove', function(event) {
		event.preventDefault();
		$(this).parents('label').remove();
	});
	
	$('body').on('click', '.menu_option_controls a.add', function(event) {
		event.preventDefault();
		var index = $('.vbx-menu-options label').last().attr('data-index');
		index++;
		var flowindex = jQuery(this).parents('.applet-content').find('span.index').text();
		flowindex--;
		$('.vbx-menu-options label').last().after('<label data-index="'+index+'">Keys: <input name="flow['+parseInt(flowindex)+'][menu_options]['+index+']" value="" type="text"><div class="dropzone" data-index="'+parseInt(flowindex)+'"><input class="drop" type="hidden" name="flow['+parseInt(flowindex)+'][menu_option_'+index+']" value=""><div class="flow ui-droppable" style=""></div><div class="backdrop"><p>Drop Applet Here</p></div></div><div class="menu_option_controls"><a href="#" class="remove"><span class="dashicons dashicons-minus"></span></a><a href="#" class="add"><span class="dashicons dashicons-plus"></span></a></div></label>');
		$('.vbx-menu-options label[data-index="'+index+'"]').find('.flow').droppable(dropArgs);
	});

});

function update_conversation() {
	
	var postid = jQuery('#post_ID').val();
	jQuery.post(site_url, {
		action: 'message_thread',
		postid: postid
	}, function(data) {
		
		jQuery(data).find('img').on('load', function() {
			jQuery('#conversation .inside .message-thread').html(data);
		});		
		
	});
	
}

function remove_flow_cascading(id) {
	
	if (jQuery(id).find('.drop').length) {
		jQuery(id).find('.drop').each(function() {
			var childid = '#applet-' + jQuery(this).val();
			console.log(childid);
			remove_flow_cascading(childid);
		});
	}
	
	jQuery(id).remove();
	
}

function retrieve_applet_content(id, post, index = 0) {
	console.log(post);
	jQuery.post(site_url, {
		action: 'get_applet_ui',
		data: {
			wp_applet_id: id,
			wp_index: index,
			wp_post: post
		}
	}, function(data) {
		data = jQuery(data);
		data.find('.dropzone .flow').droppable(dropArgs);
		data.addClass('step-hidden');
		jQuery('#advanced-sortables').append(data);
	});
	
}