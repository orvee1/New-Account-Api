<x-app-layout>
  <x-slot name="header">
    <div class="flex items-center justify-between">
      <h2 class="font-semibold text-xl text-gray-800 leading-tight">
        Edit Company User
      </h2>
      <div class="flex items-center gap-3">
        <a href="{{ route('admin.company_user.index') }}" class="text-sm text-gray-600 hover:underline">← Back</a>

        <form method="POST" action="{{ route('admin.company_user.destroy', $companyUser) }}"
              onsubmit="return confirm('Delete this user?');">
          @csrf @method('DELETE')
          <button type="submit" class="text-sm px-3 py-1.5 rounded-md bg-red-50 text-red-700 hover:bg-red-100">
            Delete
          </button>
        </form>
      </div>
    </div>
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

      @if (session('success'))
        <div class="mb-4 rounded-md bg-green-50 p-4 text-green-700">{{ session('success') }}</div>
      @endif

      @include('admin.company_user._form', [
        'companyUser' => $companyUser,
        'companies'   => $companies,
        'roles'       => $roles,
        'statuses'    => $statuses,
        'action'      => route('admin.company_user.update', $companyUser),
        'method'      => 'PUT',
        'submitLabel' => 'Update User',
      ])
    </div>
  </div>
</x-app-layout>
