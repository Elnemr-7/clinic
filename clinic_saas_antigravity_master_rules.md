# CLINIC MANAGEMENT & DOCTOR BOOKING SaaS PLATFORM
## Final Patched Master Blueprint & Execution Specification (For Antigravity)

> **DIRECTIVE FOR ANTIGRAVITY (ARCHITECTURAL AUDIT FEEDBACK & FINAL BLUEPRINT):**
> Your initial blueprint (`file.md`) has been audited. Before writing any code (or while updating your final execution plan), you **MUST** apply the **10 Mandatory Architectural Corrections** listed in **Section 0** below to fix critical logic bugs in your initial plan.

---

## SECTION 0: 10 MANDATORY CORRECTIONS TO YOUR INITIAL BLUEPRINT

### Correction 1: Decouple Modal Saves from Ending the Consultation (`DoctorWorkspaceController`)
- **The Bug in Your Plan:** You put saving the prescription, labs, and extra services inside `finishConsultation()`, which immediately changes the appointment status to `completed`. If the doctor clicks *"حفظ وطباعة الروشتة"* inside Modal 2 while the patient is still in the room, and then wants to open Modal 4 to add a Sonar service or follow-up date, the patient would already be marked `completed` and disappear from the screen!
- **The Required Fix:** Split `DoctorWorkspaceController` actions into independent non-completing Modal saves + 1 final completion action:
  1. `savePrescription(int $appointmentId, ConsultationSaveRequest $request)` -> Saves symptoms, diagnosis, private notes, auto-saves new drugs to `doctor_drugs`, generates the PDF via `PrescriptionPdfService`, and optionally triggers WhatsApp/SMS send—**while keeping `appointment.status = in_consultation`**.
  2. `saveLabsAndNotes(int $appointmentId, Request $request)` -> Auto-saves new lab/radiology tests to `doctor_lab_tests`, updates `consultations.requested_labs`, and keeps the patient in room.
  3. `saveExtraServicesAndFollowup(int $appointmentId, Request $request)` -> Updates `consultations.selected_services` and `followup_date`, recalculates `appointments.extra_services_total` and `appointments.total_amount`, and keeps the patient in room.
  4. `completeAndCallNextPatient(Request $request)` -> Triggered ONLY by the giant bottom green button (`إنهاء الكشف وإدخال المريض التالي`). Marks current appointment `completed`, auto-creates the follow-up appointment if `followup_date` was set, pulls the next `arrived_waiting` patient into `in_consultation`, triggers `CheckNearTurnNotificationsJob`, recalculates queue times, and broadcasts `QueueUpdatedEvent`.

### Correction 2: Fix Queue Time Recalculation Math for Cancelled/Postponed Slots (`QueueTimeCalculatorService`)
- **The Bug in Your Plan:** In `recalculateQueueOnStatusChange`, you queried `where('queue_number', '>', $enteredQueueNumber)` and calculated `$diffSlots = $item->queue_number - $enteredQueueNumber`.
  - *Why this breaks:*(A) If Patient `#5` was `postponed_waiting_arrival` while `#6` entered the room, and then `#5` arrived at reception (`arrived_waiting`), `#5` has `queue_number < 6` so your query completely skips `#5`! (B) If `#5` and `#6` were cancelled, Patient `#7` is actually the very next person (`1` slot away), NOT `7 - 4 = 3` slots away!
- **The Required Fix:**
  1. Query all remaining active appointments for today's shift: `whereIn('status', ['arrived_waiting', 'postponed_waiting_arrival', 'booked'])` ordered by priority (`CASE WHEN status = 'arrived_waiting' THEN 0 ELSE 1 END, queue_number ASC`).
  2. Loop over the collection using the **0-based loop index `$index`**:
     - `$slotsAhead = $index + 1;`
     - `$itemStart = $baseTime->copy()->addMinutes($slotsAhead * $clinic->avg_consultation_minutes);`
     - `$itemEnd = $itemStart->copy()->addMinutes(30);`

### Correction 3: Wire Up `CheckNearTurnNotificationsJob` ("اقترب دورك")
- **The Bug in Your Plan:** You listed `CheckNearTurnNotificationsJob.php` in the directory tree, but never specified its logic or called it anywhere.
- **The Required Fix:**
  - Dispatch `CheckNearTurnNotificationsJob::dispatch($clinic->id, $shift->id, $date)` inside `completeAndCallNextPatient()` and `ReceptionController::updateStatus()`.
  - Inside `handle()`: Fetch ordered remaining queue (`booked` or `arrived_waiting`). For any appointment sitting at index `< $tenant->notification_settings['near_turn_threshold']` (default `3`) where `near_turn_notified == false`:
    - Send WhatsApp (if `whatsapp_opted_in`) or SMS notification: `"اقترب دورك في عيادة {clinic_name}! متبقي أمامك {count} مرضى، يرجى التواجد بالعيادة."`
    - Mark `near_turn_notified = true`.

### Correction 4: Handle Collecting Unpaid Extra Services at Reception (`ReceptionController`)
- **The Bug in Your Plan:** The receptionist collects the base `consultation_fee` when the patient arrives (`وصل وتحصيل`). Later, inside the examination room, the doctor adds an extra service (e.g., `سونار +200 ج.م`) via Modal 4. Your `ReceptionController` had no method for the receptionist to collect that extra `200 EGP` when the patient exits the doctor's room!
- **The Required Fix:**
  - Add `collectExtraServices(int $appointmentId): JsonResponse` to `ReceptionController`.
  - In `LiveQueueTable.vue`, if `appointment.extra_services_total > 0` and not yet collected in `cash_transactions`, show a prominent badge **`💰 تحصيل خدمات إضافية ({extra_services_total} ج.م)`** that logs the `medical_service` income transaction in 1 click.

### Correction 5: Enforce Plan `max_clinics` Limit in `SettingsController::saveBranch`
- **The Bug in Your Plan:** `SettingsController::saveBranch` did not check the doctor's subscription plan clinic limit.
- **The Required Fix:** Before creating a new `Clinic` branch:
  - Resolve `$allowed = $tenant->max_clinics_override ?? $tenant->plan->max_clinics`.
  - If `$allowed !== -1 && $tenant->clinics()->count() >= $allowed`, abort with `403`: `"لقد وصلت للحد الأقصى لعدد العيادات المسموح به في باقتك الحالية ({$allowed} عيادات). يرجى ترقية الباقة أو التواصل مع إدارة المنصة."`

### Correction 6: Add Missing Public Lead Submission Endpoint (`PublicPlanController::submitLead`)
- **The Bug in Your Plan:** You created `LeadForm.vue` in Nuxt 3, but `PublicPlanController` only had `index()` and no `POST` endpoint to receive new doctor subscription requests.
- **The Required Fix:** Add `submitLead(Request $request): JsonResponse` mapped to `POST /api/public/leads`, validating `doctor_name`, `phone`, `specialty`, `requested_plan_id`, `notes` and inserting into `doctor_leads` with `status = 'new'`.

### Correction 7: Auto-Restore WhatsApp Sessions on Node.js Startup & Anti-Ban Presence (`BaileysManager.js`)
- **The Bug in Your Plan:** If `/whatsapp-service` restarts, all doctors' connected sessions in `auth_info_multi/` remain dormant until manually re-initialized, and `OutboundHandler.js` sends messages instantaneously without typing presence.
- **The Required Fix:**
  1. Add `restoreAllSessions(): Promise<void>` in `BaileysManager.js` and call it on `server.js` startup: scans `auth_info_multi/tenant_*` folders and automatically reconnects each socket.
  2. In `OutboundHandler.js` (`sendText` and `sendPrescriptionPdf`): before `socket.sendMessage`, call `await socket.sendPresenceUpdate('composing', jid)` and wait a random delay of `1500ms` to `3000ms` to mimic human typing.

### Correction 8: Fix Tauri Secondary Monitor URL & Window Creation (`src-tauri/src/main.rs`)
- **The Bug in Your Plan:** You wrote `Navigate to http://localhost:port/tv/{tv_code}`. In a compiled Tauri production `.exe`, there is no `localhost:port` dev server!
- **The Required Fix:** Use `tauri::WebviewUrl::App(format!("index.html#/tv/{}", tv_code).into())` with `WebviewWindowBuilder` so it works identically in both `tauri dev` and the packaged production `.exe`.

### Correction 9: Handle Super Admin Impersonation Token Handshake in `/doctor-dashboard`
- **The Bug in Your Plan:** `DoctorsListView.vue` in `/super-admin` generates an impersonation token and opens `/doctor-dashboard` in a new tab, but `/doctor-dashboard/src/router/index.js` had no logic to read that token from the URL!
- **The Required Fix:** In `/doctor-dashboard/src/router/index.js` `beforeEach` guard: if `to.query.impersonate_token` is present, save it to `authStore.token`, fetch `/api/clinic/me`, show a top yellow banner `"أنت تتصفح الآن بحساب الطبيب (وضع الدعم الفني للـ Super Admin)"`, and strip the query param from the URL.

### Correction 10: Emergency Day-Off Bulk Reschedule / Cancel Handler (`SettingsController::saveDayOff`)
- **The Bug in Your Plan:** You created `clinic_days_off` migration and model, but no controller method in `SettingsController` to add a day off and handle patients already booked on that day.
- **The Required Fix:** Add `saveDayOff(Request $request): JsonResponse` to `SettingsController` with an action parameter (`cancel_and_notify` or `move_to_date`), which updates existing appointments on that date and dispatches WhatsApp/SMS apology/reschedule alerts.

---

## SECTION A: `/backend` (Laravel 11) Complete Verified Blueprint

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

## SECTION B: `/whatsapp-service` (Node.js + Baileys) Verified Blueprint

- `BaileysManager.js`:
  - `restoreAllSessions()`: Called on startup in `server.js` to auto-connect all existing tenant folders inside `auth_info_multi/`.
  - `initSession(tenantId)`: Generates QR or reconnects socket.
  - `getSession(tenantId)`: Returns active socket.
  - `disconnectSession(tenantId)`: Logs out and deletes tenant folder.
- `InboundHandler.js`:
  - `handleIncoming(tenantId, rawMessage)`: Parses `#REF` code (`CLN-XXXX`), calls Laravel `/api/internal/whatsapp/inbound-webhook`, and sends confirmation reply + live tracking link.
- `OutboundHandler.js`:
  - `sendText(tenantId, recipientPhone, message)`: Simulates typing (`sendPresenceUpdate('composing')` + 1.5s–3s delay) then sends message.
  - `sendPrescriptionPdf(tenantId, recipientPhone, pdfUrl, filename, caption)`: Simulates typing then sends the mPDF-generated Arabic prescription PDF.

---

## SECTION C: `/super-admin` (Vue 3) Verified Blueprint

- Views: `LoginView.vue`, `DashboardView.vue`, `DoctorsListView.vue`, `LeadsListView.vue`, `PlansListView.vue`, `TopupsListView.vue`.
- Modals:
  - `EditDoctorGodModeModal.vue`: Full override for `plan_id`, `subscription_ends_at`, `max_clinics_override` (`-1` = unlimited), `whatsapp_enabled` toggle, `whatsapp_balance` manual editor, `sms_enabled` toggle, `sms_balance` manual editor, custom domain Cloudflare status, and template selector.
  - `ConvertLeadModal.vue`: 1-click conversion from `doctor_leads` into an active `Tenant` + `User` + default `Clinic`.
  - `CreateDoctorModal.vue` & `PlanEditModal.vue`.

---

## SECTION D: `/doctor-dashboard` (Vue 3 + Tauri v2) Verified Blueprint

- **Impersonation Support (`src/router/index.js`):** Captures `?impersonate_token=...`, logs in as the target doctor, and shows a top support banner.
- **Screen 1 — `DoctorWorkspaceView.vue`:**
  - Single-screen layout with Branch Switcher, Live Queue Counters, Active Patient Card, Red Medical Alert Banner (`chronic_diseases` / `drug_allergies`), and Vitals Strip (Today vs. Previous Visit).
  - **4 Independent Modals (Save without ending consultation):**
    1. `ModalPastHistory.vue`: Full timeline across all branches.
    2. `ModalPrescription.vue`: Symptoms, diagnosis, private notes, template loader, autocomplete drugs (`auto-saves` any newly typed drug to `doctor_drugs`), with `"حفظ فقط"`, `"حفظ وطباعة"`, and `"حفظ وإرسال واتساب/SMS"`.
    3. `ModalLabsAndAttachments.vue`: Autocomplete labs/radiology (`auto-saves` new items to `doctor_lab_tests`) + patient attachments uploader/viewer.
    4. `ModalExtraServicesAndFollowup.vue`: Checkboxes for `clinic_services` (updates patient's bill at reception in real time) + 1-click follow-up date buttons.
  - **Giant Bottom Action Bar:** `✅ إنهاء الكشف وإدخال المريض التالي` -> Calls `completeAndCallNextPatient()`.
- **Screen 2 — `ReceptionDeskView.vue`:**
  - Quick Modals: `ModalQuickBook.vue` (auto-fills returning patient by phone + auto-detects follow-up eligibility and price), `ModalCashbox.vue` (daily income, extra services, petty cash expenses, net cash in drawer), `ModalWaitingTvLauncher.vue`.
  - `LiveQueueTable.vue`: Color-coded 1-click buttons (`وصل وتحصيل الكشف`, `تحصيل خدمات إضافية`, `قياسات المريض Vitals`, `إدخال للطبيب`, `تأجيل لحين الوصول`, `إلغاء`).
- **Screen 3 — `WaitingRoomTvView.vue` (`/tv/:code`):**
  - Full-screen RTL kiosk display with Web Audio API two-tone chime and Tauri secondary monitor HDMI window launcher (`WebviewUrl::App("index.html#/tv/{code}")`).

---

## SECTION E: `/web-platform` (Nuxt 3) Verified Blueprint

- `server/middleware/tenant.ts`: Resolves root domain (`Platform Marketing Landing Page` + `LeadForm.vue` submitting to `POST /api/public/leads`) vs. Doctor Subdomain / Cloudflare Custom Domain (SSR Doctor Website with SEO & WhatsApp OpenGraph tags).
- `components/templates/`: 6 complete specialty templates (`TemplateGeneral`, `TemplateDental`, `TemplatePediatrics`, `TemplateDermatology`, `TemplateObgyn`, `TemplateOrthopedics`).
- `composables/useClinicBooking.ts`: Shared SDK (`fetchClinicsAndShifts`, `checkPatientAndFollowupEligibility`, `submitBooking`, `subscribeToLiveQueue`).
- `pages/track/[reference_code].vue`: Live mobile queue tracker with 1-click WhatsApp `#REF` opt-in button (`wa.me`) and PDF prescription download button upon completion.