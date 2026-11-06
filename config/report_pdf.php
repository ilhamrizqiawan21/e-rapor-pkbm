<?php

return [
    'browser_binary' => env('PDF_BROWSER_BINARY', 'google-chrome'),
    'timeout_seconds' => (int) env('PDF_BROWSER_TIMEOUT', 30),
];
