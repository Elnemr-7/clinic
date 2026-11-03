# COMPREHENSIVE FULL-PROJECT AUDIT & BUG REPORT
**Target Repository:** `https://github.com/MoElNemr/clinic`  
**Audit Scope:** `/backend`, `/doctor-dashboard`, `/super-admin`, `/web-platform`, `/JS-server`  
**Audit Criteria:** UI/UX, Frontend, Backend, System Design & Security Architecture  
**Last Updated:** 2026-11-03 (v2.0 — Reconciled Matrix & Implementation Blueprints)  

---

## 1. EXECUTIVE SUMMARY

An exhaustive, multi-criteria audit of the entire clinic management platform was conducted across all five subprojects (`Laravel 11` multi-tenant backend, `Vue 3 + Pinia` doctor/receptionist & super-admin cockpits, `Nuxt 3` SSR patient booking platform, and `Node.js + Baileys + Socket.io` real-time microservice). While foundational architectures are established, **38 distinct bugs, security gaps, data-loss vectors, and architectural disconnects** were identified and mapped to concrete code remediations.

Key high-level findings include:
1. **Silent Data Loss in Clinical Modals (`doctor-dashboard`):** Modal 3 (`ModalLabsAndAttachments.vue`) and Modal 4 (`ModalExtraServicesAndFollowup.vue`) transmit and hydrate using keys (`lab_requests`, `extra_services_billed`) that mismatch the Laravel backend schema (`requested_labs`, `selected_services`). Doctor lab orders are persisted as empty arrays (`[]`), and extra services reset on modal reload.
2. **Webhook Verification & Idempotency Gap (`backend`):** In `FawryService::verifySignature()`, signature validation returns `true` unconditionally, and the callback endpoint lacks database-level idempotency locking on `fawryRefNumber`.
3. **Broken Patient WhatsApp Opt-In Flow (`backend`):** In `WhatsAppWebhookController.php`, the inbound message regex fails to match the deep-link text generated during online booking (`"تفعيل حجز رقم CLN-XXXXX"`). Patients who click the verification link are never opted in.
4. **Reschedule Database Crash & Missing Shift Validation (`backend`):** In `SettingsController::saveDayOff`, bulk-moving booked appointments to a new date (`appointment_date = target_date`) fails to verify shift existence on the target weekday or recalculate `queue_number`, triggering duplicate key crashes on `unique_queue_per_shift (clinic_id, shift_id, appointment_date, queue_number)`.
5. **Real-Time Disconnect & Unauthenticated Socket Rooms (`JS-server`, `doctor-dashboard`, `web-platform`):** Although `JS-server` provides Socket.io channels, frontend apps rely on aggressive HTTP polling (`2s` on TV view, `10s` on tracking, `12s` on dashboard). Furthermore, `JS-server` internal broadcast endpoints and private clinic Socket rooms require token authentication before connecting clients.
6. **Nuxt 3 Codebase Shadowing (`web-platform`):** Parallel directory structures exist simultaneously (`app/pages` vs `pages`, `app/components` vs `components`), where legacy client-rendered `onMounted` pages shadow or conflict with SSR implementations.

---

## 2. RECONCILED SEVERITY MATRIX BY FOLDER

Every entry in the matrix below corresponds 1-to-1 with a documented item in Section 3.

| Folder | UI/UX | Frontend | Backend | System Design & Security | Total Findings |
| :--- | :---: | :---: | :---: | :---: | :---: |
| **`/backend`** | 1 Med (`UI-BE-11`) | 2 High (`API-BE-09`, `10`) | 2 Crit (`LOGIC-BE-04`, `05`)<br>1 High (`LOGIC-BE-06`)<br>2 Med (`LOGIC-BE-07`, `08`) | 1 Crit (`SEC-BE-01`)<br>3 High (`ARCH-BE-02`, `03`, `SEC-BE-12`)<br>1 Med (`ARCH-BE-13`) | **13** |
| **`/doctor-dashboard`** | 1 High (`UI-DD-07`)<br>2 Med (`UI-DD-08`, `09`)<br>1 Low (`UI-DD-10`) | 2 Crit (`FE-DD-01`, `02`)<br>2 High (`FE-DD-03`, `04`) | — | 1 Crit (`SYS-DD-05`)<br>1 High (`SYS-DD-06`) | **10** |
| **`/super-admin`** | 1 Med (`UI-SA-04`) | 1 High (`FE-SA-02`)<br>1 Med (`FE-SA-03`) | — | 1 Crit (`ARCH-SA-01`) | **4** |
| **`/web-platform`** | 1 Med (`UI-WP-05`) | 1 High (`FE-WP-03`)<br>1 Med (`FE-WP-04`) | — | 1 Crit (`ARCH-WP-01`)<br>1 High (`ARCH-WP-02`) | **5** |
| **`/JS-server`** | — | — | 1 Low (`LOGIC-JS-04`) | 1 Crit (`SEC-JS-05`)<br>2 High (`SYS-JS-01`, `SEC-JS-06`)<br>2 Med (`SYS-JS-02`, `03`) | **6** |
| **TOTAL** | **6** | **9** | **6** | **17** | **38** |

---

## 3. DETAILED BUG REPORT & IMPLEMENTATION PATCHES BY FOLDER

---

### FOLDER 1: `/backend` (Laravel 11 Multi-Tenant Engine)

#### Criterion: System Design & Security
- **[CRITICAL] SEC-BE-01: Fawry Signature Verification Hardcoded to `true` & Missing Replay Protection**
  - **Location:** `backend/app/Services/FawryService.php:17-21` & `FawryWebhookController.php`
  - **Problem:** `verifySignature()` unconditionally returns `true`. Additionally, Fawry SHA-256 verification requires floating-point numbers to be formatted to exactly 2 decimal places (`100.00`), and webhook processing must enforce database idempotency so replayed callbacks do not credit tenant wallets multiple times.
  - **Remediation Patch (`FawryService.php`):**
    ```php
    public function verifySignature(array $payload): bool
    {
        $securityKey = config('services.fawry.security_key');
        if (empty($securityKey) || empty($payload['messageSignature'])) {
            return false;
        }

        $fawryRefNo       = (string) ($payload['fawryRefNumber'] ?? '');
        $merchantRefNo    = (string) ($payload['merchantRefNumber'] ?? $payload['merchantRefNum'] ?? '');
        $paymentAmount    = number_format((float) ($payload['paymentAmount'] ?? 0), 2, '.', '');
        $orderAmount      = number_format((float) ($payload['orderAmount'] ?? 0), 2, '.', '');
        $orderStatus      = (string) ($payload['orderStatus'] ?? '');
        $paymentMethod    = (string) ($payload['paymentMethod'] ?? '');
        $paymentRefNumber = (string) ($payload['paymentRefrenceNumber'] ?? '');

        $concatenated = $fawryRefNo . $merchantRefNo . $paymentAmount . $orderAmount . $orderStatus . $paymentMethod . $paymentRefNumber . $securityKey;
        $expectedSignature = hash('sha256', $concatenated);

        return hash_equals(strtolower($expectedSignature), strtolower((string) $payload['messageSignature']));
    }
    ```
  - **Idempotency Requirement:** Inside the webhook controller, wrap wallet crediting in `DB::transaction()` and lock the pending transaction row (`where('reference_number', $merchantRefNo)->lockForUpdate()->first()`), verifying `status !== 'completed'` before calling `MessagingWalletService::topupBalance()`.

- **[HIGH] ARCH-BE-02: Synchronous External HTTP Dispatch Inside Locked DB Transactions**
  - **Location:** `backend/app/Events/QueueUpdatedEvent.php:31-45`
  - **Problem:** Inside `QueueUpdatedEvent::__construct()`, an HTTP call is made directly to `JS-server/internal/broadcast-queue`. Because `event(new QueueUpdatedEvent(...))` is invoked inside `DB::transaction()` blocks in `DoctorWorkspaceController::completeAndCallNextPatient()` and `ReceptionController::updateStatus()`, row locks (`lockForUpdate()`) are held while waiting for external network I/O.
  - **Remediation:** Move the HTTP broadcast out of `QueueUpdatedEvent::__construct()` into an asynchronous listener implementing `ShouldQueue` and `$afterCommit = true`, or wrap the HTTP call inside `DB::afterCommit(fn () => ...)` with a strict `->timeout(2)` second limit.

- **[HIGH] ARCH-BE-03: WhatsApp Failure Fallback Falsely Records Message As Sent**
  - **Location:** `backend/app/Services/WhatsAppChannelService.php:99-106, 145-151`
  - **Problem:** When `JS-server` is unreachable or returns an error, the `catch (\Throwable $e)` block sets `$sent = true;`, deducting the tenant's wallet balance and logging the message as `sent` even though nothing was delivered.
  - **Remediation:** Set `$sent = false;` in the catch block, only allow simulation mode when `app()->environment('local') && config('services.whatsapp.simulate_on_local', false)`, avoid deducting wallet credits on network failure, and record status as `failed` with `$e->getMessage()`.

- **[HIGH] SEC-BE-12: Impersonation Token Leakage & Missing Audit Trail**
  - **Location:** `backend/app/Http/Controllers/SuperAdmin/DoctorController.php` & `doctor-dashboard` Router
  - **Problem:** Passing a long-lived Sanctum token in the URL query string (`?impersonate_token=...`) leaves the token exposed in browser history, server logs, and Referer headers, and actions taken by the Super Admin are indistinguishable from the doctor's own actions.
  - **Remediation:**
    1. Issue impersonation tokens with a specific ability (`['impersonate']`) and a short expiration (`now()->addHours(2)`).
    2. Record an audit log entry in `super_admin_audit_logs` whenever an impersonation token is generated.
    3. In `doctor-dashboard`, immediately read `route.query.impersonate_token`, store it in `sessionStorage` (so closing the tab ends the session), set an `is_impersonating = true` flag, and strip the query parameter via `router.replace({ query: {} })`.

- **[MEDIUM] ARCH-BE-13: Tauri Desktop & Multi-Subdomain CORS Configuration**
  - **Location:** `backend/config/cors.php`
  - **Problem:** Desktop builds via Tauri (`tauri://localhost`, `http://tauri.localhost`) and dynamic clinic subdomains require explicit origin patterns in `cors.php` to avoid preflight blocks.
  - **Remediation:** Add `'tauri://localhost'`, `'http://tauri.localhost'`, and `'https://tauri.localhost'` to `allowed_origins`, and configure `allowed_origins_patterns` for tenant subdomains.

#### Criterion: Backend Logic & Database
- **[CRITICAL] LOGIC-BE-04: Emergency Day-Off Bulk Reschedule Violates Unique Queue Constraint**
  - **Location:** `backend/app/Http/Controllers/Clinic/SettingsController.php:273-280`
  - **Problem:** `$apt->update(['appointment_date' => $targetDate])` retains the old `shift_id` and `queue_number`. If `$targetDate` already has bookings with the same `queue_number`, MySQL throws a duplicate key error on `unique_queue_per_shift (clinic_id, shift_id, appointment_date, queue_number)`. Furthermore, if `$targetDate` falls on a different weekday, the old `shift_id` belongs to the wrong day of the week.
  - **Remediation Patch (`SettingsController.php`):**
    ```php
    DB::transaction(function () use ($clinicId, $dayOffDate, $targetDate) {
        $targetDayOfWeek = Carbon::parse($targetDate)->dayOfWeek; // Verify convention with ClinicShift::day_of_week
        $targetShift = ClinicShift::where('clinic_id', $clinicId)
            ->where('day_of_week', $targetDayOfWeek)
            ->where('is_active', true)
            ->first();

        if (!$targetShift) {
            throw ValidationException::withMessages([
                'target_date' => ['لا توجد فترة عمل مفعلة في التاريخ البديل المختار. يرجى إضافة فترة عمل أولاً.']
            ]);
        }

        $appointmentsToMove = Appointment::where('clinic_id', $clinicId)
            ->whereDate('appointment_date', $dayOffDate)
            ->whereNotIn('status', ['cancelled', 'completed'])
            ->orderBy('queue_number')
            ->lockForUpdate()
            ->get();

        $currentMaxQueue = (int) Appointment::where('clinic_id', $clinicId)
            ->where('shift_id', $targetShift->id)
            ->whereDate('appointment_date', $targetDate)
            ->lockForUpdate()
            ->max('queue_number');

        foreach ($appointmentsToMove as $apt) {
            $currentMaxQueue++;
            $apt->update([
                'appointment_date' => $targetDate,
                'shift_id'         => $targetShift->id,
                'queue_number'     => $currentMaxQueue,
            ]);
            // Dispatch queued WhatsApp reschedule notification after commit
        }
    });
    ```

- **[CRITICAL] LOGIC-BE-05: Inbound WhatsApp Opt-In Regex Mismatches Deep-Link Format**
  - **Location:** `backend/app/Http/Controllers/Internal/WhatsAppWebhookController.php:66-70`
  - **Problem:** `PublicBookingController` generates WhatsApp deep-links with text: `تفعيل حجز رقم CLN-XXXXX`. However, `WhatsAppWebhookController` tests `preg_match('/(?:حجز|كشف|CLN-)?\s*#?([A-Z0-9]{4,8})/iu', $text, $matches)`. Because `تفعيل` precedes `حجز` and `[A-Z0-9]` excludes the hyphen in `CLN-XXXXX`, it fails to match the reference code properly.
  - **Remediation Patch:**
    ```php
    if (preg_match('/(?:تفعيل\s*)?(?:حجز|كشف)?(?:\s*رقم)?\s*#?\b(CLN-[A-Z0-9]{4,8}|[A-Z0-9]{4,8})\b/iu', $text, $matches)) {
        $referenceCode = strtoupper($matches[1]);
        // Lookup appointment by reference_code
    }
    ```

- **[HIGH] LOGIC-BE-06: Doctor Lead Conversion Creates Branch With Zero Working Shifts**
  - **Location:** `backend/app/Http/Controllers/SuperAdmin/DoctorLeadController.php:61-70`
  - **Problem:** Converting a lead creates a `Tenant`, a `User`, and a `Clinic`, but creates zero `ClinicShift` records. Any booking attempt immediately returns `"لا توجد فترات عمل متاحة اليوم لهذا الفرع"`.
  - **Remediation:** Inside `DoctorLeadController::convert()`, seed default shifts (Saturday to Thursday, `17:00:00` to `22:00:00`, `max_patients = 30`, `is_active = true`) for the newly created clinic.

- **[MEDIUM] LOGIC-BE-07: Turn Notification Job Ignores `waiting` Status**
  - **Location:** `backend/app/Jobs/CheckNearTurnNotificationsJob.php:45`
  - **Problem:** The query filters `whereIn('status', ['arrived_waiting', 'booked'])`, skipping appointments with `'waiting'` status.
  - **Remediation:** Update filter to `whereIn('status', ['arrived_waiting', 'waiting', 'booked'])`.

- **[MEDIUM] LOGIC-BE-08: Follow-Up Eligibility Date Comparison Excludes Same-Day Follow-Up**
  - **Location:** `backend/app/Services/FollowupDetectorService.php:63`
  - **Problem:** `where('appointment_date', '>', $lastCompletedVisit->appointment_date)` excludes follow-up visits scheduled or recorded on the same day.
  - **Remediation:** Change to `where('appointment_date', '>=', $lastCompletedVisit->appointment_date)->where('id', '!=', $lastCompletedVisit->id)`.

#### Criterion: Frontend / API Contracts
- **[HIGH] API-BE-09: Quick-Book Endpoint Parameter Mismatch with Reception Desk**
  - **Location:** `backend/app/Http/Controllers/Clinic/ReceptionController.php:77-85`
  - **Problem:** `ReceptionController::quickBook` validates `visit_type` (`new_visit,followup,emergency,procedure`), while `ModalQuickBook.vue` sends `appointment_type` (`examination,urgent`).
  - **Remediation:** Map incoming `appointment_type` in `ReceptionController::quickBook`:
    ```php
    $typeMap = [
        'examination' => 'new_visit',
        'urgent'      => 'emergency',
        'followup'    => 'followup',
        'procedure'   => 'procedure',
    ];
    $visitType = $request->input('visit_type') ?? ($typeMap[$request->input('appointment_type')] ?? 'new_visit');
    ```

- **[HIGH] API-BE-10: Immediate Desk Payment Flag Ignored in `quickBook`**
  - **Location:** `backend/app/Http/Controllers/Clinic/ReceptionController.php:154`
  - **Problem:** `quickBook` hardcodes `'is_paid' => false`, ignoring `mark_paid` / `markPaidNow` from the receptionist modal and failing to record a `CashTransaction`.
  - **Remediation:** Check `$isPaid = $request->boolean('mark_paid') || $request->boolean('markPaidNow');`. When true, set `'is_paid' => true`, `'paid_at' => now()`, and create the corresponding `CashTransaction` entry inside the same DB transaction.

#### Criterion: UI/UX (Blade Template)
- **[MEDIUM] UI-BE-11: Prescription PDF Font Compatibility on Linux Hosts**
  - **Location:** `backend/resources/views/prescriptions/pdf_template.blade.php:8` & `PrescriptionPdfService.php`
  - **Problem:** Template specifies `'DejaVu Sans', 'Arial'`, which can render Arabic ligatures poorly on minimal Linux containers.
  - **Remediation:** Configure mPDF with `'autoScriptToLang' => true`, `'autoLangToFont' => true`, and bundle `Cairo` or `Amiri` TTF fonts in `resources/fonts/`.

---

### FOLDER 2: `/doctor-dashboard` (Vue 3 + Vite + Tailwind v4)

#### Criterion: Frontend State & Modals
- **[CRITICAL] FE-DD-01: Modal 3 (Labs & Attachments) Payload Field Mismatch Causes Empty DB Writes**
  - **Location:** `doctor-dashboard/src/components/doctor/modals/ModalLabsAndAttachments.vue:38, 101`
  - **Problem:** Modal 3 initializes from `c.lab_requests` and submits `{ lab_requests: labRequests.value }`, whereas `DoctorWorkspaceController::saveLabsAndNotes` reads `$request->input('requested_labs', [])` and stores `consultation.requested_labs`.
  - **Remediation:**
    - In `ModalLabsAndAttachments.vue`: hydrate from `c.requested_labs || c.lab_requests || []` and send both keys (`requested_labs: labRequests.value, lab_requests: labRequests.value`) for backward compatibility.
    - In `DoctorWorkspaceController::saveLabsAndNotes`: accept `$request->input('requested_labs', $request->input('lab_requests', []))`.

- **[CRITICAL] FE-DD-02: Modal 4 (Extra Services) State Reset on Open**
  - **Location:** `doctor-dashboard/src/components/doctor/modals/ModalExtraServicesAndFollowup.vue:33-35`
  - **Problem:** Modal 4 reads `appt.consultation.extra_services_billed`, while the backend saves and returns `consultation.selected_services`.
  - **Remediation:** Update initialization in `ModalExtraServicesAndFollowup.vue` to:
    ```javascript
    const rawServices = appt.consultation?.selected_services ?? appt.consultation?.extra_services_billed ?? [];
    ```
    And submit `selected_services` in the save payload to match `DoctorWorkspaceController::saveExtraServicesAndFollowup`.

- **[HIGH] FE-DD-03: Unmounted Impersonation Security Banner**
  - **Location:** `doctor-dashboard/src/components/common/ImpersonationBanner.vue` & `AppShellLayout.vue`
  - **Problem:** `ImpersonationBanner.vue` exists but is never imported or mounted inside `AppShellLayout.vue`.
  - **Remediation:** Import `ImpersonationBanner` inside `AppShellLayout.vue` and render `<ImpersonationBanner />` above the main top bar whenever `authStore.isImpersonating` is true.

- **[HIGH] FE-DD-04: Non-Standard HTML5 Date Input Value Format**
  - **Location:** `doctor-dashboard/src/components/doctor/modals/ModalExtraServicesAndFollowup.vue:38`
  - **Problem:** Assigning a full ISO-8601 timestamp (`2026-11-10T00:00:00.000000Z`) to `<input type="date">` fails browser validation and displays an empty date input.
  - **Remediation:** Slice the date string: `followupDate.value = String(appt.consultation?.followup_date || '').slice(0, 10);`.

#### Criterion: System Design & Performance
- **[CRITICAL] SYS-DD-05: Aggressive 2-Second HTTP Polling on Waiting Room TV**
  - **Location:** `doctor-dashboard/src/views/WaitingRoomTvView.vue:90`
  - **Problem:** `setInterval(fetchQueue, 2000)` queries `GET /api/public/tv/{code}` 30 times per minute per screen.
  - **Remediation:** Connect to `JS-server` via `socket.io-client`, emit `join_tv` with `tvCode`, and invoke `fetchQueue()` (or patch state directly) on `queue:updated`. Increase fallback `setInterval` polling to `60000` ms (60 seconds).

- **[HIGH] SYS-DD-06: Missing Real-Time Socket Connection in Doctor & Receptionist Shell**
  - **Location:** `doctor-dashboard/src/components/layout/AppShellLayout.vue:306-314`
  - **Problem:** `AppShellLayout` relies on a 12-second `setInterval` poll instead of subscribing to `clinic_${clinicId}` Socket.io events.
  - **Remediation:** Create a reusable `useClinicSocket.js` composable that connects to `import.meta.env.VITE_SOCKET_URL`, authenticates the clinic session, listens for `queue:updated`, and refreshes the workspace/reception store immediately.

#### Criterion: UI/UX & Design System
- **[HIGH] UI-DD-07: Modal 1 (Patient Past History) Hardcoded to Dark Palette**
  - **Location:** `doctor-dashboard/src/components/doctor/modals/ModalPastHistory.vue:2-40`
  - **Problem:** Hardcoded Tailwind classes (`bg-slate-950`, `bg-slate-900`, `border-slate-800`, `text-white`) break Light Mode consistency.
  - **Remediation:** Replace with semantic design tokens (`bg-surface-card`, `bg-surface-elevated`, `border-line-subtle`, `text-content-primary`, `text-content-muted`).

- **[MEDIUM] UI-DD-08: Test Print Margins Bypasses Real PDF Engine**
  - **Location:** `doctor-dashboard/src/views/settings/ClinicalSettingsView.vue:661-671`
  - **Problem:** The margin preview opens a synthetic HTML popup window instead of rendering through the backend mPDF engine.
  - **Remediation:** Call `GET /api/clinic/settings/test-print?top=...&bottom=...&left=...&right=...` to preview the exact mPDF output.

- **[MEDIUM] UI-DD-09: Missing Custom Domain Form Controls in Website Settings**
  - **Location:** `doctor-dashboard/src/views/settings/WebsiteSettingsView.vue:30-36`
  - **Problem:** Custom domain is rendered as read-only text without an input field or save trigger for `SettingsController::updateCustomDomain`.
  - **Remediation:** Add an editable input bound to `customDomain`, a CNAME instruction box, and a save button calling `PUT /api/clinic/settings/custom-domain`.

- **[LOW] UI-DD-10: Orphaned Legacy Components in Source Tree**
  - **Location:** `doctor-dashboard/src/components/common/DoctorTopNav.vue`, `OfflineBlockerModal.vue`, `doctor-dashboard/src/style.css`
  - **Problem:** Unused components and default Vite boilerplate styles remain in the codebase.
  - **Remediation:** Delete unused component files and strip boilerplate rules from `src/style.css`.

---

### FOLDER 3: `/super-admin` (Vue 3 + Vite + Tailwind v4)

#### Criterion: System Design & Architecture
- **[CRITICAL] ARCH-SA-01: Hardcoded Localhost Port & Incompatible Router Hash in Impersonation**
  - **Location:** `super-admin/src/views/DoctorsListView.vue:126-130`
  - **Problem:** `window.open('http://localhost:5174/#/workspace?impersonate_token=...')` fails outside local development and uses hash routing (`/#/`), whereas `doctor-dashboard` uses HTML5 history mode (`createWebHistory()`).
  - **Remediation Patch:**
    ```javascript
    const impersonate = async (doc) => {
      const result = await doctorsStore.impersonateDoctor(doc.id);
      const baseUrl = (import.meta.env.VITE_DOCTOR_DASHBOARD_URL || 'http://localhost:5174').replace(/\/$/, '');
      window.open(`${baseUrl}/workspace?impersonate_token=${encodeURIComponent(result.token)}`, '_blank', 'noopener,noreferrer');
    };
    ```

#### Criterion: Frontend State & Navigation
- **[HIGH] FE-SA-02: Router Auth Guard Bypasses Token Validation**
  - **Location:** `super-admin/src/router/index.js:33-44`
  - **Problem:** The router guard checks `!!localStorage.getItem('super_admin_token')` without verifying whether the token is still valid or loading the admin profile on initial boot.
  - **Remediation:** In `router.beforeEach`, if `token` exists but `!authStore.adminLoaded`, await `authStore.fetchProfile()`. If it throws a 401, clear storage and return `{ name: 'Login' }`.

- **[MEDIUM] FE-SA-03: Full Page Reload on API 401 Disconnect**
  - **Location:** `super-admin/src/api/client.js:24-26`
  - **Problem:** `window.location.href = '/login'` triggers a hard browser reload on 401 responses.
  - **Remediation:** Import the router instance (or emit an auth-expired event) and navigate cleanly via `router.push('/login')`.

#### Criterion: UI/UX & Styling
- **[MEDIUM] UI-SA-04: Conflicting Default Boilerplate CSS in `style.css`**
  - **Location:** `super-admin/src/style.css:1-50`
  - **Problem:** Retains default Vite demo styles (`#aa3bff` accent and centering rules) that conflict with `assets/css/theme.css`.
  - **Remediation:** Clear `super-admin/src/style.css` and import only `assets/css/theme.css` in `main.js`.

---

### FOLDER 4: `/web-platform` (Nuxt 3 Multi-Tenant SSR)

#### Criterion: System Design & Architecture
- **[CRITICAL] ARCH-WP-01: Parallel Duplicate Directories (`app/` vs Root)**
  - **Location:** `web-platform/app/pages` vs `web-platform/pages`, `web-platform/app/components` vs `web-platform/components`
  - **Problem:** Duplicate directory trees exist side-by-side (`app/pages/index.vue` has legacy client-side `onMounted` fetching, while root `pages/index.vue` has SSR `useRequestHeaders` + `useAsyncData`).
  - **Remediation:** Audit file timestamps and diff every file in `app/` against root (`pages/`, `components/`, `composables/`) to ensure no unique template edits are lost, then consolidate into the single root Nuxt 3 directory structure and delete the redundant duplicate folder.

- **[HIGH] ARCH-WP-02: Client-Side Redirect Delay & Environment Leak in Prescription Download**
  - **Location:** `web-platform/pages/rx/[token].vue:9-12`
  - **Problem:** Redirecting inside `onMounted()` flashes a blank screen on mobile devices and falls back to `http://localhost:8000/api` if `config.public.apiBase` is unset on the client.
  - **Remediation:** Replace `onMounted` with SSR-compatible `await navigateTo(`${apiBase}/rx/${route.params.token}`, { external: true, redirectCode: 302 })` directly inside `<script setup>`.

#### Criterion: Frontend State & Booking Logic
- **[HIGH] FE-WP-03: Booking Form Fails to Filter Shifts by Chosen Date (Timezone-Safe)**
  - **Location:** `web-platform/components/templates/DoctorBookingForm.vue:44-60`
  - **Problem:** The shift dropdown displays all shifts for the clinic across the entire week rather than filtering by the selected `appointmentDate`. Note that parsing `new Date('YYYY-MM-DD')` directly in JS treats the string as UTC midnight, which can shift the day backward in negative UTC offsets.
  - **Remediation Patch (`DoctorBookingForm.vue`):**
    ```javascript
    const shifts = computed(() => {
      const allShifts = currentClinic.value?.shifts || [];
      if (!appointmentDate.value) return allShifts;

      const [year, month, day] = appointmentDate.value.split('-').map(Number);
      if (!year || !month || !day) return allShifts;

      // Local date construction avoids UTC midnight timezone shift (0 = Sunday ... 6 = Saturday)
      const selectedDayOfWeek = new Date(year, month - 1, day).getDay();

      return allShifts.filter((s) => Number(s.day_of_week) === selectedDayOfWeek && s.is_active !== false);
    });
    ```

- **[MEDIUM] FE-WP-04: Non-SSR Axios Client Inside Nuxt Composable**
  - **Location:** `web-platform/composables/useClinicBooking.ts:1-13`
  - **Problem:** Raw `axios` instances do not automatically forward SSR headers or integrate with Nuxt's payload hydration cache.
  - **Remediation:** Replace `axios` calls in `useClinicBooking.ts` with Nuxt's built-in `$fetch` configured with `baseURL: useRuntimeConfig().public.apiBase`.

#### Criterion: UI/UX & Responsive Design
- **[MEDIUM] UI-WP-05: Hardcoded Colors in Patient Queue Tracking Page**
  - **Location:** `web-platform/pages/track/[reference_code].vue:37-95`
  - **Problem:** Hardcoded Tailwind utility colors (`bg-slate-50`, `bg-teal-600`, `text-emerald-700`) bypass the tenant theme system.
  - **Remediation:** Replace hardcoded color classes with semantic theme tokens from `assets/css/theme.css`.

---

### FOLDER 5: `/JS-server` (Node.js Baileys & Real-Time Microservice)

#### Criterion: System Design, Security & Scalability
- **[CRITICAL] SEC-JS-05: Unauthenticated Internal Broadcast & Session Management Endpoints**
  - **Location:** `JS-server/server.js` (`/internal/broadcast-queue`, `/api/sessions/*`)
  - **Problem:** Internal endpoints that trigger Socket.io broadcasts or manage WhatsApp Baileys sessions must validate an internal shared secret header (`X-Internal-Secret`) so external callers cannot spoof queue updates or manipulate WhatsApp sessions.
  - **Remediation:** Add an Express middleware checking `req.headers['x-internal-secret'] === process.env.INTERNAL_API_SECRET` using `crypto.timingSafeEqual` on `/internal/*` and `/api/sessions/*`, and pass this header from Laravel's `WhatsAppChannelService` and `QueueUpdatedEvent`.

- **[HIGH] SYS-JS-01: Dormant Real-Time Broadcast Infrastructure**
  - **Location:** `JS-server/server.js:785-824`
  - **Problem:** `server.js` exposes Socket.io rooms (`clinic_${cid}`, `tv_${tv}`, `track_${ref}`), but the frontend applications do not subscribe to them.
  - **Remediation:** Connect `doctor-dashboard` (Workspace & TV View) and `web-platform` (`track/[reference_code].vue`) via `socket.io-client`.

- **[HIGH] SEC-JS-06: Unauthenticated Socket.io Room Joins (`clinic_${clinicId}`)**
  - **Location:** `JS-server/server.js:785-824`
  - **Problem:** Allowing any client to emit `join_clinic` with an arbitrary `clinicId` exposes private patient queue data across tenants. (Public rooms `tv_${tvCode}` and `track_${referenceCode}` already use unguessable tokens/codes, whereas `clinic_${clinicId}` uses sequential integer IDs).
  - **Remediation:** Require an HMAC-signed room token (generated by Laravel on login/workspace load using `hash_hmac('sha256', "clinic_{$clinicId}", config('services.js_server.secret'))`) when joining `clinic_${clinicId}` rooms in `server.js`.

- **[MEDIUM] SYS-JS-02: In-Memory Reconnect Backoff State Not Garbage Collected**
  - **Location:** `JS-server/server.js:141-143, 340-365`
  - **Problem:** The `reconnectAttempts` map retains keys for deleted or logged-out WhatsApp sessions indefinitely.
  - **Remediation:** Delete `reconnectAttempts[sessionId]` upon session open (`connection === 'open'`) and inside the session cleanup/delete handler.

- **[MEDIUM] SYS-JS-03: Cache Memory Ceiling Unbounded**
  - **Location:** `JS-server/cache.js:1-45`
  - **Problem:** The custom in-memory cache has TTL expiration but no upper limit on key count.
  - **Remediation:** Add a `maxKeys = 1000` check in `cache.set()` that evicts the oldest key (`this.store.keys().next().value`) when capacity is reached.

#### Criterion: Frontend / Backend Integration
- **[LOW] LOGIC-JS-04: Non-Normalized Phone Numbers in Pairing Requests**
  - **Location:** `JS-server/server.js:475-485`
  - **Problem:** Phone numbers entered with leading zeros (`010...`), spaces, or `+` symbols fail Baileys `requestPairingCode()` JID formatting.
  - **Remediation:** Normalize phone numbers before requesting pairing codes:
    ```javascript
    let cleaned = String(phoneNumber).replace(/\D/g, '');
    if (cleaned.startsWith('01') && cleaned.length === 11) {
      cleaned = '2' + cleaned; // Convert 01xxxxxxxxx to 201xxxxxxxxx
    }
    ```

---

## 4. PRIORITIZED REMEDIATION ROADMAP

### Phase 1: Critical Data Integrity & Security Hardening (Immediate)
1. **Clinical Modals Data Loss (`FE-DD-01`, `FE-DD-02`, `FE-DD-04`):** Align `requested_labs` in Modal 3, `selected_services` in Modal 4, and `.slice(0, 10)` on `followup_date`.
2. **Fawry Webhook Security (`SEC-BE-01`):** Implement 2-decimal formatted SHA-256 HMAC verification in `FawryService.php` and database idempotency locking in the callback controller.
3. **WhatsApp Opt-In Regex (`LOGIC-BE-05`):** Update `WhatsAppWebhookController.php` regex to match `تفعيل حجز رقم CLN-XXXXX`.
4. **Emergency Day-Off Rescheduling (`LOGIC-BE-04`):** Validate target shift existence and recalculate `queue_number` sequentially inside `SettingsController::saveDayOff`.
5. **Super Admin Impersonation (`ARCH-SA-01`, `SEC-BE-12`, `FE-DD-03`):** Fix `DoctorsListView.vue` URL construction (remove `/#/`, use `VITE_DOCTOR_DASHBOARD_URL`), strip `impersonate_token` from URL after hydration, and mount `<ImpersonationBanner />` in `AppShellLayout.vue`.
6. **Microservice Endpoint & Socket Auth (`SEC-JS-05`, `SEC-JS-06`):** Enforce `X-Internal-Secret` middleware on `JS-server` internal routes and HMAC token verification on `clinic_${clinicId}` Socket room joins.

### Phase 2: API Contracts, SSR Consolidation & Booking Logic
1. **Quick-Book API Alignment (`API-BE-09`, `API-BE-10`):** Map `appointment_type` to `visit_type` and process immediate cash transactions when `mark_paid` is true in `ReceptionController::quickBook`.
2. **Booking Form Shift Filtering (`FE-WP-03`):** Filter available shifts by local weekday (`new Date(y, m - 1, d).getDay()`) in `DoctorBookingForm.vue`.
3. **Nuxt 3 Directory Consolidation (`ARCH-WP-01`, `ARCH-WP-02`, `FE-WP-04`):** Merge and remove duplicate `web-platform/app/` files, convert `/rx/[token].vue` to SSR redirect, and switch `useClinicBooking.ts` to `$fetch`.
4. **Lead Conversion Default Shifts (`LOGIC-BE-06`):** Seed 6 default weekly shifts when converting a doctor lead in `DoctorLeadController::convert`.

### Phase 3: Real-Time Socket Activation & UI Polish
1. **Socket.io Client Integration (`SYS-DD-05`, `SYS-DD-06`, `SYS-JS-01`):** Connect `WaitingRoomTvView.vue`, `AppShellLayout.vue`, and `track/[reference_code].vue` to `JS-server` Socket.io rooms and drop aggressive 2s/12s HTTP polling to a 60s fallback heartbeat.
2. **Asynchronous Event Broadcasting (`ARCH-BE-02`, `ARCH-BE-03`):** Move `QueueUpdatedEvent` HTTP dispatch to `DB::afterCommit()` or a queued listener, and fix `WhatsAppChannelService` error handling so failed messages are not logged as `sent`.
3. **Theme & Print Polish (`UI-DD-07`, `UI-DD-08`, `UI-DD-09`, `UI-WP-05`, `UI-BE-11`):** Replace hardcoded dark classes in `ModalPastHistory.vue`, wire test-print to mPDF, add custom domain input controls, and bundle Arabic TTF fonts for PDF generation.