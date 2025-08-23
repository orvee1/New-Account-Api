{{-- resources/views/admin/modules/index.blade.php --}}
<x-app-layout>
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 py-6">
        {{-- Header --}}
        <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <h1 class="text-xl font-semibold text-gray-900">Modules</h1>

            <button onclick="openCreateModal()"
                class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-3 py-2 text-sm font-medium text-white hover:bg-indigo-700">
                + Add Module
            </button>
        </div>

        {{-- Flash --}}
        @if(session('success'))
        <div class="mb-4 rounded-lg bg-emerald-50 text-emerald-800 px-4 py-2 text-sm">
            {{ session('success') }}
        </div>
        @endif
        @if ($errors->any())
        <div class="mb-4 rounded-lg bg-rose-50 text-rose-800 px-4 py-2 text-sm">
            <ul class="list-disc list-inside">
                @foreach ($errors->all() as $e)
                <li>{{ $e }}</li>
                @endforeach
            </ul>
        </div>
        @endif

        {{-- Filters --}}
        <form method="GET" id="filterForm" class="mb-4 flex justify-between items-center">
            {{-- Per page --}}
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Per Page</label>
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
            <div class="flex items-center gap-4">

                {{-- Course (filtered by institute client-side) --}}
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Course</label>
                    <select name="course_id" id="filter_course_id"
                        class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                        onchange="this.form.submit()">
                        <option value="">All Courses</option>
                        @foreach($courses as $c)
                        <option value="{{ $c->id }}" data-institute="{{ $c->institute_id }}" {{
                            (string)($filters['course_id'] ?? '' )===(string)$c->id ? 'selected' : '' }}>
                            {{ $c->name }}
                        </option>
                        @endforeach
                    </select>
                </div>

                {{-- Search --}}
                <div class="lg:col-span-1">
                    <label class="block text-xs font-medium text-gray-600 mb-1">Search</label>
                    <input type="text" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Module or Course"
                        class="block min-w-60 rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" />
                </div>
            </div>
        </form>

        {{-- Table --}}
        <div class="bg-white rounded-xl shadow-sm ring-1 ring-gray-200">
            <div class="px-5 py-3 border-b border-gray-200 text-sm text-gray-600">
                Showing {{ $modules->firstItem() ?? 0 }}–{{ $modules->lastItem() ?? 0 }} of {{ $modules->total() ?? 0 }}
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">ID</th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">Module</th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">Course</th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">Course Package
                            </th>
                            <th class="px-4 py-3 text-center text-xs font-medium uppercase text-gray-500">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-100">
                        @forelse ($modules as $m)
                        <tr>
                            <td class="px-4 py-3 text-sm text-gray-700">{{ $m->id }}</td>
                            <td class="px-4 py-3 text-sm text-gray-700">
                                {{ $m->name ?? 'N/A' }}
                            </td>
                            <td class="px-4 py-3 text-sm font-medium text-gray-900">
                                {{ $m->course->name ?? ('#'.$m->course_id) }}
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-700">{{ $m->course_package ?
                                ($m->course_package->course_package_name . ' Package') : ''
                                }}</td>
                            <td class="px-4 py-3 text-center">
                                <button onclick="openEditModal(@js([
                                            'id' => $m->id,
                                            'name' => $m->name,
                                            'course_id' => $m->course_id,
                                        ]))"
                                    class="bg-blue-600 px-3 py-1 text-white text-xs rounded-md hover:bg-blue-700">
                                    Edit
                                </button>

                                <a href="{{ route('modules.topics', $m->id) }}"
                                    class="bg-green-600 px-3 py-1 text-white text-xs rounded-md hover:bg-green-700">Topics</a>

                                <form action="{{ route('modules.destroy', $m->id) }}" method="POST" class="inline"
                                    onsubmit="return confirm('Are you sure?')">
                                    @csrf @method('DELETE')
                                    <button type="submit"
                                        class="bg-red-600 px-3 py-1 text-white text-xs rounded-md hover:bg-red-700">Delete</button>
                                </form>

                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="8" class="px-4 py-6 text-center text-sm text-gray-500">No entries found</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="px-5 py-3 border-t border-gray-200">
                {{ $modules->onEachSide(1)->links() }}
            </div>
        </div>
    </div>

    {{-- Create/Edit Modal --}}
    <div id="moduleModal" class="hidden fixed inset-0 bg-gray-800 bg-opacity-50 flex items-center justify-center z-50">
        <div class="bg-white rounded-lg shadow-lg p-6 w-full max-w-2xl">
            <h2 id="modalTitle" class="text-lg font-semibold mb-4">Create Module</h2>

            <form id="moduleForm" method="POST">
                @csrf
                <input type="hidden" id="methodField" name="_method" value="POST">

                <div class="grid grid-cols-1 gap-4">
                    {{-- Institute (drives course filter) --}}

                    {{-- Module Name --}}
                    <div class="col-span-1">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Module Name</label>
                        <input type="text" name="name" id="form_name"
                            class="block w-full rounded-md border-gray-300 shadow-sm sm:text-sm" required>
                    </div>
                    {{-- Course --}}
                    <div class="col-span-1">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Course</label>
                        <select name="course_id" id="form_course_id" class="w-full border rounded p-2" required>
                            <option value="">-- Select Course --</option>
                            @foreach($courses as $c)
                            <option value="{{ $c->id }}" data-institute="{{ $c->institute_id }}">{{ $c->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Course Package select --}}
                    <div class="col-span-1">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Course Package</label>
                        <select name="course_package_id" id="form_course_package_id" class="w-full border rounded p-2"
                            required>
                            <option value="">-- Select Course Package --</option>
                            @foreach($course_packages as $cp)
                            <option value="{{ $cp->id }}" data-course="{{ $cp->course_id }}">
                                {{ $cp->course_package_name }} {{ ' Package' }}
                            </option>
                            @endforeach
                        </select>
                    </div>


                </div>

                <div class="flex justify-end gap-2 mt-6">
                    <button type="button" onclick="closeModal()"
                        class="px-3 py-2 text-sm bg-gray-300 rounded-md">Cancel</button>
                    <button type="submit" class="px-3 py-2 text-sm bg-indigo-600 text-white rounded-md">Save</button>
                </div>
            </form>
        </div>
    </div>

    {{-- JS --}}
    <script>
        // ===== Elements =====
    const modal       = document.getElementById('moduleModal');
    const form        = document.getElementById('moduleForm');
    const titleEl     = document.getElementById('modalTitle');
    const methodField = document.getElementById('methodField');

    const fCourse         = document.getElementById('form_course_id');
    const fCoursePackage  = document.getElementById('form_course_package_id');
    const fName           = document.getElementById('form_name');

    // ===== Course ⇒ Course Package filter =====
    function syncCoursePackages() {
        const selectedCourseId = (fCourse.value || '').toString();
        let foundVisible = false;

        // সব option iterate করে, যে option-এর data-course current course নয়, সেগুলো লুকিয়ে দিন
        [...fCoursePackage.options].forEach(opt => {
        if (!opt.value) { // placeholder option
            opt.hidden = false;
            opt.disabled = false;
            return;
        }
        const optCourseId = (opt.getAttribute('data-course') || '').toString();
        const visible = (selectedCourseId === '' || optCourseId === selectedCourseId);

        opt.hidden   = !visible;
        opt.disabled = !visible;

        if (visible) foundVisible = true;
        });

        // নির্বাচিত প্যাকেজ invalid হলে রিসেট করুন
        const sel = fCoursePackage.selectedOptions[0];
        if (sel && (sel.hidden || sel.disabled)) {
        fCoursePackage.value = '';
        }

        // কোনো visible option না থাকলে placeholder-ই থাকবে
        if (!foundVisible) {
        fCoursePackage.value = '';
        }
    }

    // Course change হলে packages sync
    fCourse.addEventListener('change', syncCoursePackages);

    // ===== Modal actions =====
    function openCreateModal() {
        form.action         = "{{ route('modules.store') }}";
        methodField.value   = "POST";
        titleEl.textContent = "Create Module";

        fCourse.value        = "";
        fName.value          = "";

        syncCoursePackages(); // course reset করার পর packages hide/show
        modal.classList.remove('hidden');
    }

    function openEditModal(data) {
        form.action         = "/admin/modules/" + data.id;
        methodField.value   = "PUT";
        titleEl.textContent = "Edit Module";

        fCourse.value        = data.course_id ?? "";
        syncCoursePackages(); // course সেট করার পর packages sync
        fName.value          = data.name ?? "";

        modal.classList.remove('hidden');
    }

    function closeModal() {
        modal.classList.add('hidden');
    }

    // Expose for inline onclick
    window.openCreateModal = openCreateModal;
    window.openEditModal   = openEditModal;
    window.closeModal      = closeModal;

    // পেজ লোডে একবার sync (edit validation/old values থাকলে কাজে লাগবে)
    document.addEventListener('DOMContentLoaded', syncCoursePackages);
    </script>

</x-app-layout>