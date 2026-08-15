<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreStaffRequest;
use App\Http\Requests\Admin\UpdateStaffRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Spatie\Permission\Models\Role;
use Yajra\DataTables\Facades\DataTables;

class StaffController extends Controller
{
    public function index(): View
    {
        abort_unless(auth()->user()?->can('staff.view'), 403);

        return view('admin.staff.index');
    }

    public function datatable(Request $request): JsonResponse
    {
        abort_unless(auth()->user()?->can('staff.view'), 403);

        $query = User::query()
            ->role(['Super Admin', 'Manager', 'Staff', 'Support'])
            ->with('roles')
            ->latest();

        return DataTables::of($query)
            ->addColumn('role_name', fn (User $staff) => $staff->roles->pluck('name')->implode(', ') ?: '—')
            ->addColumn('status', function (User $staff) {
                $badge = $staff->is_active ? 'success' : 'secondary';
                $label = $staff->is_active ? 'Active' : 'Inactive';

                return '<span class="badge bg-'.$badge.'">'.$label.'</span>';
            })
            ->addColumn('action', function (User $staff) {
                $buttons = '';
                if (auth()->user()?->can('staff.update')) {
                    $buttons .= '<a href="'.route('admin.staff.edit', $staff).'" class="btn btn-sm btn-outline-primary me-1"><i class="bi bi-pencil"></i></a>';
                }
                if (auth()->user()?->can('staff.delete') && $staff->id !== auth()->id()) {
                    $buttons .= '<form action="'.route('admin.staff.destroy', $staff).'" method="POST" class="d-inline" data-confirm="Delete this staff member?">'
                        .csrf_field().method_field('DELETE')
                        .'<button type="submit" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button></form>';
                }

                return $buttons;
            })
            ->rawColumns(['action', 'status'])
            ->make(true);
    }

    public function create(): View
    {
        abort_unless(auth()->user()?->can('staff.create'), 403);

        return view('admin.staff.create', [
            'roles' => Role::query()->where('guard_name', 'web')->orderBy('name')->pluck('name'),
        ]);
    }

    public function store(StoreStaffRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $role = $data['role'];
        unset($data['role']);

        $data['is_active'] = $data['is_active'] ?? true;
        $data['role'] = strtolower(str_replace(' ', '_', $role));

        $staff = User::query()->create($data);
        $staff->syncRoles([$role]);

        activity_log('created', 'staff', "Created staff #{$staff->id}: {$staff->email}");

        return redirect()
            ->route('admin.staff.index')
            ->with('success', 'Staff member created successfully.');
    }

    public function edit(User $staff): View
    {
        abort_unless(auth()->user()?->can('staff.update'), 403);
        abort_unless($staff->isAdmin(), 404);

        $staff->load('roles');

        return view('admin.staff.edit', [
            'staff' => $staff,
            'roles' => Role::query()->where('guard_name', 'web')->orderBy('name')->pluck('name'),
        ]);
    }

    public function update(UpdateStaffRequest $request, User $staff): RedirectResponse
    {
        abort_unless($staff->isAdmin(), 404);

        $data = $request->validated();
        $role = $data['role'];
        unset($data['role']);

        if (empty($data['password'])) {
            unset($data['password']);
        }

        $data['role'] = strtolower(str_replace(' ', '_', $role));

        $staff->update($data);
        $staff->syncRoles([$role]);

        activity_log('updated', 'staff', "Updated staff #{$staff->id}: {$staff->email}");

        return redirect()
            ->route('admin.staff.index')
            ->with('success', 'Staff member updated successfully.');
    }

    public function destroy(User $staff): RedirectResponse
    {
        abort_unless(auth()->user()?->can('staff.delete'), 403);
        abort_unless($staff->isAdmin(), 404);
        abort_if($staff->id === auth()->id(), 403, 'You cannot delete your own account.');

        $email = $staff->email;
        $staff->syncRoles([]);
        $staff->delete();

        activity_log('deleted', 'staff', "Deleted staff: {$email}");

        return redirect()
            ->route('admin.staff.index')
            ->with('success', 'Staff member deleted successfully.');
    }
}
