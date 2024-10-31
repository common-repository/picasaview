(function($){
	picasaView = function() {
		
		var self = this;
		
		/* poorly commented, I know :(
		*/
		this.numberOfImages = 0;
		this.startIndex = 1;
		this.imagesPerPage = 20;
		this.imagesPreLoaded = 0;
		this.daddy = null;
		this.id = null;

		this.initialize = function(id, count) {
		
			if ($('#picasaViewBlock-' + id + '-' + this.startIndex).length == 0) {
				return;
			}
			
			this.id = id;	
			this.daddy = 'picasaViewBlock-daddy-' + this.id;
			
			this.options = $.extend({
						nextPage: $('#' + this.daddy + ' .picasaViewNextPage'),
						previousPage: $('#' + this.daddy + ' .picasaViewPreviousPage'),
						showingResults: $('#' + this.daddy + ' .picasaViewShowingResults'),
						totalResults: $('#' + this.daddy + ' .picasaViewTotalResults')
					}, arguments[0] || { });
	
			this.imagesPerPage = $('#picasaViewBlock-' + id + '-' + this.startIndex + ' img').length;
			this.numberOfImages = this.options.totalResults[0].innerHTML;
	
			if(this.numberOfImages <= this.imagesPerPage) {
				this.options.nextPage.css('visibility', 'hidden');
			}
			
			this.options.previousPage.css('visibility', 'hidden');
	
			this.options.nextPage.find('a').click(this.nextPage);
			this.options.previousPage.find('a').click(this.previousPage);
	
			this.adjustBlockHeight();
		};
	
		this.nextPage = function(e) {
			
			self.adjustBlockHeight();
	
			$('#picasaViewBlock-' + self.id + '-' + self.startIndex).stop().css('opacity', 1).fadeOut(400, function(e) {
				self.startIndex += self.imagesPerPage;
				
				var endIndex = self.startIndex + self.imagesPerPage - 1;
				if(endIndex >= self.numberOfImages) {
					endIndex = self.numberOfImages;
					self.options.nextPage.css('visibility', 'hidden');
				}
		
				self.options.showingResults.html(self.startIndex + '-' + endIndex);
				self.options.previousPage.css('visibility', 'visible');
					
				// show images on next page
				$('#picasaViewBlock-' + self.id + '-' + self.startIndex).stop().fadeIn(400, self.adjustBlockHeight);
			});
	
			return false;
		};
	
		this.previousPage = function(e) {
			
			self.adjustBlockHeight();
			
			$('#picasaViewBlock-' + self.id + '-' + self.startIndex).stop().css('opacity', 1).fadeOut(400, function(e) {
				self.startIndex -= self.imagesPerPage;
				
				var endIndex = self.startIndex + self.imagesPerPage - 1;
				if(self.startIndex <= 1) {
					self.startIndex = 1;
					self.options.previousPage.css('visibility', 'hidden');
				}
		
				self.options.showingResults.html(self.startIndex + '-' + endIndex);
				self.options.nextPage.css('visibility', 'visible');
		
				// show images on previous page
				$('#picasaViewBlock-' + self.id + '-' + self.startIndex).stop().fadeIn(400, self.adjustBlockHeight);
			});
	
			return false;
		};
	
		this.adjustBlockHeight = function() {
			// adjust size of parent container to avoid flickering during fade/appear effects
			var inner = $('#picasaViewBlock-' + this.id + '-' + this.startIndex);
			
			$('#' + this.daddy).find('.picasaViewBlock-son').css('min-height', inner.height() + 'px');
			
			
		};
	};
})(jQuery);


jQuery(document).ready(function($){ 
	
	$('.picasaViewBlock-daddy').each(function(i) {
		var p = new picasaView();
		p.initialize(this.id.replace('picasaViewBlock-daddy-', ''), i);
	});

});