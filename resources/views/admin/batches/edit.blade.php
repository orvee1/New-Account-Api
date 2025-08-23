{{-- resources/views/admin/batches/edit.blade.php --}}
<x-app-layout>
  <div class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8 py-6 space-y-5">

    {{-- Context header (Create পেজের মতো) --}}
    @php($ctx = $session ?? ($batch->courseSession ?? null))
    <div class="flex flex-wrap items-center gap-2">
      <a href="{{ $ctx ? route('batches.session', $ctx) : route('batches.index') }}"
         class="rounded-md bg-emerald-600 px-4 py-2 text-sm font-medium text-white hover:bg-emerald-700">← Back</a>

      <h1 class="text-xl font-semibold text-gray-900">Edit Batch</h1>

      @if($ctx)
        <span class="ml-auto"></span>
        <span class="rounded-md bg-white px-4 py-2 text-sm font-semibold border">{{ $ctx->year }}</span>
        <span class="rounded-md bg-white px-4 py-2 text-sm font-semibold border">
          {{ optional($ctx->course)->name ?? '—' }}
        </span>
        <span class="rounded-md bg-white px-4 py-2 text-sm font-semibold border">
          {{ $ctx->title ?? $ctx->name ?? 'Session' }}
        </span>
      @endif
    </div>

    {{-- Alerts --}}
    @if (session('success'))
      <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-2 text-emerald-700">
        {{ session('success') }}
      </div>
    @endif
    @if ($errors->any())
      <div class="rounded-lg border border-rose-200 bg-rose-50 px-4 py-2 text-rose-700">
        <ul class="list-disc pl-5 text-sm space-y-1">
          @foreach ($errors->all() as $err) <li>{{ $err }}</li> @endforeach
        </ul>
      </div>
    @endif

    <form method="POST" action="{{ route('batches.update', $batch) }}" class="space-y-6">
      @csrf
      @method('PUT')

      {{-- Card: Basic details --}}
      <div class="rounded-2xl border border-gray-200 bg-white shadow-sm">
        <div class="border-b px-5 py-3">
          <h2 class="text-sm font-semibold text-gray-800">Basic details</h2>
        </div>
        <div class="p-5 grid grid-cols-1 sm:grid-cols-2 gap-5">
          {{-- Name --}}
          <div>
            <label class="mb-1.5 block text-sm font-medium text-gray-700">
              Name <span class="text-rose-600">*</span>
            </label>
            <input type="text" name="name" required
                   value="{{ old('name', $batch->name) }}"
                   class="w-full rounded-md border-gray-300 focus:border-indigo-600 focus:ring-indigo-600"/>
          </div>

          {{-- Start Date (date input যাতে UX একই থাকে) --}}
          <div>
            <label class="mb-1.5 block text-sm font-medium text-gray-700">Start Date</label>
            <input type="date" name="start_date"
                   value="{{ old('start_date', $batch->start_date) }}"
                   class="w-full rounded-md border-gray-300 focus:border-indigo-600 focus:ring-indigo-600"/>
          </div>

          {{-- Expired At --}}
          <div>
            <label class="mb-1.5 block text-sm font-medium text-gray-700">Expired At</label>
            <input type="datetime-local" name="expired_at"
                   value="{{ old('expired_at', $batch->expired_at ? \Illuminate\Support\Carbon::parse($batch->expired_at)->format('Y-m-d\TH:i') : '') }}"
                   class="w-full rounded-md border-gray-300 focus:border-indigo-600 focus:ring-indigo-600"/>
          </div>

          {{-- Status --}}
          <div>
            <label class="mb-1.5 block text-sm font-medium text-gray-700">Status</label>
            <select name="status"
                    class="w-full rounded-md border-gray-300 focus:border-indigo-600 focus:ring-indigo-600">
              <option value="1" {{ (int)old('status', $batch->status ?? 1) === 1 ? 'selected' : '' }}>Active</option>
              <option value="0" {{ (int)old('status', $batch->status ?? 1) === 0 ? 'selected' : '' }}>Inactive</option>
            </select>
          </div>

          {{-- Show Admission --}}
          <div class="sm:col-span-2">
            <div class="flex items-center justify-between rounded-lg border border-gray-200 bg-gray-50 px-3 py-2">
              <span class="text-sm font-medium text-gray-800">Show Admission?</span>
              <span class="relative inline-flex items-center">
                <input type="hidden" name="is_show_admission" value="0">
                <input id="is_show_admission" name="is_show_admission" type="checkbox" value="1"
                       {{ old('is_show_admission', (bool)$batch->is_show_admission) ? 'checked' : '' }}
                       class="peer sr-only" role="switch"/>
                <span class="h-6 w-10 rounded-full bg-gray-300 ring-1 ring-inset ring-gray-300 transition-colors peer-checked:bg-indigo-600"></span>
                <span class="pointer-events-none absolute left-0.5 top-0.5 h-5 w-5 rounded-full bg-white shadow transition-transform peer-checked:translate-x-4"></span>
              </span>
            </div>
          </div>
        </div>
      </div>

      {{-- Card: Media --}}
      <div class="rounded-2xl border border-gray-200 bg-white shadow-sm">
        <div class="border-b px-5 py-3">
          <h2 class="text-sm font-semibold text-gray-800">Media</h2>
        </div>
        <div class="p-5">
          <label class="mb-1.5 block text-sm font-medium text-gray-700">Banner URL</label>
          <input type="text" name="banner_url" value="{{ old('banner_url', $batch->banner_url) }}"
                 class="w-full rounded-md border-gray-300 focus:border-indigo-600 focus:ring-indigo-600"/>
          <p class="mt-1 text-xs text-gray-500">Use a full URL (https://…)</p>
        </div>
      </div>

      {{-- Card: Content --}}
      <div class="rounded-2xl border border-gray-200 bg-white shadow-sm">
        <div class="border-b px-5 py-3">
          <h2 class="text-sm font-semibold text-gray-800">Content</h2>
        </div>
        <div class="p-5 grid grid-cols-1 gap-5">
          <div>
            <label class="mb-1.5 block text-sm font-medium text-gray-700">Description</label>
            <textarea name="description" rows="3"
              class="w-full rounded-md border-gray-300 focus:border-indigo-600 focus:ring-indigo-600">{{ old('description', $batch->description) }}</textarea>
          </div>

          <div>
            <label class="mb-1.5 block text-sm font-medium text-gray-700">Course Outline</label>
            <textarea name="course_outline" rows="3"
              class="w-full rounded-md border-gray-300 focus:border-indigo-600 focus:ring-indigo-600">{{ old('course_outline', $batch->course_outline) }}</textarea>
          </div>

          <div>
            <label class="mb-1.5 block text-sm font-medium text-gray-700">Course Fee Offer</label>
            <textarea name="course_fee_offer" rows="3"
              class="w-full rounded-md border-gray-300 focus:border-indigo-600 focus:ring-indigo-600">{{ old('course_fee_offer', $batch->course_fee_offer) }}</textarea>
          </div>

          <div>
            <label class="mb-1.5 block text-sm font-medium text-gray-700">Registration Process</label>
            <textarea name="registration_process" rows="3"
              class="w-full rounded-md border-gray-300 focus:border-indigo-600 focus:ring-indigo-600">{{ old('registration_process', $batch->registration_process) }}</textarea>
          </div>

          <div>
            <label class="mb-1.5 block text-sm font-medium text-gray-700">Expired Message</label>
            <textarea name="expired_message" rows="2"
              class="w-full rounded-md border-gray-300 focus:border-indigo-600 focus:ring-indigo-600">{{ old('expired_message', $batch->expired_message) }}</textarea>
          </div>
        </div>
      </div>

      {{-- Actions --}}
      <div class="flex justify-end gap-2">
        <a href="{{ $ctx ? route('batches.session', $ctx) : route('batches.index') }}"
           class="rounded-md bg-gray-200 px-3 py-2 text-sm">Cancel</a>
        <button type="submit"
                class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700">
          Update
        </button>
      </div>
    </form>
  </div>
</x-app-layout>
