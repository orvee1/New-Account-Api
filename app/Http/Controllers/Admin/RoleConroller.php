<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Permission;
use App\Models\Role;
use Auth;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Validation\Rule;
use Spatie\Permission\PermissionRegistrar;

class RoleConroller extends Controller
{

    private function permission_rest()
    {
        Artisan::call('permission:cache-reset');
        return 'ok';
    }

    public function index(Request $request)
    {
        if (!Auth::guard('web')->user()->hasRole('Administrator|Developer')) {
            return abort(404);
        }

        $allowedSorts = ['id', 'name'];
        $sort = in_array($request->input('sort'), $allowedSorts) ? $request->input('sort') : 'id';
        $dir  = $request->input('dir') === 'asc' ? 'asc' : 'desc';

        $query = Role::query();

        $roles = $query->orderBy($sort, $dir)
            ->get();

        return view('admin.roles.index', compact('roles', 'sort', 'dir'));
    }

    public function create()
    {
        if (!Auth::guard('web')->user()->hasRole('Administrator|Developer')) {
            return abort(404);
        }

        $permissions = Permission::query()
            ->with('children')
            ->where('parent_id', 0)
            ->get();

        $this->permission_rest();

        return view('admin.roles.create', compact('permissions'));
    }

    public function store(Request $request)
    {
        // role check: pipe string নয়, hasAnyRole ব্যবহার করুন
        abort_unless(Auth::guard('web')->user()?->hasAnyRole(['Administrator', 'Developer']), 404);

        $guard = 'web';

        // validate
        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('roles', 'name')->where('guard_name', $guard),
            ],
            'permission'   => ['array'],
            'permission.*' => ['integer', 'exists:permissions,id'],
        ]);

        // role create (guard সেট করে)
        $role = Role::create([
            'name' => $validated['name'],
            'guard_name' => $guard,
        ]);

        // IDs থেকে Permission models আনুন, same guard নিশ্চিত করে
        $permissionIds = $validated['permission'] ?? [];
        $perms = Permission::where('guard_name', $guard)
            ->whereIn('id', $permissionIds)
            ->get();

        // set permissions (exactly these)
        $role->syncPermissions($perms->pluck('name')->all());
        // অথবা: $role->givePermissionTo($perms);

        // Spatie cache clear
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return redirect()
            ->route('roles.index')
            ->with(['message' => 'Record has been created successfully', 'class' => 'alert-success']);
    }

    public function edit(Role $role)
    {
        if (
            !Auth::guard('web')->user()->hasRole('Administrator|Developer')
            || $role->name == 'Developer'
        ) {
            return abort(404);
        }

        // return 
        $role_has_permission_ids = $role->permissions()->pluck('id')->toArray() ?? [];

        // return
        $permissions = Permission::query()
            ->with('children')
            ->where('parent_id', 0)
            ->get();
        // dd($role_has_permission_ids, $permissions);
        //permission cache rest
        $this->permission_rest();

        return view('admin.roles.edit', compact(
            'role',
            'permissions',
            'role_has_permission_ids',
        ));
    }

    public function update(Request $request, Role $role) // route-model binding থাকলে
    {
        // কেবল Admin/Dev-ই পারবে
        abort_unless(Auth::guard('web')->user()?->hasAnyRole(['Administrator', 'Developer']), 404);

        // প্রোটেক্টেড রোল এডিট ব্লক
        if (in_array($role->name, ['Administrator', 'Developer'])) {
            return redirect()->route('roles.index')
                ->with(['message' => 'This role is protected and cannot be modified.', 'class' => 'alert-danger']);
        }

        $guard = $role->guard_name ?: 'web';

        // Validate
        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('roles', 'name')
                    ->ignore($role->id)
                    ->where('guard_name', $guard),
            ],
            'permission'   => ['array'],
            'permission.*' => ['integer', 'exists:permissions,id'],
        ]);

        // Update role name (guard অপরিবর্তিতই থাক)
        $role->update([
            'name' => $validated['name'],
        ]);

        // IDs → Permission models (same guard)
        $permissionIds = $validated['permission'] ?? [];
        $perms = Permission::where('guard_name', $guard)
            ->whereIn('id', $permissionIds)
            ->get();

        $role->syncPermissions($perms->pluck('name')->all());

        // Spatie cache clear
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return redirect()
            ->route('roles.edit', $role->id)
            ->with(['message' => 'Record has been updated successfully', 'class' => 'alert-success']);
    }

    public function destroy(Role $role)
    {
        if (
            !Auth::guard('web')->user()->hasRole('Administrator|Developer')
            || $role->name == 'Developer'
        ) {
            return abort(404);
        }

        if ($role->permissions()->count()) {
            return back()->with([
                'class' => 'alert-danger',
                "message" => "Please remove all permission under this role."
            ]);
        }

        $role->deleted_by = Auth::id();

        $role->deleted_at = Carbon::now();

        $role->save();

        Session::flash('message', 'Record has been deleted successfully');

        //permission cache rest
        $this->permission_rest();

        return redirect()->route('roles.index');
    }
}
