<x-app-layout>
<div class="max-w-3xl mx-auto px-4 py-8">
  <div class="mb-6">
    <h1 class="text-2xl font-bold">Add Company User</h1>
    <p class="text-sm text-gray-500">Create a user under a company with role & permissions</p>
  </div>

  @include('admin.company_user._form', [
    'route' => route('company-users.store'),
    'method' => 'POST',
    'companyUser' => null,
    'companies' => $companies,
    'roles' => $roles,
    'statuses' => $statuses
  ])
</div>
</x-app-layout>
