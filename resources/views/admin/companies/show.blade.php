<x-app-layout>
  <div class="max-w-4xl mx-auto px-4 py-6">
    <div class="flex items-center justify-between mb-6">
      <h1 class="text-2xl font-semibold">Company Details</h1>
      <div class="flex items-center gap-3">
        <a href="{{ route('companies.index') }}" class="text-sm text-gray-600 hover:underline">← Back</a>
        <a href="{{ route('companies.edit', $company) }}"
          class="text-sm px-3 py-1.5 rounded-md bg-indigo-600 text-white hover:bg-indigo-700">Edit</a>
      </div>
    </div>

    <div class="bg-white shadow-sm ring-1 ring-gray-200 rounded-lg p-6">
      <div class="flex items-start gap-6">
        <div>
          @if($company->logo_url)
          <img src="{{ $company->logo_url }}" class="h-24 w-24 rounded-lg object-cover ring-1 ring-gray-200" alt="logo">
          @else
          <div class="h-24 w-24 rounded-lg bg-gray-100 ring-1 ring-gray-200"></div>
          @endif
        </div>
        <div class="flex-1">
          <h2 class="text-xl font-semibold">{{ $company->name }}</h2>
          <div class="mt-2 flex flex-wrap gap-2">
            @php
            $badge = match($company->status){
            'active' => 'bg-green-100 text-green-700',
            'inactive' => 'bg-yellow-100 text-yellow-700',
            'suspended' => 'bg-red-100 text-red-700',
            default => 'bg-gray-100 text-gray-700'
            };
            @endphp
            <span class="px-2 py-1 rounded text-xs font-semibold {{ $badge }}">{{ ucfirst($company->status) }}</span>
            @if($company->website)
            <a href="{{ $company->website }}" target="_blank" class="text-sm text-indigo-600 hover:underline">{{
              $company->website }}</a>
            @endif
          </div>
        </div>
      </div>

      <dl class="mt-6 grid grid-cols-1 md:grid-cols-2 gap-x-8 gap-y-4">
        <div>
          <dt class="text-sm text-gray-500">Email</dt>
          <dd class="text-sm font-medium text-gray-900">{{ $company->email ?? '—' }}</dd>
        </div>
        <div>
          <dt class="text-sm text-gray-500">Phone</dt>
          <dd class="text-sm font-medium text-gray-900">{{ $company->phone ?? '—' }}</dd>
        </div>
        <div class="md:col-span-2">
          <dt class="text-sm text-gray-500">Address</dt>
          <dd class="text-sm font-medium text-gray-900">{{ $company->address ?? '—' }}</dd>
        </div>
        <div>
          <dt class="text-sm text-gray-500">Industry Type</dt>
          <dd class="text-sm font-medium text-gray-900">{{ $company->industry_type ?? '—' }}</dd>
        </div>
        <div>
          <dt class="text-sm text-gray-500">Registration No</dt>
          <dd class="text-sm font-medium text-gray-900">{{ $company->registration_no ?? '—' }}</dd>
        </div>
        <div class="md:col-span-2">
          <dt class="text-sm text-gray-500">Created</dt>
          <dd class="text-sm font-medium text-gray-900">
            {{ $company->created_at?->format('Y-m-d H:i') }} @if($company->creator) by {{ $company->creator->name }}
            @endif
          </dd>
        </div>
        <div class="md:col-span-2">
          <dt class="text-sm text-gray-500">Last Updated</dt>
          <dd class="text-sm font-medium text-gray-900">
            {{ $company->updated_at?->format('Y-m-d H:i') }} @if($company->updater) by {{ $company->updater->name }}
            @endif
          </dd>
        </div>
      </dl>
    </div>
  </div>
</x-app-layout>
