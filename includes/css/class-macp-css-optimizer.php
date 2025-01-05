<?php
class MACP_CSS_Optimizer {
    private $minifier;
    private $unused_processor;
    private $filesystem;
    private $excluded_patterns;
    private $cache_dir;

    public function __construct() {
        $this->minifier = new MACP_CSS_Minifier_Processor();
        $this->unused_processor = new MACP_Unused_CSS_Processor();
        $this->filesystem = new MACP_Filesystem();
        $this->excluded_patterns = MACP_CSS_Config::get_excluded_patterns();
        $this->cache_dir = WP_CONTENT_DIR . '/cache/min/';
        
        add_filter('style_loader_tag', [$this, 'process_stylesheet'], 10, 4);
    }
  
  
  // In includes/css/class-macp-css-optimizer.php

public function test_unused_css($url) {
    try {
        // Get the page HTML
        $response = wp_remote_get($url);
        if (is_wp_error($response)) {
            throw new Exception('Failed to fetch URL: ' . $response->get_error_message());
        }

        $html = wp_remote_retrieve_body($response);
        if (empty($html)) {
            throw new Exception('Empty response from URL');
        }

        // Extract all CSS files
        $css_files = $this->extract_css_files($html);
        $results = [];

        foreach ($css_files as $css_file) {
            try {
                // Get original CSS content
                $original_css = $this->get_stylesheet_content($css_file);
                if (!$original_css) {
                    continue;
                }

                $original_size = strlen($original_css);

                // Process CSS to remove unused rules
                $optimized_css = $this->process_css($original_css, $html);
                $optimized_size = strlen($optimized_css);

                $results[] = [
                    'file' => $css_file,
                    'originalSize' => $original_size,
                    'optimizedSize' => $optimized_size,
                    'success' => true
                ];
            } catch (Exception $e) {
                $results[] = [
                    'file' => $css_file,
                    'originalSize' => 0,
                    'optimizedSize' => 0,
                    'success' => false,
                    'error' => $e->getMessage()
                ];
            }
        }

        return $results;
    } catch (Exception $e) {
        throw new Exception('Failed to test unused CSS: ' . $e->getMessage());
    }
}

private function process_css($css, $html) {
    // Extract used selectors from HTML
    $used_selectors = $this->extract_used_selectors($html);
    
    // Filter CSS to keep only used selectors
    return $this->filter_css($css, $used_selectors);
}

private function extract_used_selectors($html) {
    $selectors = [];
    
    // Use DOMDocument to parse HTML
    $dom = new DOMDocument();
    @$dom->loadHTML($html);
    
    // Get all elements
    $xpath = new DOMXPath($dom);
    $elements = $xpath->query('//*');
    
    foreach ($elements as $element) {
        // Get element tag name
        $selectors[] = strtolower($element->tagName);
        
        // Get element classes
        if ($element->hasAttribute('class')) {
            $classes = explode(' ', $element->getAttribute('class'));
            foreach ($classes as $class) {
                if (!empty($class)) {
                    $selectors[] = '.' . trim($class);
                }
            }
        }
        
        // Get element ID
        if ($element->hasAttribute('id')) {
            $selectors[] = '#' . $element->getAttribute('id');
        }
    }
    
    return array_unique($selectors);
}

private function filter_css($css, $used_selectors) {
    $filtered = '';
    $safelist = MACP_CSS_Config::get_safelist();
    
    // Split CSS into rules
    preg_match_all('/([^{]+){[^}]*}/s', $css, $matches);
    
    foreach ($matches[0] as $rule) {
        $selectors = trim(preg_replace('/\s*{.*$/s', '', $rule));
        $selectors = explode(',', $selectors);
        
        foreach ($selectors as $selector) {
            $selector = trim($selector);
            
            // Keep if in safelist
            foreach ($safelist as $pattern) {
                if (fnmatch($pattern, $selector)) {
                    $filtered .= $rule . "\n";
                    continue 2;
                }
            }
            
            // Keep if used in HTML
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
    if (in_array($selector, ['html', 'body', '*']) || strpos($selector, '@') === 0) {
        return true;
    }

    foreach ($used_selectors as $used_selector) {
        if (strpos($used_selector, $selector) !== false) {
            return true;
        }
    }
    
    return false;
}
