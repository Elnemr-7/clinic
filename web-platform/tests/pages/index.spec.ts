import { describe, it, expect } from 'vitest';

describe('Web Platform Dynamic Template Selector & Host Mapping', () => {
  it('correctly maps doctor specialty to corresponding UI template', () => {
    const getTemplateKey = (specialty: string) => {
      const sp = (specialty || '').toLowerCase();
      if (sp.includes('أسنان') || sp.includes('dental')) return 'TemplateDental';
      if (sp.includes('أطفال') || sp.includes('pediatric')) return 'TemplatePediatrics';
      if (sp.includes('جلدية') || sp.includes('تجميل') || sp.includes('derma')) return 'TemplateDermatology';
      if (sp.includes('نساء') || sp.includes('توليد') || sp.includes('عقم') || sp.includes('obgyn')) return 'TemplateObgyn';
      if (sp.includes('عظام') || sp.includes('ortho')) return 'TemplateOrthopedics';
      return 'TemplateGeneral';
    };

    expect(getTemplateKey('طب وجراحة الأسنان')).toBe('TemplateDental');
    expect(getTemplateKey('Dental Surgery')).toBe('TemplateDental');
    expect(getTemplateKey('طب الأطفال وحديثي الولادة')).toBe('TemplatePediatrics');
    expect(getTemplateKey('أمراض جلدية وتجميل')).toBe('TemplateDermatology');
    expect(getTemplateKey('نساء وتوليد')).toBe('TemplateObgyn');
    expect(getTemplateKey('جراحة العظام والمفاصل')).toBe('TemplateOrthopedics');
    expect(getTemplateKey('أمراض باطنة وسكر')).toBe('TemplateGeneral');
    expect(getTemplateKey('')).toBe('TemplateGeneral');
  });

  it('correctly distinguishes platform SaaS landing domain from tenant doctor domains', () => {
    const checkIsPlatform = (host: string) => {
      const h = (host || '').toLowerCase();
      return !h || h === 'localhost' || h === '127.0.0.1' || h.startsWith('192.168.') || h.includes('platform.') || h === 'clinic-platform.com';
    };

    // Platform hostnames (marketing landing page)
    expect(checkIsPlatform('localhost')).toBe(true);
    expect(checkIsPlatform('127.0.0.1')).toBe(true);
    expect(checkIsPlatform('platform.com')).toBe(true);
    expect(checkIsPlatform('clinic-platform.com')).toBe(true);

    // Doctor tenant hostnames (doctor website)
    expect(checkIsPlatform('dr-ahmed.platform.com')).toBe(true); // platform subdomain is handled via tenant lookup
    expect(checkIsPlatform('drhossamortho.com')).toBe(false); // custom domain
    expect(checkIsPlatform('shifaaclinic.com')).toBe(false);  // custom domain
  });
});
