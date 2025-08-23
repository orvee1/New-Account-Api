<x-app-layout>
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 py-6">
        {{-- Header --}}
        <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <h1 class="text-xl font-semibold text-gray-900">Courses</h1>

            <button onclick="openCreateModal()"
                class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-3 py-2 text-sm font-medium text-white hover:bg-indigo-700">
                + Add Course
            </button>
        </div>

        {{-- Filters --}}
        <form method="GET" id="filterForm" class="mb-4 flex justify-between gap-3 items-center">
            {{-- Per page --}}
            <div>
                <select name="per_page"
                    class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                    onchange="this.form.submit()">
                    @foreach([10,25,50,100] as $n)
                    <option value="{{ $n }}" {{ (int)request('per_page', 10)===$n ? 'selected' : '' }}>
                        {{ $n }}
                    </option>
                    @endforeach
                </select>
            </div>

            {{-- Institute --}}
            <div class="flex items-center gap-3">
                <div>
                    <select name="institute_id"
                        class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                        onchange="this.form.submit()">
                        <option value="">All Institutes</option>
                        @foreach($institutes as $ins)
                        <option value="{{ $ins->id }}" {{ (string)($filters['institute_id'] ?? '' )===(string)$ins->id ?
                            'selected' : '' }}>
                            {{ $ins->name }}
                        </option>
                        @endforeach
                    </select>
                </div>
                {{-- Search --}}
                <div>
                    <input type="text" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Search by name"
                        class="block min-w-60 rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" />
                </div>
            </div>

        </form>

        {{-- Table --}}
        <div class="bg-white rounded-xl shadow-sm ring-1 ring-gray-200">
            <div class="px-5 py-3 border-b border-gray-200 text-sm text-gray-600">
                Showing {{ $courses->firstItem() ?? 0 }}–{{ $courses->lastItem() ?? 0 }} of {{ $courses->total() ?? 0 }}
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">ID</th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">Institute</th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">Course Name</th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">bKash Merchant
                            </th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">Status</th>
                            <th class="px-4 py-3 text-center text-xs font-medium uppercase text-gray-500">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-100">
                        @forelse ($courses as $course)
                        <tr>
                            <td class="px-4 py-3 text-sm text-gray-700">{{ $course->id }}</td>
                            <td class="px-4 py-3 text-sm text-gray-700">{{ $course->institute->name ??
                                ('#'.$course->institute_id) }}</td>
                            <td class="px-4 py-3 text-sm font-medium text-gray-900">{{ $course->name }}</td>
                            <td class="px-4 py-3 text-sm text-gray-700">{{ $course->bkash_merchant_number ?? '—' }}</td>
                            <td class="px-4 py-3">
                                @if((int)($course->status ?? 0) === 1)
                                <span
                                    class="inline-flex items-center rounded-full bg-emerald-50 px-2 py-0.5 text-xs font-medium text-emerald-700 ring-1 ring-inset ring-emerald-600/20">Active</span>
                                @else
                                <span
                                    class="inline-flex items-center rounded-full bg-rose-50 px-2 py-0.5 text-xs font-medium text-rose-700 ring-1 ring-inset ring-rose-600/20">Inactive</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-center">
                                <button onclick="openEditModal({{ json_encode([
                                            'id' => $course->id,
                                            'institute_id' => $course->institute_id,
                                            'name' => $course->name,
                                            'bkash_merchant_number' => $course->bkash_merchant_number,
                                            'status' => (int)($course->status ?? 0),
                                        ]) }})"
                                    class="bg-blue-600 px-3 py-1 text-white text-xs rounded-md hover:bg-blue-700">Edit</button>

                                <form action="{{ route('courses.destroy', $course->id) }}" method="POST" class="inline"
                                    onsubmit="return confirm('Are you sure?')">
                                    @csrf @method('DELETE')
                                    <button type="submit"
                                        class="bg-red-600 px-3 py-1 text-white text-xs rounded-md hover:bg-red-700">Delete</button>
                                </form>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="px-4 py-6 text-center text-sm text-gray-500">No entries found</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="px-5 py-3 border-t border-gray-200">
                {{ $courses->onEachSide(1)->links() }}
            </div>
        </div>
    </div>

    {{-- Create/Edit Modal --}}
    <div id="courseModal" class="hidden fixed inset-0 bg-gray-800 bg-opacity-50 flex items-center justify-center z-50">
        <div class="bg-white rounded-lg shadow-lg p-6 w-full max-w-md">
            <h2 id="modalTitle" class="text-lg font-semibold mb-4">Create Course</h2>

            <form id="courseForm" method="POST">
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
                    <label class="block text-sm font-medium text-gray-700">Course Name</label>
                    <input type="text" name="name" id="name"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm sm:text-sm" required>
                </div>

                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700">bKash Merchant Number</label>
                    <input type="text" name="bkash_merchant_number" id="bkash_merchant_number"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm sm:text-sm"
                        placeholder="Optional">
                </div>

                <div class="mb-5">
                    <label class="inline-flex items-center gap-2 text-lg text-gray-700 cursor-pointer">
                        <input type="checkbox" id="status" name="status" value="1"
                            class="h-5 w-5 rounded border-gray-300" checked>
                        <span class="text-indigo-600">Active</span>
                    </label>
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
        const modal        = document.getElementById('courseModal');
        const form         = document.getElementById('courseForm');
        const title        = document.getElementById('modalTitle');
        const methodField  = document.getElementById('methodField');

        const instituteInp = document.getElementById('institute_id');
        const nameInp      = document.getElementById('name');
        const bkashInp     = document.getElementById('bkash_merchant_number');
        const statusChk    = document.getElementById('status');

        function openCreateModal() {
            form.action       = "{{ route('courses.store') }}";
            methodField.value = "POST";
            title.textContent = "Create Course";

            instituteInp.value = "";
            nameInp.value      = "";
            bkashInp.value     = "";
            statusChk.checked  = true;

            modal.classList.remove('hidden');
        }

        function openEditModal(data) {
            form.action       = "/admin/courses/" + data.id;
            methodField.value = "PUT";
            title.textContent = "Edit Course";

            instituteInp.value = data.institute_id ?? "";
            nameInp.value      = data.name ?? "";
            bkashInp.value     = data.bkash_merchant_number ?? "";
            statusChk.checked  = (parseInt(data.status ?? 0, 10) === 1);

            modal.classList.remove('hidden');
        }

        function closeModal() {
            modal.classList.add('hidden');
        }
    </script>
</x-app-layout>