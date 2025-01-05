export function initToggleHandler($) {
    $('.macp-toggle input[type="checkbox"]').on('change', function() {
        const $checkbox = $(this);
        const option = $checkbox.attr('name');
        const value = $checkbox.prop('checked') ? 1 : 0;

        $checkbox.prop('disabled', true);

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'macp_toggle_setting',
                option: option,
                value: value,
                nonce: macp_admin.nonce
            },
            success: function(response) {
                if (response.success) {
                    if (option === 'macp_enable_html_cache') {
                        updateStatusIndicator(value);
                    }
                } else {
                    $checkbox.prop('checked', !value);
                }
            },
            error: function() {
                $checkbox.prop('checked', !value);
            },
            complete: function() {
                $checkbox.prop('disabled', false);
            }
        });
    });
}

function updateStatusIndicator(value) {
    $('.macp-status-indicator')
        .toggleClass('active inactive')
        .text(value ? 'Cache Enabled' : 'Cache Disabled');
}
