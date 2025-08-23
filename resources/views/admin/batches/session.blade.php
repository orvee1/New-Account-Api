{{-- resources/views/admin/batches/session.blade.php --}}
<x-app-layout>
  <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 py-6 space-y-4">

    {{-- Header badges --}}
    <div class="flex flex-wrap items-center gap-2">
      <a href="{{ route('batches.index') }}"
         class="rounded-md bg-emerald-600 px-4 py-2 text-sm font-medium text-white hover:bg-emerald-700">
        ← Back
      </a>

      <span class="rounded-md bg-white px-4 py-2 text-sm font-semibold border">
        {{ $selectedSession->year }}
      </span>
      <span class="rounded-md bg-white px-4 py-2 text-sm font-semibold border">
        {{ $selectedSession->course->name ?? '—' }}
      </span>
      <span class="rounded-md bg-white px-4 py-2 text-sm font-semibold border">
        {{ $selectedSession->title ?? $selectedSession->name ?? 'Session' }}
      </span>

      <a href="{{ route('batches.session.create', $selectedSession) }}"
         class="ml-auto inline-flex items-center gap-2 rounded-md bg-sky-600 px-4 py-2 text-sm font-medium text-white hover:bg-sky-700">
        + Add Batch
      </a>
    </div>

    {{-- Top meta + pagination + search --}}
    <div class="mt-3 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
      <p class="text-sm text-gray-600">
        Showing {{ $batches->firstItem() ?? 0 }} to {{ $batches->lastItem() ?? 0 }} of {{ $batches->total() ?? 0 }} results
      </p>

      <div class="flex items-center gap-3">
        <div class="hidden sm:block">
          {{ $batches->appends(request()->query())->onEachSide(1)->links() }}
        </div>

        <form method="GET" action="{{ route('batches.session', $selectedSession) }}" id="sessionSearchForm" class="relative">
          @foreach(request()->except(['q','page']) as $k => $v)
            <input type="hidden" name="{{ $k }}" value="{{ $v }}">
          @endforeach
          <input type="text" name="q" value="{{ request('q') }}" placeholder="Search..."
                 class="w-60 rounded-md border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
        </form>
      </div>
    </div>

    {{-- Table --}}
    <div class="mt-3 overflow-x-auto rounded-xl border border-gray-200 bg-white">
      <table class="min-w-full text-sm">
        <thead class="bg-gray-50">
          <tr class="text-left">
            <th class="px-4 py-3 font-medium text-gray-600">ID</th>
            <th class="px-4 py-3 font-medium text-gray-600">Name</th>
            <th class="px-4 py-3 font-medium text-gray-600">Start Date</th>
            <th class="px-4 py-3 font-medium text-gray-600">Admission</th>
            <th class="px-4 py-3 font-medium text-gray-600">Status</th>
            <th class="px-4 py-3 font-medium text-gray-600">Expiry</th>
            <th class="px-4 py-3 font-medium text-gray-600 text-right">Actions</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
          @forelse($batches as $row)
            <tr class="hover:bg-gray-50">
              <td class="px-4 py-3 text-gray-700">{{ $row->id }}</td>
              <td class="px-4 py-3 font-medium text-gray-900">{{ $row->name }}</td>
              <td class="px-4 py-3 text-gray-700">{{ $row->start_date ?? '—' }}</td>
              <td class="px-4 py-3">
                @if ($row->is_show_admission)
                  <span class="rounded-full border border-emerald-200 bg-emerald-50 px-2 py-0.5 text-xs font-medium text-emerald-700">Show</span>
                @else
                  <span class="rounded-full border border-gray-200 bg-gray-100 px-2 py-0.5 text-xs font-medium text-gray-600">Hidden</span>
                @endif
              </td>
              <td class="px-4 py-3">
                @if ((int)$row->status === 1)
                  <span class="rounded-full border border-indigo-200 bg-indigo-50 px-2 py-0.5 text-xs font-medium text-indigo-700">Active</span>
                @else
                  <span class="rounded-full border border-gray-200 bg-gray-100 px-2 py-0.5 text-xs font-medium text-gray-600">Inactive</span>
                @endif
              </td>
              <td class="px-4 py-3 text-gray-700">
                {{ $row->expired_at ? \Illuminate\Support\Carbon::parse($row->expired_at)->format('Y-m-d H:i') : '—' }}
              </td>
              <td class="px-4 py-3">
                <div class="flex justify-end gap-2">
                  <a href="{{ route('batches.edit', $row) }}"
                     class="rounded-md bg-blue-600 px-3 py-1.5 text-xs font-medium text-white hover:bg-blue-700">Edit</a>
                  <form action="{{ route('batches.destroy', $row) }}" method="POST" onsubmit="return confirm('Move to trash?')">
                    @csrf @method('DELETE')
                    <button type="submit"
                            class="rounded-md bg-amber-500 px-3 py-1.5 text-xs font-medium text-white hover:bg-amber-600">Trash</button>
                  </form>
                </div>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="7" class="px-4 py-10 text-center text-gray-500">No batches in this session yet.</td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>

    <div class="mt-3 flex justify-end">
      {{ $batches->appends(request()->query())->onEachSide(1)->links() }}
    </div>
  </div>

  <script>
    (function () {
      const f = document.getElementById('sessionSearchForm');
      const q = f?.querySelector('input[name="q"]');
      if (!q) return;
      let t = null;
      q.addEventListener('input', () => {
        clearTimeout(t);
        t = setTimeout(() => f.requestSubmit ? f.requestSubmit() : f.submit(), 500);
      });
    })();
  </script>
</x-app-layout>
