import { initTextareaHandler } from './modules/textarea-handler.js';
import { initToggleHandler } from './modules/toggle-handler.js';
import { initCSSTest } from './modules/css-test.js';
import { initCacheHandler } from './modules/cache-handler.js';

jQuery(document).ready(function($) {
    initTextareaHandler($);
    initToggleHandler($);
    initCSSTest($);
    initCacheHandler($);
});

