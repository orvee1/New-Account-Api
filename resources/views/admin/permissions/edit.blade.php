<x-app-layout>

    <div class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8 py-6">
        {{-- Card --}}
        <div class="bg-white rounded-xl shadow-sm ring-1 ring-gray-200 overflow-hidden">
            <div class="px-5 py-4 border-b border-gray-200">
                <h3 class="text-base font-semibold text-gray-900">Permission Edit</h3>
            </div>

            <form action="{{ route('permissions.update', $permission->id) }}" method="POST" class="p-5 space-y-3">
                @csrf
                @method('PUT')

                {{-- Name --}}
                <div>
                    <label for="name" class="block text-sm font-medium text-gray-700 mb-1">
                        Name <span class="text-red-500">*</span>
                    </label>
                    <input id="name" type="text" name="name" value="{{ old('name', $permission->name) }}" required
                        class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" />
                    @error('name')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Parent --}}
                <div>
                    <label for="parent_id" class="block text-sm font-medium text-gray-700 mb-1">
                        Parent
                    </label>
                    <select id="parent_id" name="parent_id"
                        class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                        <option value="0">Select Parent</option>
                        @foreach($parent as $id => $label)
                        <option value="{{ $id }}" {{ (string)old('parent_id', $permission->parent_id) === (string)$id ?
                            'selected' : '' }}>
                            {{ $label }}
                        </option>
                        @endforeach
                    </select>
                    @error('parent_id')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Actions --}}
                <div class="flex items-center justify-end gap-3">
                    <a href="{{ route('permissions.index') }}"
                        class="inline-flex items-center rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">
                        Cancel
                    </a>
                    <button type="submit"
                        class="inline-flex items-center rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        {{ __('Update') }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>