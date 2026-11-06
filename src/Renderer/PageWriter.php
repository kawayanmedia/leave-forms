<?php

declare(strict_types=1);

namespace KawayanMedia\LeaveForms\Renderer;

use KawayanMedia\LeaveForms\Support\Text;
use setasign\Fpdi\Fpdi;

/**
 * Thin, absolute-positioned wrapper over the FPDF calls the legacy screens
 * used. Every coordinate comes from a layout class, so the painters stay free
 * of magic numbers.
 */
final class PageWriter
{
    public function text(
        Fpdi $pdf,
        float $x,
        float $y,
        string $text,
        string $family = 'Arial',
        string $style = '',
        float $size = 12.0,
        ?array $color = null,
    ): void {
        $this->font($pdf, $family, $style, $size, $color);

        if ($text === '') {
            return;
        }

        $pdf->SetXY($x, $y);
        $pdf->Write(0, Text::latin1($text));
    }

    public function cell(
        Fpdi $pdf,
        float $x,
        float $y,
        float $width,
        float $height,
        string $text,
        string $family = 'Arial',
        string $style = '',
        float $size = 12.0,
        string $align = 'L',
        string $border = '0',
        ?array $color = null,
    ): void {
        $this->font($pdf, $family, $style, $size, $color);

        $pdf->SetXY($x, $y);
        $pdf->Cell($width, $height, Text::latin1($text), $border, 0, $align, false);
    }

    public function multiCell(
        Fpdi $pdf,
        float $x,
        float $y,
        float $width,
        float $lineHeight,
        string $text,
        string $family = 'Arial',
        string $style = '',
        float $size = 12.0,
        string $align = 'L',
    ): void {
        $this->font($pdf, $family, $style, $size);

        $pdf->SetXY($x, $y);
        $pdf->MultiCell($width, $lineHeight, Text::latin1($text), 0, $align, false);
    }

    /**
     * The "X" the legacy screens wrote inside a pre-printed box.
     */
    public function check(
        Fpdi $pdf,
        float $x,
        float $y,
        string $family = 'Arial',
        string $style = 'B',
        float $size = 12.0,
        string $mark = 'X',
    ): void {
        $this->font($pdf, $family, $style, $size);
        $pdf->SetXY($x, $y);
        $pdf->Write(0, $mark);
    }

    public function font(Fpdi $pdf, string $family, string $style, float $size, ?array $color = null): void
    {
        if ($color !== null) {
            $pdf->SetTextColor(...$color);
        }

        $pdf->SetFont($family, $style, $size);
    }
}
