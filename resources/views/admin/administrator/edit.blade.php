<x-app-layout>
    <div class="mb-4 text-sm text-gray-600 max-w-5xl mx-auto">
        <div class="bg-white p-6 rounded shadow">
            <h2 class="text-xl font-semibold mb-4 flex items-center gap-2">
                <i class="fa fa-reorder"></i> Administrator Edit
            </h2>

            <form action="{{ route( 'admininstrator.update', $user->id ) }}" method="POST"
                enctype="multipart/form-data">
                @csrf
                @method('PUT')

                <!-- Your form fields go here -->

                <div class="grid grid-cols-1 gap-6">

                    <div>
                        <label class="block text-sm font-medium mb-1">Name <span class="text-red-500">*</span></label>
                        <div class="relative">
                            <span class="absolute left-3 top-2.5 text-gray-400"><i class="fa fa-envelope"></i></span>
                            <input type="text" name="name" value="{{ $user->name }}"
                                class="pl-10 w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring focus:border-sky-500"
                                required>
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium mb-1">Email Address <span
                                class="text-red-500">*</span></label>
                        <div class="relative">
                            <span class="absolute left-3 top-2.5 text-gray-400"><i class="fa fa-envelope"></i></span>
                            <input type="email" name="email" value="{{ $user->email }}"
                                class="pl-10 w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring focus:border-sky-500"
                                required>
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium mb-1">Phone Number <span
                                class="text-red-500">*</span></label>
                        <div class="flex items-center border border-gray-300 rounded overflow-hidden">
                            <span class="bg-gray-100 px-3 text-sm text-gray-600">+88</span>
                            <input type="text" name="phone_number" value="{{ $user->phone_number }}"
                                class="w-full px-3 py-2 focus:outline-none" minlength="11" maxlength="11" required>
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium mb-1">
                            Select Status <span class="text-red-500">*</span>
                        </label>
                        <select name="status" class="w-full border border-gray-300 rounded px-3 py-2">
                            <option value="1" {{ $user->status == 1 ? 'selected' : '' }}>Active</option>
                            <option value="0" {{ $user->status == 0 ? 'selected' : '' }}>InActive</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium mb-1">Role</label>
                        <select name="roles[]" id="user_role"
                            class="select2 w-full border border-gray-300 rounded px-3 py-2" multiple>
                            @foreach($roles as $roleKey => $roleValue)
                            <option value="{{ $roleKey }}" {{ in_array($roleValue, old('roles', $user->
                                roles()->pluck('name')->toArray())) ? 'selected' : '' }}>
                                {{ $roleValue }}
                            </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium mb-1">Access Courses</label>
                        <select name="access_course_ids[]" id="access_course_ids" multiple
                            class="select2 w-full border border-gray-300 rounded px-3 py-2">
                            @foreach ($institutes as $institute)
                            <optgroup label="{{ $institute->name }}">
                                @foreach ($institute->active_courses as $active_course)
                                <option value="{{ $active_course->id }}" {{ in_array($active_course->id,
                                    $user->access_course_ids) ? 'selected' : '' }}>
                                    {{ $active_course->name }}
                                </option>
                                @endforeach
                            </optgroup>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium mb-1">Password</label>
                        <input type="text" name="password" class="w-full border border-gray-300 rounded px-3 py-2">
                    </div>
                </div>

                <div class="mt-6 flex gap-3">
                    <button type="submit"
                        class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">Submit</button>
                    <a href="{{ url('admin/administrator') }}"
                        class="px-4 py-2 border rounded text-gray-700 hover:bg-gray-100">Cancel</a>
                </div>
            </form>
        </div>
        <script src="https://gen-file.s3.ap-southeast-1.amazonaws.com/assets/plugins/jquery-1.11.0.min.js"
            type="text/javascript"></script>
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.5/css/select2.min.css" />
        <script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.5/js/select2.min.js"></script>
        <script>
            $(document).ready(function() {
                $('.select2').select2();
            });
        </script>
    </div>
</x-app-layout>