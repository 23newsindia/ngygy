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
            }

            // Verify user capabilities
            if (!current_user_can('manage_options')) {
                wp_send_json_error('Unauthorized access');
            }

            // Get and validate URL
            $url = isset($_POST['url']) ? esc_url_raw($_POST['url']) : home_url('/');

            // Return dummy data for testing
            $results = [
                [
                    'file' => 'style.css',
                    'originalSize' => 15000,
                    'optimizedSize' => 10000,
                    'success' => true
                ]
            ];

            wp_send_json_success($results);

        } catch (Exception $e) {
            wp_send_json_error($e->getMessage());
        }
    }
}