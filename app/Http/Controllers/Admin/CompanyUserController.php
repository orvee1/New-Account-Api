<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\CompanyUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class CompanyUserController extends Controller
{
    public function index(Request $request)
    {
        $perPage = (int) $request->input('per_page', 20);
        if ($perPage < 5)  $perPage = 5;
        if ($perPage > 200) $perPage = 200;
        $query = CompanyUser::query()
            ->with('company:id,name')
            ->when($request->filled('q'), fn ($q) => $q->search($request->q))
            ->when($request->filled('company_id'), fn ($q) => $q->forCompany($request->company_id))
            ->when($request->filled('role') && $request->role !== 'all', fn ($q) => $q->role($request->role))
            ->when($request->filled('status') && $request->status !== 'all', fn ($q) => $q->where('status', $request->status))
            ->latest('id');

        $users = $query->paginate($perPage)->withQueryString();

        $filters = [
            'q'          => $request->q,
            'company_id' => $request->company_id,
            'role'       => $request->input('role', 'all'),
            'status'     => $request->input('status', 'all'),
            'per_page'   => $perPage,
        ];

        $companies = Company::select('id', 'name')->orderBy('name')->get();
        return view('admin.company_user.index', compact('users', 'filters', 'companies'));
    }

    public function create()
    {
        $companies = Company::select('id', 'name')->orderBy('name')->get();
        $roles     = ['owner', 'admin', 'accountant', 'viewer'];
        $statuses  = ['active', 'inactive'];

        return view('admin.company_user.create', compact('companies', 'roles', 'statuses'));
    }

    public function store(Request $request)
    {
        $emailUnique = Rule::unique('company_users', 'email')
            ->where(fn ($q) => $q->where('company_id', $request->input('company_id')));

        $phoneUnique = Rule::unique('company_users', 'phone_number')
            ->where(fn ($q) => $q->where('company_id', $request->input('company_id')));

        $data = $request->validate([
            'company_id'   => ['required', 'exists:companies,id'],
            'name'         => ['required', 'string', 'max:255'],
            'email'        => ['nullable', 'email', 'max:191', $emailUnique],
            'photo'        => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp,avif', 'max:2048'],
            'phone_number' => ['required', 'string', 'max:30', $phoneUnique],
            'password'     => ['required', 'string', 'min:6', 'confirmed'],
            'role'         => ['required', Rule::in(['owner', 'admin', 'accountant', 'viewer'])],
            'status'       => ['required', Rule::in(['active', 'inactive'])],
            'is_primary'   => ['sometimes', 'boolean'],
            'permissions'  => ['nullable'],
        ]);

        if (($data['role'] ?? null) === 'owner') {
            $existsOwner = CompanyUser::where('company_id', $data['company_id'])
                ->where('role', 'owner')
                ->exists();
            if ($existsOwner) {
                return back()->withErrors(['role' => 'This company already has an Owner.'])->withInput();
            }
        }

        if ($request->hasFile('photo')) {
            $data['photo'] = $request->file('photo')->store('company_users/photos', 'public');
        }

        if ($request->has('permissions')) {
            $perm = $request->input('permissions');
            if (is_string($perm)) {
                $decoded = json_decode($perm, true);
                $data['permissions'] = is_array($decoded) ? $decoded : null;
            } elseif (is_array($perm)) {
                $data['permissions'] = $perm;
            } else {
                $data['permissions'] = null;
            }
        }

        if (($data['status'] ?? null) === 'active') {
            $data['joined_at'] = Carbon::now();
        }

        $user = CompanyUser::create($data);

        if ($user->is_primary) {
            CompanyUser::where('company_id', $user->company_id)
                ->where('id', '<>', $user->id)
                ->update(['is_primary' => false]);
        }

        return redirect()->route('company-users.index', $user)
            ->with('success', 'Company user created successfully.');
    }

    public function show(CompanyUser $companyUser)
    {
        $companyUser->load('company:id,name');
        return view('admin.company_user.show', compact('companyUser'));
    }

    public function edit(CompanyUser $companyUser)
    {
        $companies = Company::select('id', 'name')->orderBy('name')->get();
        $roles     = ['owner', 'admin', 'accountant', 'viewer'];
        $statuses  = ['active', 'inactive'];

        return view('admin.company_user.edit', compact('companyUser', 'companies', 'roles', 'statuses'));
    }

    public function update(Request $request, CompanyUser $companyUser)
    {
        $emailUnique = Rule::unique('company_users', 'email')
            ->where(fn ($q) => $q->where('company_id', $request->input('company_id')))
            ->ignore($companyUser->id);

        $phoneUnique = Rule::unique('company_users', 'phone_number')
            ->where(fn ($q) => $q->where('company_id', $request->input('company_id')))
            ->ignore($companyUser->id);

        $data = $request->validate([
            'company_id'   => ['required', 'exists:companies,id'],
            'name'         => ['required', 'string', 'max:255'],
            'email'        => ['nullable', 'email', 'max:191', $emailUnique],
            'photo'        => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp,avif', 'max:2048'],
            'remove_photo' => ['sometimes', 'boolean'],
            'phone_number' => ['required', 'string', 'max:30', $phoneUnique],
            'password'     => ['nullable', 'string', 'min:6', 'confirmed'],
            'role'         => ['required', Rule::in(['owner', 'admin', 'accountant', 'viewer'])],
            'status'       => ['required', Rule::in(['active', 'inactive'])],
            'is_primary'   => ['sometimes', 'boolean'],
            'permissions'  => ['nullable'],
        ]);

        if (($data['role'] ?? null) === 'owner') {
            $existsOwner = CompanyUser::where('company_id', $data['company_id'])
                ->where('role', 'owner')
                ->where('id', '<>', $companyUser->id)
                ->exists();
            if ($existsOwner) {
                return back()->withErrors(['role' => 'This company already has an Owner.'])->withInput();
            }
        } else {
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

        if (empty($data['password'])) {
            unset($data['password']);
        }

        if ($request->has('permissions')) {
            $perm = $request->input('permissions');
            if (is_string($perm)) {
                $decoded = json_decode($perm, true);
                $data['permissions'] = is_array($decoded) ? $decoded : null;
            } elseif (is_array($perm)) {
                $data['permissions'] = $perm;
            } else {
                $data['permissions'] = null;
            }
        }

        if (($data['status'] ?? null) === 'active' && is_null($companyUser->joined_at)) {
            $data['joined_at'] = Carbon::now();
        }

        $companyUser->update($data);

        if ($request->boolean('is_primary')) {
            CompanyUser::where('company_id', $companyUser->company_id)
                ->where('id', '<>', $companyUser->id)
                ->update(['is_primary' => false]);
        }

        return redirect()->route('company-users.index', $companyUser)
            ->with('success', 'Company user updated successfully.');
    }

    public function destroy(CompanyUser $companyUser)
    {
        if ($companyUser->role === 'owner') {
            $otherOwners = CompanyUser::where('company_id', $companyUser->company_id)
                ->where('id', '<>', $companyUser->id)
                ->where('role', 'owner')
                ->count();
            if ($otherOwners === 0) {
                return back()->withErrors(['delete' => 'You cannot delete the only Owner of this company.']);
            }
        }

        $companyUser->forceFill(['deleted_by' => Auth::id()])->save();
        $companyUser->delete();

        return redirect()->route('company-users.index')
            ->with('success', 'Company user deleted successfully.');
    }

    public function toggleStatus(CompanyUser $companyUser)
    {
        $next = $companyUser->status === 'active' ? 'inactive' : 'active';

        $payload = ['status' => $next];
        if ($next === 'active' && is_null($companyUser->joined_at)) {
            $payload['joined_at'] = Carbon::now();
        }

        $companyUser->update($payload);

        return back()->with('success', "Status changed to {$next}.");
    }

    public function makePrimary(CompanyUser $companyUser)
    {
        $companyUser->update(['is_primary' => true]);

        CompanyUser::where('company_id', $companyUser->company_id)
            ->where('id', '<>', $companyUser->id)
            ->update(['is_primary' => false]);

        return back()->with('success', 'Marked as primary user for this company.');
    }
}
