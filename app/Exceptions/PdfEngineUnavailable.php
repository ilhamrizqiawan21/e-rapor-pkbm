<?php

namespace App\Exceptions;

use RuntimeException;

final class PdfEngineUnavailable extends RuntimeException
{
    public function __construct(string $message = 'Mesin PDF belum tersedia. Atur PDF_BROWSER_BINARY ke lokasi Google Chrome, Chromium, atau wkhtmltopdf.')
    {
        parent::__construct($message);
    }
}
