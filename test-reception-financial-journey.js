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

async function runReceptionFinancialJourney() {
  console.log(`${colors.bold}Starting Automated End-to-End Reception & Cashbox Day-Close Journey Test...${colors.reset}\n`);

  let receptionToken, doctorToken, clinicId, appointmentId, patientPhone;
  const uniqueSuffix = Date.now().toString().slice(-4);

  // -------------------------------------------------------------
  // STEP 1: Staff Authentication (Receptionist & Doctor)
  // -------------------------------------------------------------
  logStep(1, "Authenticating Receptionist & Doctor Accounts");
  try {
    const recLogin = await axios.post(`${BASE_URL}/api/clinic/login`, {
      email: 'reception1@clinic.com',
      password: 'password123',
    });
    receptionToken = recLogin.data.data.token;
    clinicId = recLogin.data.data.user.clinic_id || recLogin.data.data.clinic?.id || 1;
    logSuccess(`Receptionist logged in! Clinic ID: #${clinicId}`);

    const docLogin = await axios.post(`${BASE_URL}/api/clinic/login`, {
      email: 'doctor@clinic.com',
      password: 'password123',
    });
    doctorToken = docLogin.data.data.token;
    logSuccess("Doctor logged in successfully!");
  } catch (err) {
    logError("Staff login failed", err);
  }

  // -------------------------------------------------------------
  // STEP 2: Initial Cashbox Drawer Snapshot
  // -------------------------------------------------------------
  logStep(2, "Inspecting Starting Cashbox Drawer Balances");
  let startIncome = 0, startExpense = 0, startNet = 0;
  try {
    const startBox = await axios.get(`${BASE_URL}/api/clinic/reception/cashbox?clinic_id=${clinicId}`, {
      headers: { Authorization: `Bearer ${receptionToken}` },
    });
    startIncome = startBox.data.data.total_income;
    startExpense = startBox.data.data.total_expense;
    startNet = startBox.data.data.net_in_drawer;
    logSuccess(`Starting Drawer Balance: Net ${startNet} EGP (Income: ${startIncome}, Expense: ${startExpense})`);
  } catch (err) {
    logError("Failed to fetch initial cashbox state", err);
  }

  // -------------------------------------------------------------
  // STEP 3: Walk-In Patient Registration with Immediate Cash Payment
  // -------------------------------------------------------------
  logStep(3, "Walk-in Desk Booking with Immediate Cash Collection");
  patientPhone = `010${uniqueSuffix}5678`;
  let consultationPrice = 300;

  try {
    const bookRes = await axios.post(
      `${BASE_URL}/api/clinic/reception/quick-book`,
      {
        clinic_id: clinicId,
        name: `كمال الأحمدي ${uniqueSuffix}`,
        phone: patientPhone,
        gender: 'male',
        age: 44,
        mark_paid: true, // Cashbox income transaction
      },
      { headers: { Authorization: `Bearer ${receptionToken}` } }
    );

    appointmentId = bookRes.data.data.appointment.id;
    consultationPrice = Number(bookRes.data.data.appointment.consultation_fee || 300);
    logSuccess(`Appointment booked & marked PAID! ID: #${appointmentId}, Ref: ${bookRes.data.data.reference_code}`);
  } catch (err) {
    logError("Quick booking failed", err);
  }

  // -------------------------------------------------------------
  // STEP 4: Receptionist Records Patient Biological Vitals on Arrival
  // -------------------------------------------------------------
  logStep(4, "Recording Patient Biological Vitals on Desk Arrival");
  try {
    const vitalsRes = await axios.post(
      `${BASE_URL}/api/clinic/reception/vitals/${appointmentId}`,
      {
        vitals_data: {
          bp: '135/85',
          weight: 84,
          sugar: 125,
          temp: 37.1,
        },
      },
      { headers: { Authorization: `Bearer ${receptionToken}` } }
    );
    logSuccess("Patient vitals recorded successfully!", vitalsRes.data.data.vitals_data);
  } catch (err) {
    logError("Failed to save patient vitals", err);
  }

  // -------------------------------------------------------------
  // STEP 5: Doctor Workspace Adds Extra Clinical Procedure
  // -------------------------------------------------------------
  logStep(5, "Doctor Exam Room Adds Billed Clinical Procedure & Followup");
  const procedureFee = 250;
  try {
    const extraRes = await axios.post(
      `${BASE_URL}/api/clinic/doctor/services-and-followup/${appointmentId}`,
      {
        selected_services: [
          { service_name: 'تغيير على جرح وخياطة تجميلية', price: procedureFee },
        ],
        followup_date: new Date(Date.now() + 7 * 86400000).toISOString().slice(0, 10),
      },
      { headers: { Authorization: `Bearer ${doctorToken}` } }
    );
    logSuccess(`Extra procedure billed: ${procedureFee} EGP, Total: ${extraRes.data.data.appointment.total_amount} EGP`);
  } catch (err) {
    logError("Failed to bill extra procedure", err);
  }

  // -------------------------------------------------------------
  // STEP 6: Receptionist Collects Extra Procedure Fee into Drawer
  // -------------------------------------------------------------
  logStep(6, "Receptionist Collects Extra Services Fee into Cashbox");
  try {
    const collectRes = await axios.post(
      `${BASE_URL}/api/clinic/reception/collect-extra/${appointmentId}`,
      {},
      { headers: { Authorization: `Bearer ${receptionToken}` } }
    );
    logSuccess(`Extra services payment collected and deposited into cashbox!`);
  } catch (err) {
    logError("Failed to collect extra services payment", err);
  }

  // -------------------------------------------------------------
  // STEP 7: Receptionist Records Cash Expense from Drawer
  // -------------------------------------------------------------
  logStep(7, "Receptionist Logs Cash Out-of-Drawer Expense");
  const expenseAmount = 75.50;
  try {
    const expRes = await axios.post(
      `${BASE_URL}/api/clinic/reception/expense`,
      {
        clinic_id: clinicId,
        amount: expenseAmount,
        category: 'clinic_supplies',
        description_ar: 'شراء شاش وقطن معقم من الصيدلية',
      },
      { headers: { Authorization: `Bearer ${receptionToken}` } }
    );
    logSuccess(`Expense recorded in drawer: -${expenseAmount} EGP`, expRes.data.data);
  } catch (err) {
    logError("Failed to record expense", err);
  }

  // -------------------------------------------------------------
  // STEP 8: End-of-Day Drawer Reconciliation Audit
  // -------------------------------------------------------------
  logStep(8, "Verifying End-of-Day Cashbox Drawer Mathematical Formula");
  try {
    const endBox = await axios.get(`${BASE_URL}/api/clinic/reception/cashbox?clinic_id=${clinicId}`, {
      headers: { Authorization: `Bearer ${receptionToken}` },
    });

    const endIncome = endBox.data.data.total_income;
    const endExpense = endBox.data.data.total_expense;
    const endNet = endBox.data.data.net_in_drawer;

    console.log(`Reconciled End-of-Day Cashbox:`);
    console.log(`  - Total Income:   ${endIncome} EGP`);
    console.log(`  - Total Expenses: ${endExpense} EGP`);
    console.log(`  - Net in Drawer:  ${endNet} EGP`);

    // Verify mathematical integrity: net === income - expense
    if (Math.abs(endNet - (endIncome - endExpense)) < 0.01) {
      logSuccess("Mathematical integrity verified: Net in Drawer === Total Income - Total Expenses!");
    } else {
      logError("Cashbox mathematical mismatch detected!");
    }
  } catch (err) {
    logError("Failed to audit final cashbox state", err);
  }

  console.log(`\n${colors.green}${colors.bold}================================================================${colors.reset}`);
  console.log(`${colors.green}${colors.bold}🎉 ALL 8 STEPS IN RECEPTION & CASHBOX JOURNEY COMPLETED!${colors.reset}`);
  console.log(`${colors.green}${colors.bold}================================================================${colors.reset}\n`);
}

runReceptionFinancialJourney().catch(err => {
  console.error("Fatal error:", err);
  process.exit(1);
});
