<x-app-layout>
    <div id="main" role="main" class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">

        {{-- Breadcrumbs --}}
        <nav class="mb-6" aria-label="Breadcrumb">
            <ol class="flex items-center space-x-2 text-sm text-gray-600">
                <li class="inline-flex items-center">
                    <svg class="h-4 w-4 mr-1" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true">
                        <path
                            d="M10.707 1.293a1 1 0 00-1.414 0L2 8.586V18a2 2 0 002 2h4a1 1 0 001-1v-5h2v5a1 1 0 001 1h4a2 2 0 002-2V8.586l-7.293-7.293z" />
                    </svg>
                    <a href="{{ url('/admin') }}" class="hover:text-gray-900">Dashboard</a>
                </li>
                <li class="text-gray-400">/</li>
                <li>
                    <a href="{{ url('/admin/administrator') }}" class="hover:text-gray-900">Administrator List</a>
                </li>
                <li class="text-gray-400">/</li>
                <li class="font-semibold text-gray-900">#{{ $user->id }}</li>
            </ol>
        </nav>

        {{-- Header --}}
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-xl font-semibold text-gray-900">Administrator Profile</h3>
            <a href='{{ url("admin/administrator/{$user->id}/edit") }}'
                class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-3 py-1.5 text-white text-sm hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24"
                    stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                </svg>
                Edit
            </a>
        </div>

        {{-- Profile Card --}}
        <section class="mb-8">
            <div class="bg-white shadow-sm ring-1 ring-gray-200 rounded-xl p-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="space-y-2">
                        <p class="text-sm text-gray-600">Name</p>
                        <p class="text-base font-semibold text-gray-900">{{ $user->name ?? '' }}</p>

                        <p class="text-sm text-gray-600 mt-4">Email</p>
                        <p class="text-base font-medium text-gray-900">{{ $user->email }}</p>

                        <p class="text-sm text-gray-600 mt-4">Phone</p>
                        <p class="text-base font-medium text-gray-900">{{ $user->phone_number }}</p>

                        <p class="text-sm text-gray-600 mt-4">Roles</p>
                        <div class="flex flex-wrap gap-2">
                            @foreach ($user->roles->sortBy('name') as $role)
                            <span
                                class="inline-flex items-center rounded-full bg-gray-100 px-2.5 py-0.5 text-xs font-medium text-gray-800">
                                {{ $role->name ?? '' }}
                            </span>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </section>

        {{-- Activity Logs --}}
        <div class="bg-white shadow-sm ring-1 ring-gray-200 rounded-xl overflow-hidden mb-8">
            <div class="px-4 py-3 border-b border-gray-200">
                <div class="flex items-center gap-2 text-gray-900">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                        <path d="M12 2a10 10 0 100 20 10 10 0 000-20Zm1 10V7h-2v7h6v-2h-4Z" />
                    </svg>
                    <h4 class="font-semibold">Activity Logs</h4>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Date & Time</th>
                            <th
                                class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                                IP</th>
                            <th
                                class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Method</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                URL</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Device</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-100">
                        @foreach($activities as $activity)
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900">
                                {{ $activity->created_at->format('d M Y h:i:s a') }}
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap text-sm text-center text-gray-700">
                                {{ $activity->ip }}
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap text-sm text-center">
                                <span
                                    class="inline-flex items-center rounded-md bg-blue-50 px-2 py-0.5 text-xs font-semibold text-blue-700 ring-1 ring-inset ring-blue-200">
                                    {{ $activity->method }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-700" title="{{ $activity->url }}">
                                {{ explode('?', $activity->url)[0] ?? '/' }}
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-700" title="{{ $activity->url }}">
                                {{ $activity->device_browser_string }}
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                    @if(method_exists($activities, 'links'))
                    <tfoot class="bg-gray-50">
                        <tr>
                            <td colspan="5" class="px-4 py-3">
                                <div class="flex justify-end">
                                    {{ $activities->links() }}
                                </div>
                            </td>
                        </tr>
                    </tfoot>
                    @endif
                </table>
            </div>
        </div>

    </div>
</x-app-layout>