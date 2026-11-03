export const useClinicBooking = () => {
  const config = useRuntimeConfig();
  const apiBase = config.public.apiBase || 'http://localhost:8000/api';

  const apiFetch = $fetch.create({
    baseURL: apiBase,
    headers: {
      'Accept': 'application/json',
    },
  });

  const resolveTenant = async (host: string | null = null, subdomain: string | null = null) => {
    const params: Record<string, string> = {};
    if (subdomain) params.subdomain = subdomain;
    if (host) params.host = host;

    const res: any = await apiFetch('/public/tenant-by-host', { params });
    return res.data;
  };

  const fetchPlans = async () => {
    const res: any = await apiFetch('/public/plans');
    return res.data;
  };

  const submitLead = async (leadData: any) => {
    const res: any = await apiFetch('/public/leads', {
      method: 'POST',
      body: leadData,
    });
    return res;
  };

  const checkFollowupEligibility = async (phone: string, clinicId: number | string) => {
    const res: any = await apiFetch('/public/check-followup', {
      params: { phone, clinic_id: clinicId },
    });
    return res.data;
  };

  const submitBooking = async (bookingData: any) => {
    const res: any = await apiFetch('/public/book-appointment', {
      method: 'POST',
      body: bookingData,
    });
    return res;
  };

  const fetchLiveQueueStatus = async (referenceCode: string) => {
    const res: any = await apiFetch(`/public/live-queue/${referenceCode}`);
    return res.data;
  };

  return {
    client: apiFetch,
    resolveTenant,
    fetchPlans,
    submitLead,
    checkFollowupEligibility,
    submitBooking,
    fetchLiveQueueStatus,
  };
};
