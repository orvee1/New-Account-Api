<x-app-layout>
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 py-6">

        {{-- Header + Create --}}
        <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <h1 class="text-xl font-semibold text-gray-900 flex items-center gap-2">
                <svg class="h-5 w-5 text-gray-700" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                    <path d="M4 6h16v2H4V6Zm0 5h16v2H4v-2Zm0 5h16v2H4v-2Z" />
                </svg>
                Institute Disciplines
            </h1>

            <button onclick="openCreateModal()"
                class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-3 py-2 text-sm font-medium text-white hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M11 11V5h2v6h6v2h-6v6h-2v-6H5v-2z" />
                </svg>
                Add Discipline
            </button>
        </div>

        {{-- Flash --}}
        @if(session('success'))
        <div class="mb-4 rounded-md bg-green-50 p-3 text-green-700 text-sm">
            {{ session('success') }}
        </div>
        @endif

        @php
        // Sort link helper (same style as your permission page)
        function sort_link2($label, $key, $currentSort, $currentDir) {
        $isActive = $currentSort === $key;
        $nextDir = ($isActive && $currentDir === 'asc') ? 'desc' : 'asc';
        $params = array_merge(request()->query(), ['sort' => $key, 'dir' => $nextDir]);
        $url = request()->url() . '?' . http_build_query($params);
        $arrow = $isActive ? ($currentDir === 'asc' ? '↑' : '↓') : '↕';
        return '<a href="'.$url.'" class="inline-flex items-center gap-1 hover:text-indigo-700">'.$label.' <span
                class="text-xs">'.$arrow.'</span></a>';
        }
        @endphp

        {{-- Filters (debounced search + per_page onchange) --}}
        <form method="GET" id="filterForm" class="mb-4 flex items-center justify-between">
            <div>
                <select name="per_page"
                    class="block w-28 rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                    @foreach([10,25,50,100] as $n)
                    <option value="{{ $n }}" {{ (int)($perPage ?? 10)===$n ? 'selected' : '' }}>{{ $n }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <input type="text" name="q" value="{{ $q ?? '' }}" placeholder="Search by name"
                    class="block min-w-60 rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" />
            </div>
        </form>

        {{-- Card + Table --}}
        <div class="bg-white rounded-xl shadow-sm ring-1 ring-gray-200">
            <div class="px-5 py-3 border-b border-gray-200">
                <p class="text-sm text-gray-600">
                    Showing {{ $disciplines->firstItem() ?? 0 }}–{{ $disciplines->lastItem() ?? 0 }} of {{
                    $disciplines->total() ?? 0 }}
                </p>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                                {!! sort_link2('ID','id',$sort,$dir) !!}
                            </th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                                {!! sort_link2('Institute ID','institute_id',$sort,$dir) !!}
                            </th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                                {!! sort_link2('Name','name',$sort,$dir) !!}
                            </th>
                            <th
                                class="px-4 py-3 text-center text-xs font-medium uppercase tracking-wider text-gray-500">
                                Actions
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-100">
                        @forelse ($disciplines as $discipline)
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-3 text-sm text-gray-700">{{ $discipline->id }}</td>
                            <td class="px-4 py-3 text-sm text-gray-700">{{ $discipline->institute_id }}</td>
                            <td class="px-4 py-3 text-sm font-medium text-gray-900">{{ $discipline->name }}</td>
                            <td class="px-4 py-3">
                                <div class="flex items-center justify-center gap-2">
                                    <button
                                        onclick="openEditModal({{ $discipline->id }}, {{ $discipline->institute_id }}, @js($discipline->name))"
                                        class="inline-flex items-center rounded-md bg-blue-600 px-2.5 py-1.5 text-xs font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500">
                                        Edit
                                    </button>

                                    <form action="{{ route('institute-disciplines.destroy', $discipline->id) }}"
                                        method="POST" onsubmit="return confirm('Are you sure?');" class="inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit"
                                            class="inline-flex items-center rounded-md bg-red-600 px-2.5 py-1.5 text-xs font-medium text-white hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500">
                                            Delete
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="4" class="px-4 py-6 text-center text-sm text-gray-500">
                                No entries found
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- Pagination --}}
            <div class="px-5 py-3 border-t border-gray-200">
                <div class="flex justify-end">
                    {{ $disciplines->onEachSide(1)->links() }}
                </div>
            </div>
        </div>
    </div>

    {{-- Create/Edit Modal --}}
    <div id="discModal" class="hidden fixed inset-0 bg-gray-800/60 flex items-center justify-center z-50">
        <div class="bg-white rounded-lg shadow-lg p-6 w-full max-w-md">
            <h2 id="discModalTitle" class="text-lg font-semibold mb-4">Create Discipline</h2>

            <form id="discForm" method="POST">
                @csrf
                <input type="hidden" id="discMethod" name="_method" value="POST">

                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700">Institute</label>
                    <select name="institute_id" id="disc_institute_id" class="w-full border rounded p-2" required>
                        <option value="">-- Select Institute --</option>
                        @foreach($institutes as $ins)
                        <option value="{{ $ins->id }}">{{ $ins->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="mb-6">
                    <label class="block text-sm font-medium text-gray-700">Name</label>
                    <input type="text" name="name" id="disc_name"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm sm:text-sm" required>
                </div>

                <div class="flex justify-end gap-2">
                    <button type="button" onclick="closeDiscModal()"
                        class="px-3 py-2 text-sm bg-gray-200 rounded-md">Cancel</button>
                    <button type="submit" class="px-3 py-2 text-sm bg-indigo-600 text-white rounded-md">Save</button>
                </div>
            </form>
        </div>
    </div>

    {{-- Debounce search + per_page onchange + modal JS --}}
    <script>
        (function () {
            const form = document.getElementById('filterForm');
            const qInput = form.querySelector('input[name="q"]');
            const perPageSelect = form.querySelector('select[name="per_page"]');

            let timer = null;
            qInput.addEventListener('input', function () {
                clearTimeout(timer);
                timer = setTimeout(() => {
                    if (form.requestSubmit) form.requestSubmit(); else form.submit();
                }, 500);
            });
            perPageSelect.addEventListener('change', function () {
                if (form.requestSubmit) form.requestSubmit(); else form.submit();
            });
        })();

        const discModal      = document.getElementById('discModal');
        const discForm       = document.getElementById('discForm');
        const discMethod     = document.getElementById('discMethod');
        const discTitle      = document.getElementById('discModalTitle');
        const instituteInput = document.getElementById('disc_institute_id');
        const nameInput      = document.getElementById('disc_name');

        function openCreateModal() {
            discForm.action    = "{{ route('institute-disciplines.store') }}";
            discMethod.value   = 'POST';
            discTitle.textContent = 'Create Discipline';
            instituteInput.value  = '';
            nameInput.value       = '';
            discModal.classList.remove('hidden');
        }

        function openEditModal(id, institute_id, name) {
            discForm.action    = "/admin/institute-disciplines/" + id;
            discMethod.value   = 'PUT';
            discTitle.textContent = 'Edit Discipline';
            instituteInput.value  = institute_id;
            nameInput.value       = name;
            discModal.classList.remove('hidden');
        }

        function closeDiscModal() {
            discModal.classList.add('hidden');
        }
    </script>
</x-app-layout>