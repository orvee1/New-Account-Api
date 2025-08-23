{{-- resources/views/schedules/create.blade.php --}}
<x-app-layout>
    <div class="max-w-5xl mx-auto py-6">
        <form action="{{ route('schedules.store') }}" method="POST" id="scheduleForm">
            @csrf

            <div class="mb-4 grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="col-span-2">
                    <label class="block font-semibold">Schedule Title (optional)</label>
                    <input name="title" class="w-full border rounded p-2" placeholder="e.g., Module 3 – Week Plan">
                </div>

                <div>
                    <label class="block font-semibold">Default Exam ID</label>
                    <input name="default_exam_id" id="defaultExamInput" class="w-full border rounded p-2" placeholder="EX-2025-001">
                </div>
            </div>

            @if($module)
                <input type="hidden" name="module_id" value="{{ $module->id }}">
                <div class="mb-2 text-sm text-gray-600">
                    Module: <span class="font-semibold">{{ $module->name }}</span>
                </div>
            @endif

            <div id="topic-wrapper" class="space-y-6">
                {{-- Module থেকে ডিফল্ট টপিকগুলো --}}
                @forelse($moduleTopics as $i => $mt)
                    <div class="topic-block border rounded p-4 space-y-4 bg-gray-50">
                        <div class="flex justify-between items-start">
                            <div class="text-sm text-gray-600">Module Topic</div>
                            <button type="button" class="remove-topic text-red-600">Remove</button>
                        </div>

                        <input type="hidden" name="topics[{{ $i }}][topic_id]" value="{{ $mt->topic_id }}">

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block font-semibold">Topic</label>
                                <input disabled class="w-full border rounded p-2 bg-gray-100" value="{{ $mt->topic_name }}">
                            </div>
                            <div>
                                <label class="block font-semibold">Exam ID (optional)</label>
                                <input name="topics[{{ $i }}][exam_id]" class="exam-input w-full border rounded p-2" placeholder="leave blank to use default">
                            </div>
                            <div>
                                <label class="block font-semibold">Date</label>
                                <input type="date" name="topics[{{ $i }}][date]" class="w-full border rounded p-2">
                            </div>
                            <div>
                                <label class="block font-semibold">Time</label>
                                <input type="time" name="topics[{{ $i }}][time]" class="w-full border rounded p-2" value="10:00">
                            </div>
                        </div>

                        {{-- Solves --}}
                        <div class="solve-wrapper space-y-2">
                            <label class="block font-semibold">Solve / Video IDs</label>
                            <div class="flex items-center gap-2">
                                <input name="topics[{{ $i }}][solves][]" class="w-full border rounded p-2" placeholder="Video ID">
                                <button type="button" class="add-solve bg-green-600 text-white px-3 py-2 rounded">+ Add Solve</button>
                            </div>
                        </div>
                    </div>
                @empty
                    {{-- যদি module না থাকে বা টপিক না আসে, একটি খালি ব্লক দেখাবো --}}
                @endforelse
            </div>

            {{-- বাইরে থেকে (non-module) টপিক যোগ --}}
            <div class="mt-4 flex items-center gap-2">
                <button type="button" id="add-external-topic" class="bg-blue-600 text-white px-4 py-2 rounded">+ Add Topic (outside module)</button>
                <button type="button" id="add-empty-topic" class="bg-sky-600 text-white px-4 py-2 rounded">+ Add Blank Topic</button>
            </div>

            <div class="mt-8">
                <button class="bg-purple-700 text-white px-6 py-2 rounded">Create Schedule</button>
            </div>
        </form>
    </div>

    <script>
        let topicIndex = {{ max(count($moduleTopics), 0) }};

        // default exam → topic exam inputs auto-fill if empty
        const defaultExamInput = document.getElementById('defaultExamInput');
        defaultExamInput?.addEventListener('input', () => {
            document.querySelectorAll('.exam-input').forEach(inp => {
                if (!inp.value) inp.placeholder = defaultExamInput.value || 'leave blank to use default';
            });
        });

        // Add external topic (select from list)
        document.getElementById('add-external-topic').addEventListener('click', () => {
            const wrapper = document.getElementById('topic-wrapper');
            const el = document.createElement('div');
            el.className = 'topic-block border rounded p-4 space-y-4 bg-white';

            const topicOptions = @json($allTopics->map(fn($t)=>['id'=>$t->id,'name'=>$t->name]));
            const opts = topicOptions.map(t => `<option value="${t.id}">${t.name}</option>`).join('');

            el.innerHTML = `
                <div class="flex justify-between items-start">
                    <div class="text-sm text-gray-600">External Topic</div>
                    <button type="button" class="remove-topic text-red-600">Remove</button>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block font-semibold">Select Topic</label>
                        <select name="topics[${topicIndex}][topic_id]" class="w-full border rounded p-2">
                            <option value="">-- choose --</option>
                            ${opts}
                        </select>
                    </div>
                    <div>
                        <label class="block font-semibold">Or Custom Name</label>
                        <input name="topics[${topicIndex}][custom_topic_name]" class="w-full border rounded p-2" placeholder="Type if not in list">
                    </div>

                    <div>
                        <label class="block font-semibold">Date</label>
                        <input type="date" name="topics[${topicIndex}][date]" class="w-full border rounded p-2">
                    </div>
                    <div>
                        <label class="block font-semibold">Time</label>
                        <input type="time" name="topics[${topicIndex}][time]" class="w-full border rounded p-2" value="10:00">
                    </div>

                    <div>
                        <label class="block font-semibold">Exam ID (optional)</label>
                        <input name="topics[${topicIndex}][exam_id]" class="exam-input w-full border rounded p-2" placeholder="leave blank to use default">
                    </div>
                </div>

                <div class="solve-wrapper space-y-2">
                    <label class="block font-semibold">Solve / Video IDs</label>
                    <div class="flex items-center gap-2">
                        <input name="topics[${topicIndex}][solves][]" class="w-full border rounded p-2" placeholder="Video ID">
                        <button type="button" class="add-solve bg-green-600 text-white px-3 py-2 rounded">+ Add Solve</button>
                    </div>
                </div>
            `;
            wrapper.appendChild(el);
            topicIndex++;
        });

        // Add totally blank topic (only custom name)
        document.getElementById('add-empty-topic').addEventListener('click', () => {
            const wrapper = document.getElementById('topic-wrapper');
            const el = document.createElement('div');
            el.className = 'topic-block border rounded p-4 space-y-4 bg-white';
            el.innerHTML = `
                <div class="flex justify-between items-start">
                    <div class="text-sm text-gray-600">Custom Topic</div>
                    <button type="button" class="remove-topic text-red-600">Remove</button>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block font-semibold">Topic Name</label>
                        <input name="topics[${topicIndex}][custom_topic_name]" class="w-full border rounded p-2" placeholder="Enter topic name">
                    </div>
                    <div>
                        <label class="block font-semibold">Exam ID (optional)</label>
                        <input name="topics[${topicIndex}][exam_id]" class="exam-input w-full border rounded p-2" placeholder="leave blank to use default">
                    </div>
                    <div>
                        <label class="block font-semibold">Date</label>
                        <input type="date" name="topics[${topicIndex}][date]" class="w-full border rounded p-2">
                    </div>
                    <div>
                        <label class="block font-semibold">Time</label>
                        <input type="time" name="topics[${topicIndex}][time]" class="w-full border rounded p-2" value="10:00">
                    </div>
                </div>
                <div class="solve-wrapper space-y-2">
                    <label class="block font-semibold">Solve / Video IDs</label>
                    <div class="flex items-center gap-2">
                        <input name="topics[${topicIndex}][solves][]" class="w-full border rounded p-2" placeholder="Video ID">
                        <button type="button" class="add-solve bg-green-600 text-white px-3 py-2 rounded">+ Add Solve</button>
                    </div>
                </div>
            `;
            wrapper.appendChild(el);
            topicIndex++;
        });

        // Delegated events: add-solve & remove-topic
        document.addEventListener('click', function (e) {
            if (e.target.classList.contains('add-solve')) {
                const wrap = e.target.closest('.solve-wrapper');
                const input = document.createElement('div');
                input.className = 'flex items-center gap-2 mt-2';
                const namePrefix = wrap.querySelector('input').name.replace(/\[\d+\]$/, '[]');
                input.innerHTML = `<input name="${namePrefix}" class="w-full border rounded p-2" placeholder="Video ID">`;
                wrap.appendChild(input);
            }
            if (e.target.classList.contains('remove-topic')) {
                e.target.closest('.topic-block').remove();
            }
        });
    </script>
</x-app-layout>
