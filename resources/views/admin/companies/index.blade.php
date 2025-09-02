<x-app-layout>
  <div class="max-w-7xl mx-auto px-4 py-6">
    <div class="flex items-center justify-between mb-6">
      <h1 class="text-2xl font-semibold">Companies</h1>
      <a href="{{ route('companies.create') }}"
        class="inline-flex items-center px-4 py-2 rounded-md bg-indigo-600 text-white hover:bg-indigo-700">
        + Create Company
      </a>
    </div>

    @if (session('success'))
    <div class="mb-4 rounded-md bg-green-50 p-4 text-green-700">
      {{ session('success') }}
    </div>
    @endif

    <form method="GET" action="{{ route('companies.index') }}" class="mb-4 grid grid-cols-1 md:grid-cols-4 gap-3">
      <input type="text" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Search name, email, phone..."
        class="w-full rounded-md border-gray-300 focus:border-indigo-500 focus:ring-indigo-500" />
      <select name="status" class="w-full rounded-md border-gray-300 focus:border-indigo-500 focus:ring-indigo-500">
        @php $status = $filters['status'] ?? 'all'; @endphp
        <option value="all" {{ $status==='all' ? 'selected' : '' }}>All Status</option>
        <option value="active" {{ $status==='active' ? 'selected' : '' }}>Active</option>
        <option value="inactive" {{ $status==='inactive' ? 'selected' : '' }}>Inactive</option>
        <option value="suspended" {{ $status==='suspended' ? 'selected' : '' }}>Suspended</option>
      </select>
      <select name="per_page" class="w-full rounded-md border-gray-300 focus:border-indigo-500 focus:ring-indigo-500">
        @foreach ([10,20,50,100] as $pp)
        <option value="{{ $pp }}" {{ ($filters['per_page'] ?? 20)==$pp ? 'selected' : '' }}>{{ $pp }} / page</option>
        @endforeach
      </select>
      <button
        class="inline-flex items-center justify-center rounded-md bg-gray-800 text-white px-4 py-2 hover:bg-gray-900">
        Filter
      </button>
    </form>

    <div class="overflow-x-auto bg-white shadow-sm ring-1 ring-gray-200 rounded-md">
      <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
          <tr class="text-left text-xs font-semibold uppercase tracking-wider text-gray-600">
            <th class="px-4 py-3">#</th>
            <th class="px-4 py-3">Logo</th>
            <th class="px-4 py-3">Name</th>
            <th class="px-4 py-3">Email</th>
            <th class="px-4 py-3">Phone</th>
            <th class="px-4 py-3">Industry</th>
            <th class="px-4 py-3">Reg No</th>
            <th class="px-4 py-3">Status</th>
            <th class="px-4 py-3 text-right">Actions</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
          @forelse ($companies as $company)
          <tr class="text-sm">
            <td class="px-4 py-3 text-gray-500">{{ $company->id }}</td>
            <td class="px-4 py-3">
              @if($company->logo_url)
              <img src="{{ $company->logo_url }}" alt="logo"
                class="h-8 w-8 rounded object-cover ring-1 ring-gray-200" />
              @else
              <div class="h-8 w-8 rounded bg-gray-100 ring-1 ring-gray-200"></div>
              @endif
            </td>
            <td class="px-4 py-3">
              <a href="{{ route('admin.companies.show', $company) }}" class="font-medium text-gray-900 hover:underline">
                {{ $company->name }}
              </a>
            </td>
            <td class="px-4 py-3 text-gray-700">{{ $company->email ?? '—' }}</td>
            <td class="px-4 py-3 text-gray-700">{{ $company->phone ?? '—' }}</td>
            <td class="px-4 py-3 text-gray-700">{{ $company->industry_type ?? '—' }}</td>
            <td class="px-4 py-3 text-gray-700">{{ $company->registration_no ?? '—' }}</td>
            <td class="px-4 py-3">
              @php
              $badge = match($company->status){
              'active' => 'bg-green-100 text-green-700',
              'inactive' => 'bg-yellow-100 text-yellow-700',
              'suspended' => 'bg-red-100 text-red-700',
              default => 'bg-gray-100 text-gray-700'
              };
              @endphp
              <span class="px-2 py-1 rounded text-xs font-semibold {{ $badge }}">{{ ucfirst($company->status) }}</span>
            </td>
            <td class="px-4 py-3">
              <div class="flex items-center justify-end gap-2">
                <a href="{{ route('companies.edit', $company) }}"
                  class="px-3 py-1.5 rounded-md bg-indigo-50 text-indigo-700 hover:bg-indigo-100">Edit</a>

                <form method="POST" action="{{ route('admin.companies.toggle-status', $company) }}">
                  @csrf
                  <button type="submit" class="px-3 py-1.5 rounded-md bg-slate-50 text-slate-700 hover:bg-slate-100">
                    Toggle
                  </button>
                </form>

                <form method="POST" action="{{ route('admin.companies.destroy', $company) }}"
                  onsubmit="return confirm('Delete this company? This action can be undone from Trash only if you keep files.');">
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
            <td colspan="9" class="px-4 py-10 text-center text-gray-500">No companies found.</td>
          </tr>
          @endforelse
        </tbody>
      </table>
    </div>

    <div class="mt-4">
      {{ $companies->links() }}
    </div>
  </div>
</x-app-layout>