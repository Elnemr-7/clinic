const assert = require('assert');
const crypto = require('crypto');
const http = require('http');
const { io } = require('socket.io-client');

process.env.NODE_ENV = 'test';
process.env.PORT = '3299';
const TEST_SECRET = 'unit_test_socket_secret_key_2026';
process.env.SHARED_SECRET = TEST_SECRET;

const { server, generateServerToken } = require('../server');

function makeRequest(method, endpoint, body = null, token = null) {
    return new Promise((resolve, reject) => {
        const payload = body ? JSON.stringify(body) : null;
        const req = http.request({
            hostname: '127.0.0.1',
            port: 3299,
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

async function runSocketTests() {
    console.log('🧪 Running JS-Server Socket.io Room & Broadcast Test Suite...');

    if (!server.listening) {
        await new Promise(r => server.listen(3299, r));
    }
    const socketUrl = 'http://127.0.0.1:3299';

    try {
        const clientSocket = io(socketUrl, { transports: ['websocket'] });

        await new Promise((resolve) => clientSocket.on('connect', resolve));
        console.log('✔ Socket client connected to server');

        // Test 1: join_clinic with INVALID HMAC token is rejected with error
        const unauthPromise = new Promise((resolve) => {
            clientSocket.once('error', (err) => {
                assert.ok(err.message.includes('Unauthorized'), 'Expected unauthorized error for invalid token');
                resolve();
            });
            clientSocket.emit('join_clinic', { clinicId: 101, token: 'invalid_hmac_token' });
        });
        await unauthPromise;
        console.log('✔ Test 1 Passed: Unauthorized join_clinic rejected');

        // Test 2: join_clinic with VALID HMAC token is accepted
        const expectedToken = crypto.createHmac('sha256', TEST_SECRET).update('clinic_101').digest('hex');
        const authPromise = new Promise((resolve) => {
            clientSocket.once('joined_clinic', (res) => {
                assert.strictEqual(res.success, true);
                assert.strictEqual(res.clinicId, 101);
                resolve();
            });
            clientSocket.emit('join_clinic', { clinicId: 101, token: expectedToken });
        });
        await authPromise;
        console.log('✔ Test 2 Passed: Authorized join_clinic joined successfully');

        // Test 3: Public join_tv and join_track join without error
        clientSocket.emit('join_tv', { tvCode: 'TV1001' });
        clientSocket.emit('join_track', { referenceCode: 'CLN-TEST9' });
        console.log('✔ Test 3 Passed: Public tv and track rooms joined');

        // Test 4: Internal queue broadcast endpoint delivers event to rooms
        const authToken = generateServerToken();
        const broadcastPromise = new Promise((resolve) => {
            clientSocket.once('queue:updated', (payload) => {
                assert.strictEqual(payload.test, true);
                resolve();
            });
        });

        const broadcastRes = await makeRequest('POST', '/api/internal/broadcast-queue', {
            clinic_id: 101,
            event: 'queue:updated',
            payload: { test: true },
        }, authToken);

        assert.strictEqual(broadcastRes.status, 200);
        await broadcastPromise;
        console.log('✔ Test 4 Passed: Queue broadcast delivered to clinic room');

        clientSocket.disconnect();
        console.log('\n🎉 ALL Socket.io Tests PASSED Successfully!\n');
    } finally {
        server.close();
    }
}

runSocketTests().catch(err => {
    console.error('✘ Socket test suite failed:', err);
    process.exit(1);
});
