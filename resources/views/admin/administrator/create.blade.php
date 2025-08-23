<x-app-layout>
<div class="bg-white p-6 rounded shadow max-w-5xl mx-auto">
    <h2 class="text-xl font-semibold mb-4">Create New Administrator</h2>

    <form action="{{ route('administrator.store') }}" method="POST" class="space-y-6">
        @csrf
        <div class="grid grid-cols-1 gap-6">
            <div>
                <label for="name" class="block text-sm font-medium text-gray-700 mb-1">
                    Name <span class="text-red-500">*</span>
                </label>
                <input type="text" name="name" id="name" required
                    class="w-full border border-gray-300 focus:border-blue-500 focus:ring focus:ring-blue-200 rounded-md px-3 py-2 text-gray-800 placeholder-gray-400 shadow-sm"
                    placeholder="Administrator Name">
            </div>

            <div>
                <label for="email" class="block text-sm font-medium text-gray-700 mb-1">
                    Email Address <span class="text-red-500">*</span>
                </label>
                <div class="relative">
                    <span class="absolute inset-y-0 left-0 flex items-center pl-3">
                        <i class="fa fa-envelope text-gray-400"></i>
                    </span>
                    <input type="email" name="email" id="email" required
                        class="w-full pl-10 pr-3 py-2 border border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200 text-gray-800 placeholder-gray-400"
                        placeholder="Email Address">
                </div>
            </div>

            <div>
                <label for="phone" class="block text-sm font-medium text-gray-700 mb-1">
                    Phone Number <span class="text-red-500">*</span>
                </label>
                <div class="flex rounded-md shadow-sm">
                    <span
                        class="inline-flex items-center px-3 rounded-l-md border border-r-0 border-gray-300 bg-gray-50 text-gray-500 text-sm">
                        +88
                    </span>
                    <input type="text" name="phone" id="phone" required
                        class="w-full rounded-r-md border border-gray-300 px-3 py-2 text-gray-800 placeholder-gray-400 focus:border-blue-500 focus:ring focus:ring-blue-200"
                        placeholder="Phone Number">
                </div>
            </div>

            <div>
                <label for="password" class="block text-sm font-medium text-gray-700 mb-1">
                    Password <span class="text-red-500">*</span>
                </label>
                <input type="password" name="password" id="password" required
                    class="w-full border border-gray-300 focus:border-blue-500 focus:ring focus:ring-blue-200 rounded-md px-3 py-2 text-gray-800 placeholder-gray-400 shadow-sm"
                    placeholder="Password">
            </div>
        </div>

        <div class="pt-4">
            <button type="submit"
                class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded shadow text-sm font-medium">
                Create Administrator
            </button>
        </div>
    </form>
</div>
</x-app-layout>