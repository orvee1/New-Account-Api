<x-app-layout>
    {{-- Breadcrumb / Page Header --}}
    <div class="mb-6">
        <nav class="text-sm text-gray-500" aria-label="Breadcrumb">
            <ol class="flex items-center gap-2">
                <li class="inline-flex items-center">
                    <i class="fa fa-home mr-2 text-gray-400"></i>
                    <a href="{{ url('/') }}" class="hover:text-gray-700">Home</a>
                </li>
                <li class="text-gray-400">/</li>
                <li class="font-medium text-gray-700">{{ $title }}</li>
            </ol>
        </nav>
    </div>

    {{-- Card --}}
    <div class="rounded-2xl border border-gray-200 bg-white shadow-sm">
        <div class="flex flex-col gap-3 border-b border-gray-100 p-4 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex items-center gap-3">
                <span class="inline-flex h-9 w-9 items-center justify-center rounded-full bg-indigo-50">
                    <i class="fa fa-globe text-indigo-600"></i>
                </span>
                <h1 class="text-lg font-semibold text-gray-800">{{ $title }}</h1>
            </div>
            <div class="flex items-center gap-2">
                <a href="{{ route('question-types.create') }}"
                    class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-3 py-2 text-sm font-medium text-white hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                    <i class="fa fa-plus text-xs"></i>
                    Add New
                </a>
            </div>
        </div>

        {{-- Table wrapper (scrollable on small screens) --}}
        <div class="p-4">
            <div class="overflow-x-auto">
                <table class="min-w-[960px] w-full table-auto text-left">
                    <thead class="sticky top-0 z-10 bg-gray-50 text-xs uppercase tracking-wider text-gray-500">
                        <tr>
                            <th class="px-3 py-3">ID</th>
                            <th class="px-3 py-3">Title</th>
                            <th class="px-3 py-3">MCQ</th>
                            <th class="px-3 py-3">MCQ Mark</th>
                            <th class="px-3 py-3">MCQ Negative</th>
                            <th class="px-3 py-3">SBA</th>
                            <th class="px-3 py-3">SBA Mark</th>
                            <th class="px-3 py-3">SBA Negative</th>
                            <th class="px-3 py-3">EMQ</th>
                            <th class="px-3 py-3">EMQ Mark</th>
                            <th class="px-3 py-3">EMQ Negative</th>
                            <th class="px-3 py-3">Full Mark</th>
                            <th class="px-3 py-3">Duration (min)</th>
                            <th class="px-3 py-3">Paper / Faculty</th>
                            <th class="px-3 py-3 text-right">Actions</th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-gray-100 text-sm text-gray-700">
                        @foreach ($question_types as $question)
                            <tr class="hover:bg-gray-50">
                                <td class="px-3 py-3 font-medium text-gray-900">{{ $question->id }}</td>
                                <td class="px-3 py-3">{{ $question->title }}</td>
                                <td class="px-3 py-3">{{ $question->mcq_number }}</td>
                                <td class="px-3 py-3">{{ $question->mcq_mark }}</td>
                                <td class="px-3 py-3">{{ $question->mcq_negative_mark }}</td>
                                <td class="px-3 py-3">{{ $question->sba_number }}</td>
                                <td class="px-3 py-3">{{ $question->sba_mark }}</td>
                                <td class="px-3 py-3">{{ $question->sba_negative_mark }}</td>
                                <td class="px-3 py-3">{{ $question->emq_number }}</td>
                                <td class="px-3 py-3">{{ $question->emq_mark }}</td>
                                <td class="px-3 py-3">{{ $question->emq_negative_mark }}</td>
                                <td class="px-3 py-3">{{ $question->full_mark }}</td>
                                <td class="px-3 py-3">{{ $question->duration / 60 }}</td>
                                <td class="px-3 py-3">{{ $question->paper_faculty }}</td>
                                <td class="px-3 py-3">
                                    <div class="flex items-center justify-end gap-2">
                                        {{-- Show --}}
                                        <a href="{{ route('question-types.show', $question->id) }}"
                                            class="inline-flex items-center gap-1 rounded-md border border-sky-200 bg-sky-50 px-2.5 py-1.5 text-xs font-medium text-sky-700 hover:bg-sky-100">
                                            <i class="fa fa-eye mr-1 text-[11px]"></i> Show
                                        </a>

                                        {{-- Edit --}}
                                        <a href="{{ route('question-types.edit', $question->id) }}"
                                            class="inline-flex items-center gap-1 rounded-md border border-indigo-200 bg-indigo-50 px-2.5 py-1.5 text-xs font-medium text-indigo-700 hover:bg-indigo-100">
                                            <i class="fa fa-pencil mr-1 text-[11px]"></i> Edit
                                        </a>

                                        {{-- Delete --}}
                                        <form action="{{ route('question-types.destroy', $question->id) }}"
                                            method="POST" class="inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" onclick="return confirm('Are You Sure ?')"
                                                class="inline-flex items-center gap-1 rounded-md border border-rose-200 bg-rose-50 px-2.5 py-1.5 text-xs font-medium text-rose-700 hover:bg-rose-100">
                                                <i class="fa fa-trash mr-1 text-[11px]"></i> Delete
                                            </button>
                                        </form>
                                    </div>
                                </td>


                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
@section('scripts')
    <script>
        // Any additional scripts can go here
    </script>
@endsection
