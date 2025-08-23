<x-app-layout>

    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 py-6">
        {{-- Header --}}
        <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-start">
            <h1 class="text-xl font-semibold text-gray-900 flex items-center gap-2">
                <svg class="h-5 w-5 text-gray-700" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M4 6h16v2H4V6Zm0 5h16v2H4v-2Zm0 5h16v2H4v-2Z" />
                </svg>
                Roles List
            </h1>
            <a href="{{ route('roles.create') }}"
                class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-3 py-2 text-sm font-medium text-white hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M11 11V5h2v6h6v2h-6v6h-2v-6H5v-2z" />
                </svg>

            </a>
        </div>

        @php
        function sort_link_roles($label, $key, $currentSort, $currentDir) {
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

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                                {!! sort_link_roles('SL','id',$sort,$dir) !!}</th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                                {!! sort_link_roles('Name','name',$sort,$dir) !!}</th>
                            <th
                                class="px-4 py-3 text-center text-xs font-medium uppercase tracking-wider text-gray-500">
                                Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-100">
                        @forelse ($roles as $role)
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-3 text-sm text-gray-700">
                                {{ $loop->index + 1 }}
                            </td>
                            <td class="px-4 py-3 text-sm font-medium text-gray-900">
                                {{ $role->name }}
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex items-center justify-center gap-2">
                                    @if(!in_array($role->name, ['Administrator', 'Developer']))
                                    <a href="{{ route('roles.edit', $role->id) }}"
                                        class="inline-flex items-center rounded-md bg-blue-600 px-2.5 py-1.5 text-xs font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500">
                                        Edit
                                    </a>
                                    <form action="{{ route('roles.destroy', $role->id) }}" method="POST"
                                        onsubmit="return confirm('Are you want to delete this record ?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit"
                                            class="inline-flex items-center rounded-md bg-red-600 px-2.5 py-1.5 text-xs font-medium text-white hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500">
                                            Delete
                                        </button>
                                    </form>
                                    @else
                                    <span
                                        class="inline-flex items-center rounded-md bg-gray-100 px-2.5 py-1 text-xs font-medium text-gray-600">Protected</span>
                                    @endif
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="3" class="px-4 py-6 text-center text-sm text-gray-500">No roles found.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</x-app-layout>