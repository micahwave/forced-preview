(function($){

	$.post(
		FORCED_PREVIEW_CONFIG.ajaxurl,
		{
			action: 'forced_preview',
			id: FORCED_PREVIEW_CONFIG.post_id,
			_ajax_nonce: FORCED_PREVIEW_CONFIG.nonce
		}
	);

})(jQuery)