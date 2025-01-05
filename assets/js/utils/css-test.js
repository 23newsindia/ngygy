import { formatBytes } from '../utils/format-bytes.js';

export function initCSSTest($) {
    $('#test-unused-css').on('click', function() {
        const $button = $(this);
        const $results = $('#test-results');
        const $status = $('.test-status');
        const $resultsBody = $('.results-body');
        const testUrl = $('#test-url').val() || window.location.origin;

        setLoadingState($button, $status, $resultsBody, $results);

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'macp_test_unused_css',
                url: testUrl,
                nonce: macp_admin.nonce
            },
            success: (response) => handleSuccess(response, testUrl, $status, $resultsBody),
            error: (xhr, status, error) => handleError(xhr, status, error, $status),
            complete: () => resetButton($button)
        });
    });
}

function setLoadingState($button, $status, $resultsBody, $results) {
    $button.prop('disabled', true).text('Testing...');
    $status.removeClass('success error').empty();
    $resultsBody.empty();
    $results.show();
}

function handleSuccess(response, testUrl, $status, $resultsBody) {
    if (response.success) {
        displaySuccessStatus(testUrl, $status);
        displayResults(response.data, $resultsBody);
    } else {
        displayErrorStatus(response.data, $status);
    }
}

function handleError(xhr, status, error, $status) {
    console.error('AJAX Error:', {xhr, status, error});
    displayErrorStatus(error, $status);
}

function resetButton($button) {
    $button.prop('disabled', false).text('Test Unused CSS Removal');
}

function displaySuccessStatus(testUrl, $status) {
    $status
        .addClass('success')
        .html(`Successfully analyzed CSS for <strong>${testUrl}</strong>`);
}

function displayErrorStatus(error, $status) {
    $status
        .addClass('error')
        .html(`Failed to test unused CSS removal. Server Error: ${error || 'Unknown error occurred'}`);
}

function displayResults(results, $resultsBody) {
    results.forEach(result => {
        const reduction = ((result.originalSize - result.optimizedSize) / result.originalSize * 100).toFixed(1);
        const row = createResultRow(result, reduction);
        $resultsBody.append(row);
    });
}

function createResultRow(result, reduction) {
    return `
        <tr>
            <td>${result.file}</td>
            <td>${formatBytes(result.originalSize)}</td>
            <td>${formatBytes(result.optimizedSize)}</td>
            <td>${reduction}%</td>
            <td class="file-status ${result.success ? 'success' : 'error'}">
                ${result.success ? '✓ Optimized' : '✗ ' + (result.error || 'Failed')}
            </td>
        </tr>
    `;
}
