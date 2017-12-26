jQuery(document).ready(function($) {
	$('button[name="cct_get_coinlist"],button[name="cct_parse_coinlist"]').on('click', function(e){
		e.preventDefault();

		$.ajax({
			type: 'post',
			dataType: 'json',
			async: true,
			url: ccTickerJs.ajax_url,
			data: {
				'action': $(this).attr('name')
			}
		}).done( function(response) {
			alert( response.message );
		}).fail( function(response) {
			alert( response.message );
		});

	});
});