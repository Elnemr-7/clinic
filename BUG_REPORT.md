# Clinic SaaS Platform - Comprehensive System Audit & Bug Report

**Date of Audit:** 2026-11-07  
**Environment Assessed:** Development / Staging  
**Audited Sub-projects:**
1. `backend` (Laravel 11 / PHP 8.4 API)
2. `doctor-dashboard` (Vue 3 / Vite Single Page Application)
3. `JS-server` (Node.js / Express / Baileys WhatsApp & Socket.io Microservice)
4. `super-admin` (Vue 3 / Vite Single Page Application)
5. `web-platform` (Nuxt 3 SSR Patient Booking & Website Engine)

---

## Table of Contents
1. [Backend (Laravel API)](#1-backend-laravel-api)
2. [Doctor Dashboard (Vue 3 SPA)](#2-doctor-dashboard-vue-3-spa)
3. [JS Server (Node.js Microservice)](#3-js-server-nodejs-microservice)
4. [Super Admin (Vue 3 SPA)](#4-super-admin-vue-3-spa)
5. [Web Platform (Nuxt 3 Platform)](#5-web-platform-nuxt-3-platform)

---

## 1. Backend (Laravel API)

### [SECURITY / AUTHORIZATION BYPASS] Missing Role Middleware on Clinic Settings Routes
- **File:** `backend/routes/api.php`
- **Line:** 102–135
- **Description:** The route group `Route::prefix('settings')` is wrapped exclusively in `['auth:sanctum', 'clinic.auth', 'tenant.active']`. The `ClinicAuth` middleware checks `$user->role` against arguments only when arguments are passed. Because no arguments are specified on the settings group, any authenticated receptionist can access all settings endpoints.
- **Impact:** Any receptionist can view WhatsApp QR codes/pairing codes, create, modify, or delete clinic branches, delete prescription templates, change doctor print margins, modify custom domain configurations, and create or delete other receptionist accounts.

---

### [SECURITY / INFORMATION DISCLOSURE] Unprotected Public Storage for Confidential Patient Medical Attachments
- **File:** `backend/app/Http/Controllers/Clinic/DoctorWorkspaceController.php`
- **Line:** 381–390
- **Description:** Patient attachments (lab reports, radiology scans, medical records) are stored directly on the `public` disk via `$file->store("attachments/{$tenantId}/{$patientId}", 'public')` and saved with a publicly accessible path `/storage/attachments/...`.
- **Impact:** Confidential patient health records and diagnostics are accessible to unauthenticated third parties on the public internet by simply browsing or guessing attachment URLs, violating basic healthcare data privacy laws.

---

### [SECURITY / FILE UPLOAD VULNERABILITY] Unrestricted File Types on Patient Attachments
- **File:** `backend/app/Http/Controllers/Clinic/DoctorWorkspaceController.php`
- **Line:** 376–377
- **Description:** The upload validation rule is `'file' => 'required|file|max:10240'`. There is no validation of MIME types or file extensions (e.g. `mimes:pdf,jpg,png`).
- **Impact:** Users can upload malicious `.html`, `.svg` (with embedded JavaScript), or executable scripts. When viewed by a doctor or staff member via `/storage/...`, these files execute within the platform's origin, enabling Stored Cross-Site Scripting (XSS) and session hijacking.

---

### [SECURITY / FINANCIAL FRAUD] Free Messaging Balance Credited on Failed or Expired Fawry Payments
- **File:** `backend/app/Services/FawryService.php`
- **Line:** 39–68
- **Description:** `processCallback()` validates the cryptographic signature of the callback payload from Fawry, but never inspects `$payload['orderStatus']` (e.g. `'PAID'` or `'SUCCESS'`).
- **Impact:** When a transaction fails, expires, or is cancelled, Fawry sends a signed webhook callback containing `orderStatus: 'FAILED'` or `'EXPIRED'`. The system treats any signed payload as valid payment, marks the topup as paid, and credits WhatsApp and SMS messaging quotas without receiving payment.

---

### [SECURITY / FINANCIAL DATA INTEGRITY] Hard Deletion of Cash Transactions Destroys Financial Audit Trail
- **File:** `backend/app/Http/Controllers/Clinic/ReceptionController.php`
- **Line:** 248–249
- **Description:** When an appointment status is updated to `cancelled` with `refund_money = true`, the controller executes `CashTransaction::where('appointment_id', $appointment->id)->delete()`.
- **Impact:** Hard deleting financial ledger records destroys the audit trail. Daily drawer reconciliation cannot explain missing cash, determine which receptionist processed the refund, or maintain compliance with standard accounting practices.

---

### [SECURITY / FINANCIAL MANIPULATION] Arbitrary and Negative Pricing on Extra Services
- **File:** `backend/app/Http/Controllers/Clinic/DoctorWorkspaceController.php`
- **Line:** 223–231
- **Description:** When saving extra services, the total billed amount is calculated by iterating over `$request->input('selected_services')` and summing `$srv['price']` directly from client input without verifying against the database prices in `clinic_services` or enforcing a minimum value (`min:0`).
- **Impact:** An attacker or corrupted client request can inject zero or negative price amounts (`price: -500`), artificially reducing the total consultation fee and manipulating cashbox balances.

---

### [DATA INTEGRITY / CRASH] Undefined Variable and Code Overwrite in `saveBranch`
- **File:** `backend/app/Http/Controllers/Clinic/SettingsController.php`
- **Line:** 282, 296
- **Description:** Inside `Clinic::updateOrCreate(...)`, line 296 evaluates `'tv_screen_code' => $clinic?->tv_screen_code ?? strtoupper(Str::random(6))`. At this point, the variable `$clinic` has not been assigned.
- **Impact:** In PHP 8+, accessing an undefined variable throws an error or notice. Furthermore, because `$clinic` evaluates to null, updating any branch details (such as address or phone number) regenerates a new random `tv_screen_code`, instantly disconnecting and invalidating all waiting room TV displays configured for that clinic.

---

### [LOGIC BUG / DATABASE INTEGRITY] Incompatible Enum Values in Database Migration for Appointments
- **File:** `backend/database/migrations/2026_01_01_000010_create_appointments_table.php`
- **Line:** 23–31
- **Description:** The migration defines the `status` enum as `['pending_payment', 'booked', 'arrived_waiting', 'in_consultation', 'postponed_waiting_arrival', 'completed', 'cancelled']`. However, multiple controllers and services query and filter using `'waiting'` and `'postponed'` (e.g. `DoctorWorkspaceController.php:63, 65`, `CheckNearTurnNotificationsJob.php:45`, `QueueTimeCalculatorService.php:36`).
- **Impact:** In production database engines like MySQL under default strict SQL mode, attempting to save or update an appointment with status `'waiting'` or `'postponed'` triggers a `Data truncated for column 'status'` database exception.

---

### [LOGIC BUG / DATA TYPE MISMATCH] DateTime String Inserted into `TIME` Column in Queue Calculator
- **File:** `backend/app/Services/QueueTimeCalculatorService.php`
- **Line:** 24–25, 60–61
- **Description:** `QueueTimeCalculatorService` formats `estimated_window_start` and `estimated_window_end` as `'Y-m-d H:i:s'`. However, the `appointments` migration defines both columns as `$table->time(...)`.
- **Impact:** Under MySQL strict mode, inserting a full datetime string into a `TIME` column causes time truncation or SQL syntax errors (`Data truncated for column`), or caps at `838:59:59`.

---

### [SECURITY / AUTHENTICATION] Missing Rate Limiting on Login Endpoints
- **File:** `backend/routes/api.php`
- **Line:** 31, 62
- **Description:** Neither `/api/super-admin/login` nor `/api/clinic/login` has rate limiting (`throttle` middleware) configured.
- **Impact:** Unauthenticated attackers can launch automated high-speed password brute-force and credential stuffing attacks against platform super-administrators, doctors, and clinic staff.

---

### [LOGIC BUG / BUSINESS LOGIC] Followup Appointments Silently Dropped on Inactive or Multi-Shift Days
- **File:** `backend/app/Http/Controllers/Clinic/DoctorWorkspaceController.php`
- **Line:** 276–277
- **Description:** When a doctor completes a consultation with a specified `followup_date`, the system attempts to find a shift using `$current->clinic->shifts()->where('day_of_week', $dayOfWeek)->where('is_active', true)->lockForUpdate()->first()`.
- **Impact:** If the clinic is closed on that day of the week, the condition evaluates to null and the followup appointment is silently discarded without any error or notification to the doctor. If multiple shifts exist on that day (e.g., morning and evening), the system always picks the first shift regardless of doctor schedule or capacity.

---

### [SECURITY / IDOR / BUSINESS LOGIC] Public Appointment Booking Permits Mismatched Shift and Clinic IDs
- **File:** `backend/app/Http/Controllers/Public/PublicBookingController.php`
- **Line:** 150–153
- **Description:** The public booking controller accepts both `clinic_id` and `shift_id`. It fetches the shift using `ClinicShift::withoutGlobalScope(TenantScope::class)->where('id', $request->shift_id)->lockForUpdate()->firstOrFail()`, but never checks if `$shift->clinic_id === $clinic->id` or if `$shift->day_of_week === $date->dayOfWeek`.
- **Impact:** A patient can book an appointment on a Friday using a shift configured for Mondays, or assign a shift belonging to a completely different tenant/clinic to their appointment, corrupting queue sequencing and capacity counters.

---

### [SECURITY / CONFIGURATION] Hardcoded Fallback Microservice Secret Across Components
- **File:** `backend/app/Http/Middleware/InternalWebhookAuth.php:13`, `backend/config/services.php:40`, `backend/app/Http/Controllers/Clinic/AuthController.php:43`
- **Description:** The internal microservice shared secret falls back to the hardcoded string `'vertex_wa_secure_secret_key_2026'`.
- **Impact:** If environment configuration is missing or misnamed, all inter-service webhook authentication, HMAC validation, and socket room joining use a publicly known default credential, allowing attackers to forge webhook events.

---

### [PERFORMANCE] Synchronous Microservice Dispatch in Doctor Consultation Handler
- **File:** `backend/app/Http/Controllers/Clinic/DoctorWorkspaceController.php`
- **Line:** 146
- **Description:** The prescription save handler dispatches `SendWhatsAppPrescriptionJob` synchronously using `dispatch_sync(...)`.
- **Impact:** The doctor's HTTP request blocks while the backend connects to the Node.js server, generates and base64-encodes the PDF, and transmits it to the WhatsApp Baileys socket. Any microservice lag or network delay hangs the doctor workspace UI for 5–25 seconds.

---

### [ROUTING BUG / CODE QUALITY] Dangerous State-Modifying GET Route and Duplicate Routes
- **File:** `backend/routes/api.php`
- **Line:** 74, 76–78
- **Description:** Line 74 defines `Route::get('/prescription/{appointmentId}', ... 'savePrescription')`, binding a state-mutating POST action to an HTTP GET request. Lines 76 and 78 define duplicate GET `/services` routes pointing to `SettingsController::getServices`.
- **Impact:** Web crawlers, link prefetchers, or browser caching can trigger state changes via GET requests, and route tables contain dead redundant routes.

---

### [DATA INTEGRITY / TENANT ISOLATION] Global Unique Email Constraint Prevents Multi-Tenant Staff Accounts
- **File:** `backend/database/migrations/2026_01_01_000006_create_users_table.php`
- **Line:** 16
- **Description:** The `users` table enforces a global unique index on `email` (`$table->string('email')->unique()`).
- **Impact:** In a SaaS multi-tenant platform, different clinics cannot employ receptionists with the same email address. A receptionist working across two distinct clinics or re-registering under another tenant is blocked with a duplicate email error.

---

### [PERFORMANCE / RACE CONDITION] Topup Approval Lacks Transaction and Pessimistic Locking
- **File:** `backend/app/Http/Controllers/SuperAdmin/TopupController.php`
- **Line:** 30–51
- **Description:** The `approve` method increments the tenant balance and updates the topup record without wrapping the operations in a database transaction or applying `lockForUpdate()`.
- **Impact:** If two super-administrators click approve simultaneously or if network retries duplicate the request, both requests can pass the status check, double crediting the tenant balance.

---

## 2. Doctor Dashboard (Vue 3 SPA)

### [LOGIC / UX BUG] Doctor Consultation Screen Never Updates Live (Missing Socket & Polling)
- **File:** `doctor-dashboard/src/views/DoctorWorkspaceView.vue`
- **Line:** 514–574
- **Description:** While a reusable `useClinicSocket` composable exists in `src/composables/useClinicSocket.js`, it is never imported or initialized in `DoctorWorkspaceView.vue`. The workspace also lacks any periodic background polling (`setInterval`).
- **Impact:** When a receptionist admits a patient, marks an arrival, or adds an emergency booking, the doctor's queue sidebar and current patient counter remain stale until the doctor manually refreshes the entire web page.

---

### [BUG / INFINITE RELOAD LOOP] `/rx/:token` Route Triggers Infinite Browser Reload
- **File:** `doctor-dashboard/src/router/index.js`
- **Line:** 41–47
- **Description:** The route definition for `/rx/:token` contains `beforeEnter: (to) => { window.location.href = /rx/${to.params.token}; }`. Because `window.location.href` is set to a relative path on the same Vite origin without specifying the backend host or API port, Vite serves the SPA, which matches the route again and sets `window.location.href` in an infinite reload loop.
- **Impact:** Clicking or navigating to `/rx/:token` crashes the browser tab in a continuous redirect loop.

---

### [SECURITY / URL TOKEN EXPOSURE] Sanctum Bearer Token Passed via GET Query Parameters
- **File:** `doctor-dashboard/src/router/index.js:68–95`, `super-admin/src/views/DoctorsListView.vue:181`
- **Description:** Doctor support impersonation passes the full Sanctum API bearer token inside the URL query string (`?impersonate_token=...`).
- **Impact:** Bearer tokens passed via GET parameters are stored in plain text in browser histories, HTTP access logs, proxy logs, and `Referer` headers.

---

### [CONFIGURATION / CONNECTION FAILURE] Hardcoded Port 3000 for WebSocket Server
- **File:** `doctor-dashboard/src/views/WaitingRoomTvView.vue:89`, `doctor-dashboard/src/composables/useClinicSocket.js:9`
- **Description:** Fallback socket URL is hardcoded as `http://localhost:3000`. The JS-server `.env` configuration runs on port `3001`.
- **Impact:** Unless `VITE_SOCKET_URL` is explicitly provided, waiting room TV views and clinic socket listeners fail to connect, falling back to HTTP polling.

---

### [BUG / MULTI-BRANCH DESK] Stale Socket Authentication Token on Clinic Branch Switch
- **File:** `backend/app/Http/Controllers/Clinic/AuthController.php:42–44`, `doctor-dashboard/src/stores/auth.js:10`
- **Description:** The socket room HMAC token is generated once during initial login for the user's primary clinic. When a doctor or receptionist switches branches in the dashboard, the token is not recalculated for the selected branch.
- **Impact:** The socket server rejects the `join_clinic` event with an `Unauthorized: Invalid clinic room token` error when switching away from the first clinic.

---

## 3. JS Server (Node.js Microservice)

### [CRITICAL SECURITY / SSRF & ARBITRARY FILE READ] Arbitrary Local File Read and SSRF in PDF Dispatch
- **File:** `JS-server/server.js`
- **Line:** 616–621
- **Description:** The endpoint `/api/sessions/:sessionId/send-pdf` accepts `pdf_path` and `pdf_url` directly from the request body. If `pdf_path` is present, it executes `buffer = fs.readFileSync(pdf_path)`. If `pdf_url` is present, it executes `resp = await axios.get(pdf_url)`.
- **Impact:** Any caller with access to the endpoint can read arbitrary files from the server filesystem (such as `.env`, system files, or database files) and deliver them via WhatsApp. Furthermore, `pdf_url` permits Server-Side Request Forgery (SSRF) against internal services and cloud metadata endpoints (`http://169.254.169.254`).

---

### [SECURITY / CONFIGURATION] Hardcoded Default Secret in Development
- **File:** `JS-server/server.js`
- **Line:** 18
- **Description:** In non-production environments, if `SHARED_SECRET` is unset, the application defaults to `'vertex_wa_secure_secret_key_2026'`.
- **Impact:** Any development or staging instance running without an explicit environment variable operates with a publicly known secret.

---

### [CONFIGURATION / PORT CONFLICT] Default Port Conflict Between JS Server and Nuxt Web Platform
- **File:** `JS-server/server.js:15`, `web-platform/nuxt.config.ts:10`
- **Description:** Both the JS server code (`process.env.PORT || 3000`) and the Nuxt web platform (`port: 3000`) default to port `3000`.
- **Impact:** Attempting to run both local development servers concurrently causes an `EADDRINUSE` port collision.

---

### [PERFORMANCE / MEMORY LEAK] In-Memory Active Sessions Registry Without Eviction
- **File:** `JS-server/server.js`
- **Line:** 151
- **Description:** The `activeSessions` object keeps active Baileys socket instances in Node.js process memory indefinitely.
- **Impact:** As additional clinic tenants onboard, memory consumption scales linearly without garbage collection or process distribution, leading to eventual out-of-memory crashes.

---

### [LOGIC / RESOURCE LEAK] Residual Event Listeners on Baileys Socket Logout
- **File:** `JS-server/server.js`
- **Line:** 751–764
- **Description:** During session logout (`handleLogout`), if `session.sock.logout()` rejects, it calls `session.sock.end()`, but event listeners registered on `sock.ev` are not removed.
- **Impact:** Orphaned socket event handlers remain registered in the Node.js event loop, resulting in memory leaks over repeated connect/disconnect cycles.

---

## 4. Super Admin (Vue 3 SPA)

### [CONFIGURATION / HARDCODED URL] Hardcoded Localhost Port for Doctor Impersonation
- **File:** `super-admin/src/views/DoctorsListView.vue`
- **Line:** 181
- **Description:** The doctor impersonation action executes:  
  `window.open('http://localhost:5174/workspace?impersonate_token=' + token, '_blank')`.
- **Impact:** In any staging or production environment, clicking the impersonation button attempts to open `http://localhost:5174` instead of the actual doctor dashboard domain, causing connection errors.

---

### [SECURITY / AUDIT LOGGING] Missing Audit Log on Doctor Account Impersonation
- **File:** `backend/app/Http/Controllers/SuperAdmin/TenantController.php`
- **Line:** 295–308
- **Description:** While tenant deletion records an entry in `SuperAdminAuditLog`, the `impersonate` method creates a doctor login token without creating any audit log entry.
- **Impact:** Super administrators can silently impersonate doctors and access private medical files without any administrative accountability or compliance log.

---

### [SECURITY / CREDENTIAL SECURITY] Plaintext Default Password on Lead Conversion
- **File:** `backend/app/Http/Controllers/SuperAdmin/DoctorLeadController.php`
- **Line:** 66
- **Description:** During lead conversion, if no password is provided in the request, the user account is created with default password `'password123'`.
- **Impact:** If the doctor does not immediately change their credentials, their account remains vulnerable to unauthorized access using common default passwords.

---

### [LOGIC BUG / SQL EXCEPTION] Missing Subdomain Uniqueness Check in Lead Conversion
- **File:** `backend/app/Http/Controllers/SuperAdmin/DoctorLeadController.php`
- **Line:** 45, 47–59
- **Description:** In `convert()`, `$subdomain = Str::slug($request->input('subdomain', 'dr-' . Str::random(6)))` is passed directly to `Tenant::create()` without checking if the subdomain already exists in `tenants.subdomain`.
- **Impact:** If an administrator specifies an existing subdomain or if a collision occurs, the transaction crashes with an unhandled SQL 1062 unique constraint error.

---

### [LOGIC BUG / IDEMPOTENCY] Re-converting Converted Leads Spawns Duplicate Tenants
- **File:** `backend/app/Http/Controllers/SuperAdmin/DoctorLeadController.php`
- **Line:** 28–112
- **Description:** The `convert` method does not verify that `$lead->status !== 'converted'`.
- **Impact:** If an administrator clicks the convert button multiple times or submits the form repeatedly, multiple duplicate tenant, clinic, and doctor user records are spawned from a single lead.

---

## 5. Web Platform (Nuxt 3 Platform)

### [UX / ERROR HANDLING BUG] Obscured API Error Messages in Booking Form Due to ofetch Structure
- **File:** `web-platform/components/templates/DoctorBookingForm.vue`
- **Line:** 117
- **Description:** The error handler accesses `e.response?.data?.message`. Nuxt 3 uses `ofetch`, which places the response body at `e.data?.message` (or `e.response?._data?.message`).
- **Impact:** All specific error messages from the backend (such as clinic holiday apologies, duplicate booking alerts, or shift capacity limits) fail the evaluation and fall back to the generic message: `'فشل تسجيل الحجز. يرجى مراجعة البيانات المدخلة.'`.

---

### [CONFIGURATION / CONNECTION FAILURE] Broken Socket URL String Replacement in Live Queue Tracker
- **File:** `web-platform/pages/track/[reference_code].vue`
- **Line:** 30
- **Description:** The socket URL is constructed via:  
  `socketUrl = (config.public.apiBase || 'http://localhost:8000/api').replace('/api', '').replace(':8000', ':3000')`.
- **Impact:** In production environments running on standard HTTPS ports without `:8000` (e.g. `https://api.clinic.com/api`), the string replacement does nothing, attempting WebSocket connections against the Laravel API instead of the WebSocket server. In local development, the JS server runs on port 3001, but the replacement targets 3000.

---

### [SEO / DATA MISMATCH] Uploaded Image Assets Prepend Localhost URL to OpenGraph Metadata
- **File:** `backend/app/Http/Controllers/Clinic/SettingsController.php:715`, `web-platform/pages/index.vue:58–62`
- **Description:** When an image is uploaded for OpenGraph previews, the backend stores `$url = asset('storage/' . $path)`. `asset()` evaluates using `APP_URL`, which is configured as `http://localhost:8000`.
- **Impact:** Generated meta tags for social media previews (`og:image`, `twitter:image`) contain `http://localhost:8000/storage/...`, causing broken images when shared on WhatsApp, Facebook, or Twitter.

---

### [ROUTING / HYDRATION MISMATCH] SSR vs Client Host Header Inconsistency
- **File:** `web-platform/pages/index.vue`
- **Line:** 23–28
- **Description:** During Server-Side Rendering (SSR), `headers.host` resolves to the internal container/reverse proxy host header, whereas client-side hydration evaluates `window.location.host`.
- **Impact:** In deployments behind reverse proxies or multi-stage environments, differences in host resolution between server and client produce Vue hydration mismatch warnings and flash incorrect tenant templates.

---
*End of Bug Report.*
