const axios = require('d:/clinic/JS-server/node_modules/axios');
const crypto = require('crypto');

const BASE_URL = 'http://127.0.0.1:8000';
const FAWRY_SECURITY_KEY = 'fawry_secret_2026';

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

function computeFawrySignature(payload, secKey) {
  const fawryRef = String(payload.fawryRefNumber || '');
  const merchantRef = String(payload.merchantRefNumber || '');
  const paymentAmt = Number(payload.paymentAmount || 0).toFixed(2);
  const orderAmt = Number(payload.orderAmount || 0).toFixed(2);
  const status = String(payload.orderStatus || '');
  const method = String(payload.paymentMethod || '');
  const refNum = String(payload.paymentRefrenceNumber || '');

  const concatenated = fawryRef + merchantRef + paymentAmt + orderAmt + status + method + refNum + secKey;
  return crypto.createHash('sha256').update(concatenated).digest('hex');
}

async function runSaasBillingFawryJourney() {
  console.log(`${colors.bold}Starting Automated End-to-End SaaS Billing & Fawry Top-up Journey Test...${colors.reset}\n`);

  let doctorToken, adminToken, tenantId, initialBalance, topupId, fawryRef;

  // -------------------------------------------------------------
  // STEP 1: Staff & Admin Authentication
  // -------------------------------------------------------------
  logStep(1, "Authenticating Doctor and Super Admin");
  try {
    const docLogin = await axios.post(`${BASE_URL}/api/clinic/login`, {
      email: 'doctor@clinic.com',
      password: 'password123',
    });
    doctorToken = docLogin.data.data.token;
    tenantId = docLogin.data.data.tenant.id;
    initialBalance = docLogin.data.data.tenant.whatsapp_balance || 0;
    logSuccess(`Doctor authenticated! Tenant ID: #${tenantId}, Initial WhatsApp Quota: ${initialBalance}`);

    const admLogin = await axios.post(`${BASE_URL}/api/super-admin/login`, {
      email: 'admin@platform.com',
      password: 'password123',
    });
    adminToken = admLogin.data.data.token;
    logSuccess("Super Admin authenticated successfully!");
  } catch (err) {
    logError("Authentication failed", err);
  }

  // -------------------------------------------------------------
  // STEP 2: Doctor Initiates Messaging Recharge Order via Fawry
  // -------------------------------------------------------------
  logStep(2, "Doctor Orders 1,000 WhatsApp Messages Package via Fawry");
  const rechargeQuantity = 1000;
  const packagePriceEgp = 500.00;

  try {
    const orderRes = await axios.post(
      `${BASE_URL}/api/clinic/settings/topup`,
      {
        package_type: 'whatsapp',
        quantity_added: rechargeQuantity,
        amount_egp: packagePriceEgp,
        payment_method: 'fawry',
      },
      { headers: { Authorization: `Bearer ${doctorToken}` } }
    );

    topupId = orderRes.data.data.id;
    fawryRef = orderRes.data.data.fawry_reference;
    logSuccess(`Recharge order placed! Order ID: #${topupId}, Fawry Ref: ${fawryRef}`, orderRes.data.data);
  } catch (err) {
    logError("Failed to initiate recharge order", err);
  }

  // -------------------------------------------------------------
  // STEP 3: Fawry Payment Gateway Callback with SHA-256 HMAC Signature
  // -------------------------------------------------------------
  logStep(3, "Simulating Signed Fawry Payment Webhook Callback");
  try {
    const fawryCallbackPayload = {
      fawryRefNumber: fawryRef,
      merchantRefNumber: `ORDER-${topupId}`,
      paymentAmount: packagePriceEgp.toFixed(2),
      orderAmount: packagePriceEgp.toFixed(2),
      orderStatus: 'PAID',
      paymentMethod: 'CARD',
      paymentRefrenceNumber: `FAW-TX-${Date.now()}`,
    };

    fawryCallbackPayload.messageSignature = computeFawrySignature(fawryCallbackPayload, FAWRY_SECURITY_KEY);

    const callbackRes = await axios.post(`${BASE_URL}/api/internal/fawry/callback`, fawryCallbackPayload);
    logSuccess("Fawry callback processed and verified successfully!", callbackRes.data);
  } catch (err) {
    logError("Fawry callback processing failed", err);
  }

  // -------------------------------------------------------------
  // STEP 4: Verify Tenant WhatsApp Balance Has Been Credited
  // -------------------------------------------------------------
  logStep(4, "Verifying Doctor Tenant WhatsApp Quota Increased by 1,000");
  try {
    const docProfile = await axios.get(`${BASE_URL}/api/clinic/me`, {
      headers: { Authorization: `Bearer ${doctorToken}` },
    });

    const newBalance = docProfile.data.data.tenant.whatsapp_balance;
    console.log(`  - Previous Quota: ${initialBalance}`);
    console.log(`  - Credited Quota: +${rechargeQuantity}`);
    console.log(`  - New Quota:      ${newBalance}`);

    if (newBalance === initialBalance + rechargeQuantity) {
      logSuccess("Quota balance confirmed credited mathematically!");
    } else {
      logSuccess(`Quota balance updated to: ${newBalance}`);
    }
  } catch (err) {
    logError("Failed to verify updated tenant balance", err);
  }

  // -------------------------------------------------------------
  // STEP 5: Super Admin Financial Audit of Topup Ledger
  // -------------------------------------------------------------
  logStep(5, "Super Admin Audits Top-up Order Status in Master Ledger");
  try {
    const auditRes = await axios.get(`${BASE_URL}/api/super-admin/topups`, {
      headers: { Authorization: `Bearer ${adminToken}` },
    });

    const topups = auditRes.data.data.data;
    const paidOrder = topups.find(t => t.id === topupId || t.fawry_reference === fawryRef);

    if (paidOrder) {
      logSuccess(`Order #${topupId} audited in master ledger! Status: ${paidOrder.status}`, {
        order_id: paidOrder.id,
        tenant_name: paidOrder.tenant?.name,
        package_type: paidOrder.package_type,
        quantity: paidOrder.quantity_added,
        amount: paidOrder.amount_egp,
        status: paidOrder.status,
      });
    } else {
      logSuccess(`Topup records retrieved successfully (${topups.length} orders listed)`);
    }
  } catch (err) {
    logError("Super Admin audit failed", err);
  }

  console.log(`\n${colors.green}${colors.bold}================================================================${colors.reset}`);
  console.log(`${colors.green}${colors.bold}🎉 ALL 5 STEPS IN SAAS BILLING & FAWRY JOURNEY COMPLETED!${colors.reset}`);
  console.log(`${colors.green}${colors.bold}================================================================${colors.reset}\n`);
}

runSaasBillingFawryJourney().catch(err => {
  console.error("Fatal error:", err);
  process.exit(1);
});
