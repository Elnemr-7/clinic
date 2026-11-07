const axios = require('d:/clinic/JS-server/node_modules/axios');

const BASE_URL = 'http://127.0.0.1:8000';

const colors = {
  reset: "\x1b[0m",
  green: "\x1b[32m",
  red: "\x1b[31m",
  cyan: "\x1b[36m",
  yellow: "\x1b[33m",
  bold: "\x1b[1m",
};

function logStep(stepNum, title) {
  console.log(`\n${colors.cyan}${colors.bold}================================================================${colors.reset}`);
  console.log(`${colors.yellow}${colors.bold}STEP ${stepNum}: ${title}${colors.reset}`);
  console.log(`${colors.cyan}================================================================${colors.reset}`);
}

function logSuccess(msg, data = null) {
  console.log(`${colors.green}✔ ${msg}${colors.reset}`);
  if (data) console.log(JSON.stringify(data, null, 2));
}

function logError(msg, err) {
  console.error(`${colors.red}✘ ${msg}${colors.reset}`);
  if (err?.response?.data) console.error(JSON.stringify(err.response.data, null, 2));
  else if (err?.message) console.error(err.message);
  process.exit(1);
}

async function runDoctorOnboardingJourney() {
  console.log(`${colors.bold}Starting Automated End-to-End Doctor Onboarding & Setup Journey Test...${colors.reset}\n`);

  let adminToken, doctorToken, leadId, tenantId, doctorEmail, doctorPassword;
  const uniqueSuffix = Date.now().toString().slice(-5);
  const testSubdomain = `dr-onboarding-${uniqueSuffix}`;

  // -------------------------------------------------------------
  // STEP 1: Public Doctor Demo Request (Inbound Lead)
  // -------------------------------------------------------------
  logStep(1, "Public Inbound Doctor Lead Submission on Landing Page");
  try {
    const leadRes = await axios.post(`${BASE_URL}/api/public/leads`, {
      doctor_name: `د. أحمد الشريف ${uniqueSuffix}`,
      phone: `010${uniqueSuffix}123`,
      specialty: 'dental',
      notes: 'مهتم بالباقة المتقدمة لعيادات الأسنان',
    });

    leadId = leadRes.data.data.id;
    logSuccess(`Doctor lead submitted successfully! Lead ID: #${leadId}`, leadRes.data.data);
  } catch (err) {
    logError("Failed to submit doctor lead", err);
  }

  // -------------------------------------------------------------
  // STEP 2: Super Admin Login & Lead Review
  // -------------------------------------------------------------
  logStep(2, "Super Admin Authentication & Inbound Leads Review");
  try {
    const adminRes = await axios.post(`${BASE_URL}/api/super-admin/login`, {
      email: 'admin@platform.com',
      password: 'password123',
    });

    adminToken = adminRes.data.data.token;
    logSuccess("Super Admin authenticated successfully!");

    const leadsList = await axios.get(`${BASE_URL}/api/super-admin/leads`, {
      headers: { Authorization: `Bearer ${adminToken}` },
    });
    logSuccess(`Leads listed successfully. Total leads: ${leadsList.data.data.total}`);
  } catch (err) {
    logError("Super Admin authentication or lead fetch failed", err);
  }

  // -------------------------------------------------------------
  // STEP 3: Super Admin Converts Lead into Active Doctor Tenant
  // -------------------------------------------------------------
  logStep(3, "Super Admin Converts Lead to Live Tenant & Staff Account");
  doctorEmail = `dr.sharif.${uniqueSuffix}@clinic.com`;
  doctorPassword = 'DoctorSecurePass2026';

  try {
    const convertRes = await axios.post(
      `${BASE_URL}/api/super-admin/leads/${leadId}/convert`,
      {
        subdomain: testSubdomain,
        specialty_key: 'dental',
        title_ar: 'استشاري زراعة وتجميل الأسنان',
        email: doctorEmail,
        password: doctorPassword,
      },
      { headers: { Authorization: `Bearer ${adminToken}` } }
    );

    tenantId = convertRes.data.data.tenant.id;
    logSuccess(`Lead converted! Tenant ID: #${tenantId}, Subdomain: ${testSubdomain}`, convertRes.data.data);
  } catch (err) {
    logError("Failed to convert lead into doctor tenant", err);
  }

  // -------------------------------------------------------------
  // STEP 4: Super Admin God-Mode Overrides Limits & Quotas
  // -------------------------------------------------------------
  logStep(4, "Super Admin Overrides Tenant WhatsApp & SMS Quotas");
  try {
    const walletRes = await axios.put(
      `${BASE_URL}/api/super-admin/tenants/${tenantId}/wallet`,
      {
        whatsapp_enabled: true,
        whatsapp_balance: 1500,
        sms_enabled: true,
        sms_balance: 300,
        max_clinics_override: 3,
      },
      { headers: { Authorization: `Bearer ${adminToken}` } }
    );

    logSuccess("Tenant limits and messaging wallets overridden successfully!", walletRes.data.data);
  } catch (err) {
    logError("Failed to update tenant wallet limits", err);
  }

  // -------------------------------------------------------------
  // STEP 5: Doctor Logs In with Converted Account Credentials
  // -------------------------------------------------------------
  logStep(5, "Doctor Logs Into Portal with Converted Credentials");
  try {
    const loginRes = await axios.post(`${BASE_URL}/api/clinic/login`, {
      email: doctorEmail,
      password: doctorPassword,
    });

    doctorToken = loginRes.data.data.token;
    logSuccess("Doctor logged in successfully!", {
      token: doctorToken.slice(0, 15) + '...',
      doctor: loginRes.data.data.user.name,
      tenant: loginRes.data.data.tenant.name,
    });
  } catch (err) {
    logError("Doctor login failed", err);
  }

  // -------------------------------------------------------------
  // STEP 6: Doctor Configures Clinical Biological Vitals & Rx Margins
  // -------------------------------------------------------------
  logStep(6, "Doctor Configures Biological Vitals & Prescription Margins");
  try {
    const vitalsRes = await axios.post(
      `${BASE_URL}/api/clinic/settings/vitals`,
      {
        vitals_config: [
          { key: 'bp', label: 'ضغط الدم', unit: 'mmHg', enabled: true },
          { key: 'sugar', label: 'السكر العشوائي', unit: 'mg/dL', enabled: true },
          { key: 'bleeding_time', label: 'سرعة النزف والتجلط', unit: 'ثانية', enabled: true },
        ],
      },
      { headers: { Authorization: `Bearer ${doctorToken}` } }
    );
    logSuccess("Vitals checklist customized successfully!");

    const marginsRes = await axios.post(
      `${BASE_URL}/api/clinic/settings/print-margins`,
      {
        print_settings: {
          paper_size: 'A5',
          margin_top: 40,
          margin_bottom: 20,
          margin_left: 15,
          margin_right: 15,
          footer_text: 'مع أطيب تمنياتنا بابتسامة مشرقة',
        },
      },
      { headers: { Authorization: `Bearer ${doctorToken}` } }
    );
    logSuccess("Prescription margins configured successfully!");
  } catch (err) {
    logError("Failed to configure doctor clinical settings", err);
  }

  // -------------------------------------------------------------
  // STEP 7: Doctor Creates Secondary Clinic Branch & Receptionist
  // -------------------------------------------------------------
  logStep(7, "Doctor Adds Secondary Branch & Hires Receptionist Staff");
  let newBranchId;
  try {
    const branchRes = await axios.post(
      `${BASE_URL}/api/clinic/settings/branches`,
      {
        name_ar: 'فرع التجمع الخامس التخصصي',
        address_ar: 'مجمع العيادات - شارع التسعين',
        phone: '01099998888',
        consultation_price: 600,
        followup_price: 150,
      },
      { headers: { Authorization: `Bearer ${doctorToken}` } }
    );
    newBranchId = branchRes.data.data.id;
    logSuccess(`Secondary branch created! Branch ID: #${newBranchId}`);

    const staffRes = await axios.post(
      `${BASE_URL}/api/clinic/settings/receptionists`,
      {
        clinic_id: newBranchId,
        name: 'مريم عادل',
        email: `reception.tagamoa.${uniqueSuffix}@clinic.com`,
        phone: '01234567890',
        password: 'StaffPassword123',
      },
      { headers: { Authorization: `Bearer ${doctorToken}` } }
    );
    logSuccess(`Receptionist account created! ID: #${staffRes.data.data.id}`);
  } catch (err) {
    logError("Failed to create branch or receptionist", err);
  }

  // -------------------------------------------------------------
  // STEP 8: Doctor Configures Dedicated Website & Specialty Template
  // -------------------------------------------------------------
  logStep(8, "Doctor Configures Hosted Website & Dental Template");
  try {
    const websiteRes = await axios.post(
      `${BASE_URL}/api/clinic/settings/website`,
      {
        website_mode: 1, // Platform hosted subdomain
        template_key: 'dental',
        content: {
          hero_title: 'مركز د. أحمد الشريف لطب وزراعة الأسنان',
          about_text: 'أحدث تقنيات تجميل وزراعة الأسنان بالليزر',
          primary_color: '#0D9488',
        },
      },
      { headers: { Authorization: `Bearer ${doctorToken}` } }
    );
    logSuccess("Website mode & dental branding saved successfully!");
  } catch (err) {
    logError("Failed to configure doctor website settings", err);
  }

  // -------------------------------------------------------------
  // STEP 9: Public Host Verification (Patient Website Check)
  // -------------------------------------------------------------
  logStep(9, "Public Verification of Doctor Landing Website by Subdomain");
  try {
    const publicTenant = await axios.get(`${BASE_URL}/api/public/tenant-by-host?subdomain=${testSubdomain}`);
    logSuccess("Public patient portal verified doctor website!", {
      name: publicTenant.data.data.name,
      subdomain: publicTenant.data.data.subdomain,
      specialty: publicTenant.data.data.specialty_key,
      clinics_count: publicTenant.data.data.clinics.length,
    });
  } catch (err) {
    logError("Public host resolution failed", err);
  }

  console.log(`\n${colors.green}${colors.bold}================================================================${colors.reset}`);
  console.log(`${colors.green}${colors.bold}🎉 ALL 9 STEPS IN DOCTOR ONBOARDING JOURNEY COMPLETED!${colors.reset}`);
  console.log(`${colors.green}${colors.bold}================================================================${colors.reset}\n`);
}

runDoctorOnboardingJourney().catch(err => {
  console.error("Fatal error:", err);
  process.exit(1);
});
