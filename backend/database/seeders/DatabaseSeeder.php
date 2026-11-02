<?php

namespace Database\Seeders;

use App\Models\Appointment;
use App\Models\Clinic;
use App\Models\ClinicShift;
use App\Models\Consultation;
use App\Models\DoctorDrug;
use App\Models\DoctorLabTest;
use App\Models\DoctorLead;
use App\Models\Patient;
use App\Models\Plan;
use App\Models\PrescriptionTemplate;
use App\Models\SuperAdmin;
use App\Models\Tenant;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Super Admin Account
        SuperAdmin::create([
            'name' => 'المدير العام للمنصة',
            'email' => 'admin@platform.com',
            'password' => Hash::make('password123'),
        ]);

        // 2. Subscription Plans
        $planBasic = Plan::create([
            'name_ar' => 'الباقة الأساسية',
            'price_monthly' => 300,
            'price_yearly' => 3000,
            'max_clinics' => 1,
            'free_whatsapp_quota_monthly' => 50,
            'free_sms_quota_monthly' => 50,
            'allow_custom_domain' => false,
            'allow_custom_website' => false,
            'is_active' => true,
        ]);

        $planAdvanced = Plan::create([
            'name_ar' => 'الباقة المتقدمة',
            'price_monthly' => 600,
            'price_yearly' => 6000,
            'max_clinics' => 3,
            'free_whatsapp_quota_monthly' => 150,
            'free_sms_quota_monthly' => 100,
            'allow_custom_domain' => true,
            'allow_custom_website' => false,
            'is_active' => true,
        ]);

        $planVip = Plan::create([
            'name_ar' => 'باقة VIP الشاملة',
            'price_monthly' => 1200,
            'price_yearly' => 12000,
            'max_clinics' => -1, // Unlimited
            'free_whatsapp_quota_monthly' => 500,
            'free_sms_quota_monthly' => 250,
            'allow_custom_domain' => true,
            'allow_custom_website' => true,
            'is_active' => true,
        ]);

        // 3. Demo Doctor Account (Subscribed to VIP Plan)
        $tenant = Tenant::create([
            'name' => 'د. أحمد طارق المنشاوي',
            'specialty_key' => 'general',
            'title_ar' => 'استشاري أمراض الباطنة والجهاز الهضمي والمناظير',
            'plan_id' => $planVip->id,
            'subscription_ends_at' => now()->addYear(),
            'status' => 'active',
            'max_clinics_override' => -1,
            'whatsapp_enabled' => true,
            'whatsapp_balance' => 500,
            'whatsapp_used_this_month' => 45,
            'whatsapp_session_status' => 'connected',
            'whatsapp_phone_number' => '01012345678',
            'sms_enabled' => true,
            'sms_balance' => 250,
            'sms_used_this_month' => 20,
            'subdomain' => 'dr-ahmed',
            'custom_domain' => null,
            'template_key' => 'general',
            'is_custom_standalone_website' => false,
            'public_booking_api_key' => 'pbk_demo_doctor_vip_key_2026',
            'website_settings' => [
                'primary_color' => '#0284c7',
                'secondary_color' => '#0f172a',
                'about_text' => 'خبرة أكثر من 15 عاماً في تشخيص وعلاج أمراض الباطنة والجهاز الهضمي والكبد، وعضو الجمعية المصرية لأمراض الجهاز الهضمي.',
                'services_list' => [
                    ['title' => 'مناظير الجهاز الهضمي التشخيصية والعلاجية', 'icon' => 'activity'],
                    ['title' => 'علاج ومتابعة متلازمة القولون العصبي', 'icon' => 'shield'],
                    ['title' => 'تشخيص ومتابعة أمراض الكبد الفيروسية والدهنية', 'icon' => 'heart'],
                    ['title' => 'متابعة السكري وضغط الدم والمضاعفات الباطنية', 'icon' => 'clipboard'],
                ],
            ],
            'vitals_config' => [
                ['key' => 'weight', 'label' => 'الوزن', 'unit' => 'كجم', 'enabled' => true],
                ['key' => 'bp', 'label' => 'ضغط الدم', 'unit' => 'mmHg', 'enabled' => true],
                ['key' => 'sugar', 'label' => 'السكر العشوائي', 'unit' => 'mg/dL', 'enabled' => true],
                ['key' => 'temp', 'label' => 'الحرارة', 'unit' => '°C', 'enabled' => true],
            ],
            'print_settings' => [
                'use_preprinted_letterhead' => true,
                'paper_size' => 'A5',
                'top_margin_mm' => 45,
                'bottom_margin_mm' => 30,
                'left_margin_mm' => 15,
                'right_margin_mm' => 15,
                'show_header_logo' => true,
                'footer_text' => 'العيادة: مدينة نصر - هاتف: 01012345678 | المهندسين - هاتف: 01123456789',
            ],
            'notification_settings' => [
                'send_booking_confirmation_sms' => true,
                'send_near_turn_alert' => true,
                'near_turn_threshold' => 3,
                'send_followup_reminder' => true,
            ],
        ]);

        // Doctor User
        User::create([
            'tenant_id' => $tenant->id,
            'clinic_id' => null, // sees all branches
            'name' => 'د. أحمد طارق المنشاوي',
            'email' => 'doctor@clinic.com',
            'phone' => '01012345678',
            'password' => Hash::make('password123'),
            'role' => 'doctor',
            'is_active' => true,
        ]);

        // 4. Two Clinic Branches
        // Branch 1: Nasr City
        $branchNasr = Clinic::create([
            'tenant_id' => $tenant->id,
            'name_ar' => 'عيادة مدينة نصر',
            'address_ar' => '15 شارع عباس العقاد، الدور الرابع، أعلى بنك مصر',
            'city_ar' => 'القاهرة',
            'phone' => '01012345678',
            'google_maps_url' => 'https://maps.google.com',
            'consultation_price' => 300,
            'followup_price' => 50,
            'followup_days_limit' => 10,
            'avg_consultation_minutes' => 15,
            'booking_payment_mode' => 'pay_at_clinic',
            'tv_screen_code' => 'TV1001',
            'is_active' => true,
        ]);

        // Shifts for Nasr City (All days morning & evening)
        for ($day = 0; $day <= 6; $day++) {
            ClinicShift::create([
                'tenant_id' => $tenant->id,
                'clinic_id' => $branchNasr->id,
                'day_of_week' => $day,
                'shift_name_ar' => 'الفترة الصباحية',
                'start_time' => '12:00:00',
                'end_time' => '15:00:00',
                'max_patients' => 15,
                'is_active' => true,
            ]);

            ClinicShift::create([
                'tenant_id' => $tenant->id,
                'clinic_id' => $branchNasr->id,
                'day_of_week' => $day,
                'shift_name_ar' => 'الفترة المسائية',
                'start_time' => '17:00:00',
                'end_time' => '22:00:00',
                'max_patients' => 25,
                'is_active' => true,
            ]);
        }

        // Branch 2: Mohandessin
        $branchMohandessin = Clinic::create([
            'tenant_id' => $tenant->id,
            'name_ar' => 'عيادة المهندسين',
            'address_ar' => '42 شارع جامعة الدول العربية، الدور السادس',
            'city_ar' => 'الجيزة',
            'phone' => '01123456789',
            'google_maps_url' => 'https://maps.google.com',
            'consultation_price' => 400,
            'followup_price' => 0,
            'followup_days_limit' => 14,
            'avg_consultation_minutes' => 20,
            'booking_payment_mode' => 'require_prepayment',
            'prepayment_instructions_ar' => 'يرجى تحويل قيمة الكشف (400 ج.م) عبر إنستاباي على حساب clinic@instapay وإرسال صورة التحويل عبر واتساب العيادة لتأكيد الحجز.',
            'tv_screen_code' => 'TV2002',
            'is_active' => true,
        ]);

        for ($day = 0; $day <= 6; $day++) {
            ClinicShift::create([
                'tenant_id' => $tenant->id,
                'clinic_id' => $branchMohandessin->id,
                'day_of_week' => $day,
                'shift_name_ar' => 'الفترة المسائية',
                'start_time' => '18:00:00',
                'end_time' => '23:00:00',
                'max_patients' => 20,
                'is_active' => true,
            ]);
        }

        // 5. Receptionist Accounts
        User::create([
            'tenant_id' => $tenant->id,
            'clinic_id' => $branchNasr->id,
            'name' => 'سارة محمود (استقبال مدينة نصر)',
            'email' => 'reception1@clinic.com',
            'phone' => '01099887766',
            'password' => Hash::make('password123'),
            'role' => 'receptionist',
            'is_active' => true,
        ]);

        User::create([
            'tenant_id' => $tenant->id,
            'clinic_id' => $branchMohandessin->id,
            'name' => 'مريم علي (استقبال المهندسين)',
            'email' => 'reception2@clinic.com',
            'phone' => '01188776655',
            'password' => Hash::make('password123'),
            'role' => 'receptionist',
            'is_active' => true,
        ]);

        // Doctor catalog seeds
        DoctorDrug::create([
            'tenant_id' => $tenant->id,
            'drug_name' => 'كونترولوك 40 مجم (Controloc 40mg)',
            'form_type' => 'أقراص',
            'default_dosage' => 'قرص واحد يومياً قبل الإفطار بنصف ساعة',
            'default_duration' => 'لمدة 4 أسابيع',
        ]);
        DoctorDrug::create([
            'tenant_id' => $tenant->id,
            'drug_name' => 'كولوفيرين د (Coloverin D)',
            'form_type' => 'أقراص',
            'default_dosage' => 'قرص قبل الوجبات بـ 20 دقيقة 3 مرات يومياً',
            'default_duration' => 'لمدة أسبوعين',
        ]);
        DoctorDrug::create([
            'tenant_id' => $tenant->id,
            'drug_name' => 'دوسباتالين ريتارد 200 (Duspatalin Retard)',
            'form_type' => 'كبسولات',
            'default_dosage' => 'كبسولة كل 12 ساعة قبل الأكل',
            'default_duration' => 'لمدة 10 أيام',
        ]);

        DoctorLabTest::create(['tenant_id' => $tenant->id, 'test_name' => 'صورة دم كاملة (CBC)', 'type' => 'lab']);
        DoctorLabTest::create(['tenant_id' => $tenant->id, 'test_name' => 'وظائف كبد (SGOT, SGPT)', 'type' => 'lab']);
        DoctorLabTest::create(['tenant_id' => $tenant->id, 'test_name' => 'جرثومة المعدة في البراز (H. Pylori Stool Ag)', 'type' => 'lab']);
        DoctorLabTest::create(['tenant_id' => $tenant->id, 'test_name' => 'سونار على البطن والحوض (Pelvi-Abdominal Ultrasound)', 'type' => 'radiology']);

        PrescriptionTemplate::create([
            'tenant_id' => $tenant->id,
            'template_name' => 'بروتوكول التهاب وقرحة المعدة والارتجاع (GERD)',
            'diagnosis_default' => 'ارتجاع مريئي حاد والتهاب بجدار المعدة',
            'drugs_json' => [
                ['drug_name' => 'كونترولوك 40 مجم (Controloc 40mg)', 'dosage' => 'قرص قبل الإفطار بنصف ساعة', 'duration' => 'شهر', 'instructions' => 'على معدة فارغة'],
                ['drug_name' => 'جافيسكون شراب (Gaviscon Syrup)', 'dosage' => 'ملعقة كبيرة بعد الوجبات وقبل النوم', 'duration' => 'أسبوعين', 'instructions' => 'عند اللزوم'],
            ],
        ]);

        // 6. 10 Realistic Demo Patients & Today's Live Queue in Branch 1 (Evening Shift)
        $todayShift = $branchNasr->shifts()->where('day_of_week', Carbon::today()->dayOfWeek)->where('shift_name_ar', 'الفترة المسائية')->first()
            ?? $branchNasr->shifts()->first();

        // Patients 1 & 2: completed (5 days ago had visit, testing followup eligibility)
        $p1 = Patient::create([
            'tenant_id' => $tenant->id,
            'name' => 'محمود كمال عبد الرحمن',
            'phone' => '01011122233',
            'gender' => 'male',
            'birth_year' => 1985,
            'address' => 'مدينة نصر - الحي السابع',
            'chronic_diseases' => 'الضغط المرتفع',
        ]);
        $apt1Past = Appointment::create([
            'tenant_id' => $tenant->id,
            'clinic_id' => $branchNasr->id,
            'shift_id' => $todayShift->id,
            'patient_id' => $p1->id,
            'appointment_date' => Carbon::today()->subDays(5),
            'queue_number' => 1,
            'reference_code' => 'CLN-1001',
            'visit_type' => 'new_visit',
            'status' => 'completed',
            'consultation_fee' => 300,
            'total_amount' => 300,
            'is_paid' => true,
        ]);
        Consultation::create([
            'tenant_id' => $tenant->id,
            'clinic_id' => $branchNasr->id,
            'appointment_id' => $apt1Past->id,
            'patient_id' => $p1->id,
            'diagnosis' => 'التهاب المعدة المزمن وعسر الهضم',
            'prescribed_drugs' => [
                ['drug_name' => 'كونترولوك 40 مجم', 'dosage' => 'قرص يومياً', 'duration' => '14 يوم'],
            ],
            'pdf_short_token' => 'RX1001',
        ]);

        $p2 = Patient::create([
            'tenant_id' => $tenant->id,
            'name' => 'فاطمة إبراهيم حسن',
            'phone' => '01022233344',
            'gender' => 'female',
            'birth_year' => 1992,
            'address' => 'مصر الجديدة - ميدان الحجاز',
        ]);
        $apt2 = Appointment::create([
            'tenant_id' => $tenant->id,
            'clinic_id' => $branchNasr->id,
            'shift_id' => $todayShift->id,
            'patient_id' => $p2->id,
            'appointment_date' => Carbon::today(),
            'queue_number' => 1,
            'reference_code' => 'CLN-2001',
            'visit_type' => 'new_visit',
            'status' => 'completed',
            'estimated_window_start' => '17:00:00',
            'estimated_window_end' => '17:30:00',
            'actual_entered_at' => Carbon::today()->setTime(17, 0),
            'actual_completed_at' => Carbon::today()->setTime(17, 18),
            'consultation_fee' => 300,
            'total_amount' => 300,
            'is_paid' => true,
        ]);
        Consultation::create([
            'tenant_id' => $tenant->id,
            'clinic_id' => $branchNasr->id,
            'appointment_id' => $apt2->id,
            'patient_id' => $p2->id,
            'diagnosis' => 'ارتجاع مريئي متوسط الدرجة',
            'prescribed_drugs' => [
                ['drug_name' => 'كولوفيرين د', 'dosage' => 'قرص 3 مرات', 'duration' => 'أسبوعين'],
            ],
            'pdf_short_token' => 'RX2002',
        ]);

        $apt1Today = Appointment::create([
            'tenant_id' => $tenant->id,
            'clinic_id' => $branchNasr->id,
            'shift_id' => $todayShift->id,
            'patient_id' => $p1->id,
            'appointment_date' => Carbon::today(),
            'queue_number' => 2,
            'reference_code' => 'CLN-2002',
            'visit_type' => 'followup',
            'status' => 'completed',
            'estimated_window_start' => '17:15:00',
            'estimated_window_end' => '17:45:00',
            'actual_entered_at' => Carbon::today()->setTime(17, 20),
            'actual_completed_at' => Carbon::today()->setTime(17, 35),
            'consultation_fee' => 50,
            'total_amount' => 50,
            'is_paid' => true,
        ]);

        // Patient 3: in_consultation (inside the doctor room now with red alert banner)
        $p3 = Patient::create([
            'tenant_id' => $tenant->id,
            'name' => 'طارق مصطفى الباز',
            'phone' => '01033344455',
            'gender' => 'male',
            'birth_year' => 1970,
            'address' => 'مدينة نصر - المنطقة الأولى',
            'chronic_diseases' => 'مريض سكري من النوع الثاني وضغط دم مرتفع',
            'drug_allergies' => 'حساسية شديدة من مشتقات البنسلين والمضادات الحيوية',
        ]);
        Appointment::create([
            'tenant_id' => $tenant->id,
            'clinic_id' => $branchNasr->id,
            'shift_id' => $todayShift->id,
            'patient_id' => $p3->id,
            'appointment_date' => Carbon::today(),
            'queue_number' => 3,
            'reference_code' => 'CLN-2003',
            'visit_type' => 'new_visit',
            'status' => 'in_consultation',
            'estimated_window_start' => '17:35:00',
            'estimated_window_end' => '18:05:00',
            'actual_entered_at' => Carbon::today()->setTime(17, 36),
            'vitals_data' => [
                'weight' => '84',
                'bp' => '140/90',
                'sugar' => '175',
                'temp' => '36.8',
            ],
            'consultation_fee' => 300,
            'total_amount' => 300,
            'is_paid' => true,
            'whatsapp_opted_in' => true,
        ]);

        // Patients 4, 5, 6: arrived_waiting (in waiting room)
        $p4 = Patient::create(['tenant_id' => $tenant->id, 'name' => 'ياسر محمد الشافعي', 'phone' => '01044455566', 'gender' => 'male', 'birth_year' => 1988]);
        Appointment::create([
            'tenant_id' => $tenant->id,
            'clinic_id' => $branchNasr->id,
            'shift_id' => $todayShift->id,
            'patient_id' => $p4->id,
            'appointment_date' => Carbon::today(),
            'queue_number' => 4,
            'reference_code' => 'CLN-2004',
            'visit_type' => 'new_visit',
            'status' => 'arrived_waiting',
            'estimated_window_start' => '17:50:00',
            'estimated_window_end' => '18:20:00',
            'vitals_data' => ['weight' => '76', 'bp' => '120/80', 'sugar' => '110', 'temp' => '37.0'],
            'consultation_fee' => 300,
            'total_amount' => 300,
            'is_paid' => true,
            'whatsapp_opted_in' => true,
        ]);

        $p5 = Patient::create(['tenant_id' => $tenant->id, 'name' => 'نور الهدى سامي', 'phone' => '01055566677', 'gender' => 'female', 'birth_year' => 1995]);
        Appointment::create([
            'tenant_id' => $tenant->id,
            'clinic_id' => $branchNasr->id,
            'shift_id' => $todayShift->id,
            'patient_id' => $p5->id,
            'appointment_date' => Carbon::today(),
            'queue_number' => 5,
            'reference_code' => 'CLN-2005',
            'visit_type' => 'new_visit',
            'status' => 'arrived_waiting',
            'estimated_window_start' => '18:05:00',
            'estimated_window_end' => '18:35:00',
            'vitals_data' => ['weight' => '62', 'bp' => '115/75', 'sugar' => '98', 'temp' => '36.7'],
            'consultation_fee' => 300,
            'total_amount' => 300,
            'is_paid' => true,
            'whatsapp_opted_in' => true,
        ]);

        $p6 = Patient::create(['tenant_id' => $tenant->id, 'name' => 'خالد صلاح الدين', 'phone' => '01066677788', 'gender' => 'male', 'birth_year' => 1980]);
        Appointment::create([
            'tenant_id' => $tenant->id,
            'clinic_id' => $branchNasr->id,
            'shift_id' => $todayShift->id,
            'patient_id' => $p6->id,
            'appointment_date' => Carbon::today(),
            'queue_number' => 6,
            'reference_code' => 'CLN-2006',
            'visit_type' => 'new_visit',
            'status' => 'arrived_waiting',
            'estimated_window_start' => '18:20:00',
            'estimated_window_end' => '18:50:00',
            'vitals_data' => ['weight' => '89', 'bp' => '130/85', 'sugar' => '135', 'temp' => '36.9'],
            'consultation_fee' => 300,
            'total_amount' => 300,
            'is_paid' => true,
        ]);

        // Patient 7: postponed_waiting_arrival
        $p7 = Patient::create(['tenant_id' => $tenant->id, 'name' => 'عمر عبد العزيز سليم', 'phone' => '01077788899', 'gender' => 'male', 'birth_year' => 1978]);
        Appointment::create([
            'tenant_id' => $tenant->id,
            'clinic_id' => $branchNasr->id,
            'shift_id' => $todayShift->id,
            'patient_id' => $p7->id,
            'appointment_date' => Carbon::today(),
            'queue_number' => 7,
            'reference_code' => 'CLN-2007',
            'visit_type' => 'new_visit',
            'status' => 'postponed_waiting_arrival',
            'estimated_window_start' => '18:35:00',
            'estimated_window_end' => '19:05:00',
            'consultation_fee' => 300,
            'total_amount' => 300,
            'is_paid' => false,
        ]);

        // Patients 8, 9, 10: booked (online bookings waiting to arrive)
        $p8 = Patient::create(['tenant_id' => $tenant->id, 'name' => 'هدى فاروق الديب', 'phone' => '01088899900', 'gender' => 'female', 'birth_year' => 1990]);
        Appointment::create([
            'tenant_id' => $tenant->id,
            'clinic_id' => $branchNasr->id,
            'shift_id' => $todayShift->id,
            'patient_id' => $p8->id,
            'appointment_date' => Carbon::today(),
            'queue_number' => 8,
            'reference_code' => 'CLN-2008',
            'visit_type' => 'new_visit',
            'status' => 'booked',
            'estimated_window_start' => '18:50:00',
            'estimated_window_end' => '19:20:00',
            'consultation_fee' => 300,
            'total_amount' => 300,
            'is_paid' => false,
        ]);

        $p9 = Patient::create(['tenant_id' => $tenant->id, 'name' => 'أمير حسني جاد', 'phone' => '01099900011', 'gender' => 'male', 'birth_year' => 1983]);
        Appointment::create([
            'tenant_id' => $tenant->id,
            'clinic_id' => $branchNasr->id,
            'shift_id' => $todayShift->id,
            'patient_id' => $p9->id,
            'appointment_date' => Carbon::today(),
            'queue_number' => 9,
            'reference_code' => 'CLN-2009',
            'visit_type' => 'new_visit',
            'status' => 'booked',
            'estimated_window_start' => '19:05:00',
            'estimated_window_end' => '19:35:00',
            'consultation_fee' => 300,
            'total_amount' => 300,
            'is_paid' => false,
        ]);

        $p10 = Patient::create(['tenant_id' => $tenant->id, 'name' => 'رانيا سعيد منصور', 'phone' => '01000011122', 'gender' => 'female', 'birth_year' => 1998]);
        Appointment::create([
            'tenant_id' => $tenant->id,
            'clinic_id' => $branchNasr->id,
            'shift_id' => $todayShift->id,
            'patient_id' => $p10->id,
            'appointment_date' => Carbon::today(),
            'queue_number' => 10,
            'reference_code' => 'CLN-2010',
            'visit_type' => 'new_visit',
            'status' => 'booked',
            'estimated_window_start' => '19:20:00',
            'estimated_window_end' => '19:50:00',
            'consultation_fee' => 300,
            'total_amount' => 300,
            'is_paid' => false,
        ]);

        // 7. Doctor Leads in doctor_leads
        DoctorLead::create([
            'doctor_name' => 'د. حسام عادل الدسوقي',
            'phone' => '01065432109',
            'specialty' => 'طب وجراحة العيون',
            'requested_plan_id' => $planAdvanced->id,
            'notes' => 'يرغب في معرفة إمكانية ربط جهاز فحص قاع العين ونظام التنبيهات عبر الواتساب',
            'status' => 'new',
        ]);

        DoctorLead::create([
            'doctor_name' => 'د. نورهان مجدي الشناوي',
            'phone' => '01234567890',
            'specialty' => 'طب وجراحة الأسنان وتجميل الابتسامة',
            'requested_plan_id' => $planVip->id,
            'notes' => 'لديها فرعان في التجمع والشيخ زايد وترغب في باقة VIP مع الدومين المخصص',
            'status' => 'contacted',
        ]);
    }
}
