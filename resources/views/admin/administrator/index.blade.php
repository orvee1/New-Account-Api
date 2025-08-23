<x-app-layout>
    <div class="bg-white w-full rounded shadow">
        <div class="p-4 border-b border-gray-200 flex flex-wrap items-center justify-between gap-4 text-sm">
            <div class="flex items-center gap-2">
                <h2 class="text-base font-semibold flex items-center gap-2">
                    <i class="fa fa-globe"></i> Administrator List
                </h2>
                @php $type = request()->type ?? null; @endphp

                <div class="flex flex-wrap items-center gap-2">
                    <a href="{{ route('administrator.create') }}"
                        class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-3 py-2 text-sm font-medium text-white hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M11 11V5h2v6h6v2h-6v6h-2v-6H5v-2z" />
                        </svg>

                    </a>
                    @if (!$type)
                    @foreach (['Administrator', 'Admin', 'Developer'] as $role)
                    <a href="{{ url('admin/administrator') }}?type={{ $role }}"
                        class="px-3 py-1 rounded text-white bg-sky-500 hover:bg-sky-600 text-xs">
                        {{ $role }}
                    </a>
                    @endforeach
                    @else
                    <a href="{{ url('admin/administrator') }}"
                        class="px-3 py-1 rounded text-white bg-sky-500 hover:bg-sky-600 text-xs">
                        Go to administrator list
                    </a>
                    @endif

                    <a href="{{ url('admin/admin-device-log') }}"
                        class="px-3 py-1 rounded text-white bg-yellow-500 hover:bg-yellow-600 text-xs">
                        Admin Device Log
                    </a>
                </div>
            </div>

            {{-- Search Form --}}
            <div class="flex gap-2">
                <input type="text" id="localSearchInput" placeholder="Search by name/email/phone..."
                    class="px-3 py-1 rounded border border-gray-300 text-sm focus:outline-none focus:ring focus:border-sky-500">
            </div>
        </div>


        <div class="overflow-x-auto">
            <table id="userTable" class="min-w-full divide-y divide-gray-200 text-sm text-left">
                <thead class="bg-gray-100 text-gray-700">
                    <tr>
                        <th class="p-3">ID</th>
                        <th class="p-3">Name</th>
                        <th class="p-3">User Email</th>
                        <th class="p-3">User Phone</th>
                        <th class="p-3">Roles</th>
                        <th class="p-3">Status</th>
                        <th class="p-3">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @foreach ($users as $user)
                    <tr class="hover:bg-gray-50">
                        <td class="p-3">{{ $user->id }}</td>
                        <td class="p-3">
                            <a href="{{ url('admin/administrator/' . $user->id) }}"
                                class="text-sky-600 hover:underline">
                                {{ $user->name }}
                                @if ($user->id === auth()->id())
                                <span class="text-red-500">(You)</span>
                                @endif
                            </a>
                        </td>
                        <td class="p-3">{{ $user->email }}</td>
                        <td class="p-3">{{ $user->phone_number }}</td>
                        <td class="p-3">
                            <div class="flex flex-wrap gap-1">
                                @foreach ($user->roles->sortBy('name') as $role)
                                <span class="bg-gray-200 px-2 py-1 rounded text-xs">
                                    {{ $role->name ?? '' }}
                                </span>
                                @endforeach
                            </div>
                        </td>
                        <td class="p-3">
                            <span class="{{ $user->status == 1 ? 'text-green-600' : 'text-red-600' }}">
                                {{ $user->status == 1 ? 'Active' : 'Inactive' }}
                            </span>
                        </td>
                        <td class="p-3 space-x-1">
                            <a href="{{ url('admin/administrator/' . $user->id . '/edit') }}"
                                class="bg-sky-500 text-white px-2 py-1 rounded text-xs hover:bg-sky-600">
                                Edit
                            </a>

                            @if ($is_device_accept_permission)
                            <a href="{{ url('/admin/admin-device-log') }}/{{ $user->id }}"
                                class="bg-yellow-500 text-white px-2 py-1 rounded text-xs hover:bg-yellow-600">
                                Device
                            </a>
                            @endif

                            <form method="POST" style="display:inline"
                                action="{{ route('administrator.destroy', $user->id) }}">
                                @csrf
                                @method('DELETE')
                                <button type="submit" onclick="return confirm('Are You Sure ?')"
                                    class="bg-red-500 text-white px-2 py-1 rounded text-xs hover:bg-red-600">
                                    Delete
                                </button>
                            </form>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    <script type="text/javascript">
        console.log(111);
    document.addEventListener('DOMContentLoaded', function() {
            const searchInput = document.getElementById('localSearchInput');
            const table = document.getElementById('userTable'); // <table id="userTable">
            const rows = table.querySelectorAll('tbody tr');
            console.log(1112);

            searchInput.addEventListener('keyup', function() {
                const value = this.value.toLowerCase().trim();
                console.log(value);
                rows.forEach(function(row) {
                    const text = row.textContent.toLowerCase();
                    if (text.includes(value)) {
                        row.classList.remove('hidden');
                    } else {
                        row.classList.add('hidden');
                    }
                });
            });
        });
    </script>
</x-app-layout>