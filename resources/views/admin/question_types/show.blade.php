{{-- resources/views/admin/question_types/show.blade.php --}}
<x-app-layout>
    @php
        /** @var \App\Models\QuestionTypes $question_type */
        $qt = $question_type;

        $isCombined = $qt->batch_type === 'combined';
        $durationMin = (int) floor(($qt->duration ?? 0) / 60);

        $h = intdiv($durationMin, 60);
        $m = $durationMin % 60;
        $durationHuman = $h > 0 ? "{$h}h {$m}m" : "{$m}m";

        $badge = fn(
            $text,
            $color,
        ) => "<span class=\"inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {$color}\">{$text}</span>";

        $na = fn($v) => isset($v) && $v !== '' ? $v : '—';
    @endphp

    {{-- Breadcrumb --}}
    <div class="mb-6">
        <nav class="text-sm text-gray-500" aria-label="Breadcrumb">
            <ol class="flex items-center gap-2">
                <li class="inline-flex items-center">
                    <i class="fa fa-home mr-2 text-gray-400"></i>
                    <a href="{{ url('/admin') }}" class="hover:text-gray-700">Home</a>
                </li>
                <li class="text-gray-400">/</li>
                <li>
                    <a href="{{ route('question-types.index') }}" class="hover:text-gray-700">Question Types</a>
                </li>
                <li class="text-gray-400">/</li>
                <li class="font-medium text-gray-700 truncate max-w-[60vw]">View</li>
            </ol>
        </nav>
    </div>

    {{-- Header Card --}}
    <div class="rounded-2xl border border-gray-200 bg-white shadow-sm">
        <div class="flex flex-col gap-4 border-b border-gray-100 p-5 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <div class="flex items-center gap-3">
                    <span class="inline-flex h-10 w-10 items-center justify-center rounded-xl bg-indigo-50">
                        <i class="fa fa-file-text text-indigo-600"></i>
                    </span>
                    <div>
                        <h1 class="text-lg font-semibold text-gray-900">
                            {{ $qt->title }}
                        </h1>
                        <div class="mt-1 flex flex-wrap items-center gap-2">
                            {!! $isCombined
                                ? $badge('Combined', 'bg-purple-50 text-purple-700 ring-1 ring-inset ring-purple-200')
                                : $badge('Normal', 'bg-sky-50 text-sky-700 ring-1 ring-inset ring-sky-200') !!}
                            {!! ($qt->status ?? 1) == 1
                                ? $badge('Active', 'bg-green-50 text-green-700 ring-1 ring-inset ring-green-200')
                                : $badge('Inactive', 'bg-rose-50 text-rose-700 ring-1 ring-inset ring-rose-200') !!}
                            {!! $qt->paper_faculty
                                ? $badge('Paper/Faculty: ' . $qt->paper_faculty, 'bg-amber-50 text-amber-700 ring-1 ring-inset ring-amber-200')
                                : '' !!}
                            {!! $qt->is_faculty
                                ? $badge('Is Faculty: ' . $qt->is_faculty, 'bg-fuchsia-50 text-fuchsia-700 ring-1 ring-inset ring-fuchsia-200')
                                : '' !!}
                        </div>
                    </div>
                </div>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <a href="{{ route('question-types.edit', $qt->id) }}"
                    class="inline-flex items-center gap-2 rounded-lg border border-indigo-200 bg-indigo-50 px-3 py-2 text-sm font-medium text-indigo-700 hover:bg-indigo-100">
                    <i class="fa fa-pencil"></i> Edit
                </a>
                <a href="{{ route('question-types.index') }}"
                    class="inline-flex items-center gap-2 rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">
                    <i class="fa fa-list-ul"></i> Back to list
                </a>
                <form action="{{ route('question-types.destroy', $qt->id) }}" method="POST"
                    onsubmit="return confirm('Delete this question type?')" class="inline">
                    @csrf
                    @method('DELETE')
                    <button type="submit"
                        class="inline-flex items-center gap-2 rounded-lg border border-rose-200 bg-rose-50 px-3 py-2 text-sm font-medium text-rose-700 hover:bg-rose-100">
                        <i class="fa fa-trash"></i> Delete
                    </button>
                </form>
            </div>
        </div>

        {{-- Stats --}}
        <div class="grid grid-cols-1 gap-4 p-5 sm:grid-cols-2 lg:grid-cols-4">
            <div class="rounded-xl border border-gray-100 p-4">
                <div class="text-xs text-gray-500">Full Mark</div>
                <div class="mt-1 text-2xl font-semibold text-gray-900">{{ $na($qt->full_mark) }}</div>
            </div>
            <div class="rounded-xl border border-gray-100 p-4">
                <div class="text-xs text-gray-500">Pass Mark</div>
                <div class="mt-1 text-2xl font-semibold text-gray-900">{{ $na($qt->pass_mark) }}</div>
            </div>
            <div class="rounded-xl border border-gray-100 p-4">
                <div class="text-xs text-gray-500">Duration</div>
                <div class="mt-1 text-2xl font-semibold text-gray-900">
                    {{ $durationHuman }} <span class="ml-1 text-sm text-gray-500">({{ $durationMin }} min)</span>
                </div>
            </div>
            <div class="rounded-xl border border-gray-100 p-4">
                <div class="text-xs text-gray-500">Updated</div>
                <div class="mt-1 text-base font-medium text-gray-900">
                    {{ optional($qt->updated_at)->format('M d, Y h:i A') ?? '—' }}
                </div>
            </div>
        </div>

        {{-- Sections --}}
        <div class="grid grid-cols-1 gap-6 p-5 lg:grid-cols-2">
            {{-- MCQ --}}
            <div class="rounded-xl border border-gray-100">
                <div class="flex items-center justify-between border-b border-gray-100 p-4">
                    <h3 class="text-sm font-semibold text-gray-800">MCQ</h3>
                    <i class="fa fa-question-circle text-gray-400"></i>
                </div>
                <div class="p-4 text-sm">
                    <dl class="grid grid-cols-2 gap-x-4 gap-y-2">
                        <dt class="text-gray-500">Number</dt>
                        <dd class="font-medium text-gray-900">{{ $na($qt->mcq_number) }}</dd>
                        <dt class="text-gray-500">Mark</dt>
                        <dd class="font-medium text-gray-900">{{ $na($qt->mcq_mark) }}</dd>
                        <dt class="text-gray-500">Negative Mark</dt>
                        <dd class="font-medium text-gray-900">{{ $na($qt->mcq_negative_mark) }}</dd>
                        <dt class="text-gray-500">Negative Range</dt>
                        <dd class="font-medium text-gray-900">{{ $na($qt->mcq_negative_mark_range) }}</dd>
                    </dl>
                </div>
            </div>

            {{-- MCQ2 (only if combined / data present) --}}
            @if ($isCombined || ($qt->mcq2_number || $qt->mcq2_mark || $qt->mcq2_negative_mark || $qt->mcq2_negative_mark_range))
                <div class="rounded-xl border border-gray-100">
                    <div class="flex items-center justify-between border-b border-gray-100 p-4">
                        <h3 class="text-sm font-semibold text-gray-800">MCQ-2</h3>
                        <i class="fa fa-question-circle text-gray-400"></i>
                    </div>
                    <div class="p-4 text-sm">
                        <dl class="grid grid-cols-2 gap-x-4 gap-y-2">
                            <dt class="text-gray-500">Number</dt>
                            <dd class="font-medium text-gray-900">{{ $na($qt->mcq2_number) }}</dd>
                            <dt class="text-gray-500">Mark</dt>
                            <dd class="font-medium text-gray-900">{{ $na($qt->mcq2_mark) }}</dd>
                            <dt class="text-gray-500">Negative Mark</dt>
                            <dd class="font-medium text-gray-900">{{ $na($qt->mcq2_negative_mark) }}</dd>
                            <dt class="text-gray-500">Negative Range</dt>
                            <dd class="font-medium text-gray-900">{{ $na($qt->mcq2_negative_mark_range) }}</dd>
                        </dl>
                    </div>
                </div>
            @endif

            {{-- SBA --}}
            <div class="rounded-xl border border-gray-100">
                <div class="flex items-center justify-between border-b border-gray-100 p-4">
                    <h3 class="text-sm font-semibold text-gray-800">SBA</h3>
                    <i class="fa fa-check-circle text-gray-400"></i>
                </div>
                <div class="p-4 text-sm">
                    <dl class="grid grid-cols-2 gap-x-4 gap-y-2">
                        <dt class="text-gray-500">Number</dt>
                        <dd class="font-medium text-gray-900">{{ $na($qt->sba_number) }}</dd>
                        <dt class="text-gray-500">Mark</dt>
                        <dd class="font-medium text-gray-900">{{ $na($qt->sba_mark) }}</dd>
                        <dt class="text-gray-500">Negative Mark</dt>
                        <dd class="font-medium text-gray-900">{{ $na($qt->sba_negative_mark) }}</dd>
                        <dt class="text-gray-500">Negative Range</dt>
                        <dd class="font-medium text-gray-900">{{ $na($qt->sba_negative_mark_range) }}</dd>
                    </dl>
                </div>
            </div>

            {{-- EMQ (optional) --}}
            @if ($qt->emq_number || $qt->emq_mark || $qt->emq_negative_mark || $qt->emq_negative_mark_range)
                <div class="rounded-xl border border-gray-100">
                    <div class="flex items-center justify-between border-b border-gray-100 p-4">
                        <h3 class="text-sm font-semibold text-gray-800">EMQ</h3>
                        <i class="fa fa-list-ol text-gray-400"></i>
                    </div>
                    <div class="p-4 text-sm">
                        <dl class="grid grid-cols-2 gap-x-4 gap-y-2">
                            <dt class="text-gray-500">Number</dt>
                            <dd class="font-medium text-gray-900">{{ $na($qt->emq_number) }}</dd>
                            <dt class="text-gray-500">Mark</dt>
                            <dd class="font-medium text-gray-900">{{ $na($qt->emq_mark) }}</dd>
                            <dt class="text-gray-500">Negative Mark</dt>
                            <dd class="font-medium text-gray-900">{{ $na($qt->emq_negative_mark) }}</dd>
                            <dt class="text-gray-500">Negative Range</dt>
                            <dd class="font-medium text-gray-900">{{ $na($qt->emq_negative_mark_range) }}</dd>
                        </dl>
                    </div>
                </div>
            @endif
        </div>

        {{-- Description --}}
        <div class="border-t border-gray-100 p-5">
            <h3 class="mb-3 text-sm font-semibold text-gray-800">Description</h3>
            <div class="prose max-w-none prose-p:my-2 prose-ul:my-2 prose-ol:my-2">
                {!! $qt->description ?: '<p class="text-gray-500">No description</p>' !!}
            </div>
        </div>
    </div>
</x-app-layout>
