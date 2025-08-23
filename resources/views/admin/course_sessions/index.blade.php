{{-- resources/views/admin/course_sessions/index.blade.php --}}
<x-app-layout>
  <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 py-6">
    {{-- Header --}}
    <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
      <h1 class="text-xl font-semibold text-gray-900">Course Sessions</h1>

      <button onclick="openCreateModal()"
              class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-3 py-2 text-sm font-medium text-white hover:bg-indigo-700">
        + Add Session
      </button>
    </div>

    {{-- Flash / Errors --}}
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
    <form method="GET" id="filterForm" class="mb-4 flex justify-between items-center">
      <div class="flex items-center gap-3">
        {{-- Per page --}}
        <div>
          <select name="per_page"
                  class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                  onchange="this.form.submit()">
            @foreach([10,25,50,100] as $n)
              <option value="{{ $n }}" {{ (int)($perPage ?? 10)===$n ? 'selected' : '' }}>{{ $n }}</option>
            @endforeach
          </select>
        </div>

        {{-- Course filter --}}
        <div>
          <select name="course_id"
                  class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                  onchange="this.form.submit()">
            <option value="">All Courses</option>
            @foreach($courses as $c)
              <option value="{{ $c->id }}" {{ (string)($course_id ?? '' )===(string)$c->id ? 'selected' : '' }}>
                {{ $c->name ?? ('#'.$c->id) }}
              </option>
            @endforeach
          </select>
        </div>
      </div>

      {{-- Search --}}
      <div class="flex items-center gap-3">
        <input type="text" name="q" value="{{ $q ?? '' }}" placeholder="Search"
               class="block min-w-60 rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" />
      </div>
    </form>

    {{-- Table --}}
    <div class="bg-white rounded-xl shadow-sm ring-1 ring-gray-200">
      <div class="px-5 py-3 border-b border-gray-200 text-sm text-gray-600">
        Showing {{ $sessions->firstItem() ?? 0 }}–{{ $sessions->lastItem() ?? 0 }} of {{ $sessions->total() ?? 0 }}
      </div>

      <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
          <thead class="bg-gray-50">
            <tr>
              <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">ID</th>
              <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">Course</th>
              <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">Year</th>
              <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">Name</th>
              <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">Status</th>
              <th class="px-4 py-3 text-center text-xs font-medium uppercase text-gray-500">Actions</th>
            </tr>
          </thead>
          <tbody class="bg-white divide-y divide-gray-100">
            @forelse ($sessions as $cs)
              <tr class="hover:bg-gray-50">
                <td class="px-4 py-3 text-sm text-gray-700">{{ $cs->id }}</td>
                <td class="px-4 py-3 text-sm text-gray-700">{{ $cs->course->name ?? ('#'.$cs->course_id) }}</td>
                <td class="px-4 py-3 text-sm text-gray-700">{{ $cs->year }}</td>
                <td class="px-4 py-3 text-sm font-medium text-gray-900">{{ $cs->name }}</td>
                <td class="px-4 py-3 text-sm">
                  @if ($cs->status)
                    <span class="inline-flex items-center rounded-full bg-emerald-50 px-2 py-0.5 text-xs font-medium text-emerald-700 border border-emerald-200">Active</span>
                  @else
                    <span class="inline-flex items-center rounded-full bg-gray-100 px-2 py-0.5 text-xs font-medium text-gray-600 border border-gray-200">Inactive</span>
                  @endif
                </td>
                <td class="px-4 py-3 text-center">
                  <button
                    class="bg-blue-600 px-3 py-1 text-white text-xs rounded-md hover:bg-blue-700"
                    data-edit
                    data-id="{{ $cs->id }}"
                    data-course_id="{{ $cs->course_id }}"
                    data-year="{{ $cs->year }}"
                    data-name="{{ $cs->name }}"
                    data-status="{{ (int)$cs->status }}"
                  >
                    Edit
                  </button>

                  <form action="{{ route('course-sessions.destroy', $cs->id) }}" method="POST"
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
                <td colspan="6" class="px-4 py-6 text-center text-sm text-gray-500">No entries found</td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>

      <div class="px-5 py-3 border-t border-gray-200">
        {{ $sessions->appends(request()->query())->onEachSide(1)->links() }}
      </div>
    </div>
  </div>

  {{-- Create/Edit Modal --}}
  <div id="sessionModal" class="hidden fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-2 sm:p-4">
    <div id="sessionModalContent" class="bg-white rounded-lg shadow-lg w-full max-w-md max-h-[90vh] overflow-y-auto">
      <div class="sticky top-0 flex items-center justify-between border-b px-4 py-3 bg-white/90">
        <h2 id="modalTitle" class="text-base font-semibold">Create Session</h2>
        <button type="button" onclick="closeModal()" class="rounded p-2 hover:bg-gray-100">
          <svg class="h-5 w-5" viewBox="0 0 24 24" fill="currentColor">
            <path d="M18.3 5.71 12 12l6.3 6.29-1.41 1.42L10.59 13.4 4.3 19.71 2.89 18.3 9.17 12 2.89 5.71 4.3 4.29l6.29 6.3 6.29-6.3z"/>
          </svg>
        </button>
      </div>

      <form id="sessionForm" method="POST" class="px-4 py-4">
        @csrf
        <input type="hidden" id="methodField" name="_method" value="POST">

        <div class="space-y-3">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Course</label>
            <select name="course_id" id="course_id" class="w-full rounded-md border-gray-300 shadow-sm sm:text-sm focus:border-indigo-600 focus:ring-indigo-600" required>
              <option value="">-- Select Course --</option>
              @foreach($courses as $c)
                <option value="{{ $c->id }}">{{ $c->name }}</option>
              @endforeach
            </select>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Year</label>
            <input type="number" name="year" id="year" min="1970" max="{{ date('Y') + 1 }}"
                   class="block w-full rounded-md border-gray-300 shadow-sm sm:text-sm focus:border-indigo-600 focus:ring-indigo-600" required>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Name</label>
            <input type="text" name="name" id="name"
                   class="block w-full rounded-md border-gray-300 shadow-sm sm:text-sm focus:border-indigo-600 focus:ring-indigo-600" required>
          </div>

         {{-- Status toggle --}}
        <div>
        <div class="flex items-center justify-between rounded-lg border border-gray-200 bg-gray-50 px-3 py-2">
            <span class="text-sm font-medium text-gray-800">Active?</span>

            <label for="status" class="relative inline-flex items-center cursor-pointer select-none">
            <input type="hidden" name="status" value="0">
            <input id="status" name="status" type="checkbox" value="1"
                    class="sr-only peer" aria-checked="false" />

            {{-- Track --}}
            <span class="h-6 w-10 rounded-full bg-gray-300 ring-1 ring-inset ring-gray-300
                        transition-colors peer-checked:bg-indigo-600"></span>

            {{-- Thumb --}}
            <span class="pointer-events-none absolute left-0.5 top-0.5 h-5 w-5 rounded-full bg-white shadow
                        transition-transform peer-checked:translate-x-4"></span>
            </label>
        </div>
        </div>

        <div class="mt-4 flex justify-end gap-2">
          <button type="button" onclick="closeModal()" class="px-3 py-2 text-sm bg-gray-200 rounded-md">Cancel</button>
          <button type="submit" class="px-3 py-2 text-sm bg-indigo-600 text-white rounded-md">Save</button>
        </div>
      </form>
    </div>
  </div>

  {{-- JS --}}
  <script>
    (function () {
      // Debounced search
      const filterForm = document.getElementById('filterForm');
      const q = filterForm?.querySelector('input[name="q"]');
      let timer = null;
      if (q) {
        q.addEventListener('input', function () {
          clearTimeout(timer);
          timer = setTimeout(() => {
            if (filterForm.requestSubmit) filterForm.requestSubmit(); else filterForm.submit();
          }, 500);
        });
      }

      // Modal elements
      const modal       = document.getElementById('sessionModal');
      const content     = document.getElementById('sessionModalContent');
      const form        = document.getElementById('sessionForm');
      const title       = document.getElementById('modalTitle');
      const methodField = document.getElementById('methodField');
      const courseInput = document.getElementById('course_id');
      const yearInput   = document.getElementById('year');
      const nameInput   = document.getElementById('name');
      const statusInput = document.getElementById('status');

      const STORE_URL   = @json(route('course-sessions.store'));
      const CURRENT_FILTER_COURSE = @json($course_id ?? '');

      function lockScroll(){ document.documentElement.classList.add('overflow-hidden'); document.body.classList.add('overflow-hidden'); }
      function unlockScroll(){ document.documentElement.classList.remove('overflow-hidden'); document.body.classList.remove('overflow-hidden'); }
      function syncAria() {
        if (!statusInput) return;
        statusInput.setAttribute('aria-checked', statusInput.checked ? 'true' : 'false');
        }
      statusInput?.addEventListener('change', syncAria);

      window.openCreateModal = function () {
        form.action       = STORE_URL;
        methodField.value = "POST";
        title.textContent = "Create Session";

        courseInput.value = CURRENT_FILTER_COURSE || "";
        yearInput.value   = "";
        nameInput.value   = "";
        statusInput.checked = true;
        syncAria();

        modal.classList.remove('hidden'); lockScroll();
        setTimeout(() => courseInput.focus(), 0);
      };

      // Delegate edit buttons
      document.addEventListener('click', (e) => {
        const btn = e.target.closest('button[data-edit]');
        if (!btn) return;

        form.action       = "/admin/course-sessions/" + btn.dataset.id;
        methodField.value = "PUT";
        title.textContent = "Edit Session";

        courseInput.value = btn.dataset.course_id || "";
        yearInput.value   = btn.dataset.year || "";
        nameInput.value   = btn.dataset.name || "";
        statusInput.checked = btn.dataset.status === '1';
        syncAria();

        modal.classList.remove('hidden'); lockScroll();
        setTimeout(() => courseInput.focus(), 0);
      });

      window.closeModal = function () {
        modal.classList.add('hidden'); unlockScroll();
      };
      
      // backdrop + ESC close
      modal.addEventListener('click', (e) => { if (e.target === modal) closeModal(); });
      content.addEventListener('click', (e) => e.stopPropagation());
      document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && !modal.classList.contains('hidden')) closeModal();
      });
    })();
  </script>
</x-app-layout>
