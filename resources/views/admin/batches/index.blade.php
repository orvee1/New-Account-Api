{{-- resources/views/admin/batches/index.blade.php --}}
<x-app-layout>
  <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 py-6 space-y-4">

    {{-- Filters --}}
    <form method="GET" id="filterForm"
          class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
      <div class="flex flex-wrap items-center gap-3">
        {{-- Year --}}
        <select name="year"
                class="rounded-md border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                onchange="this.form.submit()">
          <option value="">-- All Year --</option>
          @php
            $yearNow = (int) date('Y');
            $yearOptions = isset($years) && count($years)
              ? $years
              : range($yearNow + 1, $yearNow - 5, -1);
          @endphp
          @foreach ($yearOptions as $y)
            @php $val = is_object($y) ? ($y->year ?? $y->value ?? $y) : $y; @endphp
            <option value="{{ $val }}" {{ (string)request('year') === (string)$val ? 'selected' : '' }}>
              {{ $val }}
            </option>
          @endforeach
        </select>

        {{-- Course --}}
        <select name="course_id"
                class="rounded-md border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                onchange="this.form.submit()">
          <option value="">-- All Course --</option>
          @foreach($courses as $course)
            <option value="{{ $course->id }}"
              {{ (string)request('course_id') === (string)$course->id ? 'selected' : '' }}>
              {{ $course->name }}
            </option>
          @endforeach
        </select>

        {{-- Search --}}
        <input type="text" name="q" value="{{ request('q') }}" placeholder="Search..."
               class="rounded-md border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
      </div>
    </form>

    {{-- Meta + pagination --}}
    <div class="flex items-center justify-between">
      <p class="text-sm text-gray-600">
        Showing {{ $sessions->firstItem() ?? 0 }}–{{ $sessions->lastItem() ?? 0 }} of {{ $sessions->total() ?? 0 }} sessions
      </p>
      <div class="shrink-0">
        {{ $sessions->appends(request()->query())->onEachSide(1)->links() }}
      </div>
    </div>

    {{-- Session cards --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-3 gap-6">
  @forelse($sessions as $session)
    @php
      $yearShow    = $session->year ?? '—';
      $courseTitle = $session->course->name ?? '—';
      $sessionName = $session->title ?? $session->name ?? '—';
    @endphp

    <div class="h-auto rounded-2xl border border-gray-200 bg-white shadow-md hover:shadow-lg transition flex flex-col">
      {{-- Top: Year + Course --}}
      <div class="grid grid-cols-3">
        <div class="col-span-1 flex items-center justify-center border-b border-gray-200 px-4 py-3">
          <span class="text-xl font-semibold text-gray-900">{{ $yearShow }}</span>
        </div>
        <div class="col-span-2 border-b border-l border-gray-200 px-4 py-3">
          {{-- কোর্সের নাম পুরো দেখাতে clamp/truncate বাদ, wrap allow --}}
          <div class="font-medium text-gray-800 text-base md:text-lg whitespace-normal break-words"
               title="{{ $courseTitle }}">
            {{ $courseTitle }}
          </div>
        </div>
      </div>

      {{-- Middle: Session name --}}
      <div class="border-b border-gray-200 px-6 py-3">
        <div class="text-lg md:text-xl font-semibold text-center whitespace-normal break-words">
          {{ $sessionName }}
        </div>
      </div>

      {{-- Bottom: Button (padding কমানো হয়েছে, extra space নেই) --}}
      <div class="px-6 py-4">
        <a href="{{ route('batches.session', $session) }}"
           class="w-full block rounded-lg bg-sky-600 px-5 py-3 text-center text-base font-semibold text-white hover:bg-sky-700">
          Batches
        </a>
      </div>
    </div>
  @empty
    <div class="sm:col-span-2 lg:col-span-3 xl:col-span-3">
      <div class="rounded-xl border border-dashed border-gray-300 p-10 text-center text-gray-500">
        No sessions found.
      </div>
    </div>
  @endforelse
</div>


    <div class="flex justify-end">
      {{ $sessions->appends(request()->query())->onEachSide(1)->links() }}
    </div>
  </div>

  <script>
    (function () {
      const form = document.getElementById('filterForm');
      const q = form?.querySelector('input[name="q"]');
      if (!q) return;
      let t = null;
      q.addEventListener('input', () => {
        clearTimeout(t);
        t = setTimeout(() => form.requestSubmit ? form.requestSubmit() : form.submit(), 500);
      });
    })();
  </script>
</x-app-layout>
