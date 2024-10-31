(function($){
	picasaViewAdmin = function() {
		
		var croppableSizes = [32, 48, 64, 72, 104, 144, 150, 160];
		var self = this;
	
		this.initialize = function() {
			$('#picasaView_thumbnailSize').bind('change blur', self.checkThumbnailCropping);
			this.checkThumbnailCropping();
		};
	
		this.checkThumbnailCropping = function() {
			if ($.inArray(parseInt($('#picasaView_thumbnailSize').val()), croppableSizes) == -1) {
				$('#picasaView_cropThumbnails')[0].checked = false;
				$('#picasaView_cropThumbnails')[0].disabled = true;
			} else {
				$('#picasaView_cropThumbnails')[0].disabled = false;
			}
		};
	};
})(jQuery);

jQuery(document).ready(function($){
	if ($('#picasaView_thumbnailSize').length > 0) {
		var p = new picasaViewAdmin();
		p.initialize();
	}
});