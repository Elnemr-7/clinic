<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\Clinic;
use App\Models\DoctorLead;
use App\Models\Tenant;
use App\Models\WalletTopup;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;

class DashboardController extends Controller
{
    use ApiResponseTrait;

    public function stats(): JsonResponse
    {
        $totalDoctors = Tenant::count();
        $activeClinics = Clinic::where('is_active', true)->count();
        $bookingsToday = Appointment::whereDate('appointment_date', today())->count();

        $totalWhatsappUsed = Tenant::sum('whatsapp_used_this_month');
        $totalSmsUsed = Tenant::sum('sms_used_this_month');

        $pendingLeads = DoctorLead::where('status', 'new')->count();
        $monthlyRevenue = WalletTopup::where('status', 'paid')
            ->whereMonth('created_at', now()->month)
            ->sum('amount_egp');

        return $this->successResponse([
            'total_doctors' => $totalDoctors,
            'active_clinics' => $activeClinics,
            'bookings_today' => $bookingsToday,
            'whatsapp_used_this_month' => $totalWhatsappUsed,
            'sms_used_this_month' => $totalSmsUsed,
            'pending_leads' => $pendingLeads,
            'monthly_revenue_egp' => (float)$monthlyRevenue,
        ]);
    }
}
