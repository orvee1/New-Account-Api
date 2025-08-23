<x-app-layout>
    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-6">

        {{-- Page Header --}}
        <div class="mb-4">
            <h1 class="text-xl font-semibold text-gray-900 flex items-center gap-2">
                <svg class="w-5 h-5 text-gray-700" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                    <path d="M4 6h16v2H4V6Zm0 5h16v2H4v-2Zm0 5h16v2H4v-2Z" />
                </svg>
                Menu Edit
            </h1>
        </div>

        {{-- Card --}}
        <div class="bg-white rounded-xl shadow-sm ring-1 ring-gray-200">
            <div class="px-5 py-4 border-b border-gray-200">
                <p class="text-sm text-gray-600">Update the fields and save your changes.</p>
            </div>

            {{-- FORM --}}
            <form action="{{ route('menus.update', $menu->id) }}" method="POST" class="p-5 space-y-6">
                @method('PUT')
                @csrf

                {{-- Include your form fields --}}
                @include('admin.menu.form')

                {{-- Actions --}}
                <div class="flex items-center justify-end gap-3 pt-2">
                    <a href="{{ route('menus.index') }}"
                        class="inline-flex items-center rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">
                        Cancel
                    </a>
                    <button type="submit"
                        class="inline-flex items-center rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        Update
                    </button>
                </div>
            </form>
        </div>
    </div>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // আপনার পুরোনো হেল্পার থাকলে 그대로 ব্যবহার করুন
            if (typeof runCKEDITOR === 'function') {
                runCKEDITOR('description', {});
            }
        });
    </script>
</x-app-layout>