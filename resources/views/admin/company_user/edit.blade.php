<x-app-layout>
<div class="max-w-3xl mx-auto px-4 py-8">
  <div class="mb-6 flex items-center justify-between">
    <div>
      <h1 class="text-2xl font-bold">Edit Company User</h1>
      <p class="text-sm text-gray-500">Update user details, role, status, and permissions</p>
    </div>
    <a href="{{ route('company-users.show', $companyUser) }}" class="text-sm text-emerald-700 hover:underline">View Profile →</a>
  </div>

  @include('admin.company_user._form', [
    'route' => route('company-users.update', $companyUser),
    'method' => 'PUT',
    'companyUser' => $companyUser,
    'companies' => $companies,
    'roles' => $roles,
    'statuses' => $statuses
  ])
</div>
</x-app-layout>
