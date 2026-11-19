<?php

namespace App\Services\Pdf;

use App\Models\Template;
use App\Services\Files\PrivateFileService;
use Barryvdh\DomPDF\Facade\Pdf;
use Barryvdh\DomPDF\PDF as DomPdf;

/**
 * Render template ke PDF dengan ukuran persis seperti di editor (FR-TPL-02).
 * Mode `single`: satu kartu/sertifikat per halaman seukuran template.
 * Mode `sheet`: lembar A4 berisi kartu 3 × 3 dengan tanda potong (FR-GEN-06).
 */
class TemplateRenderer
{
    private const MM_TO_PT = 72 / 25.4;

    public function __construct(
        private readonly QrCodeService $qr,
        private readonly PrivateFileService $files,
    ) {}

    /**
     * @param  list<RenderSubject>  $subjects
     */
    public function pdf(Template $template, array $subjects, string $mode = 'single'): DomPdf
    {
        $pdf = Pdf::loadHTML($this->html($template, $subjects, $mode));

        if ($mode === 'sheet') {
            return $pdf->setPaper('a4', 'portrait');
        }

        return $pdf->setPaper([0, 0, $template->width_mm * self::MM_TO_PT, $template->height_mm * self::MM_TO_PT]);
    }

    /**
     * @param  list<RenderSubject>  $subjects
     */
    public function html(Template $template, array $subjects, string $mode = 'single'): string
    {
        $background = $template->background_path && $this->files->exists($template->background_path)
            ? $this->files->absolutePath($template->background_path)
            : null;

        $cards = array_map(fn (RenderSubject $subject) => [
            'subject' => $subject,
            'qr' => $this->qr->pngDataUri($subject->qrContent),
        ], $subjects);

        return view('pdf.template', [
            'template' => $template,
            'fields' => array_values(array_filter($template->fields, fn (array $field) => $field['enabled'] ?? true)),
            'background' => $background,
            'pages' => $mode === 'sheet' ? $this->sheetPages($template, $cards) : array_map(fn ($card) => [$card], $cards),
            'mode' => $mode,
            'grid' => $mode === 'sheet' ? $this->grid($template) : null,
            'fontDir' => resource_path('fonts'),
        ])->render();
    }

    /**
     * Susunan kartu di A4 (210 × 297 mm), dirapatkan dan ditengahkan.
     *
     * @return array{cols: int, rows: int, left: float, top: float}
     */
    private function grid(Template $template): array
    {
        $cols = max(1, min(3, (int) floor(210 / $template->width_mm)));
        $rows = max(1, min(3, (int) floor(297 / $template->height_mm)));

        return [
            'cols' => $cols,
            'rows' => $rows,
            'left' => (210 - $cols * $template->width_mm) / 2,
            'top' => (297 - $rows * $template->height_mm) / 2,
        ];
    }

    /**
     * @param  list<array{subject: RenderSubject, qr: string}>  $cards
     * @return list<list<array{subject: RenderSubject, qr: string}>>
     */
    private function sheetPages(Template $template, array $cards): array
    {
        $grid = $this->grid($template);

        return array_chunk($cards, max(1, $grid['cols'] * $grid['rows']));
    }
}
