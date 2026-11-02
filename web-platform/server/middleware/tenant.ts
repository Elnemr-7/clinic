import { defineEventHandler, getRequestHeader } from 'h3';

export default defineEventHandler(async (event) => {
  // Capture host header for multi-tenant doctor subdomain or custom domain resolution
  const host = getRequestHeader(event, 'host') || '';
  const cleanHost = host.split(':')[0].toLowerCase();

  // Attach to event context for SSR consumers
  event.context.tenantHost = cleanHost;
});
