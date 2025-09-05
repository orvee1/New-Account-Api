<x-app-layout>
<div class="max-w-7xl mx-auto px-4 py-8">
  @if (session('success'))
    <div class="mb-4 rounded-md bg-emerald-50 border border-emerald-200 px-4 py-3 text-emerald-700">
      {{ session('success') }}
    </div>
  @endif
  @if ($errors->any())
    <div class="mb-4 rounded-md bg-red-50 border border-red-200 px-4 py-3 text-red-700">
      <ul class="list-disc pl-5">
        @foreach ($errors->all() as $e) <li>{{ $e }}</li> @endforeach
      </ul>
    </div>
  @endif

  <div class="flex items-center justify-between mb-6">
    <div>
      <h1 class="text-2xl font-bold">Company Users</h1>
      <p class="text-sm text-gray-500">Manage users per company, roles, and status</p>
    </div>
    <a href="{{ route('company-users.create') }}"
       class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-emerald-600 text-white hover:bg-emerald-700">
      <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M12 5v14M5 12h14" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
      Add User
    </a>
  </div>

  {{-- Filters --}}
  <form method="GET" class="mb-6">
    <div class="grid grid-cols-1 md:grid-cols-5 gap-3">
      <input type="text" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Search name/email/phone"
             class="px-3 py-2 rounded-lg border border-gray-300 focus:ring-2 focus:ring-emerald-500">

      <select name="company_id" class="px-3 py-2 rounded-lg border border-gray-300 focus:ring-2 focus:ring-emerald-500">
        <option value="">All Companies</option>
        @foreach ($companies as $c)
          <option value="{{ $c->id }}" @selected(($filters['company_id'] ?? '') == $c->id)>{{ $c->name }}</option>
        @endforeach
      </select>

      <select name="role" class="px-3 py-2 rounded-lg border border-gray-300 focus:ring-2 focus:ring-emerald-500">
        @php $roles = ['all' => 'All Roles', 'owner'=>'Owner','admin'=>'Admin','accountant'=>'Accountant','viewer'=>'Viewer']; @endphp
        @foreach ($roles as $val => $label)
          <option value="{{ $val }}" @selected(($filters['role'] ?? 'all') == $val)>{{ $label }}</option>
        @endforeach
      </select>

      <select name="status" class="px-3 py-2 rounded-lg border border-gray-300 focus:ring-2 focus:ring-emerald-500">
        @php $sts = ['all'=>'All Status','active'=>'Active','inactive'=>'Inactive']; @endphp
        @foreach ($sts as $val => $label)
          <option value="{{ $val }}" @selected(($filters['status'] ?? 'all') == $val)>{{ $label }}</option>
        @endforeach
      </select>

      <div class="flex gap-2">
        <select name="per_page" class="px-3 py-2 rounded-lg border border-gray-300 focus:ring-2 focus:ring-emerald-500">
          @foreach ([20,50,100,200] as $pp)
            <option value="{{ $pp }}" @selected(($filters['per_page'] ?? 20) == $pp)>{{ $pp }}/page</option>
          @endforeach
        </select>
        <button class="px-4 py-2 rounded-lg bg-gray-900 text-white hover:bg-black">Filter</button>
      </div>
    </div>
  </form>

  {{-- Table --}}
  <div class="bg-white rounded-2xl shadow border border-gray-100 overflow-hidden">
    <table class="min-w-full divide-y divide-gray-200">
      <thead class="bg-gray-50">
        <tr>
          <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">User</th>
          <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Company</th>
          <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Role</th>
          <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
          <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Primary</th>
          <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Joined</th>
          <th class="px-4 py-3"></th>
        </tr>
      </thead>
      <tbody class="divide-y divide-gray-100">
        @forelse ($users as $u)
          <tr class="hover:bg-gray-50">
            <td class="px-4 py-3">
              <div class="flex items-center gap-3">
                <div class="h-10 w-10 rounded-full bg-gray-100 overflow-hidden ring-1 ring-gray-200">
                  @if($u->photo_url)
                    <img src="{{ $u->photo_url }}" alt="{{ $u->name }}" class="h-full w-full object-cover">
                  @else
                    <div class="h-full w-full flex items-center justify-center text-gray-500 text-sm">
                      {{ strtoupper(substr($u->name,0,1)) }}
                    </div>
                  @endif
                </div>
                <div>
                  <a href="{{ route('company-users.show', $u) }}" class="font-semibold hover:underline">{{ $u->name }}</a>
                  <div class="text-xs text-gray-500">
                    @if($u->email) <span>{{ $u->email }}</span> · @endif
                    <span>{{ $u->phone_number }}</span>
                  </div>
                </div>
              </div>
            </td>
            <td class="px-4 py-3">{{ $u->company->name ?? '—' }}</td>
            <td class="px-4 py-3">
              <span class="px-2 py-1 rounded-full text-xs
                @class([
                  'bg-purple-100 text-purple-700'=> $u->role==='owner',
                  'bg-blue-100 text-blue-700'    => $u->role==='admin',
                  'bg-amber-100 text-amber-700'  => $u->role==='accountant',
                  'bg-gray-100 text-gray-700'    => $u->role==='viewer',
                ])">{{ ucfirst($u->role) }}</span>
            </td>
            <td class="px-4 py-3">
              <span class="px-2 py-1 rounded-full text-xs
                {{ $u->status==='active' ? 'bg-emerald-100 text-emerald-700' : 'bg-red-100 text-red-700' }}">
                {{ ucfirst($u->status) }}
              </span>
            </td>
            <td class="px-4 py-3">
              @if($u->is_primary)
                <span title="Primary user" class="inline-flex items-center gap-1 text-emerald-700">
                  ★ <span class="text-xs">Yes</span>
                </span>
              @else
                <span class="text-xs text-gray-400">No</span>
              @endif
            </td>
            <td class="px-4 py-3 text-sm text-gray-600">
              {{ $u->joined_at ? $u->joined_at->format('d M Y') : '—' }}
            </td>
            <td class="px-4 py-3">
              <div class="flex items-center gap-2 ">
                <a href="{{ route('company-users.edit', $u) }}"
                   class="px-3 py-1.5 text-sm rounded-lg border border-gray-300 hover:bg-gray-50">Edit</a>

                {{-- Toggle Status --}}
                <form action="{{ route('company-users.toggle-status', $u) }}" method="POST" onsubmit="return confirm('Change status?')">
                  @csrf
                  <button class="px-3 py-1.5 text-sm rounded-lg border border-gray-300 hover:bg-gray-50">
                    {{ $u->status === 'active' ? 'Deactivate' : 'Activate' }}
                  </button>
                </form>

                {{-- Make Primary --}}
                @unless($u->is_primary)
                  <form action="{{ route('company-users.make-primary', $u) }}" method="POST">
                    @csrf
                    <button class="px-3 py-1.5 text-sm rounded-lg border border-emerald-300 text-emerald-700 hover:bg-emerald-50">
                      Make Primary
                    </button>
                  </form>
                @endunless

                {{-- Delete --}}
                <form action="{{ route('company-users.destroy', $u) }}" method="POST"
                      onsubmit="return confirm('Delete this user? This cannot be undone.');">
                  @csrf @method('DELETE')
                  <button class="px-3 py-1.5 text-sm rounded-lg border border-red-300 text-red-700 hover:bg-red-50">
                    Delete
                  </button>
                </form>
              </div>
            </td>
          </tr>
        @empty
          <tr><td colspan="7" class="px-4 py-10 text-center text-gray-500">No users found.</td></tr>
        @endforelse
      </tbody>
    </table>
  </div>

  <div class="mt-6">
    {{ $users->links() }}
  </div>
</div>
</x-app-layout>
