export function initTextareaHandler($) {
    let textareaTimeout;
    $('.macp-exclusion-section textarea').on('input', function() {
        const $textarea = $(this);
        clearTimeout(textareaTimeout);
        textareaTimeout = setTimeout(function() {
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'macp_save_textarea',
                    option: $textarea.attr('name'),
                    value: $textarea.val(),
                    nonce: macp_admin.nonce
                }
            });
        }, 1000);
    });
}
