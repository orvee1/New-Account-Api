<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\CompanyUser;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class CompanyUserController extends Controller
{
    public function index(Request $request)
    {
        $perPage = (int) $request->input('per_page', 20);
        $perPage = max(5, min(200, $perPage));

        $query = CompanyUser::query()
            ->with('company:id,name')
            ->when($request->filled('q'), fn ($q) => $q->search($request->q))
            ->when($request->filled('company_id'), fn ($q) => $q->forCompany($request->company_id))
            ->when($request->filled('role') && $request->role !== 'all', fn ($q) => $q->role($request->role))
            ->when($request->filled('status') && $request->status !== 'all', fn ($q) => $q->where('status', $request->status))
            ->latest('id');

        $paginator = $query->paginate($perPage)->withQueryString();
        $collection = $paginator->getCollection()->load('company:id,name')->map->toArray();

        return response()->json([
            'data' => $collection->values(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'per_page'     => $paginator->perPage(),
                'total'        => $paginator->total(),
                'last_page'    => $paginator->lastPage(),
            ],
            'filters' => [
                'q'          => $request->q,
                'company_id' => $request->company_id,
                'role'       => $request->input('role', 'all'),
                'status'     => $request->input('status', 'all'),
                'per_page'   => $perPage,
            ],
        ], 200);
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
            'permissions'  => ['nullable'], // array|string
        ]);

        // Single owner per company
        if (($data['role'] ?? null) === 'owner') {
            $existsOwner = CompanyUser::where('company_id', $data['company_id'])
                ->where('role', 'owner')
                ->exists();
            if ($existsOwner) {
                return response()->json([
                    'message' => 'This company already has an Owner.',
                    'errors'  => ['role' => ['This company already has an Owner.']],
                ], 409);
            }
        }

        // Photo upload
        if ($request->hasFile('photo')) {
            $data['photo'] = $request->file('photo')->store('company_users/photos', 'public');
        }

        // Normalize permissions (string JSON -> array)
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

        // If active, set joined_at
        if (($data['status'] ?? null) === 'active') {
            $data['joined_at'] = Carbon::now();
        }

        $user = CompanyUser::create($data);

        // Make primary => unset others
        if ($user->is_primary) {
            CompanyUser::where('company_id', $user->company_id)
                ->where('id', '<>', $user->id)
                ->update(['is_primary' => false]);
        }

        return response()->json([
            'data' => $user->load('company:id,name')->toArray(),
            'message' => 'Company user created successfully.',
        ], 201);
    }

    public function show(CompanyUser $companyUser)
    {
        return response()->json([
            'data' => $companyUser->load('company:id,name')->toArray(),
        ], 200);
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
            'permissions'  => ['nullable'], // array|string
        ]);

        // Owner constraints
        if (($data['role'] ?? null) === 'owner') {
            $existsOwner = CompanyUser::where('company_id', $data['company_id'])
                ->where('role', 'owner')
                ->where('id', '<>', $companyUser->id)
                ->exists();
            if ($existsOwner) {
                return response()->json([
                    'message' => 'This company already has an Owner.',
                    'errors'  => ['role' => ['This company already has an Owner.']],
                ], 409);
            }
        } else {
            if ($companyUser->role === 'owner') {
                $otherOwners = CompanyUser::where('company_id', $companyUser->company_id)
                    ->where('id', '<>', $companyUser->id)
                    ->where('role', 'owner')
                    ->count();
                if ($otherOwners === 0) {
                    return response()->json([
                        'message' => 'You cannot demote the only Owner of this company.',
                        'errors'  => ['role' => ['You cannot demote the only Owner of this company.']],
                    ], 409);
                }
            }
        }

        // Photo delete
        if ($request->boolean('remove_photo') && $companyUser->photo) {
            Storage::disk('public')->delete($companyUser->photo);
            $data['photo'] = null;
        }

        // Photo upload
        if ($request->hasFile('photo')) {
            if ($companyUser->photo) {
                Storage::disk('public')->delete($companyUser->photo);
            }
            $data['photo'] = $request->file('photo')->store('company_users/photos', 'public');
        }

        // Only set password if provided
        if (empty($data['password'])) {
            unset($data['password']);
        }

        // Normalize permissions
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

        // If becomes active & joined_at empty
        if (($data['status'] ?? null) === 'active' && is_null($companyUser->joined_at)) {
            $data['joined_at'] = Carbon::now();
        }

        $companyUser->update($data);

        // Enforce single primary
        if ($request->boolean('is_primary')) {
            CompanyUser::where('company_id', $companyUser->company_id)
                ->where('id', '<>', $companyUser->id)
                ->update(['is_primary' => false]);
        }

        return response()->json([
            'data' => $companyUser->load('company:id,name')->toArray(),
            'message' => 'Company user updated successfully.',
        ], 200);
    }

    /**
     * DELETE /api/admin/company-users/{companyUser}
     */
    public function destroy(CompanyUser $companyUser)
    {
        // Prevent deleting last Owner
        if ($companyUser->role === 'owner') {
            $otherOwners = CompanyUser::where('company_id', $companyUser->company_id)
                ->where('id', '<>', $companyUser->id)
                ->where('role', 'owner')
                ->count();
            if ($otherOwners === 0) {
                return response()->json([
                    'message' => 'You cannot delete the only Owner of this company.',
                    'errors'  => ['delete' => ['You cannot delete the only Owner of this company.']],
                ], 409);
            }
        }

        $companyUser->forceFill(['deleted_by' => Auth::id()])->save();
        $companyUser->delete();

        return response()->json([
            'message' => 'Company user deleted successfully.',
        ], 200);
    }

    /**
     * POST /api/admin/company-users/{companyUser}/toggle-status
     */
    public function toggleStatus(CompanyUser $companyUser)
    {
        $next = $companyUser->status === 'active' ? 'inactive' : 'active';
        $payload = ['status' => $next];

        if ($next === 'active' && is_null($companyUser->joined_at)) {
            $payload['joined_at'] = Carbon::now();
        }

        $companyUser->update($payload);

        return response()->json([
            'data' => $companyUser->toArray(),
            'message' => "Status changed to {$next}.",
        ], 200);
    }

    /**
     * POST /api/admin/company-users/{companyUser}/make-primary
     */
    public function makePrimary(CompanyUser $companyUser)
    {
        $companyUser->update(['is_primary' => true]);

        CompanyUser::where('company_id', $companyUser->company_id)
            ->where('id', '<>', $companyUser->id)
            ->update(['is_primary' => false]);

        return response()->json([
            'data' => $companyUser->toArray(),
            'message' => 'Marked as primary user for this company.',
        ], 200);
    }
}
