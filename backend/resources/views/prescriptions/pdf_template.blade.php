<!DOCTYPE html>
<html dir="rtl" lang="ar">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>روشتة طبية</title>
    <style>
        body {
            font-family: 'DejaVu Sans', 'Arial', sans-serif;
            direction: rtl;
            text-align: right;
            margin: 0;
            padding: 0;
            color: #1e293b;
        }
        .header {
            border-bottom: 2px solid #0284c7;
            padding-bottom: 12px;
            margin-bottom: 15px;
        }
        .doctor-name {
            font-size: 20px;
            font-weight: bold;
            color: #0369a1;
        }
        .doctor-title {
            font-size: 13px;
            color: #64748b;
        }
        .clinic-info {
            font-size: 11px;
            color: #475569;
            margin-top: 4px;
        }
        .patient-bar {
            background-color: #f1f5f9;
            padding: 8px 12px;
            border-radius: 6px;
            font-size: 12px;
            margin-bottom: 15px;
        }
        .patient-bar table {
            width: 100%;
        }
        .rx-symbol {
            font-size: 26px;
            font-weight: bold;
            color: #0284c7;
            margin-bottom: 10px;
        }
        .drug-item {
            margin-bottom: 12px;
            border-bottom: 1px dashed #cbd5e1;
            padding-bottom: 8px;
        }
        .drug-name {
            font-size: 14px;
            font-weight: bold;
            color: #0f172a;
        }
        .drug-details {
            font-size: 12px;
            color: #334155;
            margin-top: 2px;
        }
        .section-title {
            font-size: 13px;
            font-weight: bold;
            color: #0369a1;
            margin-top: 15px;
            margin-bottom: 6px;
        }
        .lab-item {
            font-size: 12px;
            color: #334155;
            padding: 3px 0;
        }
        .footer {
            margin-top: 30px;
            border-top: 1px solid #e2e8f0;
            padding-top: 10px;
            font-size: 11px;
            color: #64748b;
            text-align: center;
        }
        .followup-box {
            background: #eff6ff;
            border: 1px solid #bfdbfe;
            padding: 6px;
            border-radius: 4px;
            display: inline-block;
            font-weight: bold;
            color: #1d4ed8;
            font-size: 11px;
        }
    </style>
</head>
<body>
    @if(!$settings['use_preprinted_letterhead'])
    <div class="header">
        <div class="doctor-name">{{ $tenant->name }}</div>
        <div class="doctor-title">{{ $tenant->title_ar }}</div>
        <div class="clinic-info">{{ $clinic->name_ar }} | {{ $clinic->address_ar }} | هاتف: {{ $clinic->phone }}</div>
    </div>
    @else
    <div style="height: 10px;"></div>
    @endif

    <div class="patient-bar">
        <table>
            <tr>
                <td style="width: 45%;"><strong>اسم المريض:</strong> {{ $patient->name }}</td>
                <td style="width: 25%;"><strong>السن:</strong> {{ $patient->birth_year ? (date('Y') - $patient->birth_year) . ' سنة' : '-' }}</td>
                <td style="width: 30%; text-align: left;"><strong>التاريخ:</strong> {{ $appointment->appointment_date->format('Y-m-d') }}</td>
            </tr>
            @if(!empty($appointment->vitals_data))
            <tr>
                <td colspan="3" style="padding-top: 6px; color: #475569;">
                    <strong>القياسات:</strong>
                    @foreach($appointment->vitals_data as $key => $val)
                        {{ $key }}: {{ $val }} |
                    @endforeach
                </td>
            </tr>
            @endif
        </table>
    </div>

    @if(!empty($consultation->diagnosis))
    <div style="margin-bottom: 12px; font-size: 12px;">
        <strong>التشخيص:</strong> {{ $consultation->diagnosis }}
    </div>
    @endif

    <div class="rx-symbol">℞</div>

    <div class="drugs-list">
        @if(!empty($consultation->prescribed_drugs))
            @foreach($consultation->prescribed_drugs as $drug)
                <div class="drug-item">
                    <div class="drug-name">{{ $drug['drug_name'] ?? '' }} ({{ $drug['form_type'] ?? 'علاج' }})</div>
                    <div class="drug-details">
                        @if(!empty($drug['dosage'])) <strong>الجرعة:</strong> {{ $drug['dosage'] }} @endif
                        @if(!empty($drug['duration'])) | <strong>المدة:</strong> {{ $drug['duration'] }} @endif
                        @if(!empty($drug['instructions'])) | <strong>التعليمات:</strong> {{ $drug['instructions'] }} @endif
                    </div>
                </div>
            @endforeach
        @endif
    </div>

    @if(!empty($consultation->requested_labs))
    <div class="section-title">الفحوصات والتحاليل المطلوبة:</div>
    <ul>
        @foreach($consultation->requested_labs as $test)
            <li class="lab-item">{{ $test['test_name'] ?? '' }} ({{ ($test['type'] ?? 'lab') === 'radiology' ? 'أشعة' : 'تحليل' }}) {{ !empty($test['notes']) ? '- ' . $test['notes'] : '' }}</li>
        @endforeach
    </ul>
    @endif

    @if($consultation->followup_date)
    <div style="margin-top: 15px;">
        <span class="followup-box">موعد الاستشارة القادمة: {{ $consultation->followup_date->format('Y-m-d') }}</span>
    </div>
    @endif

    <div class="footer">
        {{ $settings['footer_text'] ?? 'مع تمنياتنا لكم بالشفاء العاجل' }}
    </div>
</body>
</html>
