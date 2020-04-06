<?php

namespace App\Http\Controllers\Api\V1\AuditLog;

use Illuminate\Http\Request;
use App\Http\ApiResponse;
use App\Http\Controllers\Controller;

// models
use App\AuditLog;

class AuditLogController extends Controller
{
    public function __construct()
    {
        $this->res = new ApiResponse();
    }

    public function all(Request $request)
    {
        $logs = AuditLog::with('user')->orderBy('created_at','DESC')->paginate(30);
        return $this->res->withSuccessData($logs);
    }

    public function delete(Request $request, int $id)
    {
        $log = AuditLog::find($id);
        if (!$log)
            return $this->res->withError('Audit log doesnt exist');

        $log->delete();

        return $this->res->withSuccess();
    }
}