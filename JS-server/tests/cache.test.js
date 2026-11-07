const assert = require('assert');
const cache = require('../cache');

function runCacheTests() {
    console.log('🧪 Running JS-Server Cache Tests...');

    cache.flush();

    // Test 1: Set and get
    cache.set('key1', { foo: 'bar' }, 10);
    const val1 = cache.get('key1');
    assert.deepStrictEqual(val1, { foo: 'bar' });
    console.log('✔ Test 1 Passed: Set and Get working');

    // Test 2: Del
    cache.del('key1');
    assert.strictEqual(cache.get('key1'), null);
    console.log('✔ Test 2 Passed: Del working');

    // Test 3: Flush
    cache.set('a', 1, 10);
    cache.set('b', 2, 10);
    cache.flush();
    assert.strictEqual(cache.get('a'), null);
    assert.strictEqual(cache.get('b'), null);
    console.log('✔ Test 3 Passed: Flush working');

    // Test 4: Expiration
    cache.set('quick', 'expire', 0.01); // 10ms
    setTimeout(() => {
        assert.strictEqual(cache.get('quick'), null);
        console.log('✔ Test 4 Passed: Cache TTL expiration working');
        console.log('\n🎉 ALL Cache Tests PASSED Successfully!\n');
    }, 20);
}

runCacheTests();
