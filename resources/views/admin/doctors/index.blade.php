{{-- resources/views/admin/doctors/index.blade.php --}}
<x-app-layout>
  <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 py-6">
    {{-- Header --}}
    <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
      <h1 class="text-xl font-semibold text-gray-900">Doctors</h1>

      <button onclick="openCreateModal()"
              class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-3 py-2 text-sm font-medium text-white hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500">
        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
          <path d="M11 11V5h2v6h6v2h-6v6h-2v-6H5v-2z" />
        </svg>
        Add Doctor
      </button>
    </div>

    {{-- Global flash/errors --}}
    @if (session('success'))
      <div class="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-2 text-emerald-700">
        {{ session('success') }}
      </div>
    @endif
    @if ($errors->any())
      <div class="mb-4 rounded-lg border border-rose-200 bg-rose-50 px-4 py-2 text-rose-700">
        <ul class="list-disc pl-5 text-sm space-y-1">
          @foreach ($errors->all() as $err)
            <li>{{ $err }}</li>
          @endforeach
        </ul>
      </div>
    @endif

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

      {{-- Right side: Search --}}
      <div class="flex items-center gap-3">
        <div>
          <input type="text" name="q" value="{{ request('q') }}" placeholder="Search"
                 class="block min-w-60 rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" />
        </div>
      </div>
    </form>

    {{-- Card/Table --}}
    <div class="bg-white rounded-xl shadow-sm ring-1 ring-gray-200">
      <div class="px-5 py-3 border-b border-gray-200 text-sm text-gray-600">
        Showing {{ $doctors->firstItem() ?? 0 }}–{{ $doctors->lastItem() ?? 0 }} of {{ $doctors->total() ?? 0 }}
      </div>

      <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
          <thead class="bg-gray-50">
            <tr>
              <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">ID</th>
              <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">Name</th>
              <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">Phone</th>
              <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">Email</th>
              <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">BMDC</th>
              <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">Status</th>
              <th class="px-4 py-3 text-right text-xs font-medium uppercase text-gray-500">Actions</th>
            </tr>
          </thead>
          <tbody class="bg-white divide-y divide-gray-100">
            @forelse($doctors as $key => $doctor)
              <tr class="hover:bg-gray-50">
                <td class="px-4 py-3 text-sm text-gray-700">{{ $doctors->firstItem() + $key }}</td>
                <td class="px-4 py-3 text-sm font-medium text-gray-900">{{ $doctor->name ?? '—' }}</td>
                <td class="px-4 py-3 text-sm text-gray-700">{{ $doctor->phone_number }}</td>
                <td class="px-4 py-3 text-sm text-gray-700">{{ $doctor->email ?? '—' }}</td>
                <td class="px-4 py-3 text-sm text-gray-700">{{ $doctor->bmdc ?? '—' }}</td>
                <td class="px-4 py-3 text-sm text-gray-700">{{ $doctor->medical_college_id ?? '—' }}</td>
                <td class="px-4 py-3 text-sm">
                  @if ($doctor->status)
                    <span class="inline-flex items-center rounded-full bg-emerald-50 px-2 py-0.5 text-xs font-medium text-emerald-700 border border-emerald-200">Active</span>
                  @else
                    <span class="inline-flex items-center rounded-full bg-gray-100 px-2 py-0.5 text-xs font-medium text-gray-600 border border-gray-200">Inactive</span>
                  @endif
                </td>
                <td class="px-4 py-3">
                  <div class="flex justify-end gap-2">
                    {{-- Edit (modal trigger via data-* to avoid JSON/quote issues) --}}
                    <button
                      class="px-3 py-1.5 rounded-md text-white bg-blue-600 hover:bg-blue-700 text-xs"
                      data-edit
                      data-id="{{ $doctor->id }}"
                      data-name="{{ $doctor->name }}"
                      data-phone="{{ $doctor->phone_number }}"
                      data-email="{{ $doctor->email }}"
                      data-bmdc="{{ $doctor->bmdc }}"
                      data-photo="{{ $doctor->photo }}"
                      data-status="{{ (int) $doctor->status }}"
                    >
                      Edit
                    </button>

                    {{-- Soft delete --}}
                    <form action="{{ route('doctors.destroy', $doctor) }}" method="POST"
                          onsubmit="return confirm('Move to trash?')">
                      @csrf @method('DELETE')
                      <button class="px-3 py-1.5 rounded-md text-white bg-red-600 hover:bg-red-700 text-xs">
                        Trash
                      </button>
                    </form>
                  </div>
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="8" class="px-4 py-10 text-center text-sm text-gray-500">No doctors found.</td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>

      <div class="px-5 py-3 border-t border-gray-200">
        {{ $doctors->appends(request()->query())->onEachSide(1)->links() }}
      </div>
    </div>
  </div>

  {{-- Create/Edit Modal --}}
  <div id="docModal" class="hidden fixed inset-0 bg-gray-800/60 flex items-center justify-center z-50">
    <div class="bg-white rounded-lg shadow-lg p-6 w-full max-w-xl">
      <h2 id="docModalTitle" class="text-lg font-semibold mb-4">Create Doctor</h2>

      <form id="docForm" method="POST">
        @csrf
        <input type="hidden" id="docMethod" name="_method" value="POST">

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          {{-- Name --}}
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Name</label>
            <input type="text" name="name" id="doc_name"
                   class="w-full rounded-lg border-gray-300 focus:border-gray-900 focus:ring-gray-900"/>
          </div>

          {{-- Phone --}}
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">
              Phone <span class="text-rose-600">*</span>
            </label>
            <input type="text" name="phone_number" id="doc_phone" required
                   class="w-full rounded-lg border-gray-300 focus:border-gray-900 focus:ring-gray-900"/>
          </div>

          {{-- Email --}}
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
            <input type="email" name="email" id="doc_email"
                   class="w-full rounded-lg border-gray-300 focus:border-gray-900 focus:ring-gray-900"/>
          </div>

          {{-- BMDC --}}
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">BMDC</label>
            <input type="text" name="bmdc" id="doc_bmdc"
                   class="w-full rounded-lg border-gray-300 focus:border-gray-900 focus:ring-gray-900"/>
          </div>

          {{-- Photo (path/URL) --}}
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Photo (URL/Path)</label>
            <input type="text" name="photo" id="doc_photo"
                   class="w-full rounded-lg border-gray-300 focus:border-gray-900 focus:ring-gray-900"/>
          </div>

          {{-- Password --}}
          <div class="sm:col-span-2">
            <label class="block text-sm font-medium text-gray-700 mb-1">
              Password <span id="doc_password_required" class="text-rose-600">*</span>
            </label>
            <input type="password" name="password" id="doc_password"
                   class="w-full rounded-lg border-gray-300 focus:border-gray-900 focus:ring-gray-900"/>
            <p class="mt-1 text-xs text-gray-500" id="doc_password_help">
              For update, keep empty to retain old password.
            </p>
          </div>

          {{-- Status toggle --}}
          <div class="sm:col-span-2">
            <div class="max-w-56 space-y-4 rounded-xl border border-gray-200 bg-gray-50 p-4">
              <label for="doc_status" class="flex items-center justify-between gap-4 cursor-pointer select-none">
                <div class="min-w-0">
                  <span class="block text-sm font-medium text-gray-800">Active?</span>
                </div>

                <span class="relative inline-flex items-center">
                  <input type="hidden" name="status" value="0">
                  <input
                    id="doc_status"
                    name="status"
                    type="checkbox"
                    value="1"
                    class="peer sr-only"
                    aria-checked="false"
                    role="switch"
                  />

                  {{-- Track --}}
                  <span
                    class="h-7 w-12 rounded-full bg-gray-300 shadow-inner ring-1 ring-inset ring-gray-300
                           transition-colors duration-300 ease-out
                           peer-checked:bg-indigo-600 peer-focus:ring-2 peer-focus:ring-indigo-500">
                  </span>

                  {{-- Thumb --}}
                  <span
                    class="pointer-events-none absolute left-0.5 top-0.5 grid h-6 w-6 place-items-center
                           rounded-full bg-white shadow-md transition-transform duration-300 ease-out
                           peer-checked:translate-x-5">
                  </span>

                  {{-- Off/On text --}}
                  <span
                    class="pointer-events-none absolute left-1 top-1/2 -translate-y-1/2 text-[10px] font-medium text-white/80
                           transition-opacity duration-200 ease-out peer-checked:opacity-0">
                    Off
                  </span>
                  <span
                    class="pointer-events-none absolute right-1 top-1/2 -translate-y-1/2 text-[10px] font-medium text-white/80
                           opacity-0 transition-opacity duration-200 ease-out peer-checked:opacity-100">
                    On
                  </span>
                </span>
              </label>
            </div>
          </div>
        </div>

        <div class="flex justify-end gap-2 pt-4">
          <button type="button" onclick="closeDocModal()"
                  class="px-3 py-2 text-sm bg-gray-200 rounded-md">Cancel</button>
          <button type="submit" class="px-3 py-2 text-sm bg-indigo-600 text-white rounded-md">Save</button>
        </div>
      </form>
    </div>
  </div>

  {{-- Debounced search + Modal JS --}}
  <script>
    (function () {
      // --- Debounced search ---
      const form  = document.getElementById('filterForm');
      const q     = form.querySelector('input[name="q"]');
      let timer   = null;

      q.addEventListener('input', function () {
        clearTimeout(timer);
        timer = setTimeout(() => {
          if (form.requestSubmit) form.requestSubmit();
          else form.submit();
        }, 500);
      });

      // --- Modal elements ---
      const docModal        = document.getElementById('docModal');
      const docForm         = document.getElementById('docForm');
      const docMethodField  = document.getElementById('docMethod');
      const docTitleEl      = document.getElementById('docModalTitle');

      const nameEl          = document.getElementById('doc_name');
      const phoneEl         = document.getElementById('doc_phone');
      const emailEl         = document.getElementById('doc_email');
      const bmdcEl          = document.getElementById('doc_bmdc');
      const photoEl         = document.getElementById('doc_photo');
      const passwordEl      = document.getElementById('doc_password');
      const passwordReqEl   = document.getElementById('doc_password_required');
      const passwordHelpEl  = document.getElementById('doc_password_help');
      const statusEl        = document.getElementById('doc_status');

      // Blade route for store
      const STORE_URL  = @json(route('doctors.store'));

      // Create modal
      window.openCreateModal = function(preset = {}) {
        docForm.action         = STORE_URL;
        docMethodField.value   = 'POST';
        docTitleEl.textContent = 'Create Doctor';

        nameEl.value           = preset.name ?? '';
        phoneEl.value          = preset.phone_number ?? '';
        emailEl.value          = preset.email ?? '';
        bmdcEl.value           = preset.bmdc ?? '';
        photoEl.value          = preset.photo ?? '';
        passwordEl.value       = '';
        statusEl.checked       = preset.status ?? true;

        passwordEl.required    = true;
        passwordReqEl.classList.remove('hidden');
        passwordHelpEl.classList.remove('hidden');

        docModal.classList.remove('hidden');
      };

      // Edit modal (row -> button[data-edit])
      document.addEventListener('click', (e) => {
        const btn = e.target.closest('button[data-edit]');
        if (!btn) return;

        docForm.action         = `/admin/doctors/${btn.dataset.id}`;
        docMethodField.value   = 'PUT';
        docTitleEl.textContent = 'Edit Doctor';

        nameEl.value           = btn.dataset.name || '';
        phoneEl.value          = btn.dataset.phone || '';
        emailEl.value          = btn.dataset.email || '';
        bmdcEl.value           = btn.dataset.bmdc || '';
        photoEl.value          = btn.dataset.photo || '';
        passwordEl.value       = '';
        statusEl.checked       = btn.dataset.status === '1';

        passwordEl.required    = false;
        passwordReqEl.classList.add('hidden');
        passwordHelpEl.classList.remove('hidden');

        docModal.classList.remove('hidden');
      });

      // Close modal
      window.closeDocModal = function() {
        docModal.classList.add('hidden');
      };

      // ESC to close
      document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') window.closeDocModal();
      });
    })();
  </script>
</x-app-layout>
