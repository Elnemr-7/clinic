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
            'top_margin_mm' => 45,
            'bottom_margin_mm' => 30,
            'left_margin_mm' => 15,
            'right_margin_mm' => 15,
            'show_header_logo' => true,
            'footer_text' => 'مع تمنياتنا لكم بالشفاء العاجل',
        ], $tenant->print_settings ?? []);

        $topMargin = $settings['use_preprinted_letterhead'] ? ($settings['top_margin_mm'] ?? 45) : 15;
        $bottomMargin = $settings['bottom_margin_mm'] ?? 20;
        $leftMargin = $settings['left_margin_mm'] ?? 15;
        $rightMargin = $settings['right_margin_mm'] ?? 15;
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

        $html = '<div style="border: 2px dashed #0284c7; padding: 20px; text-align: center; direction: rtl;">'
              . '<h2>صفحة اختبار الهوامش والمقاسات</h2>'
              . '<p>الهامش العلوي: ' . ($margins['top_margin_mm'] ?? 45) . ' مم</p>'
              . '<p>الهامش السفلي: ' . ($margins['bottom_margin_mm'] ?? 30) . ' مم</p>'
              . '<p>حجم الورق: ' . ($margins['paper_size'] ?? 'A5') . '</p>'
              . '</div>';

        try {
            $mpdf = new Mpdf([
                'mode' => 'utf-8',
                'format' => strtoupper($margins['paper_size'] ?? 'A5'),
                'margin_top' => (int)($margins['top_margin_mm'] ?? 45),
                'margin_bottom' => (int)($margins['bottom_margin_mm'] ?? 30),
                'margin_left' => (int)($margins['left_margin_mm'] ?? 15),
                'margin_right' => (int)($margins['right_margin_mm'] ?? 15),
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
