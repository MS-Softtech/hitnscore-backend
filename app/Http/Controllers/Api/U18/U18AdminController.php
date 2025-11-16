<?php

namespace App\Http\Controllers\Api\U18;

use App\Http\Controllers\Controller;
use App\Http\Service\U18\U18Service;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Class U18AdminController
 *
 * Admin endpoints to approve/reject Under-18 requests.
 */
class U18AdminController extends Controller
{
    /** @var U18Service */
    private U18Service $service;

    /**
     * U18AdminController constructor.
     *
     * @param U18Service $service
     */
    public function __construct(U18Service $service)
    {
        $this->service = $service;
    }

    /**
     * List pending Under-18 approvals.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function pending(Request $request): JsonResponse
    {
        return response()->json($this->service->pending($request->all()));
    }

    /**
     * Approve one Under-18 request by approval id.
     *
     * @param int $id
     * @return JsonResponse
     */
    public function approve(int $id): JsonResponse
    {
        $admin = Auth::guard('users')->user();
        return response()->json($this->service->approve($id, $admin));
    }

    /**
     * Reject one Under-18 request by approval id.
     * Body: {notes?: string}
     *
     * @param int $id
     * @param Request $request
     * @return JsonResponse
     */
    public function reject(int $id, Request $request): JsonResponse
    {
        $admin = Auth::guard('users')->user();
        $notes = (string)($request->input('notes', ''));
        return response()->json($this->service->reject($id, $admin, $notes));
    }
}
