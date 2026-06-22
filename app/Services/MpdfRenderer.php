<?php

namespace App\Services;

use Illuminate\Http\Response;
use Illuminate\Support\Facades\View;
use Mpdf\Mpdf;
use Mpdf\Output\Destination;

/**
 * Thin wrapper around mPDF that mirrors the DomPDF facade ergonomics
 * (loadView()->stream()/download()), so controllers barely change when
 * migrating a single PDF to mPDF while the rest stays on DomPDF.
 *
 * mPDF is used here because — unlike DomPDF — it natively supports CSS
 * multi-column layout (column-count / column-fill: auto) with correct
 * column-major flow and page breaks, so no height estimation is needed.
 */
class MpdfRenderer
{
    private string $html = '';

    /**
     * @var array<string, mixed>
     */
    private array $config = [];

    /**
     * Render a Blade view to HTML and prepare the mPDF document.
     *
     * @param  array<string, mixed>  $data  View data.
     * @param  array<string, mixed>  $config  mPDF constructor overrides (format, margins, font, ...).
     */
    public function loadView(string $view, array $data = [], array $config = []): self
    {
        $this->html = View::make($view, $data)->render();
        $this->config = $config;

        return $this;
    }

    /**
     * Return the PDF inline (opens in the browser), like Pdf::stream().
     */
    public function stream(string $filename): Response
    {
        return $this->toResponse($filename, 'inline');
    }

    /**
     * Return the PDF as a download attachment, like Pdf::download().
     */
    public function download(string $filename): Response
    {
        return $this->toResponse($filename, 'attachment');
    }

    private function toResponse(string $filename, string $disposition): Response
    {
        $mpdf = $this->makeMpdf();
        $mpdf->WriteHTML($this->html);
        $content = $mpdf->Output($filename, Destination::STRING_RETURN);

        return new Response($content, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => $disposition.'; filename="'.$filename.'"',
        ]);
    }

    private function makeMpdf(): Mpdf
    {
        // Writable scratch space: shared hosting has no system temp guarantees.
        $tempDir = storage_path('app/mpdf');
        if (! is_dir($tempDir)) {
            mkdir($tempDir, 0775, true);
        }

        // Margins are in millimetres. They reproduce the DomPDF CSS page
        // margin of 18px/16px (≈ 4.76mm / 4.23mm at 96dpi). The top margin
        // is enlarged so the running page header (mPDF replaces the old
        // <thead>) fits above the content; tune margin_top/margin_header to
        // the header height when calibrating against the DomPDF output.
        /** @var array<string, mixed> $defaults */
        $defaults = [
            'mode' => 'utf-8',
            'format' => 'A4',
            'orientation' => 'P',
            'margin_left' => 4.2,
            'margin_right' => 4.2,
            'margin_top' => 24,
            'margin_bottom' => 6,
            'margin_header' => 5,
            'margin_footer' => 0,
            'default_font' => 'dejavusans',
            'default_font_size' => 8,
            'tempDir' => $tempDir,
        ];

        return new Mpdf(array_merge($defaults, $this->config));
    }
}
