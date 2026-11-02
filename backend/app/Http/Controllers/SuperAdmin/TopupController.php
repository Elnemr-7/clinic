<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\WalletTopup;
use App\Services\MessagingWalletService;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TopupController extends Controller
{
    use ApiResponseTrait;

    protected MessagingWalletService $walletService;

    public function __construct(MessagingWalletService $walletService)
    {
        $this->walletService = $walletService;
    }

    public function index(): JsonResponse
    {
        $topups = WalletTopup::with(['tenant', 'approvedBy'])->latest()->paginate(20);
        return $this->successResponse($topups);
    }

    public function approve(int $id, Request $request): JsonResponse
    {
        $topup = WalletTopup::findOrFail($id);

        if ($topup->status === 'paid') {
            return $this->errorResponse('هذا الطلب تم اعتماده مسبقاً');
        }

        $tenant = $topup->tenant;
        $adminId = $request->user()->id;

        if ($topup->package_type === 'whatsapp') {
            $tenant->increment('whatsapp_balance', $topup->quantity_added);
        } elseif ($topup->package_type === 'sms') {
            $tenant->increment('sms_balance', $topup->quantity_added);
        }

        $topup->update([
            'status' => 'paid',
            'approved_by_admin_id' => $adminId,
        ]);

        return $this->successResponse($topup, 'تم اعتماد الطلب وشحن الرصيد بنجاح');
    }

    public function reject(int $id): JsonResponse
    {
        $topup = WalletTopup::findOrFail($id);
        $topup->update(['status' => 'rejected']);
        return $this->successResponse($topup, 'تم رفض الطلب');
    }
}
