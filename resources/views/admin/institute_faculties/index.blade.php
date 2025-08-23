<x-app-layout>
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 py-6">
        {{-- Header --}}
        <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <h1 class="text-xl font-semibold text-gray-900">Institute Faculties</h1>

            <button onclick="openCreateModal()"
                class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-3 py-2 text-sm font-medium text-white hover:bg-indigo-700">
                + Add Faculty
            </button>
        </div>

        {{-- Filters --}}
        <form method="GET" id="filterForm" class="mb-4 flex justify-between items-center">
            <div>
                <select name="per_page"
                    class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                    @foreach([10,25,50,100] as $n)
                    <option value="{{ $n }}" {{ (int)($perPage ?? 10)===$n ? 'selected' : '' }}>{{ $n }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <input type="text" name="q" value="{{ $q ?? '' }}" placeholder="Search name"
                    class="block min-w-60 rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" />
            </div>
        </form>

        {{-- Table --}}
        <div class="bg-white rounded-xl shadow-sm ring-1 ring-gray-200">
            <div class="px-5 py-3 border-b border-gray-200 text-sm text-gray-600">
                Showing {{ $faculties->firstItem() ?? 0 }}–{{ $faculties->lastItem() ?? 0 }} of {{ $faculties->total()
                ?? 0 }}
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">ID</th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">Institute ID
                            </th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">Name</th>
                            <th class="px-4 py-3 text-center text-xs font-medium uppercase text-gray-500">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-100">
                        @forelse ($faculties as $faculty)
                        <tr>
                            <td class="px-4 py-3 text-sm text-gray-700">{{ $faculty->id }}</td>
                            <td class="px-4 py-3 text-sm text-gray-700">{{ $faculty->institute_id }}</td>
                            <td class="px-4 py-3 text-sm font-medium text-gray-900">{{ $faculty->name }}</td>
                            <td class="px-4 py-3 text-center">
                                <button
                                    onclick="openEditModal({{ $faculty->id }}, {{ $faculty->institute_id }}, '{{ $faculty->name }}')"
                                    class="bg-blue-600 px-3 py-1 text-white text-xs rounded-md hover:bg-blue-700">Edit</button>

                                <form action="{{ route('institute-faculties.destroy', $faculty->id) }}" method="POST"
                                    class="inline" onsubmit="return confirm('Are you sure?')">
                                    @csrf @method('DELETE')
                                    <button type="submit"
                                        class="bg-red-600 px-3 py-1 text-white text-xs rounded-md hover:bg-red-700">Delete</button>
                                </form>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="4" class="px-4 py-6 text-center text-sm text-gray-500">No entries found</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="px-5 py-3 border-t border-gray-200">
                {{ $faculties->onEachSide(1)->links() }}
            </div>
        </div>
    </div>

    {{-- Create/Edit Modal --}}
    <div id="facultyModal" class="hidden fixed inset-0 bg-gray-800 bg-opacity-50 flex items-center justify-center z-50">
        <div class="bg-white rounded-lg shadow-lg p-6 w-full max-w-md">
            <h2 id="modalTitle" class="text-lg font-semibold mb-4">Create Faculty</h2>

            <form id="facultyForm" method="POST">
                @csrf
                <input type="hidden" id="methodField" name="_method" value="POST">

                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700">Institute</label>
                    <select name="institute_id" id="institute_id" class="w-full border rounded p-2" required>
                        <option value="">-- Select Institute --</option>
                        @foreach($institutes as $ins)
                        <option value="{{ $ins->id }}">{{ $ins->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700">Name</label>
                    <input type="text" name="name" id="name"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm sm:text-sm" required>
                </div>

                <div class="flex justify-end gap-2">
                    <button type="button" onclick="closeModal()"
                        class="px-3 py-2 text-sm bg-gray-300 rounded-md">Cancel</button>
                    <button type="submit" class="px-3 py-2 text-sm bg-indigo-600 text-white rounded-md">Save</button>
                </div>
            </form>
        </div>
    </div>

    {{-- JS for Modal --}}
    <script>
        const modal = document.getElementById('facultyModal');
        const form = document.getElementById('facultyForm');
        const title = document.getElementById('modalTitle');
        const methodField = document.getElementById('methodField');
        const nameInput = document.getElementById('name');
        const instituteInput = document.getElementById('institute_id');

        function openCreateModal() {
            form.action = "{{ route('institute-faculties.store') }}";
            methodField.value = "POST";
            title.textContent = "Create Faculty";
            instituteInput.value = "";
            nameInput.value = "";
            modal.classList.remove('hidden');
        }

        function openEditModal(id, institute_id, name) {
            form.action = "/admin/institute-faculties/" + id;
            methodField.value = "PUT";
            title.textContent = "Edit Faculty";
            instituteInput.value = institute_id;
            nameInput.value = name;
            modal.classList.remove('hidden');
        }

        function closeModal() {
            modal.classList.add('hidden');
        }
    </script>
</x-app-layout>