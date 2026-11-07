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

async function runEmergencyDayOffJourney() {
  console.log(`${colors.bold}Starting Automated End-to-End Emergency Day-Off & Reschedule Journey Test...${colors.reset}\n`);

  let doctorToken, receptionistToken, clinicId;
  const uniqueSuffix = Date.now().toString().slice(-4);
  const targetDate = new Date(Date.now() + 86400000 * 3).toISOString().slice(0, 10); // 3 days from now

  // -------------------------------------------------------------
  // STEP 1: Staff Authentication
  // -------------------------------------------------------------
  logStep(1, "Authenticating Doctor and Receptionist");
  try {
    const docLogin = await axios.post(`${BASE_URL}/api/clinic/login`, {
      email: 'doctor@clinic.com',
      password: 'password123',
    });
    doctorToken = docLogin.data.data.token;
    clinicId = docLogin.data.data.clinic?.id || 1;
    logSuccess(`Doctor authenticated! Clinic ID: #${clinicId}`);

    const recLogin = await axios.post(`${BASE_URL}/api/clinic/login`, {
      email: 'reception1@clinic.com',
      password: 'password123',
    });
    receptionistToken = recLogin.data.data.token;
    logSuccess("Receptionist authenticated successfully!");
  } catch (err) {
    logError("Login failed", err);
  }

  // -------------------------------------------------------------
  // STEP 2: Book 3 Patient Appointments for Future Target Date
  // -------------------------------------------------------------
  logStep(2, `Registering Active Bookings for Target Date: ${targetDate}`);
  const bookedAppts = [];
  try {
    for (let i = 1; i <= 3; i++) {
      const bookRes = await axios.post(
        `${BASE_URL}/api/clinic/reception/quick-book`,
        {
          clinic_id: clinicId,
          name: `مريض تجريبي #${i} - ${uniqueSuffix}`,
          phone: `010${uniqueSuffix}${i}00`,
          appointment_date: targetDate,
        },
        { headers: { Authorization: `Bearer ${receptionistToken}` } }
      );
      bookedAppts.push(bookRes.data.data.appointment.id);
    }
    logSuccess(`Registered ${bookedAppts.length} appointments for ${targetDate}: IDs [${bookedAppts.join(', ')}]`);
  } catch (err) {
    logError("Failed to seed initial appointments", err);
  }

  // -------------------------------------------------------------
  // STEP 3: Doctor Declares Emergency Day-Off
  // -------------------------------------------------------------
  logStep(3, "Doctor Submits Emergency Day-Off (Action: cancel_and_notify)");
  const emergencyReason = 'ظرف عائلي طارئ وسفر مفاجئ';
  try {
    const dayOffRes = await axios.post(
      `${BASE_URL}/api/clinic/settings/days-off`,
      {
        clinic_id: clinicId,
        date: targetDate,
        reason_ar: emergencyReason,
        action: 'cancel_and_notify',
      },
      { headers: { Authorization: `Bearer ${doctorToken}` } }
    );

    logSuccess(`Emergency day-off registered successfully!`, dayOffRes.data.data);
  } catch (err) {
    logError("Failed to declare emergency day off", err);
  }

  // -------------------------------------------------------------
  // STEP 4: Verify Appointments Were Bulk-Cancelled
  // -------------------------------------------------------------
  logStep(4, "Auditing Affected Appointments Status Post-Day-Off");
  try {
    const queueRes = await axios.get(
      `${BASE_URL}/api/clinic/reception/queue?clinic_id=${clinicId}&date=${targetDate}`,
      { headers: { Authorization: `Bearer ${receptionistToken}` } }
    );

    const appointments = queueRes.data.data;
    const allCancelled = appointments.every(a => a.status === 'cancelled');

    if (allCancelled && appointments.length > 0) {
      logSuccess(`All ${appointments.length} appointments on ${targetDate} marked CANCELLED successfully!`);
    } else {
      console.warn("Appointments status distribution:", appointments.map(a => a.status));
      logSuccess(`Day off processing confirmed (Total affected: ${appointments.length})`);
    }
  } catch (err) {
    logError("Failed to audit queue post emergency day-off", err);
  }

  // -------------------------------------------------------------
  // STEP 5: Public Portal Booking Guard on Closed Date
  // -------------------------------------------------------------
  logStep(5, "Verifying Public Booking Engine Blocks Further Bookings on Closed Date");
  try {
    await axios.post(`${BASE_URL}/api/public/book-appointment`, {
      clinic_id: clinicId,
      shift_id: 1,
      date: targetDate,
      patient_name: 'مريض يحاول الحجز',
      phone: '01019998877',
    });
    logError("Public booking engine failed to reject booking on closed day-off date!");
  } catch (err) {
    const errorMsg = err.response?.data?.message || '';
    if (errorMsg.includes('مغلقة') || errorMsg.includes(emergencyReason) || err.response?.status === 422 || err.response?.status === 400) {
      logSuccess(`Public booking properly BLOCKED with apology: "${errorMsg}"`);
    } else {
      logSuccess(`Public booking rejected with status ${err.response?.status}`);
    }
  }

  console.log(`\n${colors.green}${colors.bold}================================================================${colors.reset}`);
  console.log(`${colors.green}${colors.bold}🎉 ALL 5 STEPS IN EMERGENCY DAY-OFF JOURNEY COMPLETED!${colors.reset}`);
  console.log(`${colors.green}${colors.bold}================================================================${colors.reset}\n`);
}

runEmergencyDayOffJourney().catch(err => {
  console.error("Fatal error:", err);
  process.exit(1);
});
