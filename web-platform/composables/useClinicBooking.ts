import axios from 'axios';

export const useClinicBooking = () => {
  const config = useRuntimeConfig();
  const apiBase = config.public.apiBase || 'http://localhost:8000/api';

  const client = axios.create({
    baseURL: apiBase,
    headers: {
      'Accept': 'application/json',
      'Content-Type': 'application/json',
    },
  });

  const resolveTenant = async (host = null, subdomain = null) => {
    const params = {};
    if (subdomain) params.subdomain = subdomain;
    if (host) params.host = host;

    const res = await client.get('/public/tenant-by-host', { params });
    return res.data.data;
  };

  const fetchPlans = async () => {
    const res = await client.get('/public/plans');
    return res.data.data;
  };

  const submitLead = async (leadData) => {
    // Correction 6: Public Lead endpoint
    const res = await client.post('/public/leads', leadData);
    return res.data;
  };

  const checkFollowupEligibility = async (phone, clinicId) => {
    const res = await client.get('/public/check-followup', {
      params: { phone, clinic_id: clinicId },
    });
    return res.data.data;
  };

  const submitBooking = async (bookingData) => {
    const res = await client.post('/public/book-appointment', bookingData);
    return res.data;
  };

  const fetchLiveQueueStatus = async (referenceCode) => {
    const res = await client.get(`/public/live-queue/${referenceCode}`);
    return res.data.data;
  };

  return {
    client,
    resolveTenant,
    fetchPlans,
    submitLead,
    checkFollowupEligibility,
    submitBooking,
    fetchLiveQueueStatus,
  };
};
