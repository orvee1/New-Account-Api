<x-app-layout>
<div class="max-w-5xl mx-auto px-4 py-8">
  <div class="mb-6 flex items-center justify-between">
    <div>
      <h1 class="text-2xl font-bold">User Profile</h1>
      <p class="text-sm text-gray-500">{{ $companyUser->company->name ?? '—' }}</p>
    </div>
    <div class="flex items-center gap-2">
      <a href="{{ route('company-users.edit', $companyUser) }}"
         class="px-4 py-2 rounded-lg border border-gray-300 hover:bg-gray-50">Edit</a>
      <a href="{{ route('company-users.index') }}"
         class="px-4 py-2 rounded-lg bg-gray-900 text-white hover:bg-black">Back to list</a>
    </div>
  </div>

  <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
    <div class="md:col-span-1">
      <div class="bg-white rounded-2xl shadow border border-gray-100 p-6 text-center">
        <div class="mx-auto h-24 w-24 rounded-full overflow-hidden ring-1 ring-gray-200 bg-gray-100">
          @if($companyUser->photo_url)
            <img src="{{ $companyUser->photo_url }}" class="h-full w-full object-cover" alt="{{ $companyUser->name }}">
          @else
            <div class="h-full w-full flex items-center justify-center text-2xl text-gray-500">
              {{ strtoupper(substr($companyUser->name,0,1)) }}
            </div>
          @endif
        </div>
        <h2 class="mt-4 text-lg font-semibold">{{ $companyUser->name }}</h2>
        <div class="mt-1 text-sm text-gray-500">{{ $companyUser->email ?? '—' }}</div>
        <div class="mt-1 text-sm text-gray-500">{{ $companyUser->phone_number ?? '—' }}</div>

        <div class="mt-4 flex items-center justify-center gap-2">
          <span class="px-2 py-1 rounded-full text-xs
            @class([
              'bg-purple-100 text-purple-700'=> $companyUser->role==='owner',
              'bg-blue-100 text-blue-700'    => $companyUser->role==='admin',
              'bg-amber-100 text-amber-700'  => $companyUser->role==='accountant',
              'bg-gray-100 text-gray-700'    => $companyUser->role==='viewer',
            ])">{{ ucfirst($companyUser->role) }}</span>

          <span class="px-2 py-1 rounded-full text-xs
            {{ $companyUser->status==='active' ? 'bg-emerald-100 text-emerald-700' : 'bg-red-100 text-red-700' }}">
            {{ ucfirst($companyUser->status) }}
          </span>

          @if($companyUser->is_primary)
            <span class="px-2 py-1 rounded-full text-xs bg-emerald-50 text-emerald-700">Primary</span>
          @endif
        </div>
      </div>
    </div>

    <div class="md:col-span-2">
      <div class="bg-white rounded-2xl shadow border border-gray-100 p-6 space-y-6">
        <div>
          <h3 class="text-sm font-semibold text-gray-600">Timestamps</h3>
          <div class="mt-2 grid grid-cols-1 sm:grid-cols-2 gap-3 text-sm">
            <div class="flex justify-between border rounded-lg px-3 py-2">
              <span class="text-gray-500">Invited At</span>
              <span>{{ $companyUser->invited_at?->format('d M Y, h:i A') ?? '—' }}</span>
            </div>
            <div class="flex justify-between border rounded-lg px-3 py-2">
              <span class="text-gray-500">Joined At</span>
              <span>{{ $companyUser->joined_at?->format('d M Y, h:i A') ?? '—' }}</span>
            </div>
            <div class="flex justify-between border rounded-lg px-3 py-2">
              <span class="text-gray-500">Last Login</span>
              <span>{{ $companyUser->last_login_at?->format('d M Y, h:i A') ?? '—' }}</span>
            </div>
            <div class="flex justify-between border rounded-lg px-3 py-2">
              <span class="text-gray-500">Created</span>
              <span>{{ $companyUser->created_at?->format('d M Y, h:i A') ?? '—' }}</span>
            </div>
            <div class="flex justify-between border rounded-lg px-3 py-2">
              <span class="text-gray-500">Updated</span>
              <span>{{ $companyUser->updated_at?->format('d M Y, h:i A') ?? '—' }}</span>
            </div>
          </div>
        </div>

        <div>
          <h3 class="text-sm font-semibold text-gray-600">Permissions</h3>
          @php
            $perms = is_array($companyUser->permissions ?? null) ? $companyUser->permissions : null;
          @endphp
          @if($perms && count($perms))
            <div class="mt-2 grid grid-cols-1 sm:grid-cols-2 gap-2">
              @foreach($perms as $key => $val)
                <div class="flex items-center justify-between border rounded-lg px-3 py-2">
                  <span class="text-sm text-gray-700">{{ $key }}</span>
                  <span class="text-xs px-2 py-0.5 rounded-full {{ ($val===true || $val==='1' || $val===1 || $val==='true') ? 'bg-emerald-100 text-emerald-700' : 'bg-red-100 text-red-700' }}">
                    {{ ($val===true || $val==='1' || $val===1 || $val==='true') ? 'Allow' : 'Deny' }}
                  </span>
                </div>
              @endforeach
            </div>
          @else
            <p class="mt-2 text-sm text-gray-500">No explicit permissions set.</p>
          @endif
        </div>

        <div class="flex items-center gap-2">
          <form action="{{ route('company-users.toggle-status', $companyUser) }}" method="POST"
                onsubmit="return confirm('Change status?')">
            @csrf
            <button class="px-4 py-2 rounded-lg border border-gray-300 hover:bg-gray-50">
              {{ $companyUser->status === 'active' ? 'Deactivate' : 'Activate' }}
            </button>
          </form>

          @unless($companyUser->is_primary)
            <form action="{{ route('company-users.make-primary', $companyUser) }}" method="POST">
              @csrf
              <button class="px-4 py-2 rounded-lg border border-emerald-300 text-emerald-700 hover:bg-emerald-50">
                Make Primary
              </button>
            </form>
          @endunless

          <form action="{{ route('company-users.destroy', $companyUser) }}" method="POST"
                onsubmit="return confirm('Delete this user? This cannot be undone.')">
            @csrf @method('DELETE')
            <button class="px-4 py-2 rounded-lg border border-red-300 text-red-700 hover:bg-red-50">
              Delete
            </button>
          </form>
        </div>
      </div>
    </div>
  </div>
</div>
</x-app-layout>
