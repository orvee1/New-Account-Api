<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class CompanyUserController extends Controller
{
    public function index(Company $company)
    {
        $members = $company->users()
            ->withPivot('role','status','invited_at','joined_at','last_login_at','is_primary','permissions','notes','created_by','updated_by')
            ->get();

        return response()->json($members);
    }

    public function store(Request $request, Company $company)
    {
        $data = $request->validate([
            'user_id'     => ['required','exists:users,id'],
            'role'        => ['nullable', Rule::in(['owner','admin','accountant','viewer'])],
            'status'      => ['nullable', Rule::in(['active','inactive'])],
            'is_primary'  => ['nullable','boolean'],
            'permissions' => ['nullable','array'],
            'notes'       => ['nullable','string'],
            'invited_at'  => ['nullable','date'],
            'joined_at'   => ['nullable','date'],
        ]);

        $authId = $request->user()->id;

        return DB::transaction(function () use ($data, $company, $authId) {
            $payload = [
                'role'        => $data['role']        ?? 'viewer',
                'status'      => $data['status']      ?? 'active',
                'is_primary'  => (bool)($data['is_primary'] ?? false),
                'permissions' => $data['permissions'] ?? null,
                'notes'       => $data['notes']       ?? null,
                'invited_at'  => $data['invited_at']  ?? now(),
                'joined_at'   => $data['joined_at']   ?? now(),
                'created_by'  => $authId,
                'updated_by'  => $authId,
            ];

            $company->users()->syncWithoutDetaching([
                $data['user_id'] => $payload
            ]);

            if (!empty($payload['is_primary'])) {
                $this->ensureSinglePrimary($data['user_id'], $company->id, $authId);
            }

            $member = $company->users()->where('users.id', $data['user_id'])->first();
            return response()->json($member, 201);
        });
    }

    public function update(Request $request, Company $company, User $user)
    {
        $data = $request->validate([
            'role'          => ['sometimes', Rule::in(['owner','admin','accountant','viewer'])],
            'status'        => ['sometimes', Rule::in(['active','inactive'])],
            'is_primary'    => ['sometimes','boolean'],
            'permissions'   => ['sometimes','nullable','array'],
            'notes'         => ['sometimes','nullable','string'],
            'last_login_at' => ['sometimes','nullable','date'],
            'joined_at'     => ['sometimes','nullable','date'],
        ]);

        $authId = $request->user()->id;

        $exists = DB::table('company_users')
            ->where('company_id', $company->id)
            ->where('user_id', $user->id)
            ->exists();

        if (!$exists) {
            return response()->json(['message' => 'User is not a member of this company'], 404);
        }

        $updates = array_merge($data, [
            'updated_by' => $authId,
            'updated_at' => now(),
        ]);

        $company->users()->updateExistingPivot($user->id, $updates);

        if (array_key_exists('is_primary', $data) && $data['is_primary']) {
            $this->ensureSinglePrimary($user->id, $company->id, $authId);
        }

        $member = $company->users()->where('users.id', $user->id)->first();
        return response()->json($member);
    }

    public function destroy(Company $company, User $user)
    {
        $company->users()->detach($user->id);
        return response()->json(['message' => 'Member removed']);
    }

    public function setPrimary(Request $request, Company $company, User $user)
    {
        $authId = $request->user()->id;

        $exists = DB::table('company_users')
            ->where('company_id', $company->id)
            ->where('user_id', $user->id)
            ->exists();

        if (!$exists) {
            return response()->json(['message' => 'User is not a member of this company'], 404);
        }

        $this->ensureSinglePrimary($user->id, $company->id, $authId);

        $company->users()->updateExistingPivot($user->id, [
            'is_primary' => true,
            'updated_by' => $authId,
            'updated_at' => now(),
        ]);

        return response()->json(['message' => 'Primary company set']);
    }

    public function transferOwnership(Request $request, Company $company, User $user)
    {
        $company->users()->syncWithoutDetaching([
            $user->id => [
                'role'       => 'owner',
                'status'     => 'active',
                'joined_at'  => now(),
                'updated_by' => $request->user()->id,
            ],
        ]);

        $company->owner_id = $user->id;
        $company->updated_by = $request->user()->id;
        $company->save();

        return response()->json(['message' => 'Ownership transferred', 'company' => $company->fresh('owner')]);
    }

    private function ensureSinglePrimary(int $userId, int $companyId, int $actorId): void
    {
        DB::table('company_users')
            ->where('user_id', $userId)
            ->where('company_id', '!=', $companyId)
            ->update(['is_primary' => false, 'updated_by' => $actorId, 'updated_at' => now()]);
    }
}
