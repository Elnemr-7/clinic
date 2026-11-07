const axios = require('d:/clinic/JS-server/node_modules/axios');
const crypto = require('crypto');

const BASE_URL = 'http://127.0.0.1:8000';
const SHARED_SECRET = 'vertex_wa_secure_secret_key_2026';

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

function generateServerToken() {
  const timestamp = Math.floor(Date.now() / 1000);
  const sig = crypto.createHmac('sha256', SHARED_SECRET).update(`${timestamp}:${SHARED_SECRET}`).digest('hex');
  return `${timestamp}.${sig}`;
}

async function runWhatsAppMessagingJourney() {
  console.log(`${colors.bold}Starting Automated End-to-End WhatsApp Messaging & Inbound Activation Journey Test...${colors.reset}\n`);

  let referenceCode, appointmentId, tenantId, doctorToken, rxToken;
  const uniquePhone = `010${Date.now().toString().slice(-8)}`;

  // -------------------------------------------------------------
  // STEP 1: Doctor Login & Initial Wallet Check
  // -------------------------------------------------------------
  logStep(1, "Authenticating Doctor & Checking Messaging Wallet Balance");
  try {
    const docLogin = await axios.post(`${BASE_URL}/api/clinic/login`, {
      email: 'doctor@clinic.com',
      password: 'password123',
    });
    doctorToken = docLogin.data.data.token;
    tenantId = docLogin.data.data.tenant.id;
    const initialWaBalance = docLogin.data.data.tenant.whatsapp_balance;
    logSuccess(`Doctor authenticated! Tenant ID: #${tenantId}, Starting WhatsApp Balance: ${initialWaBalance}`);
  } catch (err) {
    logError("Doctor login failed", err);
  }

  // -------------------------------------------------------------
  // STEP 2: Online Public Patient Booking
  // -------------------------------------------------------------
  logStep(2, "Public Patient Books Online & Receives WhatsApp Deep-link");
  try {
    const bookRes = await axios.post(`${BASE_URL}/api/public/book-appointment`, {
      clinic_id: 1,
      shift_id: 1,
      date: new Date().toISOString().slice(0, 10),
      patient_name: 'إسلام فاروق',
      phone: uniquePhone,
    });

    referenceCode = bookRes.data.data.reference_code;
    const activationUrl = bookRes.data.data.whatsapp_activation_url;
    logSuccess(`Appointment registered! Ref: #${referenceCode}`, {
      queue_number: bookRes.data.data.queue_number,
      reference_code: referenceCode,
      whatsapp_activation_url: activationUrl,
    });
  } catch (err) {
    logError("Online booking failed", err);
  }

  // -------------------------------------------------------------
  // STEP 3: Simulated Inbound WhatsApp Opt-In Message from Patient
  // -------------------------------------------------------------
  logStep(3, "Patient Sends Inbound WhatsApp Opt-In: 'تفعيل حجز رقم CLN-XXXX'");
  try {
    const webhookToken = generateServerToken();
    const inboundPayload = {
      event: 'messages.upsert',
      data: {
        is_from_me: false,
        from: `2${uniquePhone}@s.whatsapp.net`,
        text: `تفعيل حجز رقم ${referenceCode}`,
        push_name: 'إسلام فاروق',
      },
    };

    const webhookRes = await axios.post(
      `${BASE_URL}/api/internal/whatsapp/webhook/${tenantId}`,
      inboundPayload,
      { headers: { 'X-Server-Token': webhookToken } }
    );

    logSuccess("Inbound WhatsApp message processed and opt-in activated!", webhookRes.data.data);
  } catch (err) {
    logError("WhatsApp webhook failed", err);
  }

  // -------------------------------------------------------------
  // STEP 4: Patient Tracks Turn Status Online
  // -------------------------------------------------------------
  logStep(4, "Patient Tracks Live Turn Number via Public Tracker URL");
  try {
    const trackRes = await axios.get(`${BASE_URL}/api/public/live-queue/${referenceCode}`);
    appointmentId = trackRes.data.data.appointment.id;
    const isOptedIn = trackRes.data.data.appointment.whatsapp_opted_in;

    logSuccess(`Live tracking status confirmed! (Opt-in: ${isOptedIn})`, {
      queue_number: trackRes.data.data.appointment.queue_number,
      current_in_room: trackRes.data.data.current_in_room_queue || 'None',
      remaining_ahead: trackRes.data.data.remaining_ahead,
      whatsapp_opted_in: isOptedIn,
    });
  } catch (err) {
    logError("Failed to fetch live queue status", err);
  }

  // -------------------------------------------------------------
  // STEP 5: Doctor Examines Patient & Sends Rx via WhatsApp
  // -------------------------------------------------------------
  logStep(5, "Doctor Saves Prescription & Triggers WhatsApp Delivery");
  try {
    const rxRes = await axios.post(
      `${BASE_URL}/api/clinic/doctor/prescription/${appointmentId}`,
      {
        diagnosis: 'التهاب الأذن الوسطى الحاد',
        prescribed_drugs: [
          { drug_name: 'Otrivin Spray', dosage: 'بخة بكل فتحة 3 مرات', duration: '5 أيام' },
          { drug_name: 'Ciprocort Drops', dosage: '3 نقط مرتين يومياً', duration: '7 أيام' },
        ],
        send_whatsapp: true,
      },
      { headers: { Authorization: `Bearer ${doctorToken}` } }
    );

    rxToken = rxRes.data.data.consultation.pdf_short_token;
    logSuccess(`Prescription saved and WhatsApp dispatch queued! Rx Token: ${rxToken}`);
  } catch (err) {
    logError("Failed to save prescription with WhatsApp send", err);
  }

  // -------------------------------------------------------------
  // STEP 6: Patient Downloads Prescription PDF via Short URL
  // -------------------------------------------------------------
  logStep(6, "Patient Accesses Prescription PDF via Public Token URL");
  try {
    const pdfRes = await axios.get(`${BASE_URL}/api/public/rx/${rxToken}`, {
      responseType: 'arraybuffer',
    });
    logSuccess(`Prescription PDF served successfully! Size: ${pdfRes.data.length} bytes`);
  } catch (err) {
    logError("Failed to download prescription PDF", err);
  }

  console.log(`\n${colors.green}${colors.bold}================================================================${colors.reset}`);
  console.log(`${colors.green}${colors.bold}🎉 ALL 6 STEPS IN WHATSAPP MESSAGING JOURNEY COMPLETED!${colors.reset}`);
  console.log(`${colors.green}${colors.bold}================================================================${colors.reset}\n`);
}

runWhatsAppMessagingJourney().catch(err => {
  console.error("Fatal error:", err);
  process.exit(1);
});
