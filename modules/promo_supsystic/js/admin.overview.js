jQuery(document).ready(function(){
	jQuery('#form-settings').submit(function(){
		jQuery(this).sendFormBup({
			btn: jQuery(this).find('.button-primary')
		,	msgElID: 'formSettingsMsg'
		,	onSuccess: function(res) {
				if(!res.error) {
					jQuery('#form-settings').slideUp();
					jQuery('#form-settings-send-msg').slideDown();
				}
			}
		});
		return false;
	});
	jQuery('.supsystic-overview-news-content').slimScroll({
		height: '500px'
	,	railVisible: true
	,	alwaysVisible: true
	,	allowPageScroll: true
	});
	jQuery('.faq-title').click(function(){
		var descBlock = jQuery(this).find('.description:first');
		if(descBlock.is(':visible')) {
			descBlock.slideUp( g_bupAnimationSpeed );
		} else {
			jQuery('.faq-title .description').slideUp( g_bupAnimationSpeed );
			descBlock.slideDown( g_bupAnimationSpeed );
		}
	});
});