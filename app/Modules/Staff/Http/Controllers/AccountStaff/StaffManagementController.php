<?php

namespace App\Modules\Staff\Http\Controllers\AccountStaff;

use App\Common\Http\Controllers\Controller;
use App\Common\Http\Responses\ApiResponse;
use App\Domains\AccountStaff\Actions\Staff\StaffManagementAction;
use App\Domains\AccountStaff\Models\AccountStaff;
use App\Modules\Staff\Http\Requests\AccountStaff\StaffListRequest;
use App\Modules\Staff\Http\Requests\AccountStaff\StaffSaveRequest;
use App\Modules\Staff\Http\Requests\AccountStaff\StaffTemplateRequest;

final class StaffManagementController extends Controller
{
    public function index(StaffListRequest $request, StaffManagementAction $action)
    {
        $result = $action->list($request->validated());

        return ApiResponse::success($result['items'], $result['meta']);
    }

    public function show(AccountStaff $staff, StaffManagementAction $action)
    {
        return ApiResponse::success($action->show($staff));
    }

    public function store(StaffSaveRequest $request, StaffManagementAction $action)
    {
        return ApiResponse::success($action->save(null, $request->validated()));
    }

    public function update(AccountStaff $staff, StaffSaveRequest $request, StaffManagementAction $action)
    {
        return ApiResponse::success($action->save($staff, $request->validated()));
    }

    public function destroy(AccountStaff $staff, StaffManagementAction $action)
    {
        $action->delete($staff);

        return ApiResponse::success(['deleted' => true]);
    }

    public function options(StaffManagementAction $action)
    {
        return ApiResponse::success($action->options());
    }

    public function template(StaffTemplateRequest $request, StaffManagementAction $action)
    {
        return ApiResponse::success($action->saveTemplate($request->validated()));
    }

    public function histories(AccountStaff $staff, StaffListRequest $request, StaffManagementAction $action)
    {
        $result = $action->histories($staff, $request->validated());

        return ApiResponse::success($result['items'], $result['meta']);
    }
}
