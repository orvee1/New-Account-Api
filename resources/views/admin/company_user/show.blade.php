<x-app-layout>
  <x-slot name="header">
    <div class="flex items-center justify-between">
      <h2 class="font-semibold text-xl text-gray-800 leading-tight">
        User Details
      </h2>
      <div class="flex items-center gap-3">
        <a href="{{ route('admin.company_user.index') }}" class="text-sm text-gray-600 hover:underline">← Back</a>
        <a href="{{ route('admin.company_user.edit', $companyUser) }}"
           class="text-sm px-3 py-1.5 rounded-md bg-indigo-600 text-white hover:bg-indigo-700">Edit</a>
      </div>
    </div>
  </x-slot>

  <div class="py-6">
    <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
      <div class="bg-white shadow-sm ring-1 ring-gray-200 rounded-lg p-6">
        <div class="flex items-start gap-6">
          <div>
            @if($companyUser->photo_url)
              <img src="{{ $companyUser->photo_url }}" class="h-24 w-24 rounded-lg object-cover ring-1 ring-gray-200" alt="photo">
            @else
              <div class="h-24 w-24 rounded-lg bg-gray-100 ring-1 ring-gray-200"></div>
            @endif
          </div>
          <div class="flex-1">
            <h2 class="text-xl font-semibold">{{ $companyUser->name }}</h2>
            <div class="mt-2 flex flex-wrap gap-2">
              @php
                $statusClr = $companyUser->status === 'active'
                    ? 'bg-green-100 text-green-700'
                    : 'bg-yellow-100 text-yellow-700';
                $roleClr = [
                  'owner' => 'bg-purple-100 text-purple-700',
                  'admin' => 'bg-blue-100 text-blue-700',
                  'accountant' => 'bg-amber-100 text-amber-700',
                  'viewer' => 'bg-slate-100 text-slate-700',
                ][$companyUser->role] ?? 'bg-gray-100 text-gray-700';
              @endphp
              <span class="px-2 py-1 rounded text-xs font-semibold {{ $roleClr }}">{{ ucfirst($companyUser->role) }}</span>
              <span class="px-2 py-1 rounded text-xs font-semibold {{ $statusClr }}">{{ ucfirst($companyUser->status) }}</span>
              @if($companyUser->is_primary)
                <span class="px-2 py-1 rounded text-xs font-semibold bg-indigo-100 text-indigo-700">Primary</span>
              @endif
            </div>
            <div class="mt-1 text-sm text-gray-600">
              Company: <span class="font-medium text-gray-900">{{ $companyUser->company?->name ?? '—' }}</span>
            </div>
          </div>
        </div>

        <dl class="mt-6 grid grid-cols-1 md:grid-cols-2 gap-x-8 gap-y-4">
          <div>
            <dt class="text-sm text-gray-500">Email</dt>
            <dd class="text-sm font-medium text-gray-900">{{ $companyUser->email ?? '—' }}</dd>
          </div>
          <div>
            <dt class="text-sm text-gray-500">Phone</dt>
            <dd class="text-sm font-medium text-gray-900">{{ $companyUser->phone_number }}</dd>
          </div>
          <div>
            <dt class="text-sm text-gray-500">Invited At</dt>
            <dd class="text-sm font-medium text-gray-900">{{ optional($companyUser->invited_at)->format('Y-m-d H:i') ?? '—' }}</dd>
          </div>
          <div>
            <dt class="text-sm text-gray-500">Joined At</dt>
            <dd class="text-sm font-medium text-gray-900">{{ optional($companyUser->joined_at)->format('Y-m-d H:i') ?? '—' }}</dd>
          </div>
          <div>
            <dt class="text-sm text-gray-500">Last Login</dt>
            <dd class="text-sm font-medium text-gray-900">{{ optional($companyUser->last_login_at)->format('Y-m-d H:i') ?? '—' }}</dd>
          </div>
          <div class="md:col-span-2">
            <dt class="text-sm text-gray-500">Permissions (JSON)</dt>
            <dd class="text-sm font-medium text-gray-900">
              <pre class="bg-gray-50 p-3 rounded ring-1 ring-gray-200 overflow-auto text-xs">
{{ json_encode($companyUser->permissions ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}
              </pre>
            </dd>
          </div>
        </dl>
      </div>
    </div>
  </div>
</x-app-layout>
