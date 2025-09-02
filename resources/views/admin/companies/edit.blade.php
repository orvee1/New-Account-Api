<x-app-layout>
  <div class="max-w-3xl mx-auto px-4 py-6">
    <div class="flex items-center justify-between mb-6">
      <h1 class="text-2xl font-semibold">Edit Company</h1>
      <div class="flex items-center gap-3">
        <a href="{{ route('companies.index') }}" class="text-sm text-gray-600 hover:underline">← Back</a>

        <form method="POST" action="{{ route('admin.companies.destroy', $company) }}"
          onsubmit="return confirm('Delete this company?');">
          @csrf @method('DELETE')
          <button type="submit" class="text-sm px-3 py-1.5 rounded-md bg-red-50 text-red-700 hover:bg-red-100">
            Delete
          </button>
        </form>
      </div>
    </div>

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

    @include('admin.companies._form', [
    'company' => $company,
    'action' => route('admin.companies.update', $company),
    'method' => 'PUT',
    'submitLabel' => 'Update Company',
    ])
  </div>
</x-app-layout>