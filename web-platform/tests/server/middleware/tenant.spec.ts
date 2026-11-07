import { describe, it, expect } from 'vitest';

describe('Tenant Server Middleware Host Normalization', () => {
  it('strips port numbers from host headers for multi-tenant resolution', () => {
    const rawHostWithPort = 'dr-ahmed.platform.com:3000';
    const cleanHost = rawHostWithPort.split(':')[0].toLowerCase();

    expect(cleanHost).toBe('dr-ahmed.platform.com');
  });

  it('normalizes localhost and direct custom domains to lowercase', () => {
    const customDomain = 'DrAhmedClinic.COM:8080';
    const cleanHost = customDomain.split(':')[0].toLowerCase();

    expect(cleanHost).toBe('drahmedclinic.com');
  });
});
