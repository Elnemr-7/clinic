# PROJECT TREE & FUNCTIONALITY SPECIFICATION

Comprehensive architectural file tree and functional specification for the entire Clinic SaaS ecosystem at `D:\clinic`.

---

# System Architecture & Directory Tree

```text
D:\clinic\
├── backend/                                # Laravel 11 PHP 8.4 REST API
│   ├── app/
│   │   ├── Events/                         # Real-time WebSocket broadcasting events
│   │   │   ├── ConsultationFinishedEvent.php
│   │   │   ├── PatientNearTurnEvent.php
│   │   │   └── QueueUpdatedEvent.php
│   │   ├── Http/
│   │   │   ├── Controllers/
│   │   │   │   ├── Clinic/                 # Doctor & Receptionist workspace handlers
│   │   │   │   │   ├── AuthController.php
│   │   │   │   │   ├── DoctorWorkspaceController.php
│   │   │   │   │   ├── ReceptionController.php
│   │   │   │   │   └── SettingsController.php
│   │   │   │   ├── Internal/               # Microservice webhooks & payment callbacks
│   │   │   │   │   ├── FawryWebhookController.php
│   │   │   │   │   └── WhatsAppWebhookController.php
│   │   │   │   ├── Public/                 # Patient booking & public verification
│   │   │   │   │   ├── PublicBookingController.php
│   │   │   │   │   └── PublicPlanController.php
│   │   │   │   └── SuperAdmin/             # Platform administration & SaaS control
│   │   │   │       ├── AuthController.php
│   │   │   │       ├── DashboardController.php
│   │   │   │       ├── DoctorLeadController.php
│   │   │   │       ├── PlanController.php
│   │   │   │       ├── TenantController.php
│   │   │   │       └── TopupController.php
│   │   │   └── Middleware/                 # Authentication & tenant isolation guards
│   │   │       ├── CheckTenantActive.php
│   │   │       ├── ClinicAuth.php
│   │   │       ├── ClinicBranchScope.php
│   │   │       ├── InternalWebhookAuth.php
│   │   │       ├── PublicApiKeyAuth.php
│   │   │       └── SuperAdminAuth.php
│   │   ├── Jobs/                           # Asynchronous messaging & queue tasks
│   │   │   ├── CheckNearTurnNotificationsJob.php
│   │   │   ├── SendSmsNotificationJob.php
│   │   │   └── SendWhatsAppPrescriptionJob.php
│   │   ├── Models/                         # Eloquent domain models
│   │   │   ├── Appointment.php
│   │   │   ├── CashTransaction.php
│   │   │   ├── Clinic.php
│   │   │   ├── ClinicDayOff.php
│   │   │   ├── ClinicService.php
│   │   │   ├── ClinicShift.php
│   │   │   ├── Consultation.php
│   │   │   ├── DoctorDrug.php
│   │   │   ├── DoctorLabTest.php
│   │   │   ├── DoctorLead.php
│   │   │   ├── MessageLog.php
│   │   │   ├── Patient.php
│   │   │   ├── PatientAttachment.php
│   │   │   ├── Plan.php
│   │   │   ├── PrescriptionTemplate.php
│   │   │   ├── SuperAdmin.php
│   │   │   ├── SuperAdminAuditLog.php
│   │   │   ├── Tenant.php
│   │   │   ├── User.php
│   │   │   └── WalletTopup.php
│   │   ├── Scopes/                         # Tenant global database scopes
│   │   │   └── TenantScope.php
│   │   ├── Services/                       # Core business logic services
│   │   │   ├── CloudflareDomainService.php
│   │   │   ├── FawryService.php
│   │   │   ├── FollowupDetectorService.php
│   │   │   ├── MessagingWalletService.php
│   │   │   ├── PrescriptionPdfService.php
│   │   │   ├── QueueTimeCalculatorService.php
│   │   │   ├── SmsGatewayService.php
│   │   │   ├── WhatsAppChannelService.php
│   │   │   └── WhatsAppTokenService.php
│   │   ├── Support/                        # Helper utilities
│   │   │   └── PhoneNormalizer.php
│   │   └── Traits/                         # Reusable controller & model traits
│   │       ├── ApiResponseTrait.php
│   │       └── BelongsToTenant.php
│   └── routes/
│       └── api.php                         # All platform REST routes
│
├── doctor-dashboard/                       # Vue 3 / Vite Doctor & Receptionist SPA
│   └── src/
│       ├── api/
│       │   └── client.js                   # Axios client with Sanctum interceptors
│       ├── components/
│       │   ├── common/
│       │   │   ├── ImpersonationBanner.vue # Top warning bar during super-admin login
│       │   │   └── OfflineBlockerModal.vue # Network loss blocker overlay
│       │   ├── doctor/
│       │   │   ├── ActivePatientCard.vue   # Patient in-room vitals, alerts & identity
│       │   │   ├── MedicalAlertBanner.vue  # Chronic conditions & allergy visual alerts
│       │   │   ├── VitalsStrip.vue         # Biological measurements comparison strip
│       │   │   └── modals/
│       │   │       ├── ModalExtraServicesAndFollowup.vue # Procedures & next visit date
│       │   │       ├── ModalLabsAndAttachments.vue       # Lab tests catalog & file upload
│       │   │       ├── ModalPastHistory.vue              # Patient historical visits & Rx
│       │   │       └── ModalPrescription.vue             # Diagnosis & drug prescription writer
│       │   ├── layout/
│       │   │   └── AppShellLayout.vue      # Main application navigation header & sidebar
│       │   └── reception/
│       │       ├── LiveQueueTable.vue      # High-density patient queue grid
│       │       └── modals/
│       │           ├── ModalCashbox.vue    # Daily income/expense reconciliation modal
│       │           ├── ModalQuickBook.vue  # Walk-in desk booking form with eligibility
│       │           ├── ModalVitalsEntry.vue# Quick biological measurements entry modal
│       │           └── ModalWaitingTvLauncher.vue # Fullscreen Waiting Room TV launcher
│       ├── composables/
│       │   ├── useClinicSocket.js          # Socket.io connection & room join composable
│       │   └── useTheme.js                 # Dark/light theme switcher composable
│       ├── router/
│       │   └── index.js                    # Route definitions and RBAC navigation guards
│       ├── stores/
│       │   ├── auth.js                     # User session & impersonation Pinia store
│       │   ├── cashbox.js                  # Financial transactions & drawer Pinia store
│       │   ├── doctorWorkspace.js          # Active patient, counts & consultation store
│       │   ├── receptionQueue.js           # Live queue & patient status Pinia store
│       │   └── settings.js                 # Clinical, branch, staff & website settings store
│       ├── utils/
│       │   └── alerts.js                   # Toast and confirmation popup wrappers
│       └── views/
│           ├── DoctorWorkspaceView.vue     # Doctor single-screen consultation room
│           ├── LoginView.vue               # Staff authentication screen
│           ├── ReceptionDeskView.vue       # Receptionist fast-queue desk
│           ├── WaitingRoomTvView.vue       # Patient-facing waiting TV broadcast screen
│           └── settings/
│               ├── ClinicalSettingsView.vue# Vitals, Rx print margins, drugs & labs setup
│               ├── ClinicBranchesView.vue  # Clinic locations, shifts & emergency days off
│               ├── ReceptionistsView.vue   # Staff account management
│               ├── SettingsLayoutView.vue  # Settings navigation tabs wrapper
│               └── WebsiteSettingsView.vue # Doctor marketing site & custom domain controls
│
├── JS-server/                              # Node.js / Baileys & Socket.io Microservice
│   ├── cache.js                            # In-memory session response cache with TTL
│   ├── package.json                        # Dependencies (Baileys, Express, Socket.io)
│   └── server.js                           # WhatsApp multi-device bot & real-time socket server
│
├── super-admin/                            # Vue 3 / Vite Platform Administration SPA
│   └── src/
│       ├── api/
│       │   └── client.js                   # Axios client with super-admin bearer token
│       ├── components/
│       │   ├── layout/
│       │   │   ├── AdminHeader.vue         # Top bar with admin profile & quick actions
│       │   │   ├── AdminShellLayout.vue    # Master layout wrapper
│       │   │   └── AdminSidebar.vue        # Admin menu navigation links
│       │   └── modals/
│       │       ├── ConvertLeadModal.vue    # Converts inbound doctor lead into live tenant
│       │       ├── CreateDoctorModal.vue   # Manual doctor registration wizard
│       │       ├── EditDoctorGodModeModal.vue # Overrides limits, quota, and domain mode
│       │       └── PlanEditModal.vue       # Creates/edits subscription pricing plans
│       ├── composables/
│       │   └── useTheme.js                 # Theme switcher
│       ├── router/
│       │   └── index.js                    # Super admin routes & auth guards
│       ├── stores/
│       │   ├── auth.js                     # Super admin credentials & login store
│       │   ├── dashboard.js                # System-wide metrics & revenue statistics store
│       │   ├── doctors.js                  # Tenant management & impersonation store
│       │   ├── leads.js                    # Inbound doctor leads store
│       │   ├── plans.js                    # SaaS pricing tiers store
│       │   └── topups.js                   # Wallet credit recharge requests store
│       ├── utils/
│       │   └── alerts.js                   # Toast notifications
│       └── views/
│           ├── DashboardView.vue           # Key performance indicators and platform overview
│           ├── DoctorsListView.vue         # Comprehensive doctor tenant accounts grid
│           ├── LeadsListView.vue           # Inbound demo request leads table
│           ├── LoginView.vue               # Super-admin authentication portal
│           ├── PlansListView.vue           # Subscription packages & feature tiers table
│           └── TopupsListView.vue          # Wallet manual approvals & transaction history
│
└── web-platform/                           # Nuxt 3 SSR Multi-Tenant Engine
    ├── components/
    │   ├── icons/
    │   │   └── IconTooth.vue               # Dental specialty SVG icon
    │   ├── platform/
    │   │   ├── LeadForm.vue                # Landing page doctor registration form
    │   │   ├── PlatformFeatures.vue        # SaaS marketing feature showcase
    │   │   ├── PlatformHero.vue            # Hero banner & call to action
    │   │   └── PlatformPricing.vue         # Public subscription pricing plans grid
    │   └── templates/
    │       ├── DoctorBookingForm.vue       # Multi-step patient booking component
    │       ├── TemplateDental.vue          # Dentist specialized landing layout
    │       ├── TemplateDermatology.vue     # Dermatologist specialized landing layout
    │       ├── TemplateGeneral.vue         # Default medical consultation landing layout
    │       ├── TemplateObgyn.vue           # Obstetrics & gynecology landing layout
    │       ├── TemplateOrthopedics.vue     # Orthopedic surgeon landing layout
    │       └── TemplatePediatrics.vue      # Pediatrician specialized landing layout
    ├── composables/
    │   └── useClinicBooking.ts             # Host resolution, booking & queue status composable
    ├── custom-sites/
    │   └── dr-nemr-test/
    │       └── index.vue                   # Example bespoke standalone website component
    ├── pages/
    │   ├── index.vue                       # Dynamic root (SaaS landing vs Doctor website)
    │   ├── d/
    │   │   └── [subdomain].vue             # Direct subdomain preview route
    │   ├── rx/
    │   │   └── [token].vue                 # Direct prescription PDF download route
    │   └── track/
    │       └── [reference_code].vue        # Live patient turn tracking screen
    └── server/
        └── middleware/
            └── tenant.ts                   # SSR incoming host extraction middleware
```

---

# Detailed Functional Directory & File Breakdown

---

## 1. Backend (`backend/`)

### 1.1 Controllers

#### `app/Http/Controllers/Clinic/AuthController.php`
- **`login(Request $request)`**: Authenticates doctor or receptionist via email and password; generates Sanctum API token (`role:doctor` or `role:receptionist`); produces 5-minute rotating HMAC socket token for the user's primary clinic.
- **`me(Request $request)`**: Returns current authenticated user object, tenant configuration, clinics, and recalculated socket token.
- **`logout(Request $request)`**: Revokes current Sanctum personal access token.

#### `app/Http/Controllers/Clinic/DoctorWorkspaceController.php`
- **`currentPatient(Request $request)`**: Fetches the appointment currently in consultation (`status: in_consultation`), waiting queue counts, and previous biological vitals for comparison.
- **`savePrescription(int $appointmentId, Request $request)`**: Saves doctor diagnosis, complaints, private notes, and prescribed drug list; auto-saves new drug names into tenant catalog; triggers PDF generation; dispatches WhatsApp and SMS notifications if requested.
- **`saveLabsAndNotes(int $appointmentId, Request $request)`**: Records requested lab tests and radiology exams for the active appointment; auto-registers unknown tests into tenant lab catalog.
- **`saveExtraServicesAndFollowup(int $appointmentId, Request $request)`**: Records surgical/clinical procedures performed, updates total billed amount, and schedules a future followup date on the consultation.
- **`completeAndCallNextPatient(Request $request)`**: Completes active consultation in a database transaction; auto-creates scheduled followup appointment if flagged; pulls next waiting patient (`arrived_waiting` prioritized over `booked`); shifts queue status to `in_consultation`; recalculates estimated time windows; fires `ConsultationFinishedEvent`, `QueueUpdatedEvent`, and turn alert jobs.
- **`previousVisits(int $patientId, Request $request)`**: Retrieves historical completed appointments, consultations, and prescriptions for a given patient.
- **`saveAttachments(int $patientId, Request $request)`**: Uploads and records patient lab results, X-rays, or PDF documents.
- **`autoCompleteDrugs(Request $request)`**: Provides fast autocompletion search across the tenant's drug catalog.
- **`autoCompleteLabs(Request $request)`**: Provides autocompletion search across tenant's lab and radiology test catalog.

#### `app/Http/Controllers/Clinic/ReceptionController.php`
- **`queue(Request $request)`**: Retrieves the complete ordered patient queue for a selected clinic, date, and shift.
- **`checkFollowup(Request $request)`**: Evaluates whether a patient's phone number qualifies for a free or discounted followup visit based on clinic rules.
- **`quickBook(Request $request)`**: Walk-in appointment registration handler; finds or creates patient; computes next sequential queue number with pessimistic row lock; calculates dynamic time window; generates unique reference code; optionally registers cash transaction if marked paid.
- **`updateStatus(int $appointmentId, Request $request)`**: Changes appointment workflow status (`booked`, `arrived_waiting`, `in_consultation`, `completed`, `cancelled`, `postponed_waiting_arrival`); records income transactions when marked paid; handles refund deletions.
- **`collectExtraServices(int $appointmentId)`**: Records income cash transaction for additional medical services performed during consultation.
- **`saveVitals(int $appointmentId, Request $request)`**: Records biological vitals (blood pressure, blood glucose, weight, temperature) on patient arrival.
- **`cashboxSummary(Request $request)`**: Summarizes daily cash drawer transactions, total income, total expenses, and net drawer balance.
- **`recordExpense(Request $request)`**: Records an out-of-drawer cash expense (clinic supplies, utilities, maintenance).

#### `app/Http/Controllers/Clinic/SettingsController.php`
- **`getSettings(Request $request)`**: Returns complete tenant profile, vitals config, print margins, WhatsApp connection status, and website mode.
- **`updateVitalsConfig(Request $request)`**: Saves tenant's customized biological vitals checklist.
- **`updatePrintMargins(Request $request)`**: Configures prescription PDF margins (top, bottom, left, right in mm) and paper size (A4/A5).
- **`testPrintPdf(Request $request)`**: Generates a test margin preview PDF with dashed borders for printer alignment.
- **`getDrugs(Request $request)`**: Lists all tenant saved drugs.
- **`saveDrug(Request $request)`**: Creates or updates a drug entry with default dosage and duration.
- **`deleteDrug(int $id, Request $request)`**: Deletes a drug from the tenant catalog.
- **`getServices(Request $request)`**: Lists all medical procedures and services with prices.
- **`getTemplates(Request $request)`**: Lists saved prescription preset templates.
- **`saveTemplate(Request $request)`**: Creates or updates a prescription template.
- **`deleteTemplate(int $id, Request $request)`**: Deletes a prescription template.
- **`getLabs(Request $request)`**: Lists configured lab and radiology tests.
- **`saveLab(Request $request)`**: Adds a new test to the tenant lab catalog.
- **`getBranches(Request $request)`**: Retrieves all clinic locations and shifts for the tenant.
- **`saveBranch(Request $request)`**: Creates or updates a clinic branch, checking plan maximum clinic limits.
- **`saveShift(Request $request)`**: Creates or updates working hours/shifts for a clinic branch.
- **`deleteShift(int $id, Request $request)`**: Removes a shift from a branch.
- **`saveDayOff(Request $request)`**: Registers an emergency day-off for a clinic; bulk moves or cancels all booked appointments for that date; sends apology notifications via WhatsApp/SMS.
- **`getReceptionists(Request $request)`**: Lists all receptionist staff accounts.
- **`saveReceptionist(Request $request)`**: Creates or updates a receptionist login account.
- **`deleteReceptionist(int $id, Request $request)`**: Removes a receptionist staff account.
- **`getQrCode(Request $request)`**: Requests an 8-digit WhatsApp pairing code from the Node.js microservice.
- **`requestTopup(Request $request)`**: Initiates a manual or Fawry messaging balance recharge request.
- **`updateCustomDomain(Request $request)`**: Submits custom domain registration to Cloudflare for custom hostnames.
- **`saveWebsiteSettings(Request $request)`**: Updates website hosting model (Modes 1 to 4), hero text, colors, SEO tags, and social media links.
- **`uploadImage(Request $request)`**: Uploads doctor profile picture, clinic logo, or OpenGraph banner.

#### `app/Http/Controllers/Internal/FawryWebhookController.php`
- **`callback(Request $request)`**: Verifies Fawry HMAC payment signature and credits message wallet quotas.

#### `app/Http/Controllers/Internal/WhatsAppWebhookController.php`
- **`handle(int $tenantId, Request $request)`**: Receives events from Node.js Baileys microservice; handles session state changes (`connected`/`disconnected`) and inbound patient activation text messages (`"تفعيل حجز رقم CLN-XXXX"`).

#### `app/Http/Controllers/Public/PublicBookingController.php`
- **`tenantByHost(Request $request)`**: Resolves tenant identity from incoming HTTP host header or subdomain.
- **`clinicDetails(int $clinicId)`**: Returns clinic branches, active shifts, and scheduled holidays for booking.
- **`checkEligibility(Request $request)`**: Public followup eligibility detector for patients booking online.
- **`bookAppointment(Request $request)`**: Public online patient booking handler; prevents duplicate same-day bookings; enforces shift capacity limits; generates reference codes; returns WhatsApp activation deep links.
- **`liveQueueStatus(string $referenceCode)`**: Returns active turn number, current patient in consultation, and remaining patients ahead for public tracking.
- **`tvQueue(string $code)`**: Broadcast endpoint for waiting room TV screens; masks patient names for privacy; outputs current and upcoming turns.
- **`downloadPrescriptionPdf(string $token)`**: Serves or dynamically regenerates prescription PDF/printable HTML by secure token.

#### `app/Http/Controllers/Public/PublicPlanController.php`
- **`index()`**: Returns all active platform SaaS subscription plans.
- **`submitLead(Request $request)`**: Captures new doctor demo requests and contact leads from marketing landing page.

#### `app/Http/Controllers/SuperAdmin/AuthController.php`
- **`login(Request $request)`**: Authenticates master platform administrators; generates `role:super-admin` Sanctum token.
- **`me(Request $request)`**: Returns authenticated super-admin profile.
- **`logout(Request $request)`**: Revokes super-admin Sanctum token.

#### `app/Http/Controllers/SuperAdmin/DashboardController.php`
- **`stats()`**: Computes system-wide aggregate metrics (total doctors, active clinics, bookings today, monthly messaging consumption, pending leads, revenue in EGP).

#### `app/Http/Controllers/SuperAdmin/DoctorLeadController.php`
- **`index()`**: Lists all inbound doctor registration leads with pagination.
- **`convert(int $leadId, Request $request)`**: Converts a sales lead into an active doctor account, creating tenant, user credentials, primary clinic branch, and default evening shifts.

#### `app/Http/Controllers/SuperAdmin/PlanController.php`
- **`index()`**: Lists all subscription plans with tenant subscriber counts.
- **`store(Request $request)`**: Creates a new SaaS subscription package.
- **`update(int $id, Request $request)`**: Modifies pricing, quotas, and feature flags of a plan.
- **`destroy(int $id)`**: Deletes an unused plan (blocks deletion if tenants are subscribed).

#### `app/Http/Controllers/SuperAdmin/TenantController.php`
- **`index()`**: Lists all doctor accounts with plans, clinics, and staff counts.
- **`store(Request $request)`**: Manually registers a new doctor account with custom limits and credentials.
- **`show(int $id)`**: Retrieves comprehensive doctor configuration.
- **`update(int $id, Request $request)`**: Modifies doctor details, plan assignment, and status.
- **`destroy(int $id)`**: Cascades deletion of doctor account and writes audit entry to `SuperAdminAuditLog`.
- **`updateWallet(int $id, Request $request)`**: Manually overrides WhatsApp/SMS balances and resets monthly usage counters.
- **`impersonate(int $id)`**: Generates a temporary `role:doctor` token enabling super-admin tech support login.

#### `app/Http/Controllers/SuperAdmin/TopupController.php`
- **`index()`**: Lists all balance top-up requests across tenants.
- **`approve(int $id, Request $request)`**: Approves top-up request and credits quota.
- **`reject(int $id)`**: Marks top-up request as rejected.

---

### 1.2 Services

#### `app/Services/CloudflareDomainService.php`
- **`registerCustomHostname(Tenant $tenant, string $hostname)`**: Dispatches API request to Cloudflare for custom SSL hostname DV provisioning.
- **`checkHostnameStatus(Tenant $tenant)`**: Returns current verification and CNAME propagation status.

#### `app/Services/FawryService.php`
- **`verifySignature(array $payload)`**: Computes SHA-256 HMAC hash over concatenated payment fields and Fawry security key.
- **`processCallback(array $payload)`**: Credits wallet quotas upon matching transaction reference.

#### `app/Services/FollowupDetectorService.php`
- **`checkEligibility(int $tenantId, int $clinicId, string $phone)`**: Scans patient historical records for previous `new_visit` appointments within the clinic's allowable followup window.

#### `app/Services/MessagingWalletService.php`
- **`deductMessageBalance(Tenant $tenant, string $channel)`**: Deducts 1 quota unit from tenant wallet under pessimistic row locking.
- **`logMessage(...)`**: Persists record in `message_logs` table.
- **`topupBalance(...)`**: Credits messaging quotas and creates audit record in `wallet_topups`.

#### `app/Services/PrescriptionPdfService.php`
- **`generatePrescriptionPdf(Consultation $consultation)`**: Renders Blade view template into an RTL A5/A4 PDF via mPDF, applying margins and letterhead settings.
- **`generateTestMarginsPdf(Tenant $tenant, array $margins)`**: Creates printable alignment PDF for clinic physical printers.

#### `app/Services/QueueTimeCalculatorService.php`
- **`calculateInitialWindow(ClinicShift $shift, Clinic $clinic, int $queueNumber, Carbon|string $date)`**: Computes estimated arrival window start and end times based on shift start time and average consultation duration.
- **`recalculateQueueOnStatusChange(Clinic $clinic, ClinicShift $shift, Carbon|string $date, ?int $enteredQueueNumber = null)`**: Recalculates estimated time windows dynamically for all waiting patients whenever a patient enters the exam room.

#### `app/Services/SmsGatewayService.php`
- **`sendSms(Tenant $tenant, string $phone, string $message, string $type, ?int $appointmentId)`**: Checks wallet balance, logs dispatch, and sends SMS notifications.

#### `app/Services/WhatsAppChannelService.php`
- **`getSessionStatus(Tenant $tenant)`**: Queries Node.js microservice for WhatsApp connection state.
- **`requestPairingCode(Tenant $tenant, string $phoneNumber)`**: Requests 8-digit phone linking pairing code.
- **`sendMessage(...)`**: Dispatches text message to Node.js Baileys gateway.
- **`sendPrescriptionPdf(...)`**: Encodes and delivers prescription PDF to patient WhatsApp.
- **`disconnectSession(Tenant $tenant)`**: Logs out WhatsApp Baileys session and cleans authentication files.

#### `app/Services/WhatsAppTokenService.php`
- **`generateToken(?string $secret)`**: Generates 5-minute rotating HMAC-SHA256 inter-service token.
- **`verifyToken(?string $token, ?string $secret)`**: Validates timestamp freshness and signature.
- **`getRequestHeaders(?string $secret)`**: Prepares authenticated HTTP headers for microservice calls.

---

### 1.3 Models

- **`Appointment`**: Represents patient booking; tracks status, queue number, dynamic time windows, fees, and payment status.
- **`CashTransaction`**: Records income and expense entries in the clinic cashbox ledger.
- **`Clinic`**: Represents a physical clinic branch with address, pricing, consultation duration, and TV codes.
- **`ClinicDayOff`**: Stores emergency closures and holidays.
- **`ClinicService`**: Catalog of billable medical procedures and treatments.
- **`ClinicShift`**: Working hours by day of week with patient capacity limits.
- **`Consultation`**: Clinical examination records, complaints, diagnoses, private doctor notes, drug prescriptions, and lab orders.
- **`DoctorDrug`**: Tenant-specific catalog of drugs with default dosages.
- **`DoctorLabTest`**: Tenant-specific catalog of diagnostic tests and radiology scans.
- **`DoctorLead`**: Inbound demo and onboarding requests.
- **`MessageLog`**: Historical log of SMS and WhatsApp outbound messages.
- **`Patient`**: Patient identity record with normalized phone number and medical history.
- **`PatientAttachment`**: File uploads (radiology, lab results, medical reports).
- **`Plan`**: SaaS subscription plan with feature limits (clinics, WhatsApp, domains).
- **`PrescriptionTemplate`**: Reusable drug and diagnosis prescription templates.
- **`SuperAdmin`**: Platform administrative user.
- **`SuperAdminAuditLog`**: Audit record of critical administrative actions.
- **`Tenant`**: Doctor tenant account managing clinics, wallets, domains, and branding.
- **`User`**: Staff login user with role `doctor` or `receptionist`.
- **`WalletTopup`**: Quota recharge payment transactions.

---

### 1.4 Middleware & Scopes

- **`CheckTenantActive`**: Verifies that the doctor's tenant account is active and subscription has not expired.
- **`ClinicAuth`**: Validates Sanctum authentication, active user status, and role-based permissions (`doctor` vs `receptionist`).
- **`ClinicBranchScope`**: Restricts receptionist data access to assigned branch.
- **`InternalWebhookAuth`**: Authenticates microservice calls via rotating 5-minute HMAC token or pre-shared secret.
- **`PublicApiKeyAuth`**: Authenticates third-party integrations using tenant public API key.
- **`SuperAdminAuth`**: Enforces `role:super-admin` Sanctum token capability.
- **`TenantScope`**: Global Eloquent scope automatically appending `WHERE tenant_id = ?` to queries.

---

### 1.5 Jobs & Events

- **`CheckNearTurnNotificationsJob`**: Background job scanning waiting patients; alerts patients when turn is within configured threshold.
- **`SendSmsNotificationJob`**: Background job sending SMS notification for an appointment.
- **`SendWhatsAppPrescriptionJob`**: Background job delivering prescription PDF to patient WhatsApp.
- **`ConsultationFinishedEvent`**: Broadcast event fired when doctor completes a patient visit.
- **`PatientNearTurnEvent`**: Real-time broadcast event fired when a patient's turn approaches.
- **`QueueUpdatedEvent`**: Real-time broadcast event triggering instant UI refresh across doctor, receptionist, and TV screens.

---

## 2. Doctor Dashboard (`doctor-dashboard/`)

### 2.1 Views & Pages

- **`DoctorWorkspaceView.vue`**: High-efficiency single-screen doctor consultation room.
  - *Contains:* Active patient card with biological vitals, previous visit comparison, chronic alert banner, diagnostic buttons, quick-prescribe modal launcher, lab order launcher, procedures & followup launcher, historical visits drawer, and live upcoming patient queue sidebar with next patient call button.
- **`ReceptionDeskView.vue`**: Fast keyboard-driven receptionist management desk.
  - *Contains:* Real-time queue metrics (waiting, in consultation, completed), search filter, quick walk-in booking button, daily cashbox modal launcher, TV screen launcher, and live queue data grid.
- **`WaitingRoomTvView.vue`**: Patient-facing fullscreen broadcast display.
  - *Contains:* Real-time clock, current turn number, upcoming queue list with masked patient names, clinic branding, and sound alert integration.
- **`LoginView.vue`**: Authentication screen with email and password fields.
- **`views/settings/SettingsLayoutView.vue`**: Settings navigation tabs container.
- **`views/settings/ClinicalSettingsView.vue`**: Customizes vitals checklist, prescription PDF margins, paper sizes, drug catalog, and lab test catalogue.
- **`views/settings/ClinicBranchesView.vue`**: Manages clinic locations, operating shifts, and emergency day-off declarations.
- **`views/settings/ReceptionistsView.vue`**: Manages receptionist employee credentials and branch assignments.
- **`views/settings/WebsiteSettingsView.vue`**: Controls doctor landing page mode (Modes 1 to 4), custom domain setup, hero text, and SEO OpenGraph tags.

### 2.2 Components

- **`ActivePatientCard.vue`**: Displays active patient name, age, phone, queue number, and visit type badge.
- **`MedicalAlertBanner.vue`**: Renders warning indicators for chronic conditions (diabetes, hypertension, cardiac, drug allergies).
- **`VitalsStrip.vue`**: Horizontal biological measurements bar comparing current vitals against previous visit readings.
- **`LiveQueueTable.vue`**: Interactive queue table with status transition buttons (Mark Arrived, In Consultation, Postpone, Cancel).
- **`ModalPrescription.vue`**: Prescription authoring modal with drug autocompletion, dosage templates, and send options.
- **`ModalLabsAndAttachments.vue`**: Diagnostic testing orders and patient attachment upload interface.
- **`ModalExtraServicesAndFollowup.vue`**: Additional procedure billing and followup appointment date picker.
- **`ModalPastHistory.vue`**: Slide-over drawer presenting patient historical visits and past prescriptions.
- **`ModalCashbox.vue`**: Daily cash drawer summary modal displaying total income, expenses, and expense entry form.
- **`ModalQuickBook.vue`**: Walk-in registration dialog with automatic followup eligibility detection.
- **`ModalVitalsEntry.vue`**: Quick measurements entry dialog for reception staff on patient arrival.
- **`ModalWaitingTvLauncher.vue`**: Modal offering one-click launch or URL copying for the waiting room TV screen.
- **`OfflineBlockerModal.vue`**: Fullscreen modal alerting staff when network connection is severed.
- **`ImpersonationBanner.vue`**: Persistent top banner alerting user when logged in via super-admin tech support impersonation.
- **`AppShellLayout.vue`**: Core navigation layout with header, branch switcher, and role-based links.

### 2.3 Pinia Stores

- **`auth.js`**: Manages authentication tokens, current user object, role checks, and impersonation session state.
  - *Actions:* `login`, `fetchProfile`, `setImpersonationToken`, `logout`.
- **`doctorWorkspace.js`**: Manages active consultation state, queue counters, and consultation actions.
  - *Actions:* `fetchCurrentPatient`, `savePrescription`, `saveLabs`, `saveExtraServices`, `completeAndNext`, `fetchPatientHistory`, `uploadAttachment`, `searchDrugs`, `searchLabs`.
- **`receptionQueue.js`**: Manages daily queue appointments and status mutations.
  - *Actions:* `fetchQueue`, `quickBook`, `checkFollowup`, `updateStatus`, `collectExtraServices`, `saveVitals`.
- **`cashbox.js`**: Manages daily cash transactions and expense recording.
  - *Actions:* `fetchSummary`, `recordExpense`.
- **`settings.js`**: Manages branches, shifts, vitals configuration, print settings, and staff.
  - *Actions:* `fetchSettings`, `updateVitals`, `updatePrintMargins`, `fetchDrugs`, `saveDrug`, `deleteDrug`, `fetchBranches`, `saveBranch`, `saveShift`, `deleteShift`, `saveDayOff`, `fetchReceptionists`, `saveReceptionist`, `deleteReceptionist`, `requestPairingCode`, `disconnectWhatsApp`.

---

## 3. JS Server (`JS-server/`)

### 3.1 Main Server (`server.js`)

#### Core Functions
- **`startSession(sessionId, meta)`**: Initializes or resumes Baileys WhatsApp multi-device socket; restores credentials from disk; registers event listeners (`connection.update`, `messages.upsert`, `creds.update`).
- **`verifyServerToken(token)`**: Verifies incoming 5-minute rotating HMAC-SHA256 tokens against `SHARED_SECRET`.
- **`generateServerToken()`**: Generates 5-minute rotating HMAC-SHA256 signature string (`timestamp.hash`).
- **`dispatchWebhook(sessionEntry, event, data)`**: Transmits outbound events to Laravel backend.
- **`autoBootSessions()`**: Scans disk storage directory on server startup to reconnect existing WhatsApp sessions.
- **`invalidateSessionCache(sessionId)`**: Flushes cached responses on status changes.
- **`getSessionDir(sessionId)`**: Returns sanitized file path for session credentials.
- **`handleLogout(req, res)`**: Terminates active Baileys socket, cleans credential files from disk, and invalidates cache.

#### REST Endpoints
- **`GET /health` & `GET /api/health`**: Service uptime and active session count.
- **`GET /api/sessions` & `GET /api/instances/active`**: Lists active WhatsApp instances.
- **`POST /api/sessions/start` & `POST /api/instance/init`**: Starts WhatsApp connection for a tenant.
- **`POST /api/sessions/:sessionId/messages` & `POST /api/instance/send`**: Sends outbound text message with anti-ban composing simulation.
- **`POST /api/sessions/:sessionId/send-pdf` & `POST /api/instance/send-pdf`**: Transmits prescription PDF document over WhatsApp.
- **`GET /api/sessions/:sessionId` & `GET /api/instance/:sessionId`**: Returns status and linked phone number.
- **`POST /api/sessions/pairing-code`**: Generates 8-digit companion registration pairing code.
- **`DELETE /api/sessions/:sessionId` & `POST /api/sessions/:sessionId/logout`**: Disconnects and deletes session.
- **`POST /api/auth/token`**: Generates fresh 5-minute HMAC token.
- **`POST /internal/broadcast-queue`**: Broadcasts queue updates from Laravel to Socket.io rooms.

#### Real-time Socket.io Events
- **`join_clinic`**: Joins authenticated private room (`clinic_{id}`) using HMAC verification.
- **`join_tv`**: Joins public waiting room TV broadcast room (`tv_{code}`).
- **`join_track`**: Joins patient tracking room (`track_{referenceCode}`).
- **`leave_clinic`**: Leaves clinic room.

### 3.2 Cache Utility (`cache.js`)
- **`get(key)`**: Retrieves cached value if not expired.
- **`set(key, val, ttlSeconds)`**: Stores value with TTL expiration.
- **`del(key)`**: Removes key from cache.
- **`flush()`**: Clears all cached items.

---

## 4. Super Admin (`super-admin/`)

### 4.1 Views & Pages

- **`DashboardView.vue`**: Metric tiles displaying total doctor tenants, active branches, today's appointments, messaging quota usage, and revenue.
- **`DoctorsListView.vue`**: Data table of all doctor tenants with search, status filters, wallet editor, "God Mode" configuration, and impersonation launcher.
- **`LeadsListView.vue`**: Manages inbound doctor inquiries and launches conversion dialog.
- **`PlansListView.vue`**: Manages SaaS subscription tiers, limits, and pricing.
- **`TopupsListView.vue`**: Reviews, approves, or rejects messaging balance topup requests.
- **`LoginView.vue`**: Super-admin authentication screen.

### 4.2 Components

- **`AdminHeader.vue`**: Navigation bar with admin profile, notifications, and logout.
- **`AdminSidebar.vue`**: Left sidebar containing dashboard navigation links.
- **`AdminShellLayout.vue`**: Main administration shell layout.
- **`ConvertLeadModal.vue`**: Converts an inbound lead into an active doctor tenant.
- **`CreateDoctorModal.vue`**: Manual doctor registration modal.
- **`EditDoctorGodModeModal.vue`**: Full administrative override dialog for clinic limits, balances, and domain modes.
- **`PlanEditModal.vue`**: Modal for creating or editing subscription plans.

### 4.3 Pinia Stores

- **`auth.js`**: Manages super-admin login session and token.
  - *Actions:* `login`, `fetchProfile`, `logout`.
- **`dashboard.js`**: Fetches system-wide aggregate statistics.
  - *Actions:* `fetchStats`.
- **`doctors.js`**: Manages doctor tenant CRUD, wallet adjustments, and impersonation.
  - *Actions:* `fetchDoctors`, `createDoctor`, `updateDoctor`, `updateDoctorWallet`, `deleteDoctor`, `impersonateDoctor`.
- **`leads.js`**: Fetches and converts doctor registration leads.
  - *Actions:* `fetchLeads`, `convertLead`.
- **`plans.js`**: Manages SaaS pricing plan creation and editing.
  - *Actions:* `fetchPlans`, `savePlan`, `deletePlan`.
- **`topups.js`**: Manages wallet recharge approvals and rejections.
  - *Actions:* `fetchTopups`, `approveTopup`, `rejectTopup`.

---

## 5. Web Platform (`web-platform/`)

### 5.1 Pages

- **`pages/index.vue`**: Dynamic hybrid entry page.
  - *Behavior:* If host header matches platform domain, renders SaaS marketing landing page (Hero, Features, Pricing, Demo Lead Form). If host matches a doctor's custom domain or subdomain, dynamically renders the doctor's specialized template.
- **`pages/d/[subdomain].vue`**: Direct route to preview or book via a doctor's subdomain.
- **`pages/track/[reference_code].vue`**: Real-time turn tracking page for patients.
  - *Contains:* Active turn counter, estimated arrival window, remaining patients ahead, and prescription download link once visit is completed.
- **`pages/rx/[token].vue`**: Direct redirect handler serving prescription PDF.

### 5.2 Components

- **`LeadForm.vue`**: Interactive doctor demo request form on the marketing landing page.
- **`PlatformHero.vue`**: Marketing hero banner showcasing clinic speed and features.
- **`PlatformFeatures.vue`**: Interactive feature cards showcasing paperless workflow and WhatsApp alerts.
- **`PlatformPricing.vue`**: Pricing comparison table presenting SaaS packages.
- **`DoctorBookingForm.vue`**: Multi-step patient booking component with branch selector, calendar date picker, shift selector, phone normalization, automatic followup check, and WhatsApp activation link.
- **`TemplateGeneral.vue`**: Clean general medicine clinic website template.
- **`TemplateDental.vue`**: Dentistry-optimized website template.
- **`TemplatePediatrics.vue`**: Child health & pediatrics specialized website template.
- **`TemplateDermatology.vue`**: Dermatology & cosmetic clinic website template.
- **`TemplateObgyn.vue`**: Women's health & obstetrics website template.
- **`TemplateOrthopedics.vue`**: Bone & joint surgery website template.
- **`IconTooth.vue`**: Dental specialty SVG icon.

### 5.3 Composables & Middleware

#### `composables/useClinicBooking.ts`
- **`resolveTenant(host, subdomain)`**: Fetches doctor configuration by hostname or slug.
- **`fetchPlans()`**: Loads public subscription packages.
- **`submitLead(leadData)`**: Submits new doctor sales lead.
- **`checkFollowupEligibility(phone, clinicId)`**: Checks if patient phone qualifies for followup pricing.
- **`submitBooking(bookingData)`**: Registers patient appointment.
- **`fetchLiveQueueStatus(referenceCode)`**: Queries current turn and queue progress.

#### `server/middleware/tenant.ts`
- Extracts incoming HTTP `Host` header and attaches clean hostname to SSR event context.

---
*End of Project Tree Specification.*
