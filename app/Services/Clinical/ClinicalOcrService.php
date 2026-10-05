<?php

namespace App\Services\Clinical;

use App\Models\ClinicalAttachment;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Symfony\Component\Process\Process;

class ClinicalOcrService
{
    public function extract(ClinicalAttachment $attachment): string
    {
        $disk = Storage::disk($attachment->disk);

        if (! $disk->exists($attachment->path)) {
            throw new RuntimeException('Arquivo clínico não encontrado no armazenamento.');
        }

        $sourcePath = $disk->path($attachment->path);
        $mime = strtolower((string) $attachment->mime_type);
        $extension = strtolower(pathinfo($sourcePath, PATHINFO_EXTENSION));

        $text = ($mime === 'application/pdf' || $extension === 'pdf')
            ? $this->extractPdf($sourcePath)
            : $this->extractImage($sourcePath);

        $text = preg_replace("/[ \t]+\n/u", "\n", $text) ?? $text;
        $text = preg_replace("/\n{4,}/u", "\n\n\n", $text) ?? $text;
        $text = trim($text);

        $max = max(1000, (int) config('clinical_ocr.max_text_chars', 1000000));

        return mb_substr($text, 0, $max);
    }

    private function extractImage(string $path): string
    {
        $binary = (string) config('clinical_ocr.tesseract_binary', '/usr/bin/tesseract');

        if (! is_file($binary) || ! is_executable($binary)) {
            throw new RuntimeException('Tesseract OCR não está instalado ou não é executável.');
        }

        $process = new Process([
            $binary,
            $path,
            'stdout',
            '-l',
            (string) config('clinical_ocr.language', 'por'),
        ]);

        $process->setTimeout((int) config('clinical_ocr.timeout_seconds', 180));
        $process->mustRun();

        return $process->getOutput();
    }

    private function extractPdf(string $path): string
    {
        $binary = (string) config('clinical_ocr.pdftoppm_binary', '/usr/bin/pdftoppm');

        if (! is_file($binary) || ! is_executable($binary)) {
            throw new RuntimeException('Poppler/pdftoppm não está instalado ou não é executável.');
        }

        $directory = storage_path('app/ocr/'.Str::uuid());

        if (! is_dir($directory) && ! mkdir($directory, 0770, true) && ! is_dir($directory)) {
            throw new RuntimeException('Não foi possível criar diretório temporário para OCR.');
        }

        $prefix = $directory.'/page';

        try {
            $convert = new Process([
                $binary,
                '-jpeg',
                '-r',
                '200',
                $path,
                $prefix,
            ]);

            $convert->setTimeout((int) config('clinical_ocr.timeout_seconds', 180));
            $convert->mustRun();

            $pages = glob($prefix.'-*.jpg') ?: [];
            natsort($pages);
            $pages = array_values($pages);

            if ($pages === []) {
                throw new RuntimeException('Não foi possível rasterizar o PDF para OCR.');
            }

            $maxPages = max(1, (int) config('clinical_ocr.max_pdf_pages', 25));
            $parts = [];

            foreach (array_slice($pages, 0, $maxPages) as $index => $page) {
                $pageText = trim($this->extractImage($page));

                if ($pageText !== '') {
                    $parts[] = '[Página '.($index + 1).']'."\n".$pageText;
                }
            }

            return implode("\n\n", $parts);
        } finally {
            foreach (glob($directory.'/*') ?: [] as $file) {
                @unlink($file);
            }
            @rmdir($directory);
        }
    }
}
