<?php
class MACP_CSS_Test_Ajax_Handler {
    public function __construct() {
        add_action('wp_ajax_macp_test_unused_css', [$this, 'handle_test_request']);
    }

    public function handle_test_request() {
        try {
            // Verify nonce
            if (!check_ajax_referer('macp_admin_nonce', 'nonce', false)) {
                wp_send_json_error('Invalid security token');
                return;
            }

            // Verify user capabilities
            if (!current_user_can('manage_options')) {
                wp_send_json_error('Unauthorized access');
                return;
            }

            // Get and validate URL
            $url = isset($_POST['url']) ? esc_url_raw($_POST['url']) : home_url('/');
            
            // Validate URL
            if (!filter_var($url, FILTER_VALIDATE_URL)) {
                wp_send_json_error('Invalid URL provided');
                return;
            }

            // Initialize CSS optimizer
            $optimizer = new MACP_CSS_Optimizer();
            
            // Get the test results
            $results = $optimizer->test_unused_css($url);
            
            if (empty($results)) {
                wp_send_json_error('No CSS files found to analyze');
                return;
            }

            wp_send_json_success($results);

        } catch (Exception $e) {
            error_log('MACP CSS Test Error: ' . $e->getMessage());
            wp_send_json_error($e->getMessage());
        }
    }
}
