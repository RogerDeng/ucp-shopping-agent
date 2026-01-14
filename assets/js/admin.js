/**
 * WooCommerce UCP Agent - Admin Scripts
 */

(function ($) {
    'use strict';

    $(document).ready(function () {
        // Create API Key
        $('#create-api-key').on('click', function () {
            var $button = $(this);
            var description = $('#api-key-description').val();
            var permissions = $('#api-key-permissions').val();

            $button.prop('disabled', true).text(wcUcpAdmin.strings.loading || 'Creating...');

            $.post(wcUcpAdmin.ajaxUrl, {
                action: 'wc_ucp_create_api_key',
                nonce: wcUcpAdmin.nonce,
                description: description,
                permissions: permissions
            }, function (response) {
                $button.prop('disabled', false).text('Generate API Key');

                if (response.success) {
                    $('#new-api-key-value').text(response.data.api_key);
                    $('#new-api-key-display').show();
                    $('#api-key-description').val('');

                    // Reload page to show new key in list
                    setTimeout(function () {
                        location.reload();
                    }, 3000);
                } else {
                    alert(response.data || wcUcpAdmin.strings.error);
                }
            }).fail(function () {
                $button.prop('disabled', false).text('Generate API Key');
                alert(wcUcpAdmin.strings.error);
            });
        });

        // Delete API Key
        $(document).on('click', '.delete-api-key', function () {
            var $button = $(this);
            var keyId = $button.data('key-id');

            if (!confirm(wcUcpAdmin.strings.confirmDelete)) {
                return;
            }

            $button.prop('disabled', true);

            $.post(wcUcpAdmin.ajaxUrl, {
                action: 'wc_ucp_delete_api_key',
                nonce: wcUcpAdmin.nonce,
                key_id: keyId
            }, function (response) {
                if (response.success) {
                    $button.closest('tr').fadeOut(function () {
                        $(this).remove();
                        // Check if list is empty
                        if ($('#api-keys-list tr').length === 0) {
                            $('#api-keys-list').html('<tr><td colspan="6">No API keys found. Create one above.</td></tr>');
                        }
                    });
                } else {
                    $button.prop('disabled', false);
                    alert(response.data || wcUcpAdmin.strings.error);
                }
            }).fail(function () {
                $button.prop('disabled', false);
                alert(wcUcpAdmin.strings.error);
            });
        });

        // Copy to Clipboard
        $(document).on('click', '.copy-to-clipboard', function () {
            var $button = $(this);
            var target = $button.data('target');
            var text = $(target).text();

            navigator.clipboard.writeText(text).then(function () {
                var originalText = $button.text();
                $button.text(wcUcpAdmin.strings.copied);
                setTimeout(function () {
                    $button.text(originalText);
                }, 2000);
            }).catch(function () {
                // Fallback for older browsers
                var $temp = $('<textarea>');
                $('body').append($temp);
                $temp.val(text).select();
                document.execCommand('copy');
                $temp.remove();

                var originalText = $button.text();
                $button.text(wcUcpAdmin.strings.copied);
                setTimeout(function () {
                    $button.text(originalText);
                }, 2000);
            });
        });
    });

})(jQuery);
