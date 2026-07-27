<?php

return [
    'show_warnings' => env('APP_DEBUG', false),
    'orientation' => 'portrait',
    'default_paper_size' => 'a4',
    'default_font' => 'DejaVu Sans',
    'dpi' => 150,
    'font_dir' => storage_path('fonts'),
    'font_cache' => storage_path('fonts'),
    'is_php_enabled' => false,
    'is_remote_enabled' => false,
    'is_javascript_enabled' => false,
    'is_html5_parser_enabled' => true,
    'is_font_subsetting_enabled' => true,
];
