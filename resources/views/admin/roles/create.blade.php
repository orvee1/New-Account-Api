<x-app-layout>

    <div class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8 py-6">
        <div class="bg-white rounded-xl shadow-sm ring-1 ring-gray-200 overflow-hidden">
            <div class="px-5 py-4 border-b border-gray-200">
                <h3 class="text-base font-semibold text-gray-900">Role Create</h3>
            </div>

            <form action="{{ route('roles.store') }}" method="POST" class="p-5 space-y-8">
                @csrf

                {{-- Name --}}
                <div class="max-w-md">
                    <label for="name" class="block text-sm font-medium text-gray-700 mb-1">
                        Name <span class="text-red-500">*</span>
                    </label>
                    <input id="name" type="text" name="name" value="{{ old('name') }}" required
                        class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" />
                    @error('name')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Permissions --}}
                @php $oldPerms = (array) old('permission', []); @endphp
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Permissions</label>

                    <div class="space-y-6">
                        @foreach($permissions as $permission)
                        <div class="rounded-lg ring-1 ring-gray-200 p-4">
                            {{-- Parent --}}
                            <label class="inline-flex items-center gap-2 font-medium text-gray-900">
                                <input type="checkbox" name="permission[]" value="{{ $permission->id }}"
                                    data-permission-id="{{ $permission->id }}"
                                    class="parent-permission h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                                    {{ in_array($permission->id, $oldPerms) ? 'checked' : '' }}>
                                <span>{{ $permission->name }}</span>
                            </label>

                            {{-- Children --}}
                            @if($permission->children && $permission->children->count())
                            <div class="mt-3 pl-6 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-2">
                                @foreach($permission->children as $child)
                                <label class="inline-flex items-center gap-2 text-sm text-gray-700">
                                    <input type="checkbox" name="permission[]" value="{{ $child->id }}"
                                        data-parent-id="{{ $permission->id }}"
                                        class="child-permission h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                                        {{ in_array($child->id, $oldPerms) ? 'checked' : '' }}>
                                    <span>{{ $child->name }}</span>
                                </label>
                                @endforeach
                            </div>
                            @endif
                        </div>
                        @endforeach
                    </div>
                </div>

                {{-- Actions --}}
                <div class="flex items-center justify-end gap-3">
                    <a href="{{ url('admin/roles') }}"
                        class="inline-flex items-center rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">
                        Cancel
                    </a>
                    <button type="submit"
                        class="inline-flex items-center rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        Submit
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- Vanilla JS: parent-child sync --}}
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const parentBoxes = document.querySelectorAll('input.parent-permission[data-permission-id]');
            const childBoxes  = document.querySelectorAll('input.child-permission[data-parent-id]');

            function getChildren(parentId) {
                return document.querySelectorAll(`input.child-permission[data-parent-id="${parentId}"]`);
            }

            function getParent(parentId) {
                return document.querySelector(`input.parent-permission[data-permission-id="${parentId}"]`);
            }

            // Parent -> Children
            parentBoxes.forEach(parent => {
                parent.addEventListener('change', function () {
                    const pid = this.getAttribute('data-permission-id');
                    getChildren(pid).forEach(ch => { ch.checked = parent.checked; });
                });
            });

            // Children -> Parent (check if any child checked; uncheck if none)
            childBoxes.forEach(child => {
                child.addEventListener('change', function () {
                    const pid = this.getAttribute('data-parent-id');
                    const siblings = getChildren(pid);
                    const anyChecked = Array.from(siblings).some(cb => cb.checked);
                    const parent = getParent(pid);
                    if (parent) parent.checked = anyChecked;
                });
            });
        });
    </script>
</x-app-layout>