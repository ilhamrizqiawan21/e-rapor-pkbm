<?php

namespace App\Services\Reports;

use App\Contracts\PdfEngine;
use App\Exceptions\PdfEngineUnavailable;
use Symfony\Component\Process\ExecutableFinder;
use Symfony\Component\Process\Process;

/**
 * Renders trusted application HTML in an isolated temporary directory and
 * removes all intermediate files after the PDF is produced.
 */
final class ChromePdfEngine implements PdfEngine
{
    public function __construct(
        private readonly string $configuredBinary,
        private readonly int $timeoutSeconds = 30,
    ) {}

    public function available(): bool
    {
        return $this->binary() !== null;
    }

    public function render(string $html): string
    {
        $binary = $this->binary();

        if ($binary === null) {
            throw new PdfEngineUnavailable;
        }

        $directory = sys_get_temp_dir().DIRECTORY_SEPARATOR.'erapor-pdf-'.bin2hex(random_bytes(8));
        if (! mkdir($directory, 0700, true) && ! is_dir($directory)) {
            throw new PdfEngineUnavailable('Folder sementara untuk PDF tidak dapat dibuat.');
        }

        $htmlPath = $directory.DIRECTORY_SEPARATOR.'report.html';
        $pdfPath = $directory.DIRECTORY_SEPARATOR.'report.pdf';

        try {
            // The HTML and PDF are private temporary artifacts and must not remain
            // on disk after the request, including when the renderer fails.
            if (file_put_contents($htmlPath, $html, LOCK_EX) === false) {
                throw new PdfEngineUnavailable('HTML rapor tidak dapat ditulis ke folder sementara.');
            }

            $process = new Process([
                $binary,
                '--headless',
                '--disable-gpu',
                '--disable-dev-shm-usage',
                '--no-sandbox',
                '--no-pdf-header-footer',
                '--print-to-pdf='.$pdfPath,
                'file://'.$htmlPath,
            ]);
            $process->setTimeout(max(1, $this->timeoutSeconds));
            $process->run();

            if (! $process->isSuccessful() || ! is_file($pdfPath) || filesize($pdfPath) === 0) {
                $details = trim($process->getErrorOutput());
                throw new PdfEngineUnavailable($details !== ''
                    ? 'Mesin PDF gagal membuat dokumen: '.$details
                    : 'Mesin PDF gagal membuat dokumen.');
            }

            $pdf = file_get_contents($pdfPath);
            if ($pdf === false || $pdf === '') {
                throw new PdfEngineUnavailable('Hasil PDF tidak dapat dibaca.');
            }

            return $pdf;
        } finally {
            @unlink($htmlPath);
            @unlink($pdfPath);
            @rmdir($directory);
        }
    }

    private function binary(): ?string
    {
        if ($this->configuredBinary === '') {
            return null;
        }

        return (new ExecutableFinder)->find($this->configuredBinary);
    }
}
