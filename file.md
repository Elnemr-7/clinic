# CLINIC MANAGEMENT & DOCTOR BOOKING SaaS PLATFORM
## Final Patched Master Architecture & Implementation Blueprint

---

## SECTION 0: 10 MANDATORY ARCHITECTURAL CORRECTIONS INTEGRATED

1. **Decoupled Modal Saves from Consultation Completion (`DoctorWorkspaceController`)**:
   - `savePrescription()`: Saves diagnosis, symptoms, private notes, auto-saves unknown drugs to `doctor_drugs`, generates PDF, keeps `appointment.status = in_consultation`.
   - `saveLabsAndNotes()`: Auto-saves unknown lab tests to `doctor_lab_tests`, updates `consultations.requested_labs`, keeps patient in room.
   - `saveExtraServicesAndFollowup()`: Updates `consultations.selected_services`, updates `appointments.extra_services_total` and `total_amount`, sets `followup_date`, keeps patient in room.
   - `completeAndCallNextPatient()`: Triggered exclusively by the giant bottom green button. Sets status `completed`, auto-books follow-up appointment if date was set, advances next `arrived_waiting` patient to `in_consultation`, triggers `CheckNearTurnNotificationsJob`, recalculates queue times, and broadcasts `QueueUpdatedEvent`.

2. **Fixed Queue Recalculation Math for Postponed/Cancelled Slots (`QueueTimeCalculatorService`)**:
   - Queries all remaining active appointments (`whereIn('status', ['arrived_waiting', 'postponed_waiting_arrival', 'booked'])`) ordered by priority: `CASE WHEN status = 'arrived_waiting' THEN 0 ELSE 1 END, queue_number ASC`.
   - Calculates time windows using 0-based iteration index `$index`:
     `$slotsAhead = $index + 1;`
     `$itemStart = $baseTime->copy()->addMinutes($slotsAhead * $clinic->avg_consultation_minutes);`
     `$itemEnd = $itemStart->copy()->addMinutes(30);`

3. **Active Dispatch of `CheckNearTurnNotificationsJob` ("اقترب دورك")**:
   - Dispatched automatically inside `completeAndCallNextPatient()` and `ReceptionController::updateStatus()`.
   - Evaluates remaining queue: for any patient at index `< $tenant->notification_settings['near_turn_threshold']` (default `3`) with `near_turn_notified == false`:
     - Dispatches WhatsApp message (if `whatsapp_opted_in == true`) or SMS.
     - Marks `near_turn_notified = true`.

4. **Reception Extra Services Collection (`ReceptionController::collectExtraServices`)**:
   - Adds dedicated endpoint `collectExtraServices(int $appointmentId)` to log payment for procedures added by the doctor during consultation into `cash_transactions` (`category = medical_service`).
   - Renders a prominent badge in `LiveQueueTable.vue`: `💰 تحصيل خدمات إضافية ({extra_services_total} ج.م)`.

5. **Subscription Plan `max_clinics` Enforcement (`SettingsController::saveBranch`)**:
   - Evaluates `$allowed = $tenant->max_clinics_override ?? $tenant->plan->max_clinics`.
   - If `$allowed !== -1 && $tenant->clinics()->count() >= $allowed`: rejects with HTTP `403` and Arabic message indicating plan limit reached.

6. **Public Doctor Lead Capture Endpoint (`PublicPlanController::submitLead`)**:
   - Endpoint `POST /api/public/leads` receives submissions from the platform landing page `LeadForm.vue`.
   - Validates `doctor_name`, `phone`, `specialty`, `requested_plan_id`, and `notes`, creating a row in `doctor_leads` with `status = new`.

7. **Persistent WhatsApp Session Recovery & Anti-Ban Human Delay (`BaileysManager.js` & `OutboundHandler.js`)**:
   - `BaileysManager.restoreAllSessions()` executes on `server.js` boot, iterating `auth_info_multi/tenant_*` folders to reconnect sockets automatically.
   - `OutboundHandler.js` simulates human typing by sending `sendPresenceUpdate('composing', jid)` and awaiting a random delay between `1500ms` and `3000ms` before calling `socket.sendMessage()`.

8. **Compiled Tauri Kiosk URL Resolution (`src-tauri/src/main.rs`)**:
   - Uses `tauri::WebviewUrl::App(format!("index.html#/tv/{}", tv_code).into())` with `WebviewWindowBuilder` to support development and packaged Windows `.exe` releases without requiring an external HTTP server.

9. **Super Admin Impersonation Handshake (`/doctor-dashboard/src/router/index.js`)**:
   - Captures `?impersonate_token=` in route `beforeEach`, saves token to `authStore`, fetches `/api/clinic/me`, strips the parameter from URL, and renders a persistent yellow support banner.

10. **Emergency Day-Off Bulk Reschedule/Cancel Engine (`SettingsController::saveDayOff`)**:
    - Receives `date`, `reason_ar`, and `action` (`cancel_and_notify` or `move_to_date`).
    - Updates affected appointments and dispatches bulk WhatsApp/SMS notifications with apology and updated booking details.

---

## SECTION A: `/backend` (Laravel 11) Architecture, Files & Logic

### 1. Directory Tree & Exact File Paths

```text
/backend
├── app/
│   ├── Enums/
│   │   ├── AppointmentStatus.php
│   │   ├── BookingPaymentMode.php
│   │   ├── BookingSource.php
│   │   ├── CashCategory.php
│   │   ├── CashType.php
│   │   ├── LabTestType.php
│   │   ├── MessageChannel.php
│   │   ├── SpecialtyKey.php
│   │   ├── TenantStatus.php
│   │   ├── UserRole.php
│   │   └── VisitType.php
│   ├── Events/
│   │   ├── ConsultationFinishedEvent.php
│   │   ├── PatientNearTurnEvent.php
│   │   └── QueueUpdatedEvent.php
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Clinic/
│   │   │   │   ├── AuthController.php
│   │   │   │   ├── DoctorWorkspaceController.php
│   │   │   │   ├── ReceptionController.php
│   │   │   │   └── SettingsController.php
│   │   │   ├── Internal/
│   │   │   │   ├── FawryWebhookController.php
│   │   │   │   └── WhatsAppWebhookController.php
│   │   │   ├── Public/
│   │   │   │   ├── PublicBookingController.php
│   │   │   │   └── PublicPlanController.php
│   │   │   └── SuperAdmin/
│   │   │       ├── AuthController.php
│   │   │       ├── DashboardController.php
│   │   │       ├── DoctorLeadController.php
│   │   │       ├── PlanController.php
│   │   │       ├── TenantController.php
│   │   │       └── TopupController.php
│   │   ├── Middleware/
│   │   │   ├── CheckTenantActive.php
│   │   │   ├── ClinicAuth.php
│   │   │   ├── ClinicBranchScope.php
│   │   │   ├── InternalWebhookAuth.php
│   │   │   ├── PublicApiKeyAuth.php
│   │   │   └── SuperAdminAuth.php
│   │   └── Requests/
│   │       ├── BookAppointmentRequest.php
│   │       ├── ClinicLoginRequest.php
│   │       ├── ConsultationSaveRequest.php
│   │       ├── LeadSubmitRequest.php
│   │       ├── QuickBookingRequest.php
│   │       ├── SuperAdminLoginRequest.php
│   │       ├── TenantStoreRequest.php
│   │       └── TenantUpdateRequest.php
│   ├── Jobs/
│   │   ├── CheckNearTurnNotificationsJob.php
│   │   ├── SendSmsNotificationJob.php
│   │   └── SendWhatsAppPrescriptionJob.php
│   ├── Models/
│   │   ├── Appointment.php
│   │   ├── CashTransaction.php
│   │   ├── Clinic.php
│   │   ├── ClinicDayOff.php
│   │   ├── ClinicService.php
│   │   ├── ClinicShift.php
│   │   ├── Consultation.php
│   │   ├── DoctorDrug.php
│   │   ├── DoctorLabTest.php
│   │   ├── DoctorLead.php
│   │   ├── MessageLog.php
│   │   ├── Patient.php
│   │   ├── PatientAttachment.php
│   │   ├── Plan.php
│   │   ├── PrescriptionTemplate.php
│   │   ├── SuperAdmin.php
│   │   ├── Tenant.php
│   │   ├── User.php
│   │   └── WalletTopup.php
│   ├── Scopes/
│   │   └── TenantScope.php
│   ├── Services/
│   │   ├── CloudflareDomainService.php
│   │   ├── FawryService.php
│   │   ├── FollowupDetectorService.php
│   │   ├── MessagingWalletService.php
│   │   ├── PrescriptionPdfService.php
│   │   ├── QueueTimeCalculatorService.php
│   │   └── SmsGatewayService.php
│   └── Traits/
│       ├── ApiResponseTrait.php
│       └── BelongsToTenant.php
├── config/
│   ├── auth.php
│   ├── cloudflare.php
│   ├── fawry.php
│   └── sms.php
├── database/
│   ├── migrations/
│   │   ├── 2026_01_01_000001_create_super_admins_table.php
│   │   ├── 2026_01_01_000002_create_plans_table.php
│   │   ├── 2026_01_01_000003_create_doctor_leads_table.php
│   │   ├── 2026_01_01_000004_create_tenants_table.php
│   │   ├── 2026_01_01_000005_create_users_table.php
│   │   ├── 2026_01_01_000006_create_clinics_table.php
│   │   ├── 2026_01_01_000007_create_clinic_shifts_table.php
│   │   ├── 2026_01_01_000008_create_clinic_days_off_table.php
│   │   ├── 2026_01_01_000009_create_patients_table.php
│   │   ├── 2026_01_01_000010_create_appointments_table.php
│   │   ├── 2026_01_01_000011_create_doctor_drugs_table.php
│   │   ├── 2026_01_01_000012_create_prescription_templates_table.php
│   │   ├── 2026_01_01_000013_create_doctor_lab_tests_table.php
│   │   ├── 2026_01_01_000014_create_consultations_table.php
│   │   ├── 2026_01_01_000015_create_patient_attachments_table.php
│   │   ├── 2026_01_01_000016_create_clinic_services_table.php
│   │   ├── 2026_01_01_000017_create_cash_transactions_table.php
│   │   └── 2026_01_01_000018_create_message_logs_and_wallet_topups_tables.php
│   └── seeders/
│       └── DatabaseSeeder.php
└── routes/
    ├── api.php
    └── channels.php
```

---

### 2. Complete Database Schema, Relationships & Indexes

#### 1. `super_admins`
- Columns: `id` (BIGINT UNSIGNED PK), `name` (VARCHAR 255), `email` (VARCHAR 255 UNIQUE), `password` (VARCHAR 255), `timestamps`.
- Relations: `hasMany(WalletTopup, 'approved_by_admin_id')`.

#### 2. `plans`
- Columns: `id` (BIGINT UNSIGNED PK), `name_ar` (VARCHAR 255), `price_monthly` (DECIMAL 10,2), `price_yearly` (DECIMAL 10,2), `max_clinics` (INT, `-1` = unlimited), `free_whatsapp_quota_monthly` (INT DEFAULT 0), `free_sms_quota_monthly` (INT DEFAULT 0), `allow_custom_domain` (BOOLEAN DEFAULT FALSE), `allow_custom_website` (BOOLEAN DEFAULT FALSE), `is_active` (BOOLEAN DEFAULT TRUE), `timestamps`.
- Relations: `hasMany(Tenant)`.

#### 3. `doctor_leads`
- Columns: `id` (BIGINT UNSIGNED PK), `doctor_name` (VARCHAR 255), `phone` (VARCHAR 50 INDEX), `specialty` (VARCHAR 100), `requested_plan_id` (BIGINT UNSIGNED NULLABLE FK -> `plans.id` ON DELETE SET NULL), `notes` (TEXT NULLABLE), `status` (ENUM: `new`, `contacted`, `converted`, `rejected` DEFAULT `new`), `timestamps`.
- Relations: `belongsTo(Plan, 'requested_plan_id')`.

#### 4. `tenants`
- Columns:
  - `id` (BIGINT UNSIGNED PK), `name` (VARCHAR 255), `specialty_key` (ENUM: `general`, `dental`, `pediatrics`, `dermatology`, `obgyn`, `orthopedics`), `title_ar` (VARCHAR 255).
  - `plan_id` (BIGINT UNSIGNED FK -> `plans.id`), `subscription_ends_at` (DATETIME), `status` (ENUM: `active`, `suspended`, `expired` DEFAULT `active`).
  - Limits & Wallets: `max_clinics_override` (INT NULLABLE), `whatsapp_enabled` (BOOLEAN DEFAULT FALSE), `whatsapp_balance` (INT DEFAULT 0), `whatsapp_used_this_month` (INT DEFAULT 0), `whatsapp_session_status` (ENUM: `disconnected`, `connected` DEFAULT `disconnected`), `whatsapp_phone_number` (VARCHAR 50 NULLABLE), `sms_enabled` (BOOLEAN DEFAULT FALSE), `sms_balance` (INT DEFAULT 0), `sms_used_this_month` (INT DEFAULT 0).
  - Domain Config: `subdomain` (VARCHAR 100 UNIQUE), `custom_domain` (VARCHAR 255 UNIQUE NULLABLE), `cloudflare_hostname_id` (VARCHAR 100 NULLABLE), `domain_status` (ENUM: `none`, `pending_cname`, `active` DEFAULT `none`), `template_key` (VARCHAR 50 DEFAULT `general`), `is_custom_standalone_website` (BOOLEAN DEFAULT FALSE), `public_booking_api_key` (VARCHAR 64 UNIQUE).
  - JSON Configs: `website_settings` (JSON), `vitals_config` (JSON), `print_settings` (JSON), `notification_settings` (JSON), `timestamps`.
- Indexes: `UNIQUE(subdomain)`, `UNIQUE(custom_domain)`, `UNIQUE(public_booking_api_key)`, `INDEX(status)`.
- Relations: `belongsTo(Plan)`, `hasMany(User)`, `hasMany(Clinic)`, `hasMany(Patient)`, `hasMany(DoctorDrug)`, `hasMany(PrescriptionTemplate)`, `hasMany(DoctorLabTest)`, `hasMany(ClinicService)`, `hasMany(MessageLog)`, `hasMany(WalletTopup)`.

#### 5. `users`
- Columns: `id` (BIGINT UNSIGNED PK), `tenant_id` (BIGINT UNSIGNED FK -> `tenants.id` ON DELETE CASCADE), `clinic_id` (BIGINT UNSIGNED NULLABLE FK -> `clinics.id` ON DELETE SET NULL), `name` (VARCHAR 255), `email` (VARCHAR 255 UNIQUE), `phone` (VARCHAR 50), `password` (VARCHAR 255), `role` (ENUM: `doctor`, `receptionist`), `permissions` (JSON NULLABLE), `is_active` (BOOLEAN DEFAULT TRUE), `timestamps`.
- Indexes: `INDEX(tenant_id, role)`, `INDEX(clinic_id)`.
- Relations: `belongsTo(Tenant)`, `belongsTo(Clinic)`.

#### 6. `clinics`
- Columns: `id` (BIGINT UNSIGNED PK), `tenant_id` (BIGINT UNSIGNED FK -> `tenants.id` ON DELETE CASCADE), `name_ar` (VARCHAR 255), `address_ar` (TEXT), `city_ar` (VARCHAR 100), `phone` (VARCHAR 50), `google_maps_url` (TEXT NULLABLE), `consultation_price` (DECIMAL 10,2), `followup_price` (DECIMAL 10,2 DEFAULT 0), `followup_days_limit` (INT DEFAULT 10), `avg_consultation_minutes` (INT DEFAULT 15), `booking_payment_mode` (ENUM: `pay_at_clinic`, `require_prepayment` DEFAULT `pay_at_clinic`), `prepayment_instructions_ar` (TEXT NULLABLE), `tv_screen_code` (VARCHAR 6 UNIQUE), `is_active` (BOOLEAN DEFAULT TRUE), `timestamps`.
- Indexes: `INDEX(tenant_id)`, `UNIQUE(tv_screen_code)`.
- Relations: `belongsTo(Tenant)`, `hasMany(ClinicShift)`, `hasMany(ClinicDayOff)`, `hasMany(Appointment)`, `hasMany(CashTransaction)`.

#### 7. `clinic_shifts`
- Columns: `id` (BIGINT UNSIGNED PK), `tenant_id` (BIGINT UNSIGNED FK -> `tenants.id`), `clinic_id` (BIGINT UNSIGNED FK -> `clinics.id` ON DELETE CASCADE), `day_of_week` (TINYINT: 0=Sunday..6=Saturday), `shift_name_ar` (VARCHAR 100), `start_time` (TIME), `end_time` (TIME), `max_patients` (INT NULLABLE), `is_active` (BOOLEAN DEFAULT TRUE), `timestamps`.
- Indexes: `INDEX(clinic_id, day_of_week, is_active)`.
- Relations: `belongsTo(Clinic)`, `hasMany(Appointment, 'shift_id')`.

#### 8. `clinic_days_off`
- Columns: `id` (BIGINT UNSIGNED PK), `tenant_id` (BIGINT UNSIGNED FK -> `tenants.id`), `clinic_id` (BIGINT UNSIGNED FK -> `clinics.id` ON DELETE CASCADE), `date` (DATE), `reason_ar` (VARCHAR 255 NULLABLE), `timestamps`.
- Indexes: `UNIQUE(clinic_id, date)`.
- Relations: `belongsTo(Clinic)`.

#### 9. `patients`
- Columns: `id` (BIGINT UNSIGNED PK), `tenant_id` (BIGINT UNSIGNED FK -> `tenants.id` ON DELETE CASCADE), `name` (VARCHAR 255), `phone` (VARCHAR 50), `gender` (ENUM: `male`, `female`), `birth_year` (SMALLINT NULLABLE), `address` (VARCHAR 255 NULLABLE), `chronic_diseases` (TEXT NULLABLE), `drug_allergies` (TEXT NULLABLE), `medical_notes` (TEXT NULLABLE), `timestamps`.
- Indexes: `UNIQUE(tenant_id, phone)`, `INDEX(tenant_id, name)`.
- Relations: `belongsTo(Tenant)`, `hasMany(Appointment)`, `hasMany(Consultation)`, `hasMany(PatientAttachment)`.

#### 10. `appointments`
- Columns:
  - `id` (BIGINT UNSIGNED PK), `tenant_id` (BIGINT UNSIGNED FK -> `tenants.id`), `clinic_id` (BIGINT UNSIGNED FK -> `clinics.id`), `shift_id` (BIGINT UNSIGNED FK -> `clinic_shifts.id`), `patient_id` (BIGINT UNSIGNED FK -> `patients.id`).
  - `appointment_date` (DATE), `queue_number` (INT), `reference_code` (VARCHAR 10 UNIQUE), `booking_source` (ENUM: `online`, `clinic_desk`, `phone`), `visit_type` (ENUM: `new_visit`, `followup`, `emergency`, `procedure`).
  - `status` (ENUM: `pending_payment`, `booked`, `arrived_waiting`, `in_consultation`, `postponed_waiting_arrival`, `completed`, `cancelled` DEFAULT `booked`).
  - Time Windows: `estimated_window_start` (TIME NULLABLE), `estimated_window_end` (TIME NULLABLE), `actual_entered_at` (DATETIME NULLABLE), `actual_completed_at` (DATETIME NULLABLE).
  - Clinical & Financial: `vitals_data` (JSON NULLABLE), `consultation_fee` (DECIMAL 10,2), `extra_services_total` (DECIMAL 10,2 DEFAULT 0), `total_amount` (DECIMAL 10,2), `is_paid` (BOOLEAN DEFAULT FALSE), `extra_services_paid` (BOOLEAN DEFAULT FALSE).
  - Messaging Flags: `whatsapp_opted_in` (BOOLEAN DEFAULT FALSE), `near_turn_notified` (BOOLEAN DEFAULT FALSE), `timestamps`.
- Indexes: `INDEX(clinic_id, appointment_date, shift_id)`, `INDEX(status)`, `UNIQUE(reference_code)`, `UNIQUE(clinic_id, shift_id, appointment_date, queue_number)`.
- Relations: `belongsTo(Tenant)`, `belongsTo(Clinic)`, `belongsTo(ClinicShift, 'shift_id')`, `belongsTo(Patient)`, `hasOne(Consultation)`.

#### 11. `doctor_drugs`
- Columns: `id` (BIGINT UNSIGNED PK), `tenant_id` (BIGINT UNSIGNED FK -> `tenants.id` ON DELETE CASCADE), `drug_name` (VARCHAR 255), `form_type` (VARCHAR 100 NULLABLE), `default_dosage` (VARCHAR 255 NULLABLE), `default_duration` (VARCHAR 100 NULLABLE), `notes` (TEXT NULLABLE), `timestamps`.
- Indexes: `UNIQUE(tenant_id, drug_name)`.
- Relations: `belongsTo(Tenant)`.

#### 12. `prescription_templates`
- Columns: `id` (BIGINT UNSIGNED PK), `tenant_id` (BIGINT UNSIGNED FK -> `tenants.id` ON DELETE CASCADE), `template_name` (VARCHAR 255), `diagnosis_default` (TEXT NULLABLE), `drugs_json` (JSON), `timestamps`.
- Indexes: `INDEX(tenant_id, template_name)`.
- Relations: `belongsTo(Tenant)`.

#### 13. `doctor_lab_tests`
- Columns: `id` (BIGINT UNSIGNED PK), `tenant_id` (BIGINT UNSIGNED FK -> `tenants.id` ON DELETE CASCADE), `test_name` (VARCHAR 255), `type` (ENUM: `lab`, `radiology`), `timestamps`.
- Indexes: `UNIQUE(tenant_id, test_name, type)`.
- Relations: `belongsTo(Tenant)`.

#### 14. `consultations`
- Columns: `id` (BIGINT UNSIGNED PK), `tenant_id` (BIGINT UNSIGNED FK -> `tenants.id`), `clinic_id` (BIGINT UNSIGNED FK -> `clinics.id`), `appointment_id` (BIGINT UNSIGNED UNIQUE FK -> `appointments.id` ON DELETE CASCADE), `patient_id` (BIGINT UNSIGNED FK -> `patients.id`), `complaints_symptoms` (TEXT NULLABLE), `diagnosis` (TEXT), `private_doctor_notes` (TEXT NULLABLE), `prescribed_drugs` (JSON), `requested_labs` (JSON NULLABLE), `selected_services` (JSON NULLABLE), `followup_date` (DATE NULLABLE), `pdf_short_token` (VARCHAR 8 UNIQUE), `timestamps`.
- Indexes: `UNIQUE(appointment_id)`, `UNIQUE(pdf_short_token)`, `INDEX(patient_id)`.
- Relations: `belongsTo(Tenant)`, `belongsTo(Clinic)`, `belongsTo(Appointment)`, `belongsTo(Patient)`.

#### 15. `patient_attachments`
- Columns: `id` (BIGINT UNSIGNED PK), `tenant_id` (BIGINT UNSIGNED FK -> `tenants.id`), `patient_id` (BIGINT UNSIGNED FK -> `patients.id` ON DELETE CASCADE), `consultation_id` (BIGINT UNSIGNED NULLABLE FK -> `consultations.id` ON DELETE SET NULL), `title` (VARCHAR 255), `file_path` (VARCHAR 255), `file_type` (VARCHAR 50), `timestamps`.
- Indexes: `INDEX(patient_id)`.
- Relations: `belongsTo(Tenant)`, `belongsTo(Patient)`, `belongsTo(Consultation)`.

#### 16. `clinic_services`
- Columns: `id` (BIGINT UNSIGNED PK), `tenant_id` (BIGINT UNSIGNED FK -> `tenants.id`), `clinic_id` (BIGINT UNSIGNED NULLABLE FK -> `clinics.id` ON DELETE CASCADE), `name_ar` (VARCHAR 255), `price` (DECIMAL 10,2), `is_active` (BOOLEAN DEFAULT TRUE), `timestamps`.
- Indexes: `INDEX(tenant_id, clinic_id)`.
- Relations: `belongsTo(Tenant)`, `belongsTo(Clinic)`.

#### 17. `cash_transactions`
- Columns: `id` (BIGINT UNSIGNED PK), `tenant_id` (BIGINT UNSIGNED FK -> `tenants.id`), `clinic_id` (BIGINT UNSIGNED FK -> `clinics.id`), `appointment_id` (BIGINT UNSIGNED NULLABLE FK -> `appointments.id` ON DELETE SET NULL), `created_by_user_id` (BIGINT UNSIGNED FK -> `users.id`), `type` (ENUM: `income`, `expense`), `category` (ENUM: `consultation`, `followup`, `medical_service`, `clinic_supplies`, `utilities`, `other_expense`), `amount` (DECIMAL 10,2), `description_ar` (VARCHAR 255), `transaction_date` (DATE), `timestamps`.
- Indexes: `INDEX(clinic_id, transaction_date)`.
- Relations: `belongsTo(Tenant)`, `belongsTo(Clinic)`, `belongsTo(Appointment)`, `belongsTo(User, 'created_by_user_id')`.

#### 18. `message_logs` & `wallet_topups`
- `message_logs`: `id`, `tenant_id` (FK), `appointment_id` (FK NULLABLE), `channel` (ENUM: `whatsapp`, `sms`), `recipient_phone`, `message_type` (ENUM: `booking_confirm`, `near_turn`, `prescription_link`, `followup_reminder`), `content`, `status` (ENUM: `sent`, `failed`), `created_at`. Indexed by `(tenant_id, created_at)`.
- `wallet_topups`: `id`, `tenant_id` (FK), `package_type` (ENUM: `whatsapp`, `sms`, `subscription`), `quantity_added`, `amount_egp`, `payment_method` (ENUM: `fawry`, `manual_super_admin`), `fawry_reference` (NULLABLE), `status` (ENUM: `pending`, `paid`), `approved_by_admin_id` (FK NULLABLE -> `super_admins.id`), `timestamps`. Indexed by `(tenant_id, status)`.

---

### 3. Middleware & Traits Specification

#### `BelongsToTenant` (`app/Traits/BelongsToTenant.php`)
- Binds `TenantScope` globally.
- In `creating` model event: automatically assigns `tenant_id = auth()->user()->tenant_id` when authenticated under `clinic_user` guard.

#### `TenantScope` (`app/Scopes/TenantScope.php`)
- Automatically adds `WHERE tenant_id = auth()->user()->tenant_id` when user is authenticated with guard `clinic_user`. Bypassed in CLI seeders or explicit `withoutGlobalScope(TenantScope::class)` Super Admin queries.

#### `SuperAdminAuth` (`app/Http/Middleware/SuperAdminAuth.php`)
- Enforces guard `super_admin` and Sanctum capability `role:super-admin`. Rejects unauthorized attempts with HTTP 401.

#### `ClinicAuth` (`app/Http/Middleware/ClinicAuth.php`)
- Enforces guard `clinic_user` and checks for Sanctum capability `role:doctor` or `role:receptionist`. Returns HTTP 401 if a Super Admin token is supplied.

#### `CheckTenantActive` (`app/Http/Middleware/CheckTenantActive.php`)
- Verifies `tenant.status === 'active'` and `subscription_ends_at >= now()`. Rejects expired accounts with HTTP 403.

#### `ClinicBranchScope` (`app/Http/Middleware/ClinicBranchScope.php`)
- If the authenticated user has role `receptionist`, asserts that the target `clinic_id` matches the user's assigned `clinic_id`.

#### `InternalWebhookAuth` (`app/Http/Middleware/InternalWebhookAuth.php`)
- Verifies that request header `X-Internal-Secret` matches `config('services.internal_secret')`.

#### `PublicApiKeyAuth` (`app/Http/Middleware/PublicApiKeyAuth.php`)
- Validates header `X-Clinic-Api-Key` against `tenants.public_booking_api_key` for VIP external custom websites.

---

### 4. Domain Services Specification

#### `QueueTimeCalculatorService` (`app/Services/QueueTimeCalculatorService.php`)
- `calculateInitialWindow(ClinicShift $shift, Clinic $clinic, int $queueNumber, Carbon $date): array`:
  - Determines shift start datetime.
  - Multiplies offset `($queueNumber - 1) * $clinic->avg_consultation_minutes`.
  - Sets window start to `start_time + offset` and window end to `startWindow + 30 minutes`.
  - Returns `['estimated_window_start' => 'H:i:s', 'estimated_window_end' => 'H:i:s']`.
- `recalculateQueueOnStatusChange(Clinic $clinic, ClinicShift $shift, Carbon $date, int $enteredQueueNumber): Collection`:
  - **Correction 2 Applied:** Queries all remaining active appointments for the shift:
    ```php
    $remaining = Appointment::whereDate('appointment_date', $date)
        ->where('clinic_id', $clinic->id)
        ->where('shift_id', $shift->id)
        ->whereIn('status', ['arrived_waiting', 'postponed_waiting_arrival', 'booked'])
        ->orderByRaw("CASE WHEN status = 'arrived_waiting' THEN 0 ELSE 1 END")
        ->orderBy('queue_number', 'asc')
        ->get();
    ```
  - Loops over collection with 0-based index `$index`:
    ```php
    $baseTime = now();
    foreach ($remaining as $index => $item) {
        $slotsAhead = $index + 1;
        $itemStart = $baseTime->copy()->addMinutes($slotsAhead * $clinic->avg_consultation_minutes);
        $itemEnd = $itemStart->copy()->addMinutes(30);
        $item->update([
            'estimated_window_start' => $itemStart->format('H:i:s'),
            'estimated_window_end' => $itemEnd->format('H:i:s'),
        ]);
    }
    ```
  - Broadcasts `QueueUpdatedEvent($clinic->id, $shift->id, $date->toDateString())`.
  - Returns updated appointments collection.

#### `FollowupDetectorService` (`app/Services/FollowupDetectorService.php`)
- `checkEligibility(int $tenantId, int $clinicId, string $phone): array`:
  - Finds patient by `tenant_id` and normalized `phone`.
  - Queries for a completed appointment of type `new_visit` within `clinic.followup_days_limit` days.
  - Verifies that no subsequent follow-up appointment was consumed for that specific visit date.
  - Returns `['eligible' => true/false, 'patient' => $patient, 'last_visit_date' => $date, 'followup_price' => $price]`.

#### `PrescriptionPdfService` (`app/Services/PrescriptionPdfService.php`)
- `generatePrescriptionPdf(Consultation $consultation): string`:
  - Loads tenant `print_settings`: margins (top, bottom, left, right in mm), paper size (`A5` or `A4`), and `use_preprinted_letterhead`.
  - Configures `\Mpdf\Mpdf` with UTF-8 and RTL directionality.
  - Renders Blade template with patient details, vital signs, prescribed medications (name, form, dose, duration, instructions), requested diagnostics, and next review date.
  - Saves file to `storage/app/public/prescriptions/{$consultation->pdf_short_token}.pdf`.
  - Returns absolute file path.
- `generateTestMarginsPdf(Tenant $tenant, array $margins): string`:
  - Generates calibration grid PDF with margin indicators for test printing on pre-printed letterhead.

#### `CloudflareDomainService` (`app/Services/CloudflareDomainService.php`)
- `registerCustomHostname(Tenant $tenant, string $hostname): array`: Calls Cloudflare for SaaS API, stores `cloudflare_hostname_id`, and sets `domain_status = pending_cname`.
- `checkHostnameStatus(Tenant $tenant): array`: Queries Cloudflare verification status and updates `domain_status = active` when SSL/CNAME passes.
- `removeCustomHostname(Tenant $tenant): bool`: Sends DELETE to Cloudflare API and clears hostname fields.

#### `MessagingWalletService` (`app/Services/MessagingWalletService.php`)
- `deductMessageBalance(Tenant $tenant, string $channel): bool`: Locks tenant row (`lockForUpdate`), checks balance, decrements balance by 1, increments monthly usage counter by 1. Returns false if insufficient balance.
- `logMessage(int $tenantId, ?int $appointmentId, string $channel, string $phone, string $type, string $content, string $status): MessageLog`: Persists log in `message_logs`.
- `topupBalance(Tenant $tenant, string $packageType, int $amount, string $paymentMethod, ?string $fawryRef, ?int $adminId): WalletTopup`: Creates `wallet_topups` record with status `paid` and increments tenant balance.

#### `SmsGatewayService` (`app/Services/SmsGatewayService.php`)
- `sendSms(Tenant $tenant, string $phone, string $message, string $type, ?int $appointmentId): bool`:
  - Checks and deducts balance via `MessagingWalletService`.
  - Routes payload through mock driver (`storage/logs/sms.log`) in local environment or Egyptian SMS HTTP Gateway in production.
  - Records delivery in `message_logs`.

#### `FawryService` (`app/Services/FawryService.php`)
- `verifySignature(array $payload): bool`: Computes SHA256 checksum and compares with Fawry callback signature.
- `processCallback(array $payload): bool`: Resolves pending top-up, marks paid, and credits tenant balance.

---

### 5. Jobs Specification

#### `CheckNearTurnNotificationsJob` (`app/Jobs/CheckNearTurnNotificationsJob.php`)
- **Correction 3 Applied:**
  - Parameters: `int $clinicId, int $shiftId, string $date`.
  - Logic:
    1. Resolve clinic, shift, and tenant.
    2. Read `$threshold = $tenant->notification_settings['near_turn_threshold'] ?? 3`.
    3. Query remaining queue in order: `status IN ('arrived_waiting', 'booked')` ordered by `queue_number ASC`.
    4. Loop through appointments with index `$idx < $threshold`:
       - If `$appointment->near_turn_notified == false`:
         - Message text: `"اقترب دورك في عيادة {$clinic->name_ar}! متبقي أمامك " . ($idx + 1) . " مرضى، يرجى التواجد بالعيادة."`
         - If `$appointment->whatsapp_opted_in && $tenant->whatsapp_enabled`: send via WhatsApp microservice.
         - Else if `$tenant->sms_enabled`: send via `SmsGatewayService`.
         - Update `$appointment->update(['near_turn_notified' => true])`.

#### `SendWhatsAppPrescriptionJob` (`app/Jobs/SendWhatsAppPrescriptionJob.php`)
- Calls `/whatsapp-service/message/send-pdf` with generated PDF URL, patient phone, and custom message caption.

#### `SendSmsNotificationJob` (`app/Jobs/SendSmsNotificationJob.php`)
- Calls `SmsGatewayService::sendSms()` with short link `{APP_URL}/rx/{token}`.

---

### 6. Controllers & Exact Method Logic

#### A. Clinic Controllers (`app/Http/Controllers/Clinic/`)

1. **`DoctorWorkspaceController` (Correction 1 Applied — Decoupled Saves)**:
   - `currentPatient(Request $request): JsonResponse`: Returns appointment with `status = in_consultation` for active clinic with vitals comparison and medical alerts.
   - `savePrescription(int $appointmentId, ConsultationSaveRequest $request): JsonResponse`:
     - Finds appointment (`status` must be `in_consultation`).
     - Auto-saves any unknown drug names into `doctor_drugs` for this `tenant_id`.
     - Creates or updates `consultations` row: diagnosis, complaints, private doctor notes, prescribed drugs.
     - Generates prescription PDF via `PrescriptionPdfService`.
     - Dispatches WhatsApp/SMS if requested by doctor.
     - **Keeps `appointment.status = in_consultation`** so doctor can continue examination.
   - `saveLabsAndNotes(int $appointmentId, Request $request): JsonResponse`:
     - Auto-saves any new diagnostic test names into `doctor_lab_tests`.
     - Updates `consultations.requested_labs`.
     - Keeps `appointment.status = in_consultation`.
   - `saveExtraServicesAndFollowup(int $appointmentId, Request $request): JsonResponse`:
     - Updates `consultations.selected_services` and `consultations.followup_date`.
     - Recalculates `appointments.extra_services_total` and `appointments.total_amount`.
     - Keeps `appointment.status = in_consultation`.
   - `completeAndCallNextPatient(Request $request): JsonResponse`:
     - Triggered exclusively by the giant bottom green button.
     - Marks current patient `completed`.
     - If `consultations.followup_date` is set: automatically creates a `booked` `followup` appointment on that date.
     - Selects next patient with `status = arrived_waiting` (lowest `queue_number`).
     - Updates next patient status to `in_consultation`, setting `actual_entered_at = now()`.
     - Dispatches `CheckNearTurnNotificationsJob`.
     - Recalculates queue windows via `QueueTimeCalculatorService`.
     - Broadcasts `QueueUpdatedEvent`.
   - `previousVisits(int $patientId): JsonResponse`: Returns full chronological consultation history across all branches.
   - `saveAttachments(int $patientId, Request $request): JsonResponse`: Stores patient scan files in `patient_attachments`.
   - `autoCompleteDrugs(Request $request): JsonResponse`: Searches `doctor_drugs`.
   - `autoCompleteLabs(Request $request): JsonResponse`: Searches `doctor_lab_tests`.

2. **`ReceptionController` (Corrections 3 & 4 Applied)**:
   - `queue(Request $request): JsonResponse`: Returns today's appointments for given branch and shift.
   - `quickBook(QuickBookingRequest $request): JsonResponse`: Auto-detects follow-up eligibility, assigns queue number, calculates time window, creates appointment, broadcasts queue update.
   - `checkFollowup(Request $request): JsonResponse`: Calls `FollowupDetectorService::checkEligibility()`.
   - `updateStatus(int $appointmentId, Request $request): JsonResponse`:
     - Handles transitions (`arrived_waiting`, `postponed_waiting_arrival`, `cancelled`).
     - If transitioning to `arrived_waiting`, logs consultation cash collection in `cash_transactions` if marked paid.
     - Recalculates queue times.
     - Dispatches `CheckNearTurnNotificationsJob`.
     - Broadcasts `QueueUpdatedEvent`.
   - `collectExtraServices(int $appointmentId): JsonResponse`:
     - **Correction 4 Applied:** Records receipt of unpaid extra procedure fees in `cash_transactions` (`type = income`, `category = medical_service`).
     - Updates `appointments.extra_services_paid = true`.
   - `saveVitals(int $appointmentId, Request $request): JsonResponse`: Saves vitals matching doctor's `vitals_config`.
   - `cashboxSummary(Request $request): JsonResponse`: Returns total income, expenses, and net cash in drawer for today.
   - `recordExpense(Request $request): JsonResponse`: Records daily petty cash expense.

3. **`SettingsController` (Corrections 5 & 10 Applied)**:
   - `getSettings(): JsonResponse`: Returns tenant configs.
   - `updateVitalsConfig(Request $request): JsonResponse`: Updates dynamic vitals configuration.
   - `updatePrintMargins(Request $request): JsonResponse`: Updates print settings.
   - `testPrintPdf(): Response`: Generates sample test print PDF.
   - `getDrugs(): JsonResponse`, `saveDrug(Request $request): JsonResponse`.
   - `getTemplates(): JsonResponse`, `saveTemplate(Request $request): JsonResponse`.
   - `getLabs(): JsonResponse`, `saveLab(Request $request): JsonResponse`.
   - `getBranches(): JsonResponse`.
   - `saveBranch(Request $request): JsonResponse`:
     - **Correction 5 Applied:** Enforces plan limits:
       ```php
       $allowed = $tenant->max_clinics_override ?? $tenant->plan->max_clinics;
       if ($allowed !== -1 && $tenant->clinics()->count() >= $allowed) {
           return response()->json([
               'success' => false,
               'message' => "لقد وصلت للحد الأقصى لعدد العيادات المسموح به في باقتك الحالية ({$allowed} عيادات)",
           ], 403);
       }
       ```
   - `saveShift(Request $request): JsonResponse`, `deleteShift(int $id): JsonResponse`.
   - `saveDayOff(Request $request): JsonResponse`:
     - **Correction 10 Applied:** Creates record in `clinic_days_off`.
     - If `$request->action === 'cancel_and_notify'`: cancels booked appointments and sends WhatsApp/SMS apologies.
     - If `$request->action === 'move_to_date'`: bulk shifts appointments to target date and notifies patients with new queue numbers.
   - `getReceptionists(): JsonResponse`, `saveReceptionist(Request $request): JsonResponse`.
   - `getQrCode(): JsonResponse`: Queries Node.js WhatsApp service session QR.
   - `requestTopup(Request $request): JsonResponse`: Creates top-up request.

4. **`AuthController`**:
   - `login(ClinicLoginRequest $request): JsonResponse`: Authenticates against `users` table; rejects Super Admin accounts with HTTP 401; issues Sanctum token with ability `role:doctor` or `role:receptionist`.
   - `me(Request $request): JsonResponse`, `logout(Request $request): JsonResponse`.

#### B. Public Controllers (`app/Http/Controllers/Public/`)

1. **`PublicPlanController` (Correction 6 Applied)**:
   - `index(): JsonResponse`: Lists active subscription plans for landing page.
   - `submitLead(LeadSubmitRequest $request): JsonResponse`:
     - **Correction 6 Applied:** Receives lead form data from Nuxt 3 landing page, stores record in `doctor_leads` with `status = new`, and returns success confirmation.

2. **`PublicBookingController`**:
   - `tenantByHost(Request $request): JsonResponse`: Returns doctor public profile and clinics by host domain.
   - `checkEligibility(Request $request): JsonResponse`: Validates follow-up eligibility online.
   - `bookAppointment(BookAppointmentRequest $request): JsonResponse`: Creates booking, assigns queue number, returns reference code and WhatsApp activation URL (`https://wa.me/{phone}?text=تفعيل%20حجز%20رقم%20{ref}`).
   - `liveQueueStatus(string $referenceCode): JsonResponse`: Returns current active queue number and dynamic estimated time window for live tracking page.
   - `downloadPrescriptionPdf(string $token): Response`: Public streaming endpoint for patient PDF.

#### C. Super Admin Controllers (`app/Http/Controllers/SuperAdmin/`)
- `AuthController`: Dedicated login against `super_admins` table issuing token with ability `role:super-admin`.
- `DashboardController::stats()`: Aggregates total doctors, active clinics, platform daily bookings, wallet usage, and MRR.
- `DoctorLeadController`: Manages leads and converts lead into tenant + user in 1 click.
- `TenantController`: Manages doctor organizations, sets `max_clinics_override`, updates `whatsapp_balance` and `sms_balance`, toggles channels, and executes `impersonate()`.
- `PlanController`: CRUD for subscription plans.
- `TopupController`: Reviews and approves manual and Fawry top-ups.

#### D. Internal Webhook Controllers (`app/Http/Controllers/Internal/`)
- `WhatsAppWebhookController`: Handles inbound message webhook from Baileys, matches `#REF` code, marks `whatsapp_opted_in = true`, deducts wallet credit, and instructs Baileys to reply with queue status.
- `FawryWebhookController`: Verifies signature and executes wallet credit.

---

## SECTION B: `/whatsapp-service` (Node.js + Baileys) Architecture & Logic

### 1. Directory Tree & Exact File Paths

```text
/whatsapp-service
├── package.json
├── auth_info_multi/              # Multi-tenant Baileys auth state directory
├── src/
│   ├── config/
│   │   └── index.js              # Environment variables & webhook secrets
│   ├── middleware/
│   │   ├── auth.js               # Validates X-Internal-Secret from Laravel
│   │   └── errorHandler.js
│   ├── routes/
│   │   └── api.js                # Internal API routes
│   ├── services/
│   │   ├── BaileysManager.js     # Multi-session lifecycle manager
│   │   ├── InboundHandler.js     # Inbound message parsing (#REF detection)
│   │   └── OutboundHandler.js    # Human-delayed message and PDF delivery
│   └── server.js                 # Express server on port 3001
```

---

### 2. Services Specification & Corrections

#### `BaileysManager.js` (Correction 7 Applied — Auto-Restore Sessions)
- `restoreAllSessions(): Promise<void>`:
  - Scans `auth_info_multi/` for folders matching `tenant_*`.
  - For each folder found, calls `initSession(tenantId)` without re-generating new keys, restoring socket connections automatically on service reboot.
- `initSession(tenantId: string): Promise<{ qrCode: string|null, status: string }>`:
  - Initializes `useMultiFileAuthState("auth_info_multi/tenant_" + tenantId)`.
  - Instantiates socket with `@whiskeysockets/baileys`.
  - Listens to `connection.update`: generates base64 QR code during authentication phase; notifies Laravel `/api/internal/whatsapp/session-status` on connect/disconnect.
  - Listens to `messages.upsert`: routes incoming messages to `InboundHandler.handleIncoming(tenantId, msg)`.
- `getSession(tenantId: string): WASocket | null`: Returns active socket.
- `disconnectSession(tenantId: string): Promise<boolean>`: Closes socket, unlinks auth folder, notifies Laravel.

#### `InboundHandler.js`
- `handleIncoming(tenantId: string, rawMessage: WAMessage): Promise<void>`:
  - Filters out status broadcasts and outgoing messages.
  - Extracts sender phone number and message text.
  - Applies regex: `/(?:حجز|كشف|CLN-)?\s*#?([A-Z0-9]{4,8})/iu`.
  - If reference code matched:
    - Sends POST to Laravel `/api/internal/whatsapp/inbound-webhook`.
    - If Laravel verifies opt-in: dispatches confirmation text + live queue tracking link to patient.
  - If no code matched: replies with guidance message asking patient to send their `#REF` booking code.

#### `OutboundHandler.js` (Correction 7 Applied — Anti-Ban Delay)
- `sendText(tenantId: string, recipientPhone: string, message: string): Promise<boolean>`:
  - Resolves socket.
  - Normalizes phone number to JID: `${recipientPhone}@s.whatsapp.net`.
  - **Correction 7 Applied:**
    ```javascript
    await socket.sendPresenceUpdate('composing', jid);
    const delay = Math.floor(Math.random() * 1500) + 1500; // 1.5s - 3.0s delay
    await new Promise(resolve => setTimeout(resolve, delay));
    await socket.sendMessage(jid, { text: message });
    ```
  - Returns true.
- `sendPrescriptionPdf(tenantId: string, recipientPhone: string, pdfUrl: string, filename: string, caption: string): Promise<boolean>`:
  - Fetches PDF buffer.
  - Simulates typing presence with 2000ms delay.
  - Dispatches document message: `{ document: buffer, mimetype: 'application/pdf', fileName: filename, caption: caption }`.

---

## SECTION C: `/super-admin` (Vue 3) Pages, Components & Stores

### 1. Directory Tree & Exact File Paths

```text
/super-admin
├── package.json
├── vite.config.js
├── tailwind.config.js
├── src/
│   ├── App.vue
│   ├── main.js
│   ├── assets/css/main.css
│   ├── api/
│   │   ├── client.js             # Axios client (/api/super-admin)
│   │   └── endpoints.js
│   ├── components/
│   │   ├── layout/
│   │   │   ├── AdminHeader.vue
│   │   │   └── AdminSidebar.vue
│   │   └── modals/
│   │       ├── ConvertLeadModal.vue
│   │       ├── CreateDoctorModal.vue
│   │       ├── EditDoctorGodModeModal.vue
│   │       └── PlanEditModal.vue
│   ├── router/
│   │   └── index.js
│   ├── stores/
│   │   ├── auth.js
│   │   ├── dashboard.js
│   │   ├── doctors.js
│   │   ├── leads.js
│   │   ├── plans.js
│   │   └── topups.js
│   └── views/
│       ├── DashboardView.vue
│       ├── DoctorsListView.vue
│       ├── LeadsListView.vue
│       ├── LoginView.vue
│       ├── PlansListView.vue
│       └── TopupsListView.vue
```

---

### 2. Views & Modals Breakdown

- **`LoginView.vue`**: Isolated authentication against `super_admins` table. Shows rejection if a clinic doctor email is entered.
- **`DashboardView.vue`**: Aggregates registered doctors, active clinics today, total platform bookings, WhatsApp/SMS credits consumed, and MRR.
- **`DoctorsListView.vue`**: Table listing doctors with plan status, clinic counts, and message wallet balances. Contains row buttons for **God Mode Edit** and **الدخول كدكتور (Impersonate)**.
- **`EditDoctorGodModeModal.vue`**:
  - Plan assignment & expiration extension.
  - Clinic limit override (`max_clinics_override`, with toggle for unlimited `-1`).
  - WhatsApp Controls: `whatsapp_enabled` toggle, manual balance editor, monthly consumption counter.
  - SMS Controls: `sms_enabled` toggle, manual balance editor, monthly consumption counter.
  - Domain Controls: Cloudflare CNAME status viewer, template selector, standalone website toggle, public API key display.
- **`LeadsListView.vue`**: Displays doctor leads from landing page. Includes button to open `ConvertLeadModal.vue` which pre-populates doctor registration.
- **`PlansListView.vue`**: CRUD interface for platform subscription plans.
- **`TopupsListView.vue`**: Reviews pending recharge requests and logs Fawry payment transactions.

---

## SECTION D: `/doctor-dashboard` (Vue 3 + Tauri v2) Pages, Modals & Stores

### 1. Directory Tree & Exact File Paths

```text
/doctor-dashboard
├── package.json
├── vite.config.js
├── tailwind.config.js
├── src-tauri/
│   ├── Cargo.toml
│   ├── tauri.conf.json
│   ├── icons/
│   └── src/
│       └── main.rs               # Window management & HDMI secondary display command
├── src/
│   ├── App.vue
│   ├── main.js
│   ├── assets/css/main.css
│   ├── api/
│   │   └── client.js             # Axios client with offline blocker interceptor
│   ├── components/
│   │   ├── common/
│   │   │   ├── ImpersonationBanner.vue   # Top yellow support banner
│   │   │   └── OfflineBlockerModal.vue   # Full-screen network disconnect blocker
│   │   ├── doctor/
│   │   │   ├── ActivePatientCard.vue
│   │   │   ├── MedicalAlertBanner.vue
│   │   │   ├── VitalsStrip.vue
│   │   │   └── modals/
│   │   │       ├── ModalExtraServicesAndFollowup.vue
│   │   │       ├── ModalLabsAndAttachments.vue
│   │   │       ├── ModalPastHistory.vue
│   │   │       └── ModalPrescription.vue
│   │   └── reception/
│   │       ├── LiveQueueTable.vue
│   │       └── modals/
│   │           ├── ModalCashbox.vue
│   │           ├── ModalQuickBook.vue
│   │           ├── ModalVitalsEntry.vue
│   │           └── ModalWaitingTvLauncher.vue
│   ├── router/
│   │   └── index.js              # Impersonation handshake & clinic route guards
│   ├── stores/
│   │   ├── auth.js
│   │   ├── cashbox.js
│   │   ├── doctorWorkspace.js
│   │   ├── receptionQueue.js
│   │   └── settings.js
│   └── views/
│       ├── LoginView.vue
│       ├── DoctorWorkspaceView.vue
│       ├── ReceptionDeskView.vue
│       ├── WaitingRoomTvView.vue
│       └── settings/
│           ├── ClinicalSettingsView.vue
│           ├── ClinicBranchesView.vue
│           ├── ReceptionistsView.vue
│           └── WebsiteSettingsView.vue
```

---

### 2. Router & Impersonation Handshake (`src/router/index.js` — Correction 9 Applied)

```javascript
router.beforeEach(async (to, from, next) => {
  const authStore = useAuthStore();
  
  // Correction 9: Intercept Super Admin impersonation token
  if (to.query.impersonate_token) {
    const token = to.query.impersonate_token;
    authStore.setToken(token);
    authStore.isImpersonating = true;
    await authStore.fetchProfile();
    
    // Remove query param from browser address bar
    const query = { ...to.query };
    delete query.impersonate_token;
    return next({ path: to.path, query, replace: true });
  }

  if (to.meta.requiresAuth && !authStore.isAuthenticated) {
    return next({ name: 'login' });
  }

  next();
});
```

When `authStore.isImpersonating === true`, `App.vue` renders `ImpersonationBanner.vue` across the top:
> **"أنت تتصفح الآن بحساب الطبيب (وضع الدعم الفني للـ Super Admin) — [إنهاء جلسة الدعم]"**

---

### 3. Screen 1: Doctor's Single-Screen Workspace (`DoctorWorkspaceView.vue` — Correction 1 Applied)

- **Header Bar**: Branch switcher dropdown, live queue counters (`في الانتظار`, `تم الكشف`, `مؤجل`).
- **Active Patient Area (`status = in_consultation`)**:
  - `ActivePatientCard.vue`: Queue sequence number, patient name, age, visit type badge.
  - `MedicalAlertBanner.vue`: High-contrast red banner displaying `chronic_diseases` and `drug_allergies`.
  - `VitalsStrip.vue`: Renders enabled doctor vitals, displaying today's readings side-by-side with previous visit values.
- **4 Independent Action Modals (Non-completing saves)**:
  1. `ModalPastHistory.vue`: Chronological past consultations across all clinics.
  2. `ModalPrescription.vue`:
     - Inputs: Symptoms, Diagnosis, Private Notes.
     - Prescription template loader dropdown.
     - Drug builder table with autocomplete querying `doctor_drugs` (auto-saves newly typed drugs to `doctor_drugs`).
     - Actions: `حفظ فقط`, `حفظ وطباعة`, `حفظ وإرسال واتساب/SMS`.
     - Calls `doctorWorkspaceStore.savePrescription()` -> keeps patient in room.
  3. `ModalLabsAndAttachments.vue`:
     - Diagnostic requests autocomplete querying `doctor_lab_tests` (auto-saves new items).
     - Upload and preview patient X-rays and medical files.
     - Calls `doctorWorkspaceStore.saveLabs()` -> keeps patient in room.
  4. `ModalExtraServicesAndFollowup.vue`:
     - Select extra clinic procedures (updates invoice in real time).
     - 1-click follow-up scheduling (`بعد 7 أيام`, `بعد 10 أيام`, `بعد 14 يوم`).
     - Calls `doctorWorkspaceStore.saveExtraServices()` -> keeps patient in room.
- **Giant Bottom Green Button**:
  - **`✅ إنهاء الكشف وإدخال المريض التالي`**: Calls `completeAndCallNextPatient()`, completes active patient, books follow-up if date was set, advances next patient to `in_consultation`, triggers turn alerts, and rings TV screen.

---

### 4. Screen 2: Receptionist's Daily Desk (`ReceptionDeskView.vue` — Correction 4 Applied)

- **Top Action Modals**:
  - `ModalQuickBook.vue`: Phone input with automatic follow-up eligibility detection and price adjustment.
  - `ModalCashbox.vue`: Daily fees collected, procedures income, petty cash expenses, and net cash in drawer.
  - `ModalWaitingTvLauncher.vue`: Shows 6-digit TV code and button for Tauri dual-monitor launch.
- **Live Queue Table (`LiveQueueTable.vue`)**:
  - Lists appointments for active shift.
  - **Correction 4 Badge**: If `appointment.extra_services_total > 0` and `extra_services_paid == false`, renders prominent button: **`💰 تحصيل خدمات إضافية ({extra_services_total} ج.م)`** which calls `ReceptionController::collectExtraServices`.
  - 1-Click Action Buttons:
    - 🟢 `وصل العيادة وتحصيل الكشف`
    - 🟣 `قياسات المريض (Vitals)` -> Opens `ModalVitalsEntry.vue`
    - 🔵 `إدخال لغرفة الكشف`
    - 🟠 `تأجيل لحين الوصول`
    - 🔴 `إلغاء الحجز`

---

### 5. Screen 3: Waiting Room TV Screen (`WaitingRoomTvView.vue`) & Tauri Kiosk Command

- Route: `/tv/:tv_screen_code`.
- Kiosk layout: Doctor name, clinic branch, animated current patient card (`#N`), next patient card (`#N+1`), and upcoming waiting numbers list.
- Web Audio API Chime: Synthesized two-tone bell fires when `QueueUpdatedEvent` arrives via Reverb.
- **Tauri Window Command (`src-tauri/src/main.rs` — Correction 8 Applied)**:
  ```rust
  #[tauri::command]
  fn open_tv_on_secondary_monitor(app: tauri::AppHandle, tv_code: String) -> Result<(), String> {
      let monitors = app.available_monitors().map_err(|e| e.to_string())?;
      let target_monitor = monitors.get(1).or_else(|| monitors.get(0));
      
      let url = tauri::WebviewUrl::App(format!("index.html#/tv/{}", tv_code).into());
      
      let mut builder = tauri::WebviewWindowBuilder::new(&app, "tv_window", url)
          .title("Waiting Room TV")
          .fullscreen(true)
          .decorations(false);
          
      if let Some(monitor) = target_monitor {
          let pos = monitor.position();
          builder = builder.position(pos.x as f64, pos.y as f64);
      }
      
      builder.build().map_err(|e| e.to_string())?;
      Ok(())
  }
  ```

---

### 6. Offline Blocker (`OfflineBlockerModal.vue`)

- Attached globally in `App.vue`.
- Listens to `window.offline` and verifies backend heartbeat ping every 10 seconds.
- Displays full-screen modal blocking user actions during network drops:
  > **"انقطع الاتصال بالإنترنت — تم إيقاف العمليات مؤقتاً لمنع تضارب البيانات، سيعود النظام للعمل تلقائياً فور عودة الاتصال"**

---

## SECTION E: `/web-platform` (Nuxt 3) Pages, 6 Templates & Shared SDK

### 1. Directory Tree & Exact File Paths

```text
/web-platform
├── package.json
├── nuxt.config.ts
├── tailwind.config.ts
├── server/
│   └── middleware/
│       └── tenant.ts             # Host resolution middleware
├── composables/
│   ├── useClinicBooking.ts       # Shared Booking SDK
│   └── useTenant.ts
├── components/
│   ├── platform/
│   │   ├── LeadForm.vue          # Correction 6: submits to POST /api/public/leads
│   │   ├── PlatformFeatures.vue
│   │   ├── PlatformHero.vue
│   │   └── PlatformPricing.vue
│   ├── shared/
│   │   ├── BookingModal.vue
│   │   ├── FollowupBadge.vue
│   │   └── LiveQueueWidget.vue
│   └── templates/
│       ├── TemplateDental.vue
│       ├── TemplateDermatology.vue
│       ├── TemplateGeneral.vue
│       ├── TemplateObgyn.vue
│       ├── TemplateOrthopedics.vue
│       └── TemplatePediatrics.vue
└── pages/
    ├── index.vue                 # Dynamic render (Platform landing or Doctor website)
    ├── d/
    │   └── [subdomain].vue       # Preview route
    ├── track/
    │   └── [reference_code].vue  # Real-time patient queue tracking
    └── rx/
        └── [token].vue           # PDF prescription download route
```

---

### 2. Host Resolution Middleware (`server/middleware/tenant.ts`)

- Resolves incoming HTTP `Host` header:
  - If root domain (`platform.com` or `localhost:3000`): sets `event.context.isPlatform = true`, rendering Platform Marketing Landing Page with `LeadForm.vue`.
  - If doctor subdomain (`dr-ahmed.platform.com`) or custom domain (`drahmedclinic.com`): calls `/api/public/tenant-by-host?host=...` and SSR-renders doctor website using the tenant's chosen template from `components/templates/`.

---

### 3. The 6 Specialty Templates Breakdown

1. `TemplateGeneral.vue`: Navy/slate theme for internal medicine and multi-specialty practices.
2. `TemplateDental.vue`: Cyan/white theme with cosmetic dental treatment showcases.
3. `TemplatePediatrics.vue`: Pastel theme with growth trackers and child immunization info.
4. `TemplateDermatology.vue`: Luxury rose-gold theme highlighting aesthetic and laser procedures.
5. `TemplateObgyn.vue`: Soft blush theme with pregnancy calculation tools and 4D ultrasound booking.
6. `TemplateOrthopedics.vue`: Blue/gray theme highlighting sports injury and joint replacement services.

---

### 4. Shared Booking Composable / SDK (`composables/useClinicBooking.ts`)

```typescript
export interface BookingPayload {
  clinicId: number;
  shiftId: number;
  date: string;
  patientName: string;
  phone: string;
  gender: 'male' | 'female';
  age?: number;
}

export interface BookingResponse {
  queueNumber: number;
  referenceCode: string;
  estimatedWindowStart: string;
  estimatedWindowEnd: string;
  visitType: 'new_visit' | 'followup';
  amountDue: number;
  paymentInstructions?: string;
  whatsappActivationUrl: string;
}

export function useClinicBooking(apiKey?: string) {
  const fetchClinicsAndShifts = async () => { /* ... */ };
  const checkPatientAndFollowupEligibility = async (phone: string, clinicId: number) => { /* ... */ };
  const submitBooking = async (payload: BookingPayload): Promise<BookingResponse> => { /* ... */ };
  const subscribeToLiveQueue = (referenceCode: string, onUpdate: (data: any) => void) => { /* ... */ };

  return {
    fetchClinicsAndShifts,
    checkPatientAndFollowupEligibility,
    submitBooking,
    subscribeToLiveQueue,
  };
}
```

---

### 5. Live Queue Tracking Page (`pages/track/[reference_code].vue`)

- Opened by patients via WhatsApp confirmation link.
- Connects to Reverb WebSocket channel `appointment.{referenceCode}`.
- Displays current active queue number, patient's queue number, count of patients ahead, and updated dynamic time window.
- Renders 1-click WhatsApp opt-in button (`wa.me`) and PDF download button upon completion.

---

## SECTION F: MANDATORY DEMO SEEDER (`DatabaseSeeder.php`) SPECIFICATION

1. **Super Admin**:
   - `admin@platform.com` / `password123`.
2. **Plans**:
   - الباقة الأساسية (`max_clinics = 1`), الباقة المتقدمة (`max_clinics = 3`), باقة VIP الشاملة (`max_clinics = -1`).
3. **Demo Doctor Organization**:
   - Doctor: `د. أحمد طارق المنشاوي` (`doctor@clinic.com` / `password123`).
   - Subdomain: `dr-ahmed`, WhatsApp Enabled (`balance = 500`), SMS Enabled (`balance = 250`).
   - Vitals: `الوزن`, `ضغط الدم`, `السكر العشوائي`, `الحرارة`.
   - Print Margins: A5, top 45mm, bottom 30mm, preprinted letterhead = true.
4. **2 Clinic Branches**:
   - Branch 1: `عيادة مدينة نصر` (Consultation: 300 EGP, Follow-up: 50 EGP in 10 days, TV Code: `TV1001`, Shifts: Morning 12:00–15:00 & Evening 17:00–22:00).
   - Branch 2: `عيادة المهندسين` (Consultation: 400 EGP, Follow-up: 0 EGP in 14 days, TV Code: `TV2002`).
5. **2 Receptionists**:
   - `reception1@clinic.com` (Nasr City) / `password123`.
   - `reception2@clinic.com` (Mohandessin) / `password123`.
6. **10 Demo Patients & Live Queue**:
   - Patients #1 & #2: `completed` (tested with past visits 5 days ago for follow-up detection).
   - Patient #3: `in_consultation` (inside room with medical alerts: `مريض سكري وضغط - حساسية بنسلين`).
   - Patients #4, #5, #6: `arrived_waiting` (vitals recorded).
   - Patient #7: `postponed_waiting_arrival`.
   - Patients #8, #9, #10: `booked` (online bookings awaiting arrival).
7. **Doctor Leads**:
   - 2 sample records in `doctor_leads` for testing Super Admin conversion.
