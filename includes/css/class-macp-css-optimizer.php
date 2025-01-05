<?php
class MACP_CSS_Optimizer {
    private $used_css_manager;
    private $css_extractor;
    private $filesystem;

    public function __construct() {
        $this->used_css_manager = new MACP_Used_CSS_Manager();
        $this->css_extractor = new MACP_CSS_Extractor();
        $this->filesystem = new MACP_Filesystem();
    }

    public function process_page($html, $url) {
        if (!$this->should_process()) {
            return $html;
        }

        $is_mobile = wp_is_mobile();
        
        // Try to get cached used CSS
        $used_css = $this->used_css_manager->get_used_css($url, $is_mobile);
        if ($used_css) {
            return $this->inject_used_css($html, $used_css);
        }

        // Extract all CSS
        $all_css = $this->extract_all_css($html);
        if (empty($all_css)) {
            return $html;
        }

        // Generate used CSS
        $used_css = $this->css_extractor->extract_used_css($html, $all_css);
        
        // Save used CSS
        $this->used_css_manager->save_used_css($url, $used_css, $is_mobile);

        // Remove original CSS and inject optimized CSS
        return $this->inject_used_css($html, $used_css);
    }

    private function should_process() {
        // Don't process admin pages
        if (is_admin()) {
            return false;
        }

        // Don't process if option is disabled
        if (!get_option('macp_remove_unused_css', 0)) {
            return false;
        }

        return true;
    }

    private function extract_all_css($html) {
        $css = '';

        // Extract inline CSS
        preg_match_all('/<style[^>]*>(.*?)<\/style>/s', $html, $matches);
        if (!empty($matches[1])) {
            $css .= implode("\n", $matches[1]);
        }

        // Extract external CSS
        preg_match_all('/<link[^>]*rel=[\'"]stylesheet[\'"][^>]*href=[\'"]([^\'"]+)[\'"][^>]*>/', $html, $matches);
        if (!empty($matches[1])) {
            foreach ($matches[1] as $url) {
                $content = $this->get_external_css($url);
                if ($content) {
                    $css .= "\n" . $content;
                }
            }
        }

        return $css;
    }

    private function get_external_css($url) {
        $response = wp_remote_get($url);
        if (is_wp_error($response)) {
            return false;
        }
        return wp_remote_retrieve_body($response);
    }

    private function inject_used_css($html, $used_css) {
        // Remove existing CSS
        $html = preg_replace('/<link[^>]*rel=[\'"]stylesheet[\'"][^>]*>/', '', $html);
        $html = preg_replace('/<style[^>]*>.*?<\/style>/s', '', $html);

        // Inject optimized CSS
        $css_tag = sprintf(
            '<style id="macp-used-css">%s</style>',
            $used_css
        );

        return preg_replace('/<\/head>/', $css_tag . '</head>', $html);
    }
}