<x-app-layout>
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 bg-slate-50 border border-gray-100 py-4">
        <div class="flex justify-between items-center mb-4 mt-2">
            <h1 class="text-xl font-bold mb-4">
                <span class="font-semibold">Module:</span>
                <span class="font-semibold text-sky-600">{{ $module->name }}</span>

            </h1>

            <!-- Trigger button (vanilla JS) -->
            <button id="openAssignBtn" class="px-4 py-2 bg-blue-600 text-white rounded">+ Add Topics</button>
        </div>

        {{-- Assigned Topics (sortable list) --}}
        <div class="mb-6">
            <div class="flex items-center justify-center gap-3 mb-3">
                <h2 class="text-lg p-2 rounded-md tracking-tight border-indigo-200 text-indigo-600 bg-indigo-50">
                    Assigned Topics
                    <span
                        class="inline-flex items-center rounded-full bg-sky-100 text-sky-700 text-xs font-medium px-2.5 py-1">
                        {{ $assignedTopics->count() }} selected
                    </span>
                </h2>
            </div>

            @if ($assignedTopics->isEmpty())
            <div class="rounded-2xl border border-dashed border-gray-300 p-8 text-center bg-white">
                <div class="mx-auto mb-2 w-10 h-10 rounded-full grid place-items-center bg-gray-100">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-500" viewBox="0 0 24 24"
                        fill="none" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 6v12m6-6H6" />
                    </svg>
                </div>
                <p class="text-gray-600 text-sm">No topics assigned yet.</p>
                <p class="text-gray-400 text-xs">Use “Add Topics” to assign from the list.</p>
            </div>
            @else
            <ul id="sortable-list" class="space-y-2">
                @foreach ($assignedTopics as $mt)
                <li class="group flex items-center gap-3 p-3 rounded-2xl border border-gray-200 bg-white hover:bg-gray-50 hover:shadow-sm transition-all"
                    data-id="{{ $mt->id }}">
                    <!-- Drag handle -->
                    <button type="button"
                        class="drag-handle shrink-0 inline-flex h-10 w-10 items-center justify-center rounded-xl border border-orange-200 text-orange-600 bg-orange-50 hover:bg-orange-100 hover:border-orange-300 cursor-grab active:cursor-grabbing"
                        aria-label="Drag to reorder" title="Drag to reorder">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor">
                            <circle cx="9" cy="7" r="1" />
                            <circle cx="15" cy="7" r="1" />
                            <circle cx="9" cy="12" r="1" />
                            <circle cx="15" cy="12" r="1" />
                            <circle cx="9" cy="17" r="1" />
                            <circle cx="15" cy="17" r="1" />
                        </svg>
                    </button>

                    <!-- Topic title -->
                    <div class="flex-1 min-w-0">
                        <div class="font-medium text-gray-800 truncate">{{ $mt->topic->name }}</div>
                    </div>

                    <!-- Actions -->
                    <form action="{{ route('modules.topics.remove', [$module->id, $mt->topic_id]) }}" method="POST"
                        class="remove-topic-form shrink-0">
                        @csrf @method('DELETE')
                        <button type="submit"
                            class="inline-flex items-center gap-1 px-3 h-9 rounded-xl border border-red-200 text-red-600 bg-red-50 hover:bg-red-100 hover:border-red-300 text-sm transition-colors"
                            title="Remove">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24"
                                fill="currentColor">
                                <path d="M9 9h6v10H9z" />
                                <path d="M19 7h-3.5l-1-1h-5l-1 1H5v2h14z" />
                            </svg>
                            Remove
                        </button>
                    </form>
                </li>
                @endforeach
            </ul>

            <p class="mt-2 text-xs text-gray-400 flex items-center gap-1">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M4 4h16v2H4zM4 11h16v2H4zM4 18h16v2H4z" />
                </svg>
                Drag the handle to reorder. Priority updates automatically.
            </p>
            @endif
        </div>
    </div>

    <!-- Modal (vanilla JS controlled) -->
    <div id="assignModal" class="hidden fixed inset-0 z-[9999] flex items-center justify-center bg-black/50">
        <div class="bg-white rounded-2xl shadow-xl w-full max-w-xl p-6 relative">
            <!-- Close button -->
            <button type="button"
                class="js-close absolute top-3 right-3 inline-flex h-8 w-8 items-center justify-center rounded-lg hover:bg-gray-100"
                aria-label="Close modal">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-500" viewBox="0 0 24 24" fill="none"
                    stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>

            <h2 class="text-lg font-bold mb-4">Assign Topics</h2>
            <form action="{{ route('modules.topics.assign', $module->id) }}" method="POST">
                @csrf
                <label class="block mb-2">Select Topics</label>
                <select id="topicSelect" name="topic_ids[]" multiple class="w-full border rounded">
                    @foreach ($availableTopics as $topic)
                    <option value="{{ $topic->id }}">{{ $topic->name }}</option>
                    @endforeach
                </select>
                <div class="mt-4 flex justify-end gap-2">
                    <button type="button" data-modal-hide="assignModal" class="px-4 py-2 border rounded">Cancel</button>
                    <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded">Save</button>
                </div>
            </form>
        </div>
    </div>

    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- Select2 -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.5/css/select2.min.css" rel="stylesheet" />
    <script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.5/js/select2.min.js"></script>

    <!-- SortableJS -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Sortable/1.15.0/Sortable.min.js"></script>

    <script>
        // ---------- Modal (vanilla JS) ----------
        (function() {
            const modal = document.getElementById('assignModal');
            const openBtn = document.getElementById('openAssignBtn');
            const closeBtns = modal.querySelectorAll('[data-modal-hide="assignModal"], .js-close');

            function openModal() {
                modal.classList.remove('hidden');
                document.body.classList.add('overflow-hidden');
                // Fix Select2 z-index/parent after open
                if (window.jQuery && $('#topicSelect').data('select2') == null) {
                    $('#topicSelect').select2({
                        placeholder: "Choose topics",
                        width: '100%',
                        dropdownParent: $('#assignModal')
                    });
                }
            }

            function closeModal() {
                modal.classList.add('hidden');
                document.body.classList.remove('overflow-hidden');
            }

            openBtn?.addEventListener('click', openModal);
            closeBtns.forEach(btn => btn.addEventListener('click', closeModal));
            // Click on overlay to close
            modal.addEventListener('click', function(e) {
                if (e.target === modal) closeModal();
            });
        })();

        // ---------- Select2 (fallback init if modal opened before this runs) ----------
        $(document).ready(function() {
            // If someone opens modal very fast, also ensure init
            if ($('#assignModal').is(':visible') && $('#topicSelect').data('select2') == null) {
                $('#topicSelect').select2({
                    placeholder: "Choose topics",
                    width: '100%',
                    dropdownParent: $('#assignModal')
                });
            }
        });

        // ---------- Sortable ----------
        document.addEventListener('DOMContentLoaded', function() {
            const el = document.getElementById('sortable-list');

            // Confirm before remove
            document.querySelectorAll('.remove-topic-form').forEach(function(form) {
                form.addEventListener('submit', function(e) {
                    const name = this.closest('li').querySelector('.font-medium')?.textContent
                        ?.trim() || 'this topic';
                    if (!confirm('Remove "' + name + '" from this module?')) {
                        e.preventDefault();
                    }
                });
            });

            if (el && window.Sortable) {
                Sortable.create(el, {
                    animation: 180,
                    handle: '.drag-handle',
                    ghostClass: 'sortable-ghost',
                    chosenClass: 'sortable-chosen',
                    onEnd: function() {
                        const orders = [];
                        el.querySelectorAll('li[data-id]').forEach((li, idx) => {
                            orders.push({
                                id: li.dataset.id,
                                priority: idx + 1
                            });
                        });

                        fetch("{{ route('modules.topics.priority', $module->id) }}", {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}'
                            },
                            body: JSON.stringify({
                                orders
                            })
                        }).catch(() => alert('Could not update order. Try again.'));
                    }
                });
            }
        });
    </script>
</x-app-layout>