jQuery(document).ready(function($) {
    // Auto-save functionality for textareas
    let textareaTimeout;
    $('.macp-exclusion-section textarea').on('input', function() {
        const $textarea = $(this);
        clearTimeout(textareaTimeout);
        textareaTimeout = setTimeout(function() {
            const option = $textarea.attr('name');
            const value = $textarea.val();

            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'macp_save_textarea',
                    option: option,
                    value: value,
                    nonce: macp_admin.nonce
                }
            });
        }, 1000); // Save after 1 second of no typing
    });

    // Handle toggle switches for specific options
    $('.macp-toggle input[type="checkbox"]').on('change', function() {
        const $checkbox = $(this);
        const option = $checkbox.attr('name');
        const value = $checkbox.prop('checked') ? 1 : 0;

        // Disable the checkbox while saving
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
                    // Update status indicator for cache toggle
                    if (option === 'macp_enable_html_cache') {
                        $('.macp-status-indicator')
                            .toggleClass('active inactive')
                            .text(value ? 'Cache Enabled' : 'Cache Disabled');
                    }
                } else {
                    $checkbox.prop('checked', !value); // Revert on failure
                }
            },
            error: function() {
                $checkbox.prop('checked', !value); // Revert on error
            },
            complete: function() {
                $checkbox.prop('disabled', false); // Re-enable checkbox
            }
        });
    });

    // Test unused CSS functionality
    $('#test-unused-css').on('click', function() {
        const $button = $(this);
        const $results = $('#test-results');
        const $status = $('.test-status');
        const $resultsBody = $('.results-body');
        const testUrl = $('#test-url').val() || window.location.origin;

        // Reset and show loading
        $button.prop('disabled', true).text('Testing...');
        $status.removeClass('success error').empty();
        $resultsBody.empty();
        $results.show();

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'macp_test_unused_css',
                url: testUrl,
                nonce: macp_admin.nonce
            },
            success: function(response) {
                console.log('Response:', response); // Debug log
                if (response.success) {
                    $status
                        .addClass('success')
                        .html(`Successfully analyzed CSS for <strong>${testUrl}</strong>`);

                    response.data.forEach(function(result) {
                        const reduction = ((result.originalSize - result.optimizedSize) / result.originalSize * 100).toFixed(1);
                        const row = `
                            <tr>
                                <td>${result.file}</td>
                                <td>${formatBytes(result.originalSize)}</td>
                                <td>${formatBytes(result.optimizedSize)}</td>
                                <td>${reduction}%</td>
                                <td class="file-status ${result.success ? 'success' : 'error'}">
                                    ${result.success ? '✓ Optimized' : '✗ Failed'}
                                </td>
                            </tr>
                        `;
                        $resultsBody.append(row);
                    });
                } else {
                    $status
                        .addClass('error')
                        .html(`Error: ${response.data}`);
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX Error:', {xhr, status, error}); // Debug log
                $status
                    .addClass('error')
                    .html(`Failed to test unused CSS removal. Server Error: ${error}`);
            },
            complete: function() {
                $button.prop('disabled', false).text('Test Unused CSS Removal');
            }
        });
    });

    // Helper function to format bytes
    function formatBytes(bytes) {
        if (bytes === 0) return '0 B';
        const k = 1024;
        const sizes = ['B', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    }

    // Clear cache button functionality
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
            success: function(response) {
                if (response.success) {
                    $button.text('Cache Cleared!');
                    setTimeout(function() {
                        $button.text('Clear Cache').prop('disabled', false);
                    }, 2000);
                }
            },
            error: function() {
                $button.text('Error!');
                setTimeout(function() {
                    $button.text('Clear Cache').prop('disabled', false);
                }, 2000);
            }
        });
    });
});
