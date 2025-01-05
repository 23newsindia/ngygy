<?php
class MACP_CSS_Minifier_Processor {
    public function process($css) {
        if (empty($css)) {
            return $css;
        }

        // Remove comments
        $css = preg_replace('!/\*[^*]*\*+([^/][^*]*\*+)*/!', '', $css);
        
        // Remove whitespace
        $css = preg_replace('/\s+/', ' ', $css);
        $css = preg_replace('/\s*([\{\};:,])\s*/', '$1', $css);
        
        return trim($css);
    }
}