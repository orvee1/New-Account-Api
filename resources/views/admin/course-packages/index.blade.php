<x-app-layout>
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 py-6">
        {{-- Header --}}
        <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <h1 class="text-xl font-semibold text-gray-900">Course Packages</h1>

            <button onclick="openCreateModal()"
                class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-3 py-2 text-sm font-medium text-white hover:bg-indigo-700">
                + Add Package
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

            {{-- Right side: Course filter + Search --}}
            <div class="flex items-center gap-3">
                <div>
                    <select name="course_id"
                        class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                        onchange="this.form.submit()">
                        <option value="">All Courses</option>
                        @foreach($courses as $c)
                            <option value="{{ $c->id }}" {{ (string)($filters['course_id'] ?? '' )===(string)$c->id ? 'selected' : '' }}>
                                {{ $c->name ?? ('#'.$c->id) }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <input type="text" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Search by name"
                        class="block min-w-60 rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" />
                </div>
            </div>
        </form>

        {{-- Table --}}
        <div class="bg-white rounded-xl shadow-sm ring-1 ring-gray-200">
            <div class="px-5 py-3 border-b border-gray-200 text-sm text-gray-600">
                Showing {{ $packages->firstItem() ?? 0 }}–{{ $packages->lastItem() ?? 0 }} of {{ $packages->total() ?? 0 }}
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">ID</th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">Institute</th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">Course</th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">Faculty</th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">Discipline</th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">Created</th>
                            <th class="px-4 py-3 text-center text-xs font-medium uppercase text-gray-500">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-100">
                        @forelse ($packages as $package)
                            <tr>
                                <td class="px-4 py-3 text-sm text-gray-700">{{ $package->id }}</td>

                                <td class="px-4 py-3 text-sm font-medium text-gray-900">
                                    {{ optional($package->institute)->name ?? ('#'.$package->institute_id) }}
                                </td>

                                <td class="px-4 py-3 text-sm font-medium text-gray-900">
                                    {{ optional($package->course)->name ?? ('#'.$package->course_id) }}
                                </td>

                                <td class="px-4 py-3 text-sm text-gray-700">
                                    {{ optional($package->faculty)->name ?? ($package->institute_faculty_id ? '#'.$package->institute_faculty_id : '—') }}
                                </td>

                                <td class="px-4 py-3 text-sm text-gray-700">
                                    {{ optional($package->discipline)->name ?? ($package->institute_discipline_id ? '#'.$package->institute_discipline_id : '—') }}
                                </td>

                                <td class="px-4 py-3 text-sm text-gray-600">
                                    {{ optional($package->created_at)->format('Y-m-d') ?? '—' }}
                                </td>

                                <td class="px-4 py-3 text-center">
                                    <button
                                        onclick="openEditModal({{ json_encode([
                                            'id' => $package->id,
                                            'institute_id' => $package->institute_id,
                                            'course_id' => $package->course_id,
                                            'institute_faculty_id' => $package->institute_faculty_id,
                                            'institute_discipline_id' => $package->institute_discipline_id,
                                        ]) }})"
                                        class="bg-blue-600 px-3 py-1 text-white text-xs rounded-md hover:bg-blue-700">
                                        Edit
                                    </button>

                                    <form action="{{ route('course-packages.destroy', $package->id) }}" method="POST"
                                        class="inline" onsubmit="return confirm('Are you sure?')">
                                        @csrf @method('DELETE')
                                        <button type="submit"
                                            class="bg-red-600 px-3 py-1 text-white text-xs rounded-md hover:bg-red-700">
                                            Delete
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-4 py-6 text-center text-sm text-gray-500">No entries found</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="px-5 py-3 border-t border-gray-200">
                {{ $packages->onEachSide(1)->links() }}
            </div>
        </div>
    </div>

    {{-- Create/Edit Modal --}}
    <div id="packageModal" class="hidden fixed inset-0 bg-gray-800 bg-opacity-50 flex items-center justify-center z-50">
        <div class="bg-white rounded-lg shadow-lg p-6 w-full max-w-md">
            <h2 id="modalTitle" class="text-lg font-semibold mb-4">Create Package</h2>

            <form id="packageForm" method="POST">
                @csrf
                <input type="hidden" id="methodField" name="_method" value="POST">

                {{-- Institute --}}
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700">Institute</label>
                    <select name="institute_id" id="institute_id" class="w-full border rounded p-2" required>
                        <option value="">-- Select Institute --</option>
                        @foreach($institutes as $i)
                            <option value="{{ $i->id }}">{{ $i->name ?? ('#'.$i->id) }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Course (dependent) --}}
                <div class="mb-4" id="course_group">
                    <label class="block text-sm font-medium text-gray-700">Course</label>
                    <select name="course_id" id="course_id" class="w-full border rounded p-2" required disabled>
                        <option value="">-- Select Course --</option>
                    </select>
                </div>

                {{-- Faculty (dependent, optional) --}}
                <div class="mb-4" id="faculty_group" style="display:none;">
                    <label class="block text-sm font-medium text-gray-700">Faculty (optional)</label>
                    <select name="institute_faculty_id" id="institute_faculty_id" class="w-full border rounded p-2" disabled>
                        <option value="">-- Select Faculty --</option>
                    </select>
                </div>

                {{-- Discipline (dependent, optional) --}}
                <div class="mb-5" id="discipline_group" style="display:none;">
                    <label class="block text-sm font-medium text-gray-700">Discipline (optional)</label>
                    <select name="institute_discipline_id" id="institute_discipline_id" class="w-full border rounded p-2" disabled>
                        <option value="">-- Select Discipline --</option>
                    </select>
                </div>

                <div class="flex justify-end gap-2">
                    <button type="button" onclick="closeModal()"
                        class="px-3 py-2 text-sm bg-gray-300 rounded-md">Cancel</button>
                    <button type="submit" class="px-3 py-2 text-sm bg-indigo-600 text-white rounded-md">Save</button>
                </div>
            </form>
        </div>
    </div>

    {{-- JS for Modal + Dependent dropdowns (with show/hide by availability) --}}
    <script>
        const modal         = document.getElementById('packageModal');
        const form          = document.getElementById('packageForm');
        const titleEl       = document.getElementById('modalTitle');
        const methodField   = document.getElementById('methodField');

        const instituteEl   = document.getElementById('institute_id');
        const courseEl      = document.getElementById('course_id');
        const facultyEl     = document.getElementById('institute_faculty_id');
        const disciplineEl  = document.getElementById('institute_discipline_id');

        const facultyGroup   = document.getElementById('faculty_group');
        const disciplineGroup= document.getElementById('discipline_group');
        const courseGroup    = document.getElementById('course_group');

        // Endpoint base (expects GET /admin/course-packages/filter/{institute})
        const OPTIONS_BASE_URL = @json(url('/admin/course-packages/filter'));

            function openCreateModal(preset = {}) {
            form.action         = @json(route('course-packages.store'));
            methodField.value   = "POST";
            titleEl.textContent = "Create Package";

            const currentInst   = instituteEl.value && instituteEl.value !== '' ? instituteEl.value : null;
            const selectedId    = preset.institute_id ?? currentInst ?? '';

            instituteEl.value   = selectedId;

            resetAllDependentUI();

            if (selectedId) {
                loadOptionsByInstitute(selectedId, {
                    course_id: preset.course_id ?? '',
                    institute_faculty_id: preset.institute_faculty_id ?? '',
                    institute_discipline_id: preset.institute_discipline_id ?? ''
                });
            }

            modal.classList.remove('hidden');
            }

        function openEditModal(data) {
            form.action       = `/admin/course-packages/${data.id}`;
            methodField.value = "PUT";
            titleEl.textContent = "Edit Package";

            instituteEl.value  = data.institute_id ?? "";
            resetAllDependentUI();

            if (instituteEl.value) {
                loadOptionsByInstitute(instituteEl.value, {
                    course_id: data.course_id ?? '',
                    institute_faculty_id: data.institute_faculty_id ?? '',
                    institute_discipline_id: data.institute_discipline_id ?? ''
                });
            }

            modal.classList.remove('hidden');
        }

        function closeModal() {
            modal.classList.add('hidden');
        }

        function clearSelect(el, placeholder) {
            el.innerHTML = '';
            const opt = document.createElement('option');
            opt.value = '';
            opt.textContent = placeholder;
            el.appendChild(opt);
        }

        function show(el) { el.style.display = ''; }
        function hide(el) { el.style.display = 'none'; }

        function enable(el, on = true) { el.disabled = !on; }
        function disable(el) { el.disabled = true; }

        function resetAllDependentUI() {
            // Courses are always required, keep the group visible but disable until loaded
            clearSelect(courseEl, '-- Select Course --');
            enable(courseEl, false);

            // Faculty and Discipline should be hidden until we know they exist
            clearSelect(facultyEl, '-- Select Faculty --');
            hide(facultyGroup);  // hide entire section
            disable(facultyEl);  // and disable

            clearSelect(disciplineEl, '-- Select Discipline --');
            hide(disciplineGroup);
            disable(disciplineEl);
        }

        function fillSelect(el, items, selectedId = null) {
            items.forEach(({id, name}) => {
                const opt = document.createElement('option');
                opt.value = id;
                opt.textContent = name;
                if (String(selectedId) === String(id)) opt.selected = true;
                el.appendChild(opt);
            });
        }

        function ensureStillValid(selectEl) {
            const current = selectEl.value;
            if (!current) return;
            if (![...selectEl.options].some(o => o.value === current)) {
                selectEl.value = '';
            }
        }

        async function loadOptionsByInstitute(instituteId, preselect = {}) {
            if (!instituteId) {
                resetAllDependentUI();
                return;
            }

            try {
                const res = await fetch(`${OPTIONS_BASE_URL}/${encodeURIComponent(instituteId)}`);
                if (!res.ok) throw new Error('Failed to fetch options');
                const data = await res.json();

                // ----- Courses (always shown; required) -----
                clearSelect(courseEl, '-- Select Course --');
                fillSelect(courseEl, data.courses || [], preselect.course_id);
                enable(courseEl, true);

                // ----- Faculty: show only if there is data -----
                clearSelect(facultyEl, '-- Select Faculty --');
                const hasFaculty = Array.isArray(data.faculties) && data.faculties.length > 0;
                if (hasFaculty) {
                    fillSelect(facultyEl, data.faculties, preselect.institute_faculty_id);
                    show(facultyGroup);
                    enable(facultyEl, true);
                    ensureStillValid(facultyEl);
                } else {
                    hide(facultyGroup);
                    disable(facultyEl);
                    facultyEl.value = '';
                }

                // ----- Discipline: show only if there is data -----
                clearSelect(disciplineEl, '-- Select Discipline --');
                const hasDiscipline = Array.isArray(data.disciplines) && data.disciplines.length > 0;
                if (hasDiscipline) {
                    fillSelect(disciplineEl, data.disciplines, preselect.institute_discipline_id);
                    show(disciplineGroup);
                    enable(disciplineEl, true);
                    ensureStillValid(disciplineEl);
                } else {
                    hide(disciplineGroup);
                    disable(disciplineEl);
                    disciplineEl.value = '';
                }

            } catch (e) {
                console.error(e);
                resetAllDependentUI();
                alert('Could not load options for the selected institute.');
            }
        }

        // On institute change
        instituteEl.addEventListener('change', (e) => {
            const id = e.target.value;
            // Reset previous dependent state, then load fresh
            resetAllDependentUI();
            loadOptionsByInstitute(id, {
                course_id: '',
                institute_faculty_id: '',
                institute_discipline_id: ''
            });
        });
    </script>
</x-app-layout>
