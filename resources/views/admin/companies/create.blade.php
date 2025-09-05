<x-app-layout>
  <div class="max-w-3xl mx-auto px-4 py-6">
    <div class="flex items-center justify-between mb-6">
      <h1 class="text-2xl font-semibold">Create Company</h1>
      <a href="{{ route('companies.index') }}" class="inline-flex items-center px-4 py-2 text-sm font-medium text-white bg-green-600 rounded-lg shadow hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-1 transition">← Back to list</a>
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
    'company' => new \App\Models\Company,
    'action' => route('companies.store'),
    'method' => 'POST',
    'submitLabel' => 'Create Company',
    ])
  </div>
</x-app-layout>
