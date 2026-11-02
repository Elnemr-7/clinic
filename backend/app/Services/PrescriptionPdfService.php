<?php

namespace App\Services;

use App\Models\Consultation;
use App\Models\Tenant;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\View;
use Mpdf\Mpdf;

class PrescriptionPdfService
{
    public function generatePrescriptionPdf(Consultation $consultation): string
    {
        $tenant = $consultation->tenant;
        $clinic = $consultation->clinic;
        $patient = $consultation->patient;
        $appointment = $consultation->appointment;

        $settings = array_merge([
            'use_preprinted_letterhead' => false,
            'paper_size' => 'A5',
            'margin_top' => 45,
            'margin_bottom' => 25,
            'margin_left' => 15,
            'margin_right' => 15,
            'show_header_logo' => true,
            'footer_text' => 'مع تمنياتنا لكم بالشفاء العاجل',
        ], $tenant->print_settings ?? []);

        $isPreprinted = !empty($settings['use_preprinted_letterhead']);
        $topMargin = (float)($settings['margin_top'] ?? $settings['top_margin_mm'] ?? ($isPreprinted ? 45 : 15));
        $bottomMargin = (float)($settings['margin_bottom'] ?? $settings['bottom_margin_mm'] ?? 20);
        $leftMargin = (float)($settings['margin_left'] ?? $settings['left_margin_mm'] ?? 15);
        $rightMargin = (float)($settings['margin_right'] ?? $settings['right_margin_mm'] ?? 15);
        $paperSize = strtoupper($settings['paper_size'] ?? 'A5');

        $html = View::make('prescriptions.pdf_template', [
            'consultation' => $consultation,
            'tenant' => $tenant,
            'clinic' => $clinic,
            'patient' => $patient,
            'appointment' => $appointment,
            'settings' => $settings,
        ])->render();

        $storageDir = storage_path('app/public/prescriptions');
        if (!File::exists($storageDir)) {
            File::makeDirectory($storageDir, 0755, true);
        }

        $filePath = "{$storageDir}/{$consultation->pdf_short_token}.pdf";

        try {
            $mpdf = new Mpdf([
                'mode' => 'utf-8',
                'format' => $paperSize,
                'margin_top' => $topMargin,
                'margin_bottom' => $bottomMargin,
                'margin_left' => $leftMargin,
                'margin_right' => $rightMargin,
                'autoScriptToLang' => true,
                'autoLangToFont' => true,
                'setAutoTopMargin' => $isPreprinted ? false : 'stretch',
                'setAutoBottomMargin' => $isPreprinted ? false : 'stretch',
            ]);

            $mpdf->SetDirectionality('rtl');
            $mpdf->WriteHTML($html);
            $mpdf->Output($filePath, 'F');
        } catch (\Throwable $e) {
            // Fallback for development if mPDF has system issues: save HTML as printable
            file_put_contents($filePath, $html);
        }

        return $filePath;
    }

    public function generateTestMarginsPdf(Tenant $tenant, array $margins): string
    {
        $storageDir = storage_path('app/public/prescriptions');
        if (!File::exists($storageDir)) {
            File::makeDirectory($storageDir, 0755, true);
        }

        $filePath = "{$storageDir}/test_margins_{$tenant->id}.pdf";

        $top = (float)($margins['margin_top'] ?? $margins['top_margin_mm'] ?? 45);
        $bottom = (float)($margins['margin_bottom'] ?? $margins['bottom_margin_mm'] ?? 25);
        $left = (float)($margins['margin_left'] ?? $margins['left_margin_mm'] ?? 15);
        $right = (float)($margins['margin_right'] ?? $margins['right_margin_mm'] ?? 15);
        $size = strtoupper($margins['paper_size'] ?? 'A5');

        $html = '<div style="border: 2px dashed #0f766e; padding: 20px; text-align: center; direction: rtl; font-family: sans-serif;">'
              . '<h2 style="color: #0f766e;">ورقة اختبار قياسات وهوامش الطباعة</h2>'
              . '<p><strong>الهامش العلوي:</strong> ' . $top . ' مم</p>'
              . '<p><strong>الهامش السفلي:</strong> ' . $bottom . ' مم</p>'
              . '<p><strong>الهامش الأيمن:</strong> ' . $right . ' مم | <strong>الهامش الأيسر:</strong> ' . $left . ' مم</p>'
              . '<p><strong>حجم الورق:</strong> ' . $size . '</p>'
              . '</div>';

        try {
            $mpdf = new Mpdf([
                'mode' => 'utf-8',
                'format' => $size,
                'margin_top' => $top,
                'margin_bottom' => $bottom,
                'margin_left' => $left,
                'margin_right' => $right,
                'setAutoTopMargin' => false,
                'setAutoBottomMargin' => false,
            ]);

            $mpdf->SetDirectionality('rtl');
            $mpdf->WriteHTML($html);
            $mpdf->Output($filePath, 'F');
        } catch (\Throwable $e) {
            file_put_contents($filePath, $html);
        }

        return $filePath;
    }
}
