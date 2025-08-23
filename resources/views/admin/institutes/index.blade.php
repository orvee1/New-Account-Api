{{-- resources/views/institutes/index.blade.php --}}
<x-app-layout>
  <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 py-6">
    {{-- Header --}}
    <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
      <h1 class="text-xl font-semibold text-gray-900">Institutes</h1>

      <button onclick="openCreateModal()"
              class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-3 py-2 text-sm font-medium text-white hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500">
        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor">
          <path d="M11 11V5h2v6h6v2h-6v6h-2v-6H5v-2z" />
        </svg>
        Add Institute
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

      {{-- Right side: Search --}}
      <div class="flex items-center gap-3">
        <div>
          <input type="text" name="q" value="{{ request('q') }}" placeholder="Search by name"
                 class="block min-w-60 rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" />
        </div>
      </div>
    </form>

    {{-- Card/Table --}}
    <div class="bg-white rounded-xl shadow-sm ring-1 ring-gray-200">
      <div class="px-5 py-3 border-b border-gray-200 text-sm text-gray-600">
        Showing {{ $institutes->firstItem() ?? 0 }}–{{ $institutes->lastItem() ?? 0 }} of {{ $institutes->total() ?? 0 }}
      </div>

      <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
          <thead class="bg-gray-50">
            <tr>
              <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">ID</th>
              <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">Name</th>
              <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">Faculty?</th>
              <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">Discipline?</th>
              <th class="px-4 py-3 text-right text-xs font-medium uppercase text-gray-500">Actions</th>
            </tr>
          </thead>
          <tbody class="bg-white divide-y divide-gray-100">
            @forelse($institutes as $institute)
              <tr class="hover:bg-gray-50">
                <td class="px-4 py-3 text-sm text-gray-700">#{{ $institute->id }}</td>
                <td class="px-4 py-3 text-sm font-medium text-gray-900">{{ $institute->name }}</td>

                <td class="px-4 py-3 text-sm">
                  @if ($institute->has_faculty)
                    <span class="inline-flex items-center rounded-full bg-emerald-50 px-2 py-0.5 text-xs font-medium text-emerald-700 border border-emerald-200">
                      Yes
                    </span>
                  @else
                    <span class="text-gray-400">—</span>
                  @endif
                </td>

                <td class="px-4 py-3 text-sm">
                  @if ($institute->has_discipline)
                    <span class="inline-flex items-center rounded-full bg-emerald-50 px-2 py-0.5 text-xs font-medium text-emerald-700 border border-emerald-200">
                      Yes
                    </span>
                  @else
                    <span class="text-gray-400">—</span>
                  @endif
                </td>

                <td class="px-4 py-3">
                  <div class="flex justify-end gap-2">
                    {{-- Edit (modal) --}}
                    <button
                      onclick="openEditModal({{json_encode([
                        'id'             => $institute->id,
                        'name'           => $institute->name,
                        'has_faculty '   => (bool)$institute->has_faculty,
                        'has_discipline' => (bool)$institute->has_discipline,
                      ]) }})"
                      class="px-3 py-1.5 rounded-md text-white bg-blue-600 hover:bg-blue-700 text-xs">
                      Edit
                    </button>

                    {{-- Soft delete --}}
                    <form action="{{ route('institutes.destroy', $institute) }}" method="POST"
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
                <td colspan="5" class="px-4 py-10 text-center text-sm text-gray-500">No institutes found.</td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>

      <div class="px-5 py-3 border-t border-gray-200">
        {{ $institutes->appends(request()->query())->onEachSide(1)->links() }}
      </div>
    </div>
  </div>

  {{-- Create/Edit Modal (Institutes) --}}
  <div id="instModal" class="hidden fixed inset-0 bg-gray-800/60 flex items-center justify-center z-50">
    <div class="bg-white rounded-lg shadow-lg p-6 w-full max-w-md">
      <h2 id="instModalTitle" class="text-lg font-semibold mb-4">Create Institute</h2>

      <form id="instForm" method="POST">
        @csrf
        <input type="hidden" id="instMethod" name="_method" value="POST">

        {{-- Name --}}
        <div class="mb-4">
          <label class="block text-sm font-medium text-gray-700 mb-1">Name <span class="text-rose-600">*</span></label>
          <input type="text" name="name" id="inst_name" required
                 class="w-full rounded-lg border-gray-300 focus:border-gray-900 focus:ring-gray-900"/>
        </div>

        {{-- Flags --}}
      <div class="max-w-56 space-y-4 rounded-xl border border-gray-200 bg-gray-50 p-4">
  {{-- Has Faculty --}}
  <label for="inst_has_faculty" class="flex items-center justify-between gap-4 cursor-pointer select-none">
    <div class="min-w-0">
      <span class="block text-sm font-medium text-gray-800">Has Faculty?</span>
    </div>

    <span class="relative inline-flex items-center">
      <input type="hidden" name="has_faculty" value="0">
      <input
        id="inst_has_faculty"
        name="has_faculty"
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

      {{-- Off/On text (inside track via absolute) --}}
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

  {{-- Has Discipline --}}
  <label for="inst_has_discipline" class="flex items-center justify-between gap-4 cursor-pointer select-none">
    <div class="min-w-0">
      <span class="block text-sm font-medium text-gray-800">Has Discipline?</span>
    </div>

    <span class="relative inline-flex items-center">
      <input type="hidden" name="has_discipline" value="0">
      <input
        id="inst_has_discipline"
        name="has_discipline"
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

        <div class="flex justify-end gap-2 pt-2">
          <button type="button" onclick="closeInstModal()"
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
      const instModal        = document.getElementById('instModal');
      const instForm         = document.getElementById('instForm');
      const instMethodField  = document.getElementById('instMethod');
      const instTitleEl      = document.getElementById('instModalTitle');

      const nameEl           = document.getElementById('inst_name');
      const hasFacultyEl     = document.getElementById('inst_has_faculty');
      const hasDisciplineEl  = document.getElementById('inst_has_discipline');

      // Blade routes
      const STORE_URL  = @json(route('institutes.store'));

      // Open "Create" modal
      window.openCreateModal = function(preset = {}) {
        instForm.action         = STORE_URL;
        instMethodField.value   = 'POST';
        instTitleEl.textContent = 'Create Institute';

        nameEl.value            = preset.name ?? '';
        hasFacultyEl.checked    = !!preset.has_faculty;
        hasDisciplineEl.checked = !!preset.has_discipline;

        instModal.classList.remove('hidden');
      };

      // Open "Edit" modal
      window.openEditModal = function(data) {
        // data: {id, name, has_faculty, has_discipline}
        instForm.action         = `/admin/institutes/${data.id}`;
        instMethodField.value   = 'PUT';
        instTitleEl.textContent = 'Edit Institute';

        nameEl.value            = data.name ?? '';
        hasFacultyEl.checked    = !!data.has_faculty;
        hasDisciplineEl.checked = !!data.has_discipline;

        instModal.classList.remove('hidden');
      };

      // Close modal
      window.closeInstModal = function() {
        instModal.classList.add('hidden');
      };

      // ESC to close
      document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') window.closeInstModal();
      });
    })();
  </script>
</x-app-layout>
