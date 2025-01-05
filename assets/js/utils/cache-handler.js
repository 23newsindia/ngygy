export function initCacheHandler($) {
    $('.macp-clear-cache').on('click', function(e) {
        e.preventDefault();
        const $button = $(this);

        $button.prop('disabled', true).text('Clearing...');

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'macp_clear_cache',
                nonce: macp_admin.nonce
            },
            success: () => handleSuccess($button),
            error: () => handleError($button)
        });
    });
}

function handleSuccess($button) {
    $button.text('Cache Cleared!');
    resetButton($button);
}

function handleError($button) {
    $button.text('Error!');
    resetButton($button);
}

function resetButton($button) {
    setTimeout(() => {
        $button.text('Clear Cache').prop('disabled', false);
    }, 2000);
}
