<?php

return [
    'enabled' => (bool) env('CLINICAL_OCR_ENABLED', false),
    'language' => env('CLINICAL_OCR_LANGUAGE', 'por'),
    'tesseract_binary' => env('CLINICAL_OCR_TESSERACT', '/usr/bin/tesseract'),
    'pdftoppm_binary' => env('CLINICAL_OCR_PDFTOPPM', '/usr/bin/pdftoppm'),
    'timeout_seconds' => (int) env('CLINICAL_OCR_TIMEOUT', 180),
    'max_pdf_pages' => (int) env('CLINICAL_OCR_MAX_PAGES', 25),
    'max_text_chars' => (int) env('CLINICAL_OCR_MAX_TEXT_CHARS', 1000000),
];
