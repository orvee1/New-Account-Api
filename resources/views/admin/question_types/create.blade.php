{{-- resources/views/admin/question_types/create.blade.php --}}
<x-app-layout>
    {{-- Breadcrumb --}}
    <div class="mb-6">
        <nav class="text-sm text-gray-500" aria-label="Breadcrumb">
            <ol class="flex items-center gap-2">
                <li class="inline-flex items-center">
                    <i class="fa fa-home mr-2 text-gray-400"></i>
                    <a href="{{ url('/') }}" class="hover:text-gray-700">Home</a>
                </li>
                <li class="text-gray-400">/</li>
                <li class="font-medium text-gray-700">{{ $title ?? 'Create Question Type' }}</li>
            </ol>
        </nav>
    </div>

    {{-- Flash --}}
    {{-- @if (session('message'))
        <div class="mb-6 rounded-lg border {{ session('class') ?: 'border-green-200 bg-green-50 text-green-800' }} p-4">
            <p>{{ session('message') }}</p>
        </div>
    @endif --}}

    {{-- Validation errors --}}
    {{-- @if ($errors->any())
        <div class="mb-6 rounded-lg border border-rose-200 bg-rose-50 p-4 text-rose-700">
            <div class="font-semibold mb-1">Please fix the following:</div>
            <ul class="list-disc pl-5 space-y-0.5">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif --}}

    {{-- Card --}}
    <div class="rounded-2xl border border-gray-200 bg-white shadow-sm">
        <div class="flex items-center gap-3 border-b border-gray-100 p-4">
            <span class="inline-flex h-9 w-9 items-center justify-center rounded-full bg-indigo-50">
                <i class="fa fa-reorder text-indigo-600"></i>
            </span>
            <h1 class="text-lg font-semibold text-gray-800">{{ $title ?? 'Create Question Type' }}</h1>
        </div>

        <div class="p-6">
            {{-- BEGIN FORM --}}
            <form method="POST" action="{{ route('question-types.store') }}" enctype="multipart/form-data" class="space-y-6" id="qt-form">
                @csrf

                {{-- Batch Type --}}
                <div class="sm:w-1/2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Batch Type <i class="fa fa-asterisk text-[10px] text-rose-500"></i>
                    </label>
                    <select name="batch_type" id="batch-type"
                            class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                        <option value="-" {{ old('batch_type') == '' ? 'selected' : '' }}>Normal</option>
                        <option value="combined" {{ old('batch_type') == 'combined' ? 'selected' : '' }}>Combined</option>
                    </select>
                </div>

                {{-- Title --}}
                <div class="sm:w-1/2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Question Type Title <i class="fa fa-asterisk text-[10px] text-rose-500"></i>
                    </label>
                    <input type="text" name="title" required value="{{ old('title') }}"
                           class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                </div>

                {{-- MCQ / MCQ2 / SBA / EMQ (partials) --}}
                <div id="mcq_data" class="my-5 border-b border-dashed border-green-500 py-5">
                    @include('admin.question_types.question_type_data', ['name' => 'mcq', 'required' => true])
                </div>

                {{-- MCQ2: hidden when not combined (initial state from old()) --}}
                <div id="mcq2_data"
                     class="my-5 border-b border-dashed border-green-500 pb-5 {{ old('batch_type') == 'combined' ? '' : 'hidden' }}">
                    @include('admin.question_types.question_type_data', ['name' => 'mcq2'])
                </div>

                <div id="sba_data" class="my-5 border-b border-dashed border-green-500 pb-5">
                    @include('admin.question_types.question_type_data', ['name' => 'sba', 'required' => true])
                </div>

                <div id="emq_data" class="my-5 border-b border-dashed border-green-500 pb-5">
                    @include('admin.question_types.question_type_data', ['name' => 'emq'])
                </div>

                {{-- Pass Mark --}}
                <div class="sm:w-1/2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Pass Mark <i class="fa fa-asterisk text-[10px] text-rose-500"></i>
                    </label>
                    <input type="number" name="pass_mark" required value="{{ old('pass_mark') }}"
                           class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                </div>

                {{-- Duration (minutes) --}}
                <div class="sm:w-1/2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Duration (In Minutes) <i class="fa fa-asterisk text-[10px] text-rose-500"></i>
                    </label>
                    <input type="number" name="duration" required value="{{ old('duration') }}"
                           class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                </div>

                {{-- Paper / Faculty --}}
                @php $pf = old('paper_faculty','None'); @endphp
                <div>
                    <span class="mb-2 block text-sm font-medium text-gray-700">Paper or Faculty (BCPS)</span>
                    <div class="flex flex-wrap gap-4 text-sm">
                        <label class="inline-flex items-center gap-2">
                            <input type="radio" name="paper_faculty" value="Paper" {{ $pf==='Paper' ? 'checked' : '' }}
                                   class="h-4 w-4 border-gray-300 text-indigo-600">
                            <span>Paper</span>
                        </label>
                        <label class="inline-flex items-center gap-2">
                            <input type="radio" name="paper_faculty" value="Faculty" {{ $pf==='Faculty' ? 'checked' : '' }}
                                   class="h-4 w-4 border-gray-300 text-indigo-600">
                            <span>Faculty</span>
                        </label>
                        <label class="inline-flex items-center gap-2">
                            <input type="radio" name="paper_faculty" value="None" {{ $pf==='None' ? 'checked' : '' }}
                                   class="h-4 w-4 border-gray-300 text-indigo-600">
                            <span>None</span>
                        </label>
                    </div>
                </div>

                {{-- Is Faculty --}}
                <div>
                    <span class="mb-2 block text-sm font-medium text-gray-700">Is Faculty?</span>
                    <div class="flex flex-wrap gap-4 text-sm">
                        <label class="inline-flex items-center gap-2">
                            <input type="radio" name="is_faculty" value="Yes" required
                                   {{ old('is_faculty') === 'Yes' ? 'checked' : '' }}
                                   class="h-4 w-4 border-gray-300 text-indigo-600">
                            <span>Yes</span>
                        </label>
                        <label class="inline-flex items-center gap-2">
                            <input type="radio" name="is_faculty" value="No" required
                                   class="h-4 w-4 border-gray-300 text-indigo-600">
                            <span>No</span>
                        </label>
                    </div>
                </div>

                {{-- Description (CKEditor) --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                    <textarea name="description" id="description"
                              class="w-full min-h-[220px] rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-indigo-500">@if (strlen(old('description') ?? '') > 0)
{{ old('description') }}
@else
<p class="m-2">1. Start now বক্স এ click করার পরপরই আপনার পরীক্ষা শুরু হয়ে যাবে।</p>
<p class="m-2">2. প্রশ্ন MCQ type হলে প্রতিটি অপশন এর বিপরীতে T এবং F বৃত্ত রয়েছে...</p>
<p class="m-2">3. প্রশ্ন SBA Type হলে যেকোনো একটি বৃত্ত click করুন।</p>
<p class="m-2">4. Next / TRY LATER ব্যবহার করে নেভিগেট করুন...</p>
<p class="m-2">5. পরীক্ষা শেষে Submit করুন; সময় শেষ হলে auto-submit হবে।</p>
<p class="m-2">6. পরীক্ষার পর Answer details প্রোফাইল থেকে দেখা যাবে।</p>
@endif</textarea>
                </div>

                {{-- Buttons --}}
                <div class="flex items-center gap-3 pt-4">
                    <button type="submit"
                            class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                        Submit
                    </button>
                    <a href="{{ route('question-types.index') }}"
                       class="inline-flex items-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50">
                        Cancel
                    </a>
                </div>
            </form>
            {{-- END FORM --}}
        </div>
    </div>

    {{-- Optional vendor assets --}}
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.5/css/select2.css" />
    <script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.5/js/select2.js"></script>
    <script>
        // CKEditor init
        if (typeof runCKEDITOR === 'function') {
            runCKEDITOR('description', {});
        }

        // ===== Vanilla JS: combined mode toggle + sanitize + label changes =====
        (function () {
            const batchSel = document.getElementById('batch-type');
            const mcq = document.getElementById('mcq_data');
            const mcq2 = document.getElementById('mcq2_data');
            const form = document.getElementById('qt-form');
            const initialCombined = "{{ old('batch_type') }}" === 'combined';

            function changeLabels(container, name) {
                if (!container) return;
                const star = '<span class="text-rose-600">*</span>';
                const q = (sel) => container.querySelector(sel);
                const n = q('.number-label');
                const m = q('.mark-label');
                const ng = q('.ng-mark-label');
                const ngr = q('.ng-mark-range-label');
                if (n) n.innerHTML  = `Number of ${name} ${star}`;
                if (m) m.innerHTML  = `Mark of ${name} ${star}`;
                if (ng) ng.innerHTML = `${name} Negative Mark/stamp ${star}`;
                if (ngr) ngr.innerHTML = `${name} Negative Mark Range`;
            }

            // Remove/restore name to prevent submission; also disable/enable
            function enableScope(scope, on) {
                if (!scope) return;
                scope.querySelectorAll('input,select,textarea').forEach(el => {
                    if (on) {
                        if (el.dataset.name && !el.name) el.name = el.dataset.name;
                        el.disabled = false;
                    } else {
                        if (el.name) { el.dataset.name = el.name; el.removeAttribute('name'); }
                        el.disabled = true;
                    }
                });
            }

            function applyCombined(isCombined) {
                if (isCombined) {
                    mcq2.classList.remove('hidden');
                    enableScope(mcq2, true);
                    changeLabels(mcq, 'MCQ-R');
                    changeLabels(mcq2, 'MCQ-F');
                } else {
                    mcq2.classList.add('hidden');
                    enableScope(mcq2, false);
                    changeLabels(mcq, 'MCQ');
                    changeLabels(mcq2, 'MCQ');
                }
            }

            // Sanitize Bengali digits + strip spaces/commas before submit
            function bn2en(s) {
                if (s == null) return s;
                const map = {'০':'0','১':'1','২':'2','৩':'3','৪':'4','৫':'5','৬':'6','৭':'7','৮':'8','৯':'9'};
                return String(s).replace(/[০-৯]/g, d => map[d]).replace(/[\s,]+/g, '');
            }

            // Validate negative mark range inputs visually
            function validateRange(val) {
                if (!val) return true;
                return val.split(',').every(seg => {
                    seg = seg.trim();
                    return /^-?\d+(\.\d+)?(-\-?\d+(\.\d+)?)?$/.test(seg) || /^-?\d+(\.\d+)?\s*-\s*-?\d+(\.\d+)?$/.test(seg);
                });
            }

            document.addEventListener('DOMContentLoaded', function () {
                // Stash names for mcq2 once
                if (mcq2) {
                    mcq2.querySelectorAll('input,select,textarea').forEach(el => {
                        if (el.name) el.dataset.name = el.name;
                    });
                }

                applyCombined(initialCombined);

                batchSel.addEventListener('change', function (e) {
                    applyCombined(e.target.value === 'combined');
                });

                // live style for negative mark range
                document.querySelectorAll('.nagetive_mark_range input').forEach(input => {
                    input.addEventListener('input', function () {
                        const ok = validateRange(this.value.trim());
                        this.classList.toggle('border-rose-500', !ok);
                        this.classList.toggle('focus:border-rose-500', !ok);
                    });
                });

                // sanitize numeric fields on submit
                form.addEventListener('submit', function (e) {
                    const fields = [
                        'mcq_number','mcq_mark','mcq_negative_mark',
                        'mcq2_number','mcq2_mark','mcq2_negative_mark',
                        'sba_number','sba_mark','sba_negative_mark',
                        'emq_number','emq_mark','emq_negative_mark',
                        'pass_mark','duration','full_mark'
                    ];
                    fields.forEach(name => {
                        const el = form.querySelector(`[name="${name}"]`);
                        if (el && typeof el.value === 'string') {
                            el.value = bn2en(el.value.trim());
                        }
                    });
                });
            });
        })();
    </script>

    {{-- Page scripts (your existing) --}}
    @include('admin.question_types.scripts')
</x-app-layout>
