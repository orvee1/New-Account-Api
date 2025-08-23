<x-app-layout>
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 py-6">
        {{-- Header + Create --}}
        <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-start">
            <h1 class="text-xl font-semibold text-gray-900 flex items-center gap-2">
                <svg class="h-5 w-5 text-gray-700" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                    <path d="M4 6h16v2H4V6Zm0 5h16v2H4v-2Zm0 5h16v2H4v-2Z" />
                </svg>
                Permission List
            </h1>

            <a href="{{ route('permissions.create') }}"
                class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-3 py-2 text-sm font-medium text-white hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M11 11V5h2v6h6v2h-6v6h-2v-6H5v-2z" />
                </svg>
            </a>
        </div>

        {{-- Filters (debounced search + per_page onchange) --}}
        <form method="GET" id="filterForm" class="mb-4 flex justify-between items-center">

            <div>
                <select name="per_page"
                    class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                    @foreach([10,25,50,100] as $n)
                    <option value="{{ $n }}" {{ (int)($perPage ?? 10)===$n ? 'selected' : '' }}>{{ $n }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <input type="text" name="q" value="{{ $q ?? '' }}" placeholder="Search name"
                    class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" />
            </div>
        </form>

        @php
        // Sort link helper
        function sort_link2($label, $key, $currentSort, $currentDir) {
        $isActive = $currentSort === $key;
        $nextDir = ($isActive && $currentDir === 'asc') ? 'desc' : 'asc';
        $params = array_merge(request()->query(), ['sort' => $key, 'dir' => $nextDir]);
        $url = request()->url() . '?' . http_build_query($params);
        $arrow = $isActive ? ($currentDir === 'asc' ? '↑' : '↓') : '↕';
        return '<a href="'.$url.'" class="inline-flex items-center gap-1 hover:text-indigo-700">'.$label.' <span
                class="text-xs">'.$arrow.'</span></a>';
        }
        @endphp

        {{-- Card + Table --}}
        <div class="bg-white rounded-xl shadow-sm ring-1 ring-gray-200">
            <div class="px-5 py-3 border-b border-gray-200">
                <p class="text-sm text-gray-600">
                    Showing {{ $permissions->firstItem() ?? 0 }}–{{ $permissions->lastItem() ?? 0 }} of {{
                    $permissions->total() ?? 0 }}
                </p>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                                {!!
                                sort_link2('ID','id',$sort,$dir) !!}</th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                                {!!
                                sort_link2(__('Name'),'name',$sort,$dir) !!}</th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                                Parent</th>
                            <th
                                class="px-4 py-3 text-center text-xs font-medium uppercase tracking-wider text-gray-500">
                                Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-100">
                        @forelse ($permissions as $idx => $permission)
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-3 text-sm text-gray-700">{{ $permission->id }}</td>
                            <td class="px-4 py-3 text-sm font-medium text-gray-900">{{ $permission->name }}</td>
                            <td class="px-4 py-3 text-sm text-gray-700">
                                {{ $permission->parent ?? '—' }}
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex items-center justify-center gap-2">
                                    <a href="{{ route('permissions.edit', $permission->id) }}"
                                        class="inline-flex items-center rounded-md bg-blue-600 px-2.5 py-1.5 text-xs font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500">
                                        {{ __('Edit') }}
                                    </a>
                                    <form action="{{ route('permissions.destroy', $permission->id) }}" method="POST"
                                        onsubmit="return confirm('{{ __('Are you sure?') }}');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit"
                                            class="inline-flex items-center rounded-md bg-red-600 px-2.5 py-1.5 text-xs font-medium text-white hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500">
                                            {{ __('Delete') }}
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="4" class="px-4 py-6 text-center text-sm text-gray-500">
                                {{ __('No entries found') }}
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- Pagination --}}
            <div class="px-5 py-3 border-t border-gray-200">
                <div class="flex justify-end">
                    {{ $permissions->onEachSide(1)->links() }}
                </div>
            </div>
        </div>
    </div>
    {{-- Debounced auto-submit for search + onchange for per_page --}}
    <script>
        (function () {
        const form = document.getElementById('filterForm');
        const qInput = form.querySelector('input[name="q"]');
        const perPageSelect = form.querySelector('select[name="per_page"]');

        let timer = null;

        qInput.addEventListener('input', function () {
            clearTimeout(timer);
            timer = setTimeout(() => {
                if (form.requestSubmit) form.requestSubmit();
                else form.submit();
            }, 500); // 500ms debounce
        });

        perPageSelect.addEventListener('change', function () {
            if (form.requestSubmit) form.requestSubmit();
            else form.submit();
        });
    })();
    </script>
</x-app-layout>