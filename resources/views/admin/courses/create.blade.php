{{-- resources/views/courses/create.blade.php --}}
<x-app-layout>
    <div class="max-w-3xl mx-auto p-6">


        <form action="{{ route('courses.store') }}" method="POST"
            class="space-y-6 bg-white border border-gray-200 rounded-xl px-6 py-3">
            @csrf

            <div class="mb-3">
                <h1 class="text-2xl font-semibold text-gray-900">Create Course</h1>
            </div>

            @if ($errors->any())
                <div class="mb-4 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-rose-800">
                    <ul class="list-disc list-inside">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif
            {{-- Name --}}
            <div class="grid grid-cols-1 gap-4">
                {{-- Name --}}
                <div>
                    <label for="name" class="block text-sm font-medium text-gray-700">Name <span
                            class="text-red-500">*</span></label>
                    <input type="text" id="name" name="name" value="{{ old('name', '') }}" required
                        class="mt-1 w-full rounded-md border-gray-300  focus:ring-indigo-500 focus:border-indigo-500">
                    @error('name')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Institute --}}
                <div>
                    <label for="institute_id" class="block text-sm font-medium text-gray-700">Institute <span
                            class="text-red-500">*</span></label>
                    <select id="institute_id" name="institute_id" required
                        class="mt-1 w-full rounded-md border-gray-300  focus:ring-indigo-500 focus:border-indigo-500">
                        <option value="">Select...</option>
                        @foreach ($institutes as $inst)
                            <option value="{{ $inst->id }}" @selected(old('institute_id', '') == $inst->id)>
                                {{ $inst->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('institute_id')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                {{-- bKash merchant --}}
                <div>
                    <label for="bkash_merchant_number" class="block text-sm font-medium text-gray-700">bKash
                        Merchant
                        Number</label>
                    <input type="text" id="bkash_merchant_number" name="bkash_merchant_number"
                        value="{{ old('bkash_merchant_number', '') }}"
                        class="mt-1 w-full rounded-md border-gray-300  focus:ring-indigo-500 focus:border-indigo-500"
                        placeholder="01XXXXXXXXX">
                    @error('bkash_merchant_number')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Status --}}
                <div>
                    <label for="status" class="block text-sm font-medium text-gray-700">Status</label>
                    <select id="status" name="status"
                        class="mt-1 w-full rounded-md border-gray-300  focus:ring-indigo-500 focus:border-indigo-500">
                        @php $selectedStatus = old('status', 1); @endphp
                        <option value="1" @selected($selectedStatus === 1)>Active</option>
                        <option value="0" @selected($selectedStatus === 0)>Inactive</option>
                    </select>
                    @error('status')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

            </div>


            {{-- Actions --}}
            <div class="flex items-center gap-3">
                <button
                    class="inline-flex items-center px-4 py-2 rounded-lg bg-green-700 text-white hover:bg-green-600">Save</button>
                <a href="{{ route('courses.index') }}"
                    class="px-4 py-2 rounded-lg border bg-orange-600 hover:bg-orange-500 text-white">Cancel</a>
            </div>
        </form>
    </div>
</x-app-layout>
