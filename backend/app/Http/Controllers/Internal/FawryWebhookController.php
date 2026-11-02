<?php

namespace App\Http\Controllers\Internal;

use App\Http\Controllers\Controller;
use App\Services\FawryService;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FawryWebhookController extends Controller
{
    use ApiResponseTrait;

    protected FawryService $fawryService;

    public function __construct(FawryService $fawryService)
    {
        $this->fawryService = $fawryService;
    }

    public function callback(Request $request): JsonResponse
    {
        if (!$this->fawryService->verifySignature($request->all())) {
            return $this->errorResponse('Invalid signature', null, 400);
        }

        $this->fawryService->processCallback($request->all());

        return $this->successResponse(null, 'Payment processed successfully');
    }
}
