const assert = require('assert');
const crypto = require('crypto');
const http = require('http');
const path = require('path');
const os = require('os');
const fs = require('fs');

process.env.NODE_ENV = 'test';
process.env.AUTO_BOOT = 'false';
process.env.PORT = '3198';
const TEST_SECRET = 'unit_test_security_secret_key_2026';
process.env.SHARED_SECRET = TEST_SECRET;

const TEST_AUTH_DIR = path.join(os.tmpdir(), `sec_test_${Date.now()}`);
if (!fs.existsSync(TEST_AUTH_DIR)) fs.mkdirSync(TEST_AUTH_DIR, { recursive: true });
process.env.AUTH_BASE_DIR = TEST_AUTH_DIR;

const { app, server, verifyServerToken, generateServerToken } = require('../server');

function makeRequest(method, endpoint, body = null, token = null) {
    return new Promise((resolve, reject) => {
        const payload = body ? JSON.stringify(body) : null;
        const req = http.request({
            hostname: '127.0.0.1',
            port: 3198,
            path: endpoint,
            method: method,
            headers: {
                'Content-Type': 'application/json',
                ...(token ? { 'X-Server-Token': token } : {}),
                ...(payload ? { 'Content-Length': Buffer.byteLength(payload) } : {})
            }
        }, (res) => {
            let data = '';
            res.on('data', chunk => { data += chunk; });
            res.on('end', () => {
                let parsed = data;
                try { parsed = JSON.parse(data); } catch (e) {}
                resolve({ status: res.statusCode, data: parsed });
            });
        });
        req.on('error', reject);
        if (payload) req.write(payload);
        req.end();
    });
}

async function runSecurityTests() {
    console.log('🧪 Running JS-Server Security & Token Test Suite...');

    await new Promise(r => server.listen(3198, r));

    try {
        // Test 1: Unauthenticated request to protected endpoint must return 401
        const unauth = await makeRequest('GET', '/api/sessions');
        assert.strictEqual(unauth.status, 401, 'Unauthenticated request must be rejected with 401');
        console.log('✔ Test 1 Passed: Unauthenticated requests rejected');

        // Test 2: Expired or tampered token must return 401
        const oldTimestamp = Math.floor(Date.now() / 1000) - 350;
        const expiredSignature = crypto.createHmac('sha256', TEST_SECRET).update(`${oldTimestamp}:${TEST_SECRET}`).digest('hex');
        const expiredToken = `${oldTimestamp}.${expiredSignature}`;

        const expiredReq = await makeRequest('GET', '/api/sessions', null, expiredToken);
        assert.strictEqual(expiredReq.status, 401, 'Expired token (> 300s) must be rejected with 401');
        console.log('✔ Test 2 Passed: Expired token rejected');

        // Test 3: Valid fresh token succeeds
        const validToken = generateServerToken();
        const validReq = await makeRequest('GET', '/api/sessions', null, validToken);
        assert.strictEqual(validReq.status, 200, 'Valid token must be accepted');
        console.log('✔ Test 3 Passed: Valid 5-minute token authenticated');

        // Test 4: Verify HMAC token generation & verification utility directly
        assert.strictEqual(verifyServerToken(validToken), true, 'Valid token must pass verification');
        assert.strictEqual(verifyServerToken('invalid.token'), false, 'Malformed token must fail');
        assert.strictEqual(verifyServerToken(null), false, 'Null token must fail');
        console.log('✔ Test 4 Passed: Server token verification logic accurate');

        console.log('\n🎉 ALL JS-Server Security Tests PASSED Successfully!\n');
    } finally {
        server.close();
        if (fs.existsSync(TEST_AUTH_DIR)) fs.rmSync(TEST_AUTH_DIR, { recursive: true, force: true });
    }
}

runSecurityTests().catch(err => {
    console.error('✘ Test suite failed:', err);
    process.exit(1);
});
