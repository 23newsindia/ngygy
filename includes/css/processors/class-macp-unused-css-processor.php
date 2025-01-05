<?php
class MACP_Unused_CSS_Processor {
    public function process($css, $html) {
        if (empty($css) || empty($html)) {
            return $css;
        }

        $used_selectors = $this->extract_used_selectors($html);
        return $this->filter_css($css, $used_selectors);
    }

    private function extract_used_selectors($html) {
        $selectors = [];
        $dom = new DOMDocument();
        @$dom->loadHTML($html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        
        $xpath = new DOMXPath($dom);
        $elements = $xpath->query('//*');
        
        foreach ($elements as $element) {
            $selectors[] = strtolower($element->tagName);
            
            if ($element->hasAttribute('class')) {
                foreach (explode(' ', $element->getAttribute('class')) as $class) {
                    if (!empty($class)) {
                        $selectors[] = '.' . trim($class);
                    }
                }
            }
            
            if ($element->hasAttribute('id')) {
                $selectors[] = '#' . $element->getAttribute('id');
            }
        }
        
        return array_unique($selectors);
    }

    private function filter_css($css, $used_selectors) {
        $filtered = '';
        $safelist = MACP_CSS_Config::get_safelist();
        
        preg_match_all('/([^{]+){[^}]*}/s', $css, $matches);
        
        foreach ($matches[0] as $rule) {
            if ($this->should_keep_rule($rule, $used_selectors, $safelist)) {
                $filtered .= $rule . "\n";
            }
        }
        
        return $filtered;
    }

    private function should_keep_rule($rule, $used_selectors, $safelist) {
        $selectors = explode(',', trim(preg_replace('/\s*{.*$/s', '', $rule)));
        
        foreach ($selectors as $selector) {
            $selector = trim($selector);
            
            if ($this->is_essential_selector($selector) || 
                $this->is_in_safelist($selector, $safelist) ||
                $this->is_selector_used($selector, $used_selectors)) {
                return true;
            }
        }
        
        return false;
    }

    private function is_essential_selector($selector) {
        return in_array($selector, ['html', 'body', '*']) || strpos($selector, '@') === 0;
    }

    private function is_in_safelist($selector, $safelist) {
        foreach ($safelist as $pattern) {
            if (fnmatch($pattern, $selector)) {
                return true;
            }
        }
        return false;
    }

    private function is_selector_used($selector, $used_selectors) {
        foreach ($used_selectors as $used_selector) {
            if (strpos($used_selector, $selector) !== false) {
                return true;
            }
        }
        return false;
    }
}