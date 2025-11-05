/**
 * Recommendation Engine WP - Admin JavaScript
 */

(function($) {
    'use strict';
    
    $(document).ready(function() {
        // Test connection button
        $('#recengine-test-connection').on('click', function(e) {
            e.preventDefault();
            
            const $button = $(this);
            const $status = $('#recengine-connection-status');
            
            $button.prop('disabled', true).text('Testing...');
            $status.removeClass('success error').text('');
            
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'recengine_test_connection',
                    nonce: recengineAdmin.nonce
                },
                success: function(response) {
                    if (response.success) {
                        $status.addClass('success').text('Connection successful!');
                    } else {
                        $status.addClass('error').text('Connection failed: ' + response.data.message);
                    }
                },
                error: function() {
                    $status.addClass('error').text('Connection test failed');
                },
                complete: function() {
                    $button.prop('disabled', false).text('Test Connection');
                }
            });
        });
    });
    
})(jQuery);
