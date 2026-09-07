<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Service;
use App\Libraries\Logger;

/**
 * PDF generation service.
 *
 * The report's single source of truth is HTML (print-ready). When Dompdf is
 * installed (Composer), the same HTML is rendered to a downloadable PDF. When
 * it is not, the HTML is served print-ready so the user can save as PDF via the
 * browser. The absence of Dompdf is logged, never a hard failure.
 *
 * This keeps one report structure and lets the PDF engine be swapped later
 * without rewriting the report.
 */
final class PdfService extends Service
{
    public function isPdfEngineAvailable(): bool
    {
        return class_exists(\Dompdf\Dompdf::class);
    }

    /**
     * Render HTML to a PDF and stream it as a download.
     * Returns true if a PDF was produced; false if the engine is unavailable.
     */
    public function stream(string $html, string $filename): bool
    {
        if (!$this->isPdfEngineAvailable()) {
            $this->logger()->info('PDF não gerado: Dompdf ausente. Servindo HTML print-ready.', [
                'filename' => $filename,
            ]);

            return false;
        }

        $dompdf = new \Dompdf\Dompdf([
            'isRemoteEnabled'      => true,  // allow logo/images over https
            'defaultFont'          => 'DejaVu Sans',
            'chroot'               => dirname(__DIR__, 2),
        ]);
        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        echo $dompdf->output();

        return true;
    }

    private function logger(): Logger
    {
        /** @var Logger $l */
        $l = $this->container->get('logger');

        return $l;
    }
}
