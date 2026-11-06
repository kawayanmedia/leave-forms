<?php

declare(strict_types=1);

namespace KawayanMedia\LeaveForms\Tests\Support;

use FPDF;
use KawayanMedia\LeaveForms\Config\FormLayout;

/**
 * Builds blank templates so the feature tests never depend on the scanned
 * forms that ship with the package.
 */
final class TemplateFactory
{
    /**
     * @return array<string, string> document key => path
     */
    public static function create(string $directory): array
    {
        if (!is_dir($directory)) {
            mkdir($directory, 0777, true);
        }

        $templates = [
            FormLayout::LEAVE_CERTIFICATION => ['certification4.pdf', 'P', 'Letter'],
            FormLayout::MONEY_VALUE => ['computation_money_value.pdf', 'P', 'Letter'],
            FormLayout::LEAVE_APPLICATION => ['application_for_leave.pdf', 'P', 'A4'],
            FormLayout::CTO_APPLICATION => ['app_for_offset.pdf', 'P', 'Legal'],
            FormLayout::STATEMENT_OF_LEAVE => ['statement_leave.pdf', 'P', 'Legal'],
            FormLayout::RETIREMENT_SCHEDULE => ['schedule_retirement_pay.pdf', 'L', 'Legal'],
        ];

        $paths = [];

        foreach ($templates as $key => [$file, $orientation, $size]) {
            $path = $directory . '/' . $file;
            self::blank($path, strtoupper(pathinfo($file, PATHINFO_FILENAME)) . ' TEMPLATE', $orientation, $size);
            $paths[$key] = $path;
        }

        return $paths;
    }

    public static function blank(string $path, string $label, string $orientation = 'P', string $size = 'Letter'): void
    {
        $pdf = new FPDF($orientation, 'mm', $size);
        $pdf->SetCompression(false);
        $pdf->AddPage();
        $pdf->SetFont('Arial', '', 10);
        $pdf->SetXY(10, 5);
        $pdf->Cell(0, 5, $label);
        $pdf->Output('F', $path);
    }

    public static function pageCount(string $pdf): int
    {
        preg_match_all('/\/Type\s*\/Page\b/', $pdf, $matches);

        return count($matches[0]);
    }
}
