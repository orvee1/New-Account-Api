<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\CompanyUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Carbon\Carbon;

class CompanyUserController extends Controller
{
    // public function __construct()
    // {
    //     $this->middleware('auth'); // add gates/policies if you use them
    // }

    /**
     * GET /admin/company_user
     * Filters: ?q=&company_id=&role=&status=&per_page=
     */
    public function index(Request $request)
    {
        $perPage = (int) $request->input('per_page', 20);

        $query = CompanyUser::query()
            ->with('company:id,name')
            ->when($request->filled('q'), function ($q) use ($request) {
                $term = $request->q;
                $q->where(function ($sub) use ($term) {
                    $sub->where('name', 'like', "%{$term}%")
                        ->orWhere('email', 'like', "%{$term}%")
                        ->orWhere('phone_number', 'like', "%{$term}%");
                });
            })
            ->when($request->filled('company_id'), fn($q) => $q->where('company_id', $request->company_id))
            ->when($request->filled('role') && $request->role !== 'all', fn($q) => $q->where('role', $request->role))
            ->when($request->filled('status') && $request->status !== 'all', fn($q) => $q->where('status', $request->status))
            ->latest('id');

        $users = $query->paginate($perPage)->withQueryString();

        $filters = [
            'q'         => $request->q,
            'company_id'=> $request->company_id,
            'role'      => $request->input('role', 'all'),
            'status'    => $request->input('status', 'all'),
            'per_page'  => $perPage,
        ];

        $companies = Company::select('id', 'name')->orderBy('name')->get();

        return view('admin.company_user.index', compact('users', 'filters', 'companies'));
    }

    /**
     * GET /admin/company_user/create
     */
    public function create()
    {
        $companies = Company::select('id', 'name')->orderBy('name')->get();
        $roles = ['owner', 'admin', 'accountant', 'viewer'];
        $statuses = ['active', 'inactive'];

        return view('admin.company_user.create', compact('companies', 'roles', 'statuses'));
    }

    /**
     * POST /admin/company_user
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'company_id'   => ['required', 'exists:companies,id'],
            'name'         => ['required', 'string', 'max:255'],
            'email'        => ['nullable', 'email', 'max:255', 'unique:company_users,email'],
            'photo'        => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp,avif', 'max:2048'],
            'phone_number' => ['required', 'string', 'max:40'],
            'password'     => ['required', 'string', 'min:8', 'confirmed'],
            'role'         => ['required', Rule::in(['owner', 'admin', 'accountant', 'viewer'])],
            'status'       => ['required', Rule::in(['active', 'inactive'])],
            'is_primary'   => ['sometimes', 'boolean'],
            'permissions'  => ['nullable', 'array'],
        ]);

        // Optional: Enforce only one owner per company
        if (($data['role'] ?? null) === 'owner') {
            $existsOwner = CompanyUser::where('company_id', $data['company_id'])
                ->where('role', 'owner')
                ->exists();
            if ($existsOwner) {
                return back()->withErrors(['role' => 'This company already has an Owner.'])->withInput();
            }
        }

        // Handle photo upload
        if ($request->hasFile('photo')) {
            $data['photo'] = $request->file('photo')->store('company_users/photos', 'public');
        }

        // permissions may come as [] or JSON string from UI
        if (is_string($request->permissions)) {
            $decoded = json_decode($request->permissions, true);
            $data['permissions'] = is_array($decoded) ? $decoded : null;
        }

        // Auto mark joined_at if active on creation (optional)
        if (($data['status'] ?? null) === 'active') {
            $data['joined_at'] = Carbon::now();
        }

        $data['created_by'] = Auth::id();

        $user = CompanyUser::create($data);

        // If set as primary, unset others in same company
        if ($user->is_primary) {
            CompanyUser::where('company_id', $user->company_id)
                ->where('id', '<>', $user->id)
                ->update(['is_primary' => false]);
        }

        return redirect()->route('admin.company_user.edit', $user)
            ->with('success', 'Company user created successfully.');
    }

    /**
     * GET /admin/company_user/{companyUser}
     */
    public function show(CompanyUser $companyUser)
    {
        $companyUser->load('company:id,name');
        return view('admin.company_user.show', compact('companyUser'));
    }

    /**
     * GET /admin/company_user/{companyUser}/edit
     */
    public function edit(CompanyUser $companyUser)
    {
        $companies = Company::select('id', 'name')->orderBy('name')->get();
        $roles = ['owner', 'admin', 'accountant', 'viewer'];
        $statuses = ['active', 'inactive'];

        return view('admin.company_user.edit', compact('companyUser', 'companies', 'roles', 'statuses'));
    }

    /**
     * PUT/PATCH /admin/company_user/{companyUser}
     */
    public function update(Request $request, CompanyUser $companyUser)
    {
        $data = $request->validate([
            'company_id'   => ['required', 'exists:companies,id'],
            'name'         => ['required', 'string', 'max:255'],
            'email'        => ['nullable', 'email', 'max:255', 'unique:company_users,email,' . $companyUser->id],
            'photo'        => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp,avif', 'max:2048'],
            'remove_photo' => ['sometimes', 'boolean'],
            'phone_number' => ['required', 'string', 'max:40'],
            'password'     => ['nullable', 'string', 'min:8', 'confirmed'],
            'role'         => ['required', Rule::in(['owner', 'admin', 'accountant', 'viewer'])],
            'status'       => ['required', Rule::in(['active', 'inactive'])],
            'is_primary'   => ['sometimes', 'boolean'],
            'permissions'  => ['nullable', 'array'],
        ]);

        // Enforce single owner per company (ignore self)
        if (($data['role'] ?? null) === 'owner') {
            $existsOwner = CompanyUser::where('company_id', $data['company_id'])
                ->where('role', 'owner')
                ->where('id', '<>', $companyUser->id)
                ->exists();
            if ($existsOwner) {
                return back()->withErrors(['role' => 'This company already has an Owner.'])->withInput();
            }
        } else {
            // Prevent removing the last Owner
            if ($companyUser->role === 'owner') {
                $otherOwners = CompanyUser::where('company_id', $companyUser->company_id)
                    ->where('id', '<>', $companyUser->id)
                    ->where('role', 'owner')
                    ->count();
                if ($otherOwners === 0) {
                    return back()->withErrors(['role' => 'You cannot demote the only Owner of this company.'])->withInput();
                }
            }
        }

        if ($request->boolean('remove_photo') && $companyUser->photo) {
            Storage::disk('public')->delete($companyUser->photo);
            $data['photo'] = null;
        }

        if ($request->hasFile('photo')) {
            if ($companyUser->photo) {
                Storage::disk('public')->delete($companyUser->photo);
            }
            $data['photo'] = $request->file('photo')->store('company_users/photos', 'public');
        }

        // Only set password if provided (mutator will hash)
        if (empty($data['password'])) {
            unset($data['password']);
        }

        if (is_string($request->permissions)) {
            $decoded = json_decode($request->permissions, true);
            $data['permissions'] = is_array($decoded) ? $decoded : null;
        }

        // Auto set joined_at when moving to active and it was null
        if (($data['status'] ?? null) === 'active' && is_null($companyUser->joined_at)) {
            $data['joined_at'] = Carbon::now();
        }

        $data['updated_by'] = Auth::id();

        $companyUser->update($data);

        // Ensure only one primary user per company if flagged
        if ($request->boolean('is_primary')) {
            CompanyUser::where('company_id', $companyUser->company_id)
                ->where('id', '<>', $companyUser->id)
                ->update(['is_primary' => false]);
        }

        return redirect()->route('admin.company_user.edit', $companyUser)
            ->with('success', 'Company user updated successfully.');
    }

    /**
     * DELETE /admin/company_user/{companyUser}
     */
    public function destroy(CompanyUser $companyUser)
    {
        // Prevent deleting the last Owner
        if ($companyUser->role === 'owner') {
            $otherOwners = CompanyUser::where('company_id', $companyUser->company_id)
                ->where('id', '<>', $companyUser->id)
                ->where('role', 'owner')
                ->count();
            if ($otherOwners === 0) {
                return back()->withErrors(['delete' => 'You cannot delete the only Owner of this company.']);
            }
        }

        $companyUser->update(['deleted_by' => Auth::id()]);
        $companyUser->delete();

        return redirect()->route('admin.company_user.index')
            ->with('success', 'Company user deleted successfully.');
    }

    /**
     * POST /admin/company_user/{companyUser}/toggle-status
     */
    public function toggleStatus(CompanyUser $companyUser)
    {
        $next = $companyUser->status === 'active' ? 'inactive' : 'active';
        $companyUser->update([
            'status'     => $next,
            'updated_by' => Auth::id(),
            'joined_at'  => $companyUser->joined_at ?: ($next === 'active' ? Carbon::now() : $companyUser->joined_at),
        ]);

        return back()->with('success', "Status changed to {$next}.");
    }

    /**
     * POST /admin/company_user/{companyUser}/make-primary
     */
    public function makePrimary(CompanyUser $companyUser)
    {
        $companyUser->update([
            'is_primary' => true,
            'updated_by' => Auth::id(),
        ]);

        CompanyUser::where('company_id', $companyUser->company_id)
            ->where('id', '<>', $companyUser->id)
            ->update(['is_primary' => false]);

        return back()->with('success', 'Marked as primary user for this company.');
    }
}
