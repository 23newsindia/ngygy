<?php
class MACP_CSS_Extractor {
    private $excluded_selectors = [
        'html', 'body', ':root',
        '.wp-*', '.has-*', '.is-*',
        '.admin-bar', '.logged-in'
    ];

    public function extract_used_css($html, $css) {
        // Create a new DOMDocument
        $dom = new DOMDocument();
        @$dom->loadHTML($html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        
        // Get all elements
        $xpath = new DOMXPath($dom);
        $elements = $xpath->query('//*');

        // Extract all used classes and IDs
        $used_selectors = [];
        foreach ($elements as $element) {
            // Get element tag
            $used_selectors[] = $element->tagName;
            
            // Get classes
            if ($element->hasAttribute('class')) {
                $classes = explode(' ', $element->getAttribute('class'));
                foreach ($classes as $class) {
                    if ($class = trim($class)) {
                        $used_selectors[] = '.' . $class;
                    }
                }
            }
            
            // Get ID
            if ($element->hasAttribute('id')) {
                $used_selectors[] = '#' . $element->getAttribute('id');
            }
        }

        // Add excluded selectors
        $used_selectors = array_merge($used_selectors, $this->excluded_selectors);
        
        // Parse CSS and keep only used selectors
        return $this->filter_css($css, array_unique($used_selectors));
    }

    private function filter_css($css, $used_selectors) {
        // Basic CSS parser
        preg_match_all('/([^{]+){[^}]*}/', $css, $matches);
        
        $filtered_css = '';
        foreach ($matches[0] as $rule) {
            $selector = trim(preg_replace('/\s*{.*$/s', '', $rule));
            
            // Check if selector is used
            foreach ($used_selectors as $used_selector) {
                if (strpos($selector, $used_selector) !== false) {
                    $filtered_css .= $rule . "\n";
                    break;
                }
            }
        }

        return $filtered_css;
    }
}