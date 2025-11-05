/**
 * Recommendation Engine WP - Frontend JavaScript
 */

(function ($) {
	'use strict';

	const RecEngineWP = {
		init: function () {
			this.bindEvents();
		},

		bindEvents: function () {
			// Track product views
			if (typeof recengineWP !== 'undefined') {
				this.trackPageView();
			}
		},

		trackPageView: function () {
			// Track current page view for better recommendations
			const pageData = {
				url: window.location.href,
				title: document.title,
				type: document.body.className
			};

			// Send to analytics (if Umami is loaded)
			if (typeof umami !== 'undefined') {
				umami.track( 'pageview', pageData );
			}
		},

		loadRecommendations: function (container, options) {
			const defaults = {
				count: 4,
				context: {}
			};

			options = $.extend( {}, defaults, options );

			$.ajax(
				{
					url: recengineWP.ajaxUrl,
					type: 'POST',
					data: {
						action: 'recengine_get_recommendations',
						nonce: recengineWP.nonce,
						count: options.count,
						context: options.context
					},
					success: function (response) {
						if (response.success && response.data.recommendations) {
							RecEngineWP.renderRecommendations( container, response.data.recommendations );
						}
					},
					error: function (xhr, status, error) {
						console.error( 'RecEngine: Failed to load recommendations', error );
					}
				}
			);
		},

		renderRecommendations: function (container, recommendations) {
			// Simple rendering - can be customized
			let html = '<div class="recengine-dynamic-recommendations">';

			recommendations.forEach(
				function (rec) {
					html += '<div class="recengine-rec-item">';
					html += '<h4>' + rec.product_id + '</h4>';
					html += '<p>Score: ' + rec.score + '</p>';
					if (rec.reason) {
						html += '<p class="reason">' + rec.reason + '</p>';
					}
					html += '</div>';
				}
			);

			html += '</div>';

			$( container ).html( html );
		}
	};

	$( document ).ready(
		function () {
			RecEngineWP.init();
		}
	);

	// Expose to global scope
	window.RecEngineWP = RecEngineWP;

})( jQuery );
