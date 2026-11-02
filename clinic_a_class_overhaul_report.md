# COMPREHENSIVE CODE AUDIT, BUG FIX & A-CLASS UI/UX OVERHAUL SPECIFICATION
## Target Repository: `https://github.com/MoElNemr/clinic`

> **CRITICAL DIRECTIVE FOR THE AI CODING ASSISTANT (ANTIGRAVITY):**
> A complete code and UI/UX audit was conducted on the `MoElNemr/clinic` repository across `/backend`, `/doctor-dashboard`, `/super-admin`, `/web-platform`, and `/JS-server`.
>
> **3 MAJOR ARCHITECTURAL & UI/UX FAILURES MUST BE FIXED IMMEDIATELY:**
> 1. **Inconsistent Headers & Page Layouts:** Currently, every page in `/doctor-dashboard` (`DoctorWorkspaceView`, `ReceptionDeskView`, `SettingsView`) renders its own different header and layout structure, making the app jump and feel like disconnected pages. You MUST implement a **Single Persistent Master Layout (`AppShellLayout.vue`)** with an unchanging `64px` Top Header and exact 12-column page grid divisions defined in **Part 3**.
> 2. **Hardcoded Scattered Colors (No Centralized Theme Engine):** Colors are currently hardcoded across `.vue` files (`#1e293b`, `bg-white`, `bg-slate-800`, etc.). You MUST centralize 100% of colors into **ONE single file (`src/assets/css/theme.css`)** using CSS Variables (`:root` for Light Mode and `html.dark` for Dark Mode) mapped to semantic Tailwind classes (`bg-app`, `bg-card`, `text-content`, `border-line`, `bg-brand`, etc.) as defined in **Part 3.1**.
> 3. **Backend & Frontend Logic Bugs:** Race conditions in queue numbering, missing Egyptian phone normalization, missing `/web-platform/server/middleware/tenant.ts` for Custom Domains, SSR state bleed, and UI flickering every 12 seconds during polling.
>
> Execute **every single fix and UI/UX layout overhaul in this document** without skipping any item or leaving placeholders.

---

## PART 1: CRITICAL BACKEND & MICROSERVICE BUGS (`/backend` & `/JS-server`)

### 1.1 Queue Number Race Conditions & Double-Booking Bug
- **Affected Files:**
  - `backend/app/Http/Controllers/Public/PublicBookingController.php`
  - `backend/app/Http/Controllers/Clinic/ReceptionController.php`
  - `backend/app/Http/Controllers/Clinic/DoctorWorkspaceController.php`
- **The Bug:**
  Queue number assignment (`max(queue_number) + 1`) and status transitions (`in_consultation`, `completed`, `arrived_waiting`) execute outside pessimistic database locks. When two patients book simultaneously online (or when the receptionist books a walk-in at the exact moment a patient books online), both queries read the same `max(queue_number)` and collide on the unique index `(clinic_id, shift_id, appointment_date, queue_number)`.
- **Mandatory Fix:**
  1. Wrap all booking creations (`bookAppointment`, `quickBook`) inside `DB::transaction(function () { ... })`.
  2. Lock the parent `ClinicShift` row using `ClinicShift::where('id', $shiftId)->lockForUpdate()->firstOrFail()` before querying the current `max('queue_number')` and checking `max_patients` capacity.
  3. Wrap `completeAndCallNextPatient()` and `updateStatus()` in `DB::transaction()` with `lockForUpdate()` on the target `Appointment` rows.

### 1.2 Broken Egyptian Phone Number Normalization (Duplicate Patient Records)
- **Affected Files:**
  - `backend/app/Services/FollowupDetectorService.php`
  - `backend/app/Http/Controllers/Public/PublicBookingController.php`
  - `backend/app/Http/Controllers/Clinic/ReceptionController.php`
- **The Bug:**
  If a patient books first with `01012345678`, and next week enters `+201012345678`, `201012345678`, or `010 1234 5678`, the backend treats them as **different strings**, failing to match their existing `Patient` record, failing to detect their free Follow-up (`استشارة`) eligibility, and creating duplicate patient profiles!
- **Mandatory Fix:**
  - Create `App\Support\PhoneNormalizer::normalizeEgyptianPhone(string $raw): string` that strips all spaces/dashes/plus signs and normalizes both local (`01xxxxxxxxx`) and international (`201xxxxxxxxx`) inputs into a canonical 11-digit Egyptian mobile format (`010xxxxxxxx`, `011xxxxxxxx`, `012xxxxxxxx`, `015xxxxxxxx`) for DB storage, and converts to `201xxxxxxxxx` when dispatching to `/JS-server` (WhatsApp).
  - Apply `PhoneNormalizer::normalizeEgyptianPhone()` in `BookAppointmentRequest`, `QuickBookingRequest`, and `FollowupDetectorService` BEFORE querying `patients`.

### 1.3 Time-of-Day Precision Bug in `FollowupDetectorService.php`
- **Affected File:** `backend/app/Services/FollowupDetectorService.php`
- **The Bug:**
  Using raw `now()->subDays($clinic->followup_days_limit)` with timestamps or unnormalized `diffInDays()` causes edge-case failures when a patient visited at `14:00` on Day 1 and books at `20:00` on Day 10.
- **Mandatory Fix:**
  - Always normalize both dates using `->startOfDay()`:
    ```php
    $cutoffDate = now()->timezone('Africa/Cairo')->startOfDay()->subDays($clinic->followup_days_limit)->toDateString();
    ```
  - Verify that no subsequent `followup` appointment with status `IN ('booked', 'arrived_waiting', 'in_consultation', 'completed')` exists after `$lastNewVisit->appointment_date` so a patient cannot book multiple free follow-ups off a single consultation.

### 1.4 Unrealistic/Negative Wait Times & Ignored Shift Start Times in `QueueTimeCalculatorService.php`
- **Affected File:** `backend/app/Services/QueueTimeCalculatorService.php`
- **The Bug:**
  When a patient books at `11:00 AM` for an Evening Shift that starts at `05:00 PM (17:00)`, if recalculation triggers before the shift starts, using `now()` as `$baseTime` tells the patient their appointment is at `11:15 AM` (hours before the clinic opens!).
- **Mandatory Fix:**
  - Set `$baseTime = max(now()->timezone('Africa/Cairo'), Carbon::parse($date . ' ' . $shift->start_time, 'Africa/Cairo'))`.
  - Ensure `estimated_window_start` is NEVER earlier than `now()->addMinutes(5)` when recalculating live queues.

### 1.5 Prescription PDF Layout Overflow & Unescaped Input (`PrescriptionPdfService.php`)
- **Affected File:** `backend/app/Services/PrescriptionPdfService.php` & Blade PDF view
- **Mandatory Fix:**
  - In `mPDF` configuration, strictly bind `margin_top`, `margin_bottom`, `margin_left`, `margin_right` from `$tenant->print_settings` with `setAutoTopMargin = false` and `setAutoBottomMargin = false` when `use_preprinted_letterhead == true` so exact millimeter offsets on physical A5/A4 paper are respected.
  - Use clean HTML table layout with `page-break-inside: avoid` per drug row and strict Blade `{{ }}` escaping.

### 1.6 Public Booking Spam Vulnerability & Duplicate Same-Day Guard (`routes/api.php`)
- **Affected Files:** `backend/routes/api.php`, `backend/app/Http/Controllers/Public/PublicBookingController.php`
- **Mandatory Fix:**
  1. Apply `middleware('throttle:10,1')` on `POST /api/public/book-appointment` and `POST /api/public/leads`.
  2. Inside `PublicBookingController::bookAppointment`, check if the normalized phone number already has an active appointment (`status NOT IN ('cancelled', 'completed')`) in the same `clinic_id` on the same `appointment_date`. If yes, reject with HTTP `422`: `"لديك حجز مسجل بالفعل في هذا اليوم برقم الدور #{queue_number} (كود #{reference_code})"`.

### 1.7 Memory Leak & Socket Disconnects in `/JS-server` (`server.js` & `cache.js`)
- **Affected Files:** `JS-server/server.js`, `JS-server/cache.js`
- **Mandatory Fix:**
  1. Add TTL cleanup (5 minutes for un-scanned QR codes and dead states) in `cache.js`.
  2. Implement exponential backoff auto-reconnect (`1s, 2s, 5s, 10s, 30s`) on socket drop in `server.js` unless `statusCode === DisconnectReason.loggedOut`.
  3. Ensure whenever Laravel updates a queue (`QueueUpdatedEvent`), it pushes an instant HTTP webhook to `/JS-server/internal/broadcast-queue` which immediately emits a Socket.io event `queue:updated` to rooms `clinic_${clinicId}`, `tv_${tvScreenCode}`, and `track_${referenceCode}` so all screens update in **< 200ms**.

---

## PART 2: CRITICAL FRONTEND ARCHITECTURAL & FUNCTIONAL BUGS

### 2.1 Missing `server/middleware/tenant.ts` in `/web-platform` (Custom Domains Broken!)
- **Affected Directory:** `/web-platform`
- **The Bug:**
  The entire `/web-platform/server/` directory is missing! Only a client-side route `/d/[subdomain].vue` was created, which means Custom Domains (`drahmedclinic.com`) land on the platform marketing page instead of the doctor's website, and SSR OpenGraph tags don't work on root domains.
- **Mandatory Fix:**
  1. Create `/web-platform/server/middleware/tenant.ts` and update `pages/index.vue` so during SSR (`useRequestHeaders(['host'])`), it checks the `host` header:
     - If `host` is a custom domain or doctor subdomain (not `localhost:3000` or the root platform domain), fetch `/api/public/tenant-by-host?host=${host}` via `useAsyncData`, inject dynamic `<Title>` and `<Meta property="og:..." />` via `useSeoMeta()`, and render the doctor's Specialty Template directly on `/`!
     - Keep `/d/[subdomain].vue` working for local preview testing using the exact same `<DoctorSiteRenderer :tenant="tenant" />` component.
     - If a subdomain or custom domain is not found, render a clean Arabic 404 Medical Page (`"عذراً، رابط العيادة غير صحيح أو غير مفعل حالياً"`).

### 2.2 SSR State Bleed & Hydration Errors in `/web-platform/composables/useClinicBooking.ts`
- **Affected File:** `web-platform/composables/useClinicBooking.ts`
- **Mandatory Fix:**
  - Move ALL `ref()` / `useState()` declarations strictly **inside** `export function useClinicBooking()` so state never bleeds between concurrent SSR requests.
  - Use the server-provided Cairo date from `/api/public/tenant-by-host` instead of client-side `new Date()`.

### 2.3 Janky Polling Flicker, Memory Leaks & Modal Backdrop Data Loss
- **Affected Files:** `/doctor-dashboard` and `/super-admin` views & modals
- **Mandatory Fixes:**
  1. **Delete `HelloWorld.vue`** from both `/doctor-dashboard` and `/super-admin`.
  2. **Fix Uncleaned Timers (`onUnmounted`):** Every `setInterval` must be cleared inside `onUnmounted(() => clearInterval(timer))`.
  3. **Stop Setting `isLoading = true` on Background Polling:** Background polling every 12s must update Pinia store arrays silently in-place without setting `isLoading = true` (which flashes the screen and resets scroll position).
  4. **Prevent Accidental Modal Backdrop Close:** Clicking the dark overlay behind `ModalPrescription.vue`, `ModalLabsAndAttachments.vue`, `ModalExtraServicesAndFees.vue`, or `ModalQuickBook.vue` MUST NOT close the modal if the user has unsaved changes (`isDirty`).

---

## PART 3: CENTRALIZED SINGLE-FILE THEME ENGINE & UNIFIED PAGE LAYOUT ARCHITECTURE

### 3.1 Single-File Centralized Color & Dark/Light Theme Engine (`src/assets/css/theme.css`)

> **STRICT RULE FOR ANTIGRAVITY:**
> It is **FORBIDDEN** to scatter hardcoded hex colors (`#1e293b`, `#000`, `#fff`) or raw Tailwind color classes (`bg-white`, `bg-slate-900`, `text-slate-800`, `border-gray-200`) across `.vue` files!
> **100% of colors across `/doctor-dashboard`, `/super-admin`, and `/web-platform` MUST come from ONE single CSS Variables file (`src/assets/css/theme.css`)** mapped into `tailwind.config.js`. Changing a variable in `theme.css` or toggling `<html class="dark">` MUST instantly re-theme every header, sidebar, card, table, input, and modal across the entire application!

#### Step 1: Create `src/assets/css/theme.css` (The Single Source of Truth for All Colors)

```css
/* src/assets/css/theme.css — SINGLE SOURCE OF TRUTH FOR LIGHT & DARK THEMES */
@import url('https://fonts.googleapis.com/css2?family=Cairo:wght@600;700;800&family=IBM+Plex+Sans+Arabic:wght@400;500;600;700&display=swap');

:root {
  /* 1. App Surfaces & Backgrounds (Light Mode - Clean Medical Daylight) */
  --bg-app: 248 250 252;          /* #F8FAFC - Main viewport background */
  --bg-header: 255 255 255;       /* #FFFFFF - Persistent top header & sidebar */
  --bg-card: 255 255 255;         /* #FFFFFF - Standard card & table surface */
  --bg-elevated: 241 245 249;     /* #F1F5F9 - Secondary/hover surface, table header, modal footer */
  --bg-input: 255 255 255;        /* #FFFFFF - Form input background */
  --bg-overlay: 15 23 42;         /* #0F172A - Modal backdrop (use with opacity) */

  /* 2. Typography & Content Hierarchy */
  --text-primary: 15 23 42;       /* #0F172A - Headings, patient names, primary values */
  --text-secondary: 71 85 105;    /* #475569 - Labels, table cells, descriptions */
  --text-muted: 148 163 184;      /* #94A3B8 - Placeholders, previous visit muted text, hints */
  --text-inverse: 255 255 255;    /* #FFFFFF - Text on primary brand buttons */

  /* 3. Borders & Dividers */
  --border-subtle: 226 232 240;   /* #E2E8F0 - Card & table borders */
  --border-strong: 203 213 225;   /* #CBD5E1 - Input borders & active dividers */

  /* 4. Primary Brand Accent (Medical Teal) */
  --brand-primary: 15 118 110;    /* #0F766E - Primary buttons, active tab indicator */
  --brand-hover: 17 94 89;        /* #115E59 - Primary button hover */
  --brand-subtle: 240 253 250;    /* #F0FDFA - Active nav pill background, selected row */
  --brand-border: 153 246 228;    /* #99F6E4 - Brand badge border */

  /* 5. Semantic Clinical Status Colors */
  /* Success / In Consultation / Completed (Emerald) */
  --status-success: 4 120 87;         /* #047857 */
  --status-success-bg: 236 253 245;   /* #ECFDF5 */
  --status-success-border: 167 243 208;/* #A7F3D0 */

  /* Info / Arrived Waiting in Reception (Royal Blue) */
  --status-info: 29 78 216;           /* #1D4ED8 */
  --status-info-bg: 239 246 255;      /* #EFF6FF */
  --status-info-border: 191 219 254;  /* #BFDBFE */

  /* Warning / Postponed / Unpaid Extra Services (Amber) */
  --status-warning: 180 83 9;         /* #B45309 */
  --status-warning-bg: 255 251 235;   /* #FFFBEB */
  --status-warning-border: 253 230 138;/* #FDE68A */

  /* Danger / Critical Medical Alert / Drug Allergy / Cancel (Crimson Red) */
  --status-danger: 185 28 28;         /* #B91C1C */
  --status-danger-bg: 254 242 242;    /* #FEF2F2 */
  --status-danger-border: 254 202 202;/* #FECACA */
}

html.dark, [data-theme="dark"] {
  /* 1. App Surfaces & Backgrounds (Dark Mode - Eye-Safe Clinical Slate) */
  --bg-app: 11 17 32;             /* #0B1120 - Deep clinical slate canvas */
  --bg-header: 15 23 42;          /* #0F172A - Persistent header & sidebar */
  --bg-card: 30 41 59;            /* #1E293B - Card & table surface */
  --bg-elevated: 51 65 85;        /* #334155 - Hover surface, table header, modal sub-surface */
  --bg-input: 15 23 42;           /* #0F172A - Form input background */
  --bg-overlay: 2 6 23;           /* #020617 - Modal backdrop */

  /* 2. Typography & Content Hierarchy */
  --text-primary: 248 250 252;    /* #F8FAFC - Crisp white-slate headings & values */
  --text-secondary: 203 213 225;  /* #CBD5E1 - Readable secondary labels */
  --text-muted: 100 116 139;      /* #64748B - Muted hints */
  --text-inverse: 255 255 255;    /* #FFFFFF */

  /* 3. Borders & Dividers */
  --border-subtle: 51 65 85;      /* #334155 - Subtle dark border */
  --border-strong: 71 85 105;     /* #475569 - Input border */

  /* 4. Primary Brand Accent (Luminous Medical Teal for Dark Mode) */
  --brand-primary: 20 184 166;    /* #14B8A6 */
  --brand-hover: 13 148 136;      /* #0D9488 */
  --brand-subtle: 19 78 74;       /* #134E4A */
  --brand-border: 45 212 191;     /* #2DD4BF */

  /* 5. Semantic Clinical Status Colors (Dark-Adjusted) */
  --status-success: 52 211 153;       /* #34D399 */
  --status-success-bg: 6 78 59;       /* #064E3B */
  --status-success-border: 5 150 105; /* #059669 */

  --status-info: 96 165 250;          /* #60A5FA */
  --status-info-bg: 30 58 138;        /* #1E3A8A */
  --status-info-border: 59 130 246;   /* #3B82F6 */

  --status-warning: 251 191 36;       /* #FBBF24 */
  --status-warning-bg: 120 53 15;     /* #78350F */
  --status-warning-border: 217 119 6; /* #D97706 */

  --status-danger: 248 113 113;       /* #F87171 */
  --status-danger-bg: 127 29 29;      /* #7F1D1D */
  --status-danger-border: 220 38 38;  /* #DC2626 */
}
```

#### Step 2: Map Semantic Tokens in `tailwind.config.js`

```javascript
// tailwind.config.js (Use identical mapping in /doctor-dashboard, /super-admin, /web-platform)
export default {
  darkMode: 'class',
  content: ['./index.html', './src/**/*.{vue,js,ts,jsx,tsx}'],
  theme: {
    extend: {
      fontFamily: {
        sans: ['"IBM Plex Sans Arabic"', '"Cairo"', 'sans-serif'],
        display: ['"Cairo"', '"IBM Plex Sans Arabic"', 'sans-serif'],
      },
      colors: {
        surface: {
          app: 'rgb(var(--bg-app) / <alpha-value>)',
          header: 'rgb(var(--bg-header) / <alpha-value>)',
          card: 'rgb(var(--bg-card) / <alpha-value>)',
          elevated: 'rgb(var(--bg-elevated) / <alpha-value>)',
          input: 'rgb(var(--bg-input) / <alpha-value>)',
          overlay: 'rgb(var(--bg-overlay) / <alpha-value>)',
        },
        content: {
          primary: 'rgb(var(--text-primary) / <alpha-value>)',
          secondary: 'rgb(var(--text-secondary) / <alpha-value>)',
          muted: 'rgb(var(--text-muted) / <alpha-value>)',
          inverse: 'rgb(var(--text-inverse) / <alpha-value>)',
        },
        line: {
          subtle: 'rgb(var(--border-subtle) / <alpha-value>)',
          strong: 'rgb(var(--border-strong) / <alpha-value>)',
        },
        brand: {
          DEFAULT: 'rgb(var(--brand-primary) / <alpha-value>)',
          hover: 'rgb(var(--brand-hover) / <alpha-value>)',
          subtle: 'rgb(var(--brand-subtle) / <alpha-value>)',
          border: 'rgb(var(--brand-border) / <alpha-value>)',
        },
        status: {
          success: 'rgb(var(--status-success) / <alpha-value>)',
          'success-bg': 'rgb(var(--status-success-bg) / <alpha-value>)',
          'success-border': 'rgb(var(--status-success-border) / <alpha-value>)',
          info: 'rgb(var(--status-info) / <alpha-value>)',
          'info-bg': 'rgb(var(--status-info-bg) / <alpha-value>)',
          'info-border': 'rgb(var(--status-info-border) / <alpha-value>)',
          warning: 'rgb(var(--status-warning) / <alpha-value>)',
          'warning-bg': 'rgb(var(--status-warning-bg) / <alpha-value>)',
          'warning-border': 'rgb(var(--status-warning-border) / <alpha-value>)',
          danger: 'rgb(var(--status-danger) / <alpha-value>)',
          'danger-bg': 'rgb(var(--status-danger-bg) / <alpha-value>)',
          'danger-border': 'rgb(var(--status-danger-border) / <alpha-value>)',
        },
      },
    },
  },
  plugins: [],
};
```

#### Step 3: Create `src/composables/useTheme.js`
- Reads/saves `localStorage.getItem('clinic_theme')` (`'light'` or `'dark'`).
- Toggles `document.documentElement.classList.toggle('dark', isDark)` in 1 click from the persistent Top Header.

---

### 3.2 Fixing the Changing Header: The Unified App Shell (`AppShellLayout.vue`)

> **THE PROBLEM right now:** Every view in `/doctor-dashboard` (`DoctorWorkspaceView.vue`, `ReceptionDeskView.vue`, `SettingsView.vue`) builds its own separate header with different heights, buttons, and styles! When moving from one page to another, the entire top bar jumps and changes shape.
>
> **THE MANDATORY SOLUTION:**
> Remove all individual headers from inside page views (`DoctorWorkspaceView.vue`, `ReceptionDeskView.vue`, `SettingsView.vue`)! Wrap all authenticated dashboard pages inside **ONE persistent master shell component: `src/components/layout/AppShellLayout.vue`**.

#### Exact Anatomy of `AppShellLayout.vue` (Never Changes Shape Across Pages)

```text
+---------------------------------------------------------------------------------------------------+
|  PERSISTENT UNIFIED TOP HEADER (Fixed Height: h-16 / 64px, bg-surface-header border-b border-line)|
|                                                                                                   |
|  [Right: Brand & Branch Switcher]   [Center: Main Navigation Pills]    [Left: Global Utilities]   |
|  🏥 د. أحمد المنشاوي                [🩺 غرفة الكشف] [📋 الاستقبال]     [⏳ انتظار: 8] [📺 TV]     |
|  📍 [فرع مدينة نصر ▾] (الفترة 2)     [💰 الخزنة] [⚙️ الإعدادات]         [🌗 الوضع الليلي] [خروج 🚪] |
+---------------------------------------------------------------------------------------------------+
|                                                                                                   |
|  DYNAMIC PAGE CANVAS (<RouterView /> inside h-[calc(100vh-4rem)] bg-surface-app)                  |
|  Only this inner region changes when navigating between Workspace, Reception, and Settings!       |
|                                                                                                   |
+---------------------------------------------------------------------------------------------------+
```

1. **Zone 1 (Right — Identity & Global Branch Switcher):**
   - Shows Clinic Badge + Doctor Name + **Global Clinic Branch Dropdown (`عيادة مدينة نصر ▾`)** + Active Shift badge (`الفترة المسائية`).
   - Switching the branch here updates `settingsStore.activeClinicId` globally across all pages.
   - *(If logged in as Receptionist, the branch dropdown is locked to their assigned branch).*
2. **Zone 2 (Center — Unified Navigation Tabs):**
   - Renders clean, consistent navigation pills with active indicator (`bg-brand-subtle text-brand border border-brand-border font-bold`):
     - `🩺 غرفة الكشف` (`/workspace` — visible to Doctor)
     - `📋 مكتب الاستقبال والطابور` (`/reception` — visible to Doctor & Receptionist)
     - `⚙️ إعدادات العيادة` (`/settings/clinical` — visible to Doctor)
3. **Zone 3 (Left — Persistent Live Counters & Theme Switcher):**
   - **Compact Live Queue Pills** (`في الانتظار: 8` | `مؤجل: 1` | `تم اليوم: 14`) — always visible on every page!
   - **📺 زر شاشة الانتظار** (opens `ModalWaitingTvLauncher.vue` from anywhere).
   - **🌗 زر الوضع الليلي / النهاري** (`useTheme().toggleTheme()`).
   - **🚪 تسجيل الخروج**.

---

### 3.3 Exact Page-by-Page Structural Division (`12-Column Grid Wireframes`)

Below the unchanging `64px` header (`h-[calc(100vh-4rem)]`), structure each page using these exact grid divisions:

#### Page 1: `DoctorWorkspaceView.vue` (Split-Pane Zero-Scroll Doctor Cockpit)

Instead of stacking everything in one long vertical column, divide the Doctor Workspace into a **12-Column Fixed Viewport Grid (`grid grid-cols-12 gap-5 p-5 h-[calc(100vh-4rem)] overflow-hidden`)**:

```text
+-------------------------------+-------------------------------------------------------------------+
| RIGHT SIDEBAR (3 Cols)        | MAIN CONSULTATION COCKPIT (9 Cols - col-span-9)                   |
| Live Queue & Next Patient     |                                                                   |
|-------------------------------| 1. [⚠️ RED MEDICAL ALERT BAR: مريض سكري وضغط | حساسية بنسلين]     |
| 🟢 بالداخل الآن:              |-------------------------------------------------------------------|
|    #4 محمد عبد الرحمن (كشف)   | 2. ACTIVE PATIENT & VITALS COMPARISON STRIP (Single Horizontal Card)|
|-------------------------------|    [#4] محمد عبد الرحمن (42 سنة) | الوزن: 82kg (↓85) | ضغط: 120/80|
| ⏳ في صالة الانتظار (3):      |-------------------------------------------------------------------|
|    #5 محمود سامي (كشف)        | 3. THE 4 CLINICAL ACTION CARDS (2x2 Bento Grid - Equal Height)    |
|    #6 سارة عادل (استشارة)     | +-------------------------------+-------------------------------+ |
|    #8 كريم حسن (كشف)          | | 📋 1. التاريخ المرضي (Alt+1)  | 💊 2. التشخيص والروشتة (Alt+2)| |
|-------------------------------| | 3 زيارات سابقة                | ✅ تم كتابة التشخيص و 4 أدوية | |
| 🟠 مؤجل لحين الوصول (1):      | | آخر زيارة: منذ 5 أيام         | [فتح وتعديل]  [طباعة سريعة 🖨️]| |
|    #7 هاني رمزي               | +-------------------------------+-------------------------------+ |
|-------------------------------| | 🔬 3. التحاليل والمرفقات(Alt+3)| ➕ 4. خدمات إضافية وإعادة(Alt+4)| |
| [✅ إنهاء الكشف واستدعاء #5]  | | المطلوب اليوم: (2) تحاليل     | المضاف: موجات صوتية (+200 ج.م)| |
| (Giant Sticky Bottom Button)  | | أرشيف المريض: (3) ملفات أشعة  | موعد الإعادة: الأحد 10 نوفمبر | |
|                               | +-------------------------------+-------------------------------+ |
+-------------------------------+-------------------------------------------------------------------+
```

- **Why this layout is A-Class:**
  1. **Zero Page Scrolling:** Everything fits on a single desktop screen (`100vh - 4rem`).
  2. **Right Sidebar (3 Columns):** The doctor can always see who is waiting outside (`arrived_waiting`) and who is postponed without switching pages, and the giant green button **`✅ إنهاء الكشف واستدعاء التالي (#5)`** is anchored cleanly at the bottom of the queue rail!
  3. **Live Preview Inside the 4 Bento Cards:** Before clicking any modal, each of the 4 cards shows a live summary of what has been entered for this patient today, plus quick-action mini-buttons (like `🖨️ طباعة الروشتة` directly on Card 2 once drugs are saved!).

---

#### Page 2: `ReceptionDeskView.vue` (Structured 3-Tier Operational Desk)

Divide the Reception Desk inside `AppShellLayout.vue` into **3 clean horizontal tiers (`p-5 space-y-5 max-w-[1600px] mx-auto`)**:

```text
+---------------------------------------------------------------------------------------------------+
| TIER 1: KPI METRICS (8 Cols) & PRIMARY ACTION BUTTONS (4 Cols)                                    |
| [إجمالي اليوم: 24] [بالانتظار الآن: 6] [داخل الكشف: #4] [صافي الدرج: 4,250 ج.م]                   |
|                                              [➕ حجز مريض سريع (F2)]  [💰 خزنة ومصروفات اليوم (F4)]|
+---------------------------------------------------------------------------------------------------+
| TIER 2: UNIFIED FILTER & SEARCH BAR (Single Elevated Strip)                                       |
| [🔍 بحث باسم المريض، رقم الموبايل، أو كود #REF...]   [الكل (24)] [بالانتظار (6)] [لم يحضر (8)] [مؤجل]|
+---------------------------------------------------------------------------------------------------+
| TIER 3: HIGH-CLARITY LIVE QUEUE TABLE (bg-surface-card border border-line-subtle rounded-2xl)     |
| #  | كود الحجز | اسم المريض والهاتف | نوع الزيارة والحساب      | القياسات الحيوية | الإجراء السريع |
|----|-----------|--------------------|--------------------------|------------------|----------------|
| #4 | #CLN-4829 | محمد عبد الرحمن    | كشف (300 ج.م - مدفوع ✅) | 120/80 | 82kg    | 🟢 داخل الكشف  |
| #5 | #CLN-9102 | محمود سامي         | كشف + خدمة ⚠️ (+200 ج.م) | [🟣 تسجيل قياسات]| [💰 تحصيل 200] |
| #6 | #CLN-3310 | سارة عادل          | استشارة مجانية 🎉        | 110/70 | 65kg    | [🔵 إدخال] [🟠]|
+---------------------------------------------------------------------------------------------------+
```

- **Strict Table Design Rules:**
  - Use `tabular-nums` on all numbers, prices, and phones.
  - Row `#4` (`in_consultation`) gets a highlighted border and subtle tint (`bg-status-success-bg/40 border-r-4 border-r-status-success`).
  - If the doctor added an extra service inside the room (`extra_services_total > 0 && !extra_services_paid`), show the pulsing amber button **`💰 تحصيل خدمات إضافية (+200 ج.م)`** right in the payment column!

---

#### Page 3: `SettingsLayoutView.vue` (2-Column Master-Detail Settings Architecture)

Replace the messy single `SettingsView.vue` with a structured **Master-Detail Split Grid (`grid grid-cols-12 gap-6 p-6 max-w-[1440px] mx-auto`)** inside `AppShellLayout.vue`:

```text
+-------------------------------+-------------------------------------------------------------------+
| RIGHT MENU RAIL (3 Cols)      | ACTIVE SETTINGS CONTENT PANEL (9 Cols - bg-surface-card p-6)      |
| Sticky Vertical Tabs Card     |                                                                   |
|-------------------------------| [Section Title & Description Header]                              |
| [💊 1. الإعدادات السريرية]    |-------------------------------------------------------------------|
|    - العلامات الحيوية         | (If Tab 1 - Clinical Settings):                                   |
|    - هوامش طباعة الروشتة      | - Sub-tabs: [هوامش الطباعة والمعاينة] [العلامات الحيوية] [الأدوية] |
|    - كتالوج الأدوية والقوالب  | - Interactive Split View for Print Margins:                       |
|    - كتالوج التحاليل والأشعة  |   Right Half: Millimeter inputs (Top/Bottom/Left/Right, A5/A4)    |
|                               |   Left Half: LIVE SCALED A5 PAPER VISUAL SIMULATOR showing exact  |
| [🏥 2. الفروع والورديات]      |   dashed letterhead zones + [🖨️ طباعة ورقة اختبار القياسات]       |
|    - فروع العيادة والأسعار    |                                                                   |
|    - مواعيد العمل والورديات   |-------------------------------------------------------------------|
|    - العطلات والاعتذار الطارئ | [Sticky Footer Bar inside Card:                  [💾 حفظ التعديلات]|
|                               +-------------------------------------------------------------------+
| [👩‍💼 3. طاقم الاستقبال]        |
| [🌐 4. الموقع والواتساب]      |
+-------------------------------+
```

---

#### Page 4: `/super-admin` Master Layout (`AdminShellLayout.vue`)

Apply the exact same `theme.css` variables and persistent shell principle to `/super-admin`:
- **Persistent Right Sidebar (`AdminSidebar.vue`, width `w-64`, `bg-surface-header border-l border-line-subtle`):**
  - Brand Header + 5 Navigation Links (`📊 لوحة الإحصائيات`, `👨‍⚕️ إدارة الأطباء (God-Mode)`, `📥 طلبات الاشتراك (Leads)`, `💎 الباقات والأسعار`, `💳 شحن المحافظ وفوري`).
- **Persistent Top Header (`AdminHeader.vue`, height `h-16`):**
  - Breadcrumb title on the right + Global Doctor Quick-Search in the center + **🌗 Dark/Light Theme Toggle** & Admin Profile on the left.

---

## PART 4: EXECUTION ORDER FOR THE AI ASSISTANT

Execute these 4 phases in order and verify each with `npm run build` and `php artisan test`:

1. **Phase 1 — Centralized Theme Engine (`theme.css`) & Unified App Shells:**
   - Create `src/assets/css/theme.css` and update `tailwind.config.js` in `/doctor-dashboard`, `/super-admin`, and `/web-platform`.
   - Create `useTheme.js` (Light/Dark mode toggle with `localStorage` persistence).
   - Build `AppShellLayout.vue` in `/doctor-dashboard` (removing all duplicate per-page headers!) and `AdminShellLayout.vue` in `/super-admin`.
   - Replace all hardcoded hex colors and raw `bg-white` / `bg-slate-*` classes across all views and modals with the semantic theme classes (`bg-surface-app`, `bg-surface-card`, `text-content-primary`, `border-line-subtle`, `bg-brand`, etc.).
2. **Phase 2 — Page Grid Layouts & Settings Split Overhaul (`/doctor-dashboard`):**
   - Refactor `DoctorWorkspaceView.vue` into the **3-Col Queue Sidebar + 9-Col Consultation Cockpit** layout with live summaries on the 4 Bento cards, keyboard shortcuts (`Alt+1..4`), `@media print` prescription sheet, and backdrop dirty-check protection on all modals.
   - Refactor `ReceptionDeskView.vue` into the **3-Tier Operational Desk** with silent diff-based background polling (no UI flicker), Socket.io real-time updates, and the unpaid extra services collection button.
   - Refactor Settings into the **3-Col Sticky Sub-Nav + 9-Col Content Panel** (`SettingsLayoutView.vue`) with all 4 sections, the **Live Visual A5/A4 Print Margin Paper Simulator**, and the **Emergency Day-Off Bulk Reschedule Modal**.
   - Upgrade `WaitingRoomTvView.vue` to the Hospital Kiosk design with `onUnmounted` timer cleanup.
3. **Phase 3 — Backend & `/JS-server` Bug Fixes:**
   - Add `PhoneNormalizer`, `DB::transaction` + `lockForUpdate()` in booking & queue controllers, rate-limiting on public endpoints, and duplicate same-day booking guard.
   - Fix `FollowupDetectorService` (`startOfDay()` + unconsumed follow-up check) and `QueueTimeCalculatorService` (future shift start time handling).
   - Fix `/JS-server` TTL cache cleanup, exponential backoff auto-reconnect, and Socket.io instant room broadcasts.
4. **Phase 4 — `/web-platform` SSR Custom Domain Middleware & 6 Specialty Templates:**
   - Create `/web-platform/server/middleware/tenant.ts` and update `pages/index.vue` + `pages/d/[subdomain].vue` for SSR Host resolution, SEO/OpenGraph tags, and clean 404 fallback.
   - Fix SSR state bleed in `composables/useClinicBooking.ts` and upgrade `DoctorBookingForm.vue` (Digital Queue Ticket UI).