<x-app-layout>
  <x-slot name="header">
    <h2 class="font-semibold text-xl text-gray-800 leading-tight">
      Create Company User
    </h2>
  </x-slot>

  <div class="py-6">
    <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
      @if ($errors->any())
        <div class="mb-4 rounded-md bg-red-50 p-4 text-red-700">
          <ul class="list-disc ml-5">
            @foreach ($errors->all() as $error) <li>{{ $error }}</li> @endforeach
          </ul>
        </div>
      @endif

      @include('admin.company_user._form', [
        'companyUser' => new \App\Models\CompanyUser,
        'companies'   => $companies,
        'roles'       => $roles,
        'statuses'    => $statuses,
        'action'      => route('admin.company_user.store'),
        'method'      => 'POST',
        'submitLabel' => 'Create User',
      ])
    </div>
  </div>
</x-app-layout>
