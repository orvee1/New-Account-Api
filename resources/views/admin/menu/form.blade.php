<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.5/css/select2.min.css" />

<div class="space-y-6 max-w-5xl">

    {{-- Error Messages --}}
    @if ($errors->any())
    <div class="rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-800">
        <ul class="list-disc list-inside space-y-1">
            @foreach ($errors->all() as $error)
            <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    {{-- Title --}}
    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">
            Title <span class="text-red-500">*</span>
        </label>
        <input type="text" name="title" value="{{ old('title') ?? $menu->title }}" required placeholder="Title"
            class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
        <input type="hidden" name="id" value="{{ $menu->id }}">
    </div>

    {{-- Parent --}}
    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">
            Parent <span class="text-red-500">*</span>
        </label>
        <select name="parent_id"
            class="select2 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
            <option value="0">---Select---</option>
            @foreach($menus as $index => $value)
            <option value="{{ $index }}" @if($menu->parent_id == $index) selected @endif>{{ $value }}</option>
            @endforeach
        </select>
    </div>

    {{-- Permission --}}
    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">
            Permission <span class="text-red-500">*</span>
        </label>
        <select name="permission"
            class="select3 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
            <option value="0">---Select---</option>
            @foreach($permissions as $id => $value)
            <option value="{{ $value->name }}" @if($value->name == $menu->permission) selected @endif>
                {{ $value->name }}
            </option>
            @endforeach
        </select>
    </div>

    {{-- URL --}}
    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">
            URL <span class="text-red-500">*</span>
        </label>
        <input type="text" name="url" value="{{ old('url') ?? $menu->url }}" required placeholder="URL"
            class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
    </div>

    {{-- Icon Class --}}
    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">
            Icon Class Name <span class="text-red-500">*</span>
        </label>
        <input type="text" name="icon" value="{{ old('icon') ?? $menu->icon }}" required placeholder="Enter Icon Class"
            class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
    </div>

</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.5/js/select2.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        $('.select2').select2();
        $('.select3').select2();
    });
</script>
