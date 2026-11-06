<?php

namespace App\Contracts;

interface PdfEngine
{
    public function available(): bool;

    public function render(string $html): string;
}
