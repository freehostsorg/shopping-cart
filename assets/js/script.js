$(document).ready(function() {

	// Debug mode
	var $debug		   = $('#debug'),
		$debug_options = $('#debug_options'),
		$debug_display = $debug_options.find('#debug_display');
		$debug_log 	   = $debug_options.find('#debug_log');

	$debug.change(function() {
		if ( $debug.is(':checked') ) {
			$debug.parent().hide().siblings('p').hide();
			$debug_options.slideDown();
			$debug_display.attr('checked', true);
			$debug_log.attr('checked', true);
		}
	});

	$('#debug_display, #debug_log').change(function(){
		if ( ! $debug_display.is(':checked') && ! $debug_log.is(':checked') ) {
			$debug_options.slideUp().siblings().slideDown();
			$debug.removeAttr('checked');
		}
	});

	/*--------------------------*/
	/*	Install folder
	/*--------------------------*/

	if ( typeof data.directory !='undefined' ) {
		$('#directory').val(data.directory);
	}

	/*--------------------------*/
	/*	Language
	/*--------------------------*/

	if ( typeof data.language !='undefined' ) {
		$('#language').val(data.language);
	}


	/*--------------------------*/
	/*	Plugins
	/*--------------------------*/

	if ( typeof data.plugins !='undefined' ) {
		$('#plugins').val( data.plugins.join(';') );
	}

/* ?? */

	var $response  = $('#response');

	$('#submit').click( function() {

		errors = false;

		// We hide errors div
		$('#errors').hide().html('<strong>Warning !</strong>');

		$('input.required').each(function(){
			if ( $.trim($(this).val()) == '' ) {
				errors = true;
				$(this).addClass('error');
				$(this).css("border", "1px solid #FF0000");
			} else {
				$(this).removeClass('error');
				$(this).css("border", "1px solid #DFDFDF");
			}
		});

		if ( ! errors ) {

			/*--------------------------*/
			/*	We verify the database connection and if WP already exists
			/*  If there is no errors we install
			/*--------------------------*/

			$.post(window.location.href + '?action=check_before_upload', $('form').serialize(), function(data) {

				errors = false;
				data = $.parseJSON(data);

				if ( data.wp == "error directory" ) {
					errors = true;
					$('#errors').show().append('<p style="margin-bottom:0px;">&bull; WordPress seems to be Already Installed.</p>');
				}

				if ( ! errors ) {
					$('form').fadeOut( 'fast', function() {

						$('.progress').show();

						// Fire Step
						// We dowload WordPress
						$response.html("<p>Downloading WordPress ...</p>");

						$.post(window.location.href + '?action=download_wp', $('form').serialize(), function() {
							unzip_wp();
						});
					});
				} else {
					// If there is an error
					$('html,body').animate( { scrollTop: $( 'html,body' ).offset().top } , 'slow' );
				}
			});

		} else {
			// If there is an error
			$('html,body').animate( { scrollTop: $( 'input.error:first' ).offset().top-20 } , 'slow' );
		}
		return false;
	});

	// Let's unzip WordPress
	function unzip_wp() {
		$response.html("<p>Decompressing Files...</p>" );
		$('.progress-bar').animate({width: "16.5%"});
		$.post(window.location.href + '?action=unzip_wp', $('form').serialize(), function(data) {
			wp_config();
		});
	}

	// Let's install plugins
	function wp_config() {
		$response.html("<p>Installing Plugins...</p>");
		$('.progress-bar').animate({width: "33%"});
		$.post(window.location.href + '?action=wp_config', $('form').serialize(), function(data) {
			install_plugins();
		});
	}

/*
	// CDatabase
	function install_wp() {
		$response.html("<p>Unzipping WordPress...</p>");
		$('.progress-bar').animate({width: "49.5%"});
		$.post(window.location.href + '/wp-admin/install.php?action=install_wp', $('form').serialize(), function(data) {
			install_plugins();
		});
	}
*/
	// Plugin
	function install_plugins() {
		$response.html("<p>Installing Plugins...</p>");
		$('.progress-bar').animate({width: "82.5%"});
		$.post(window.location.href + '?action=install_plugins', $('form').serialize(), function(data) {
			$response.html(data);
			success();
		});
	}

	// Remove the archive
	function success() {
		$response.html("<p>Successful installation completed</p>");
		$('.progress-bar').animate({width: "100%"});
		$response.hide();
		$('.progress').delay(500).hide();
		$.post(window.location.href + '?action=success',$('form').serialize(), function(data) {
			$('#success').show().append(data);
		});
		$.get( 'http://wp-quick-install.com/inc/incr-counter.php' );
	}

});