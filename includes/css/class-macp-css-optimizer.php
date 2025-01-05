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

        // Extract all CSS first
        $all_css = $this->extract_all_css($html);
        if (empty($all_css)) {
            return $html;
        }

        // Extract used selectors from HTML
        $used_selectors = $this->css_extractor->extract_used_selectors($html);
        
        // Filter CSS to keep only used rules
        $used_css = $this->filter_css($all_css, $used_selectors);

        // Remove original CSS links and inject optimized CSS
        $html = $this->remove_original_css($html);
        $html = $this->inject_optimized_css($html, $used_css);

        // Save for caching
        $this->used_css_manager->save_used_css($url, $used_css, wp_is_mobile());

        return $html;
    }

    private function should_process() {
        if (is_admin() || is_customize_preview()) {
            return false;
        }

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
        preg_match_all('/<link[^>]*rel=[\'"]stylesheet[\'"][^>]*href=[\'"]([^\'"]+)[\'"][^>]*>/i', $html, $matches);
        if (!empty($matches[1])) {
            foreach ($matches[1] as $stylesheet) {
                $content = $this->get_external_css($stylesheet);
                if ($content) {
                    $css .= "\n" . $content;
                }
            }
        }

        return $css;
    }

    private function get_external_css($url) {
        // Handle protocol-relative URLs
        if (strpos($url, '//') === 0) {
            $url = 'https:' . $url;
        }

        $response = wp_remote_get($url);
        if (is_wp_error($response)) {
            return false;
        }

        $css = wp_remote_retrieve_body($response);
        
        // Convert relative URLs to absolute
        $css = $this->convert_relative_urls($css, $url);
        
        return $css;
    }

    private function convert_relative_urls($css, $base_url) {
        // Convert relative URLs to absolute
        return preg_replace_callback('/url\([\'"]?([^\'"\)]+)[\'"]?\)/i', function($matches) use ($base_url) {
            $url = $matches[1];
            
            // Skip if already absolute
            if (preg_match('/^(https?:\/\/|data:)/i', $url)) {
                return $matches[0];
            }

            // Convert relative to absolute
            $absolute_url = $this->make_absolute_url($url, $base_url);
            return 'url("' . $absolute_url . '")';
        }, $css);
    }

    private function make_absolute_url($url, $base_url) {
        $base_parts = parse_url($base_url);
        
        // Handle root-relative URLs
        if (strpos($url, '/') === 0) {
            return $base_parts['scheme'] . '://' . $base_parts['host'] . $url;
        }
        
        // Handle relative URLs
        $base_dir = dirname($base_parts['path']);
        if ($base_dir !== '/') {
            $base_dir .= '/';
        }
        
        return $base_parts['scheme'] . '://' . $base_parts['host'] . $base_dir . $url;
    }

    private function filter_css($css, $used_selectors) {
        $filtered = '';
        
        // Split CSS into rules
        preg_match_all('/([^{]+){[^}]*}/s', $css, $matches);
        
        foreach ($matches[0] as $rule) {
            $selectors = trim(preg_replace('/\s*{.*$/s', '', $rule));
            $selectors = explode(',', $selectors);
            
            foreach ($selectors as $selector) {
                $selector = trim($selector);
                if ($this->is_selector_used($selector, $used_selectors)) {
                    $filtered .= $rule . "\n";
                    break;
                }
            }
        }
        
        return $filtered;
    }

    private function is_selector_used($selector, $used_selectors) {
        // Always keep essential selectors
        if (in_array($selector, ['html', 'body', '*'])) {
            return true;
        }

        // Check if selector matches any used selectors
        foreach ($used_selectors as $used_selector) {
            if (strpos($used_selector, $selector) !== false) {
                return true;
            }
        }

        return false;
    }

    private function remove_original_css($html) {
        // Remove link tags
        $html = preg_replace('/<link[^>]*rel=[\'"]stylesheet[\'"][^>]*>/i', '', $html);
        
        // Remove style tags
        $html = preg_replace('/<style[^>]*>.*?<\/style>/s', '', $html);
        
        return $html;
    }

    private function inject_optimized_css($html, $css) {
        $css_tag = sprintf(
            '<style id="macp-optimized-css">%s</style>',
            $css
        );

        // Inject before </head>
        return preg_replace('/<\/head>/', $css_tag . '</head>', $html);
    }
}