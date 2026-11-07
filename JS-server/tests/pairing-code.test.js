const { describe, it } = require('node:test');
const assert = require('assert');

describe('JS-Server Phone Number Formatting for Pairing Code', () => {
    it('normalizes local Egyptian mobile 01xxxxxxxxx to international 201xxxxxxxxx', () => {
        let phoneNumber = '01012345678'.replace(/\D/g, '');
        if (phoneNumber.startsWith('01') && phoneNumber.length === 11) {
            phoneNumber = '2' + phoneNumber;
        }
        assert.strictEqual(phoneNumber, '201012345678');
    });

    it('keeps already international numbers with country code unchanged', () => {
        let phoneNumber = '201123456789'.replace(/\D/g, '');
        if (phoneNumber.startsWith('01') && phoneNumber.length === 11) {
            phoneNumber = '2' + phoneNumber;
        }
        assert.strictEqual(phoneNumber, '201123456789');
    });

    it('formats 8-digit pairing code with dash separator for readability', () => {
        const rawCode = '12345678';
        const formatted = rawCode.match(/.{1,4}/g)?.join(' - ');
        assert.strictEqual(formatted, '1234 - 5678');
    });
});
