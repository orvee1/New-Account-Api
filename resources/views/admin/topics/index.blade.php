<x-app-layout>
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 py-6">
        {{-- Header + Create --}}
        {{-- Header + Create --}}
        <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <h1 class="text-xl font-semibold text-gray-900">Topic List</h1>

            <div class="flex items-center gap-2">
                {{-- Excel Upload (hidden input trigger) --}}
                {{-- Excel Upload Form --}}
                <form id="excelPreviewForm" action="{{ route('topics.import.commit') }}" method="POST"
                    enctype="multipart/form-data">
                    @csrf
                    <input id="excelFileInput" type="file" name="file" class="hidden" accept=".xlsx,.xls,.csv">
                    <button type="button" onclick="document.getElementById('excelFileInput').click()"
                        class="inline-flex items-center gap-2 rounded-lg bg-emerald-600 px-3 py-1 text-sm font-medium text-white hover:bg-emerald-700">
                        <!-- icon --> Excel Upload
                    </button>
                </form>


                {{-- Sample template link --}}
                <a href="{{ route('topics.import.sample') }}"
                    class="inline-flex items-center gap-2 rounded-lg bg-orange-500 px-3 py-1 text-sm font-medium text-white hover:bg-orange-600">
                    {{-- Download Icon --}}
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M12 3a1 1 0 0 1 1 1v9.586l2.293-2.293a1 1 0
        0 1 1.414 1.414l-4 4a1 1 0 0 1-1.414 0l-4-4a1 1
        0 0 1 1.414-1.414L11 13.586V4a1 1 0 0 1 1-1z" />
                        <path d="M4 15a1 1 0 0 1 1 1v3h14v-3a1 1 0 0 1 2 0v4a1
        1 0 0 1-1 1H3a1 1 0 0 1-1-1v-4a1 1 0 0 1 1-1z" />
                    </svg>
                    Sample
                </a>

                {{-- Create button (manual add) --}}
                <button onclick="openCreateModal()"
                    class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-3 py-1 text-sm font-medium text-white hover:bg-indigo-700">
                    + Add Topic
                </button>
            </div>
        </div>

        {{-- Filters --}}
        <form method="GET" id="filterForm" class="mb-4 flex justify-between items-center">
            <div>
                <select name="per_page" class="rounded-md border-gray-300" onchange="this.form.submit()">
                    @foreach ([10, 25, 50, 100] as $n)
                        <option value="{{ $n }}" {{ $perPage == $n ? 'selected' : '' }}>{{ $n }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="flex items-center gap-3">
                <div>
                    <select name="institute_id"
                        class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                        onchange="this.form.submit()">
                        <option value="">All Institutes</option>
                        @foreach ($institutes as $ins)
                            <option value="{{ $ins->id }}"
                                {{ (string) ($institute_id ?? '') === (string) $ins->id ? 'selected' : '' }}>
                                {{ $ins->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <input type="text" name="q" value="{{ $q }}" placeholder="Search name"
                        class="rounded-md border-gray-300" oninput="debouncedSubmit()" />
                </div>
            </div>
        </form>

        {{-- Table --}}
        <div class="bg-white rounded-xl shadow-sm ring-1 ring-gray-200">
            <div class="px-5 py-3 border-b border-gray-200">
                <p class="text-sm text-gray-600">
                    Showing {{ $topics->firstItem() }}–{{ $topics->lastItem() }} of {{ $topics->total() }}
                </p>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3">ID</th>
                            <th class="px-4 py-3">Institute</th>
                            <th class="px-4 py-3">Name</th>
                            <th class="px-4 py-3 text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-100">
                        @forelse ($topics as $topic)
                            <tr>
                                <td class="px-4 py-3 text-center">{{ $topic->id }}</td>
                                <td class="px-4 py-3 text-center">{{ $topic->institute->name ?? '' }}</td>
                                <td class="px-4 py-3 text-center">{{ $topic->name }}</td>
                                <td class="px-4 py-3 text-center flex justify-center gap-2">
                                    <button onclick='openEditModal(@json($topic))'
                                        class="bg-blue-600 text-white px-3 py-1 rounded">Edit</button>
                                    <form method="POST" action="{{ route('topics.destroy', $topic->id) }}"
                                        onsubmit="return confirm('Are you sure?')">
                                        @csrf @method('DELETE')
                                        <button type="submit"
                                            class="bg-red-600 text-white px-3 py-1 rounded">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-4 py-6 text-center">No topics found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- Pagination --}}
            <div class="px-5 py-3 border-t border-gray-200">
                {{ $topics->links() }}
            </div>
        </div>
    </div>

    {{-- Create Modal --}}
    <div id="createModal" class="hidden fixed inset-0 bg-black/50 items-center justify-center top-12">
        <div class="bg-slate-200 rounded-lg p-6 w-full max-w-md">
            <h2 class="text-lg font-semibold mb-4">Add Topic</h2>
            <form method="POST" action="{{ route('topics.store') }}">
                @csrf
                <div class="mb-3">
                    <label>Institute ID</label>
                    <select name="institute_id" class="w-full border rounded p-2" required>
                        <option value="">-- Select Institute --</option>
                        @foreach ($institutes as $ins)
                            <option value="{{ $ins->id }}" @selected(old('institute_id') == $ins->id)>
                                {{ $ins->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="mb-3">
                    <label>Name</label>
                    <input type="text" name="name" value="{{ old('name') }}" class="w-full border rounded p-2"
                        required>
                </div>
                <div class="flex justify-end gap-2">
                    <button type="button" onclick="closeCreateModal()"
                        class="px-4 py-2 bg-red-600 hover:bg-red-500 text-white border rounded">Cancel</button>
                    <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded">Save</button>
                </div>
            </form>
        </div>
    </div>

    {{-- Edit Modal --}}
    <div id="editModal" class="hidden fixed inset-0 bg-black/50 items-center justify-center top-12">
        <div class="bg-slate-200 rounded-lg p-6 w-full max-w-md">
            <h2 class="text-lg font-semibold mb-4">Edit Topic</h2>
            <form method="POST" id="editForm">
                @csrf @method('PUT')
                <div class="mb-3">
                    <label>Institute</label>
                    <select name="institute_id" class="w-full border rounded p-2" required>
                        <option value="">-- Select Institute --</option>
                        @foreach ($institutes as $ins)
                            <option value="{{ $ins->id }}">{{ $ins->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="mb-3">
                    <label>Name</label>
                    <input type="text" name="name" class="w-full border rounded p-2" required>
                </div>
                <div class="flex justify-end gap-2">
                    <button type="button" onclick="closeEditModal()"
                        class="px-4 py-2 bg-red-600 hover:bg-red-500 text-white border rounded">Cancel</button>
                    <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded">Update</button>
                </div>
            </form>
        </div>
    </div>

    {{-- Preview after upload --}}
    @include('admin.topics.excel_import_preview')

    <script>
        let timer;

        function debouncedSubmit() {
            clearTimeout(timer);
            timer = setTimeout(() => document.getElementById('filterForm').submit(), 500);
        }

        function openCreateModal() {
            document.getElementById('createModal').classList.remove('hidden');
            document.getElementById('createModal').classList.add('flex');
        }

        function closeCreateModal() {
            document.getElementById('createModal').classList.add('hidden');
            document.getElementById('createModal').classList.remove('flex');
        }

        function openEditModal(topic) {
            const form = document.getElementById('editForm');
            form.action = `/admin/topics/${topic.id}`;
            form.institute_id.value = topic.institute_id; // set selected institute
            form.name.value = topic.name;

            document.getElementById('editModal').classList.remove('hidden');
            document.getElementById('editModal').classList.add('flex');
        }

        function closeEditModal() {
            document.getElementById('editModal').classList.add('hidden');
            document.getElementById('editModal').classList.remove('flex');
        }
    </script>

    <script src="https://cdn.jsdelivr.net/npm/xlsx@0.18.5/dist/xlsx.full.min.js" defer></script>
    {{-- PapaParse for csv --}}
    <script src="https://cdn.jsdelivr.net/npm/papaparse@5.4.1/papaparse.min.js" defer></script>

    <script>
        // ==== Config ====
        const REQUIRED_HEADERS = ['institute_id', 'name'];
        // Laravel route + CSRF
        const COMMIT_URL = "{{ route('topics.import.commit') }}";
        const CSRF_TOKEN = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ??
            '{{ csrf_token() }}';

        // ==== Elements ====
        const fileInput = document.getElementById('excelFileInput');
        const formEl = document.getElementById('excelPreviewForm'); // kept, but not used for submit now
        const modalEl = document.getElementById('excelPreviewModal');
        const tbodyEl = document.getElementById('previewTbody');
        const sumTotalEl = document.getElementById('sumTotal');
        const sumErrorEl = document.getElementById('sumError');
        const sumCanImportEl = document.getElementById('sumCanImport');
        const headerErrorMsg = document.getElementById('headerErrorMsg');
        const confirmBtn = document.getElementById('confirmUploadBtn');

        // Keep valid rows globally
        let validRows = [];

        // ==== Event: choose file -> preview ====
        fileInput.addEventListener('change', async (e) => {
            const file = e.target.files?.[0];
            if (!file) return;

            resetPreview();

            try {
                const ext = file.name.split('.').pop().toLowerCase();
                let data = [];

                if (ext === 'csv') {
                    data = await parseCSV(file);
                } else if (ext === 'xlsx' || ext === 'xls') {
                    data = await parseExcel(file);
                } else {
                    alert('Unsupported file type.');
                    fileInput.value = '';
                    return;
                }

                if (!data.length) {
                    alert('File is empty.');
                    fileInput.value = '';
                    return;
                }

                renderPreview(data);
                openExcelPreview();
            } catch (err) {
                console.error(err);
                alert('Failed to read file. See console for details.');
                fileInput.value = '';
            }
        });

        // ==== CSV Parser ====
        function parseCSV(file) {
            return new Promise((resolve, reject) => {
                Papa.parse(file, {
                    complete: (results) => {
                        const rows = results.data
                            .map(r => r.map(v => (v == null ? '' : String(v))))
                            .filter(r => r.length && r.some(cell => String(cell).trim() !== ''));
                        resolve(rows);
                    },
                    error: (err) => reject(err),
                    skipEmptyLines: 'greedy',
                });
            });
        }

        // ==== Excel Parser (XLSX/XLS) ====
        function parseExcel(file) {
            return new Promise((resolve, reject) => {
                const reader = new FileReader();
                reader.onload = (evt) => {
                    try {
                        const data = evt.target.result;
                        const wb = XLSX.read(data, {
                            type: 'array'
                        });
                        const ws = wb.Sheets[wb.SheetNames[0]];
                        const arr = XLSX.utils.sheet_to_json(ws, {
                            header: 1,
                            raw: false,
                            defval: ''
                        });
                        const rows = arr.filter(r => r.length && r.some(cell => String(cell).trim() !== ''));
                        resolve(rows);
                    } catch (err) {
                        reject(err);
                    }
                };
                reader.onerror = reject;
                reader.readAsArrayBuffer(file);
            });
        }

        // ==== Preview Renderer ====
        function renderPreview(rows) {
            // Reset validRows
            validRows = [];

            // Header row
            const headerRow = (rows[0] || []).map(v => String(v ?? '').trim().toLowerCase());
            const headerIndex = Object.fromEntries(headerRow.map((h, i) => [h, i]));

            // Check required headers
            const missing = REQUIRED_HEADERS.filter(h => !(h in headerIndex));
            const headerOk = missing.length === 0;

            headerErrorMsg.classList.toggle('hidden', headerOk);

            // Body rows
            const body = rows.slice(1);
            let total = body.length,
                errors = 0,
                canImport = 0;

            const frag = document.createDocumentFragment();

            body.forEach((r, idx) => {
                const rowNo = idx + 2;
                const instituteId = safeCell(r, headerIndex['institute_id']);
                const name = safeCell(r, headerIndex['name']);
                // console.log(`Processing row ${rowNo}: institute_id=${instituteId}, name=${name}`);

                const errList = [];
                if (!headerOk) {
                    errList.push('Missing required header(s)');
                } else {
                    if (String(instituteId).trim() === '' || !/^\d+$/.test(String(instituteId).trim())) {
                        errList.push('institute_id must be integer');
                    }
                    if (String(name).trim() === '') {
                        errList.push('name is required');
                    }
                }

                const hasError = errList.length > 0;
                if (hasError) {
                    errors++;
                } else {
                    canImport++;
                    // collect valid row (convert institute_id to int)
                    validRows.push({
                        institute_id: parseInt(String(instituteId).trim(), 10),
                        name: String(name).trim()
                    });
                }

                const tr = document.createElement('tr');
                tr.innerHTML = `
        <td class="px-3 py-2">${rowNo}</td>
        <td class="px-3 py-2">${escapeHtml(instituteId)}</td>
        <td class="px-3 py-2">${escapeHtml(name)}</td>
        <td class="px-3 py-2 ${hasError ? 'text-red-600' : 'text-gray-500'}">
          ${hasError ? `<ul class="list-disc ml-5">${errList.map(e => `<li>${escapeHtml(e)}</li>`).join('')}</ul>` : '—'}
        </td>
        <td class="px-3 py-2 ${hasError ? 'text-gray-400' : 'text-emerald-700 font-semibold'}">
          ${hasError ? 'No' : 'Yes'}
        </td>
      `;
                frag.appendChild(tr);
            });

            tbodyEl.innerHTML = '';
            tbodyEl.appendChild(frag);

            sumTotalEl.textContent = String(total);
            sumErrorEl.textContent = String(errors);
            sumCanImportEl.textContent = String(canImport);

            // Confirm বাটন চালু থাকবে যখন:
            // 1) header ঠিক আছে, এবং 2) অন্তত ১টা valid row আছে
            confirmBtn.disabled = !(headerOk && validRows.length > 0);
        }

        function safeCell(row, idx) {
            if (idx == null || idx < 0 || idx >= row.length) return '';
            const v = row[idx];
            return v == null ? '' : String(v);
        }

        function escapeHtml(s) {
            return String(s).replace(/[&<>"']/g, (ch) => ({
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;'
            } [ch]));
        }

        // ==== Modal control ====
        function openExcelPreview() {
            modalEl.classList.remove('hidden');
            modalEl.classList.add('flex');
        }

        function closeExcelPreview() {
            modalEl.classList.add('hidden');
            modalEl.classList.remove('flex');
        }

        function resetPreview() {
            validRows = [];
            tbodyEl.innerHTML = '';
            sumTotalEl.textContent = '0';
            sumErrorEl.textContent = '0';
            sumCanImportEl.textContent = '0';
            headerErrorMsg.classList.add('hidden');
            confirmBtn.disabled = true;
        }

        // ==== Submit ONLY valid rows to commit route ====
        function submitExcelForm() {
            if (!validRows.length) {
                alert('No valid rows to import.');
                return;
            }

            // Build a throwaway form and post JSON payload
            const f = document.createElement('form');
            f.method = 'POST';
            f.action = COMMIT_URL;

            // CSRF
            const csrf = document.createElement('input');
            csrf.type = 'hidden';
            csrf.name = '_token';
            csrf.value = CSRF_TOKEN;
            f.appendChild(csrf);

            // Payload as JSON
            const rowsJson = document.createElement('input');
            rowsJson.type = 'hidden';
            rowsJson.name = 'rows_json';
            rowsJson.value = JSON.stringify(validRows);
            f.appendChild(rowsJson);

            // Optional: flag to let backend know this is client-side preview payload
            const src = document.createElement('input');
            src.type = 'hidden';
            src.name = 'source';
            src.value = 'client_preview';
            f.appendChild(src);

            document.body.appendChild(f);
            f.submit();
        }

        // Expose to window for buttons
        window.closeExcelPreview = closeExcelPreview;
        window.submitExcelForm = submitExcelForm;
    </script>


</x-app-layout>
