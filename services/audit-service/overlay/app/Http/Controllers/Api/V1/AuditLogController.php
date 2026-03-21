<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\AuditLogResource;
use App\Services\AuditLogService;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class AuditLogController extends Controller
{
    public function __construct(private readonly AuditLogService $auditLogs)
    {
    }

    public function index(): AnonymousResourceCollection
    {
        return AuditLogResource::collection($this->auditLogs->paginate());
    }
}
