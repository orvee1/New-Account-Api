<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Company;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class CompanyController extends Controller
{
    public function index(Request $request)
    {
        $q = $request->string('q')->toString();
        $status = $request->string('status')->toString();
        $per_page = (int) $request->integer('per_page') ?: 15;

        $companies = Company::query()
            ->when($q, function ($query, $q) {
                $query->where(function ($q2) use ($q) {
                     $q2->where('name', 'like', "%{$q}%")
                        ->where('email', 'like', "%{$q}%")
                        ->where('phone', 'like', "%{$q}%")
                        ->where('slug', 'like', "%{$q}%");

                });
            })
            ->when($status, fn($query, $status) => $query->where('status', $status))
            ->latest('id')
            ->paginate($per_page);

            return response()->json($companies);
    }

    public function myCompanies(Request $request)
    {
        $user = $request->user();
        $companies = $user->companies()
            ->withPivot('role', 'status', 'invited_at', 'joined_at', 'last_login_at', 'is_primary', 'permissions', 'notes', 'created_by', 'updated_by')
            ->orderByPivot('is_primary', 'desc')
            ->get();

            return response()->json($companies);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'            => ['required','string','max:255','unique:companies,name'],
            'slug'            => ['nullable','string','max:255','unique:companies,slug'],
            'email'           => ['nullable','email','max:255'],
            'phone'           => ['nullable','string','max:50'],
            'address'         => ['nullable','string'],
            'logo'            => ['nullable','string','max:255'],
            'industry_type'   => ['nullable','string','max:255'],
            'registration_no' => ['nullable','string','max:255'],
            'website'         => ['nullable','string','max:255'],
            'status'          => ['nullable', Rule::in(['active','inactive','suspended'])],
            'is_verified'     => ['nullable','boolean'],
            'owner_id'        => ['nullable','exists:users,id'],
        ]);

        $authId = $request->user()->id;

        return DB::transaction(function () use ($data, $authId, $request) {
            $slug = $data['slug'] ?? $this->uniqueSlug($data['name']);
            $data['slug'] = $slug;

            $ownerId = $data['owner_id'] ?? $authId;

            $company = Company::create(array_merge($data, [
                'owner_id'  => $ownerId,
                'created_by'=> $authId,
                'updated_by'=> $authId,
            ]));

            $company->users()->syncWithoutDetaching([
                $authId => [
                    'role'       => $authId === $ownerId ? 'owner' : 'admin',
                    'status'     => 'active',
                    'joined_at'  => now(),
                    'is_primary' => true,
                    'created_by' => $authId,
                    'updated_by' => $authId,
                ],
            ]);

            $this->ensureSinglePrimary($authId, $company->id);

            if ($ownerId !== $authId) {
                $company->users()->syncWithoutDetaching([
                    $ownerId => [
                        'role'       => 'owner',
                        'status'     => 'active',
                        'joined_at'  => now(),
                        'is_primary' => false,
                        'created_by' => $authId,
                        'updated_by' => $authId,
                    ],
                ]);
            }

            return response()->json($company->fresh(), 201);
        });
    }

    public function show(Company $company)
    {
        $company->load(['owner:id,name,email', 'users:id,name,email']);
        return response()->json($company);
    }

     public function update(Request $request, Company $company)
    {
        $data = $request->validate([
            'name'            => ['sometimes','required','string','max:255', Rule::unique('companies','name')->ignore($company->id)],
            'slug'            => ['sometimes','nullable','string','max:255', Rule::unique('companies','slug')->ignore($company->id)],
            'email'           => ['sometimes','nullable','email','max:255'],
            'phone'           => ['sometimes','nullable','string','max:50'],
            'address'         => ['sometimes','nullable','string'],
            'logo'            => ['sometimes','nullable','string','max:255'],
            'industry_type'   => ['sometimes','nullable','string','max:255'],
            'registration_no' => ['sometimes','nullable','string','max:255'],
            'website'         => ['sometimes','nullable','string','max:255'],
            'status'          => ['sometimes', Rule::in(['active','inactive','suspended'])],
            'is_verified'     => ['sometimes','boolean'],
            'owner_id'        => ['sometimes','nullable','exists:users,id'],
        ]);

        $data['updated_by'] = $request->user()->id;

        if (empty($data['slug']) && !empty($data['name'])) {
            $data['slug'] = $this->uniqueSlug($data['name'], $company->id);
        }

        $company->update($data);
        return response()->json($company->fresh());
    }

      public function destroy(Company $company)
    {
        $company->delete();
        return response()->json([
            'message' => 'Company deleted',
            'deleted_at' => $company->deleted_at
        ]);
    }

      private function uniqueSlug(string $name, ?int $ignoreId = null): string
    {
        $base = Str::slug($name);
        $slug = $base;
        $i = 1;
        while (
            Company::where('slug', $slug)
                ->when($ignoreId, fn($q) => $q->where('id', '!=', $ignoreId))
                ->exists()
        ) {
            $slug = "{$base}-{$i}";
            $i++;
        }
        return $slug;
    }

      private function ensureSinglePrimary(int $userId, int $companyId): void
    {
        DB::table('company_users')
            ->where('user_id', $userId)
            ->where('company_id', '!=', $companyId)
            ->update(['is_primary' => false, 'updated_by' => $userId, 'updated_at' => now()]);
    }
}
