<x-app-layout>
  <x-slot name="header">
    <h2 class="font-semibold text-xl text-gray-800 leading-tight">
      Company Users
    </h2>
  </x-slot>

  <div class="py-6">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
      @if (session('success'))
        <div class="mb-4 rounded-md bg-green-50 p-4 text-green-700">
          {{ session('success') }}
        </div>
      @endif

      <div class="flex items-center justify-between mb-4">
        <form method="GET" action="{{ route('admin.company_user.index') }}" class="w-full">
          <div class="grid grid-cols-1 md:grid-cols-6 gap-3">
            <input type="text" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Search name, email, phone…"
                   class="w-full rounded-md border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 md:col-span-2"/>

            <select name="company_id" class="w-full rounded-md border-gray-300 focus:border-indigo-500 focus:ring-indigo-500">
              <option value="">All Companies</option>
              @foreach ($companies as $c)
                <option value="{{ $c->id }}" @selected(($filters['company_id'] ?? '') == $c->id)>{{ $c->name }}</option>
              @endforeach
            </select>

            @php $role = $filters['role'] ?? 'all'; @endphp
            <select name="role" class="w-full rounded-md border-gray-300 focus:border-indigo-500 focus:ring-indigo-500">
              <option value="all" @selected($role==='all')>All Roles</option>
              <option value="owner" @selected($role==='owner')>Owner</option>
              <option value="admin" @selected($role==='admin')>Admin</option>
              <option value="accountant" @selected($role==='accountant')>Accountant</option>
              <option value="viewer" @selected($role==='viewer')>Viewer</option>
            </select>

            @php $status = $filters['status'] ?? 'all'; @endphp
            <select name="status" class="w-full rounded-md border-gray-300 focus:border-indigo-500 focus:ring-indigo-500">
              <option value="all" @selected($status==='all')>All Status</option>
              <option value="active" @selected($status==='active')>Active</option>
              <option value="inactive" @selected($status==='inactive')>Inactive</option>
            </select>

            <select name="per_page" class="w-full rounded-md border-gray-300 focus:border-indigo-500 focus:ring-indigo-500">
              @foreach ([10,20,50,100] as $pp)
                <option value="{{ $pp }}" @selected(($filters['per_page'] ?? 20) == $pp)>{{ $pp }} / page</option>
              @endforeach
            </select>

            <button class="inline-flex items-center justify-center rounded-md bg-gray-800 text-white px-4 py-2 hover:bg-gray-900">
              Filter
            </button>
          </div>
        </form>

        <a href="{{ route('admin.company_user.create') }}"
           class="ml-4 inline-flex items-center px-4 py-2 rounded-md bg-indigo-600 text-white hover:bg-indigo-700">
          + Create
        </a>
      </div>

      <div class="bg-white overflow-x-auto shadow-sm sm:rounded-lg ring-1 ring-gray-200">
        <table class="min-w-full divide-y divide-gray-200">
          <thead class="bg-gray-50">
            <tr class="text-left text-xs font-semibold uppercase tracking-wider text-gray-600">
              <th class="px-4 py-3">#</th>
              <th class="px-4 py-3">Photo</th>
              <th class="px-4 py-3">Name</th>
              <th class="px-4 py-3">Email</th>
              <th class="px-4 py-3">Phone</th>
              <th class="px-4 py-3">Company</th>
              <th class="px-4 py-3">Role</th>
              <th class="px-4 py-3">Status</th>
              <th class="px-4 py-3 text-right">Actions</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-100">
            @forelse ($users as $u)
              <tr class="text-sm">
                <td class="px-4 py-3 text-gray-500">{{ $u->id }}</td>
                <td class="px-4 py-3">
                  @if($u->photo_url)
                    <img src="{{ $u->photo_url }}" class="h-8 w-8 rounded-full object-cover ring-1 ring-gray-200" alt="">
                  @else
                    <div class="h-8 w-8 rounded-full bg-gray-100 ring-1 ring-gray-200"></div>
                  @endif
                </td>
                <td class="px-4 py-3">
                  <a href="{{ route('admin.company_user.show', $u) }}" class="font-medium text-gray-900 hover:underline">
                    {{ $u->name }}
                  </a>
                  @if($u->is_primary)
                    <span class="ml-2 text-[10px] px-2 py-0.5 rounded bg-indigo-100 text-indigo-700 font-semibold">Primary</span>
                  @endif
                </td>
                <td class="px-4 py-3 text-gray-700">{{ $u->email ?? '—' }}</td>
                <td class="px-4 py-3 text-gray-700">{{ $u->phone_number }}</td>
                <td class="px-4 py-3 text-gray-700">{{ $u->company?->name ?? '—' }}</td>
                <td class="px-4 py-3">
                  @php
                    $roleClr = [
                      'owner' => 'bg-purple-100 text-purple-700',
                      'admin' => 'bg-blue-100 text-blue-700',
                      'accountant' => 'bg-amber-100 text-amber-700',
                      'viewer' => 'bg-slate-100 text-slate-700',
                    ][$u->role] ?? 'bg-gray-100 text-gray-700';
                  @endphp
                  <span class="px-2 py-1 rounded text-xs font-semibold {{ $roleClr }}">{{ ucfirst($u->role) }}</span>
                </td>
                <td class="px-4 py-3">
                  @php
                    $statusClr = $u->status === 'active'
                        ? 'bg-green-100 text-green-700'
                        : 'bg-yellow-100 text-yellow-700';
                  @endphp
                  <span class="px-2 py-1 rounded text-xs font-semibold {{ $statusClr }}">{{ ucfirst($u->status) }}</span>
                </td>
                <td class="px-4 py-3">
                  <div class="flex items-center justify-end gap-2">
                    <a href="{{ route('admin.company_user.edit', $u) }}"
                       class="px-3 py-1.5 rounded-md bg-indigo-50 text-indigo-700 hover:bg-indigo-100">Edit</a>

                    <form method="POST" action="{{ route('admin.company_user.toggle-status', $u) }}">
                      @csrf
                      <button type="submit" class="px-3 py-1.5 rounded-md bg-slate-50 text-slate-700 hover:bg-slate-100">
                        Toggle
                      </button>
                    </form>

                    @unless($u->is_primary)
                      <form method="POST" action="{{ route('admin.company_user.make-primary', $u) }}">
                        @csrf
                        <button type="submit" class="px-3 py-1.5 rounded-md bg-purple-50 text-purple-700 hover:bg-purple-100">
                          Make Primary
                        </button>
                      </form>
                    @endunless

                    <form method="POST" action="{{ route('admin.company_user.destroy', $u) }}"
                          onsubmit="return confirm('Delete this user?');">
                      @csrf @method('DELETE')
                      <button type="submit" class="px-3 py-1.5 rounded-md bg-red-50 text-red-700 hover:bg-red-100">
                        Delete
                      </button>
                    </form>
                  </div>
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="9" class="px-4 py-10 text-center text-gray-500">No users found.</td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>

      <div class="mt-4">
        {{ $users->links() }}
      </div>
    </div>
  </div>
</x-app-layout>
