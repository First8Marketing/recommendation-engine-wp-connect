/**
 * First8 Marketing Recommendation Engine - Popup Scripts
 */

(function($) {
    'use strict';

    $(document).ready(function() {
        // Show popups with delay
        $('.recengine-popup').each(function() {
            var $popup = $(this);
            var delay = parseInt($popup.data('delay')) || 0;

            setTimeout(function() {
                $popup.fadeIn(300);
            }, delay * 1000);
        });

        // Close popup on close button click
        $('.recengine-popup-close').on('click', function(e) {
            e.preventDefault();
            $(this).closest('.recengine-popup').fadeOut(300);
        });

        // Close popup on overlay click
        $('.recengine-popup-overlay').on('click', function() {
            $(this).closest('.recengine-popup').fadeOut(300);
        });

        // Close popup on ESC key
        $(document).on('keydown', function(e) {
            if (e.keyCode === 27) {
                $('.recengine-popup:visible').fadeOut(300);
            }
        });

        // Trigger popup on click
        $('.recengine-popup-trigger').on('click', function(e) {
            e.preventDefault();
            var popupId = $(this).data('popup-id');
            $('#recengine-popup-' + popupId).fadeIn(300);
        });
    });

})(jQuery);

