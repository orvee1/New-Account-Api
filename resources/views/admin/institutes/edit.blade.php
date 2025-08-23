{{-- resources/views/institutes/edit.blade.php --}}
<x-app-layout>
<div class="max-w-3xl mx-auto p-6">
  <div class="mb-6">
    <h1 class="text-2xl font-semibold text-gray-900">Edit Institute</h1>
    <p class="text-sm text-gray-500">Update details for <span class="font-medium">{{ $institute->name }}</span>.</p>
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

  @if(method_exists($institute,'trashed') && $institute->trashed())
    <div class="mb-4 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-amber-800">
      This record is currently in Trash. You can edit and save, or restore it from the list page.
    </div>
  @endif

  <form action="{{ route('institutes.update', $institute) }}" method="POST" class="space-y-6 bg-white border border-gray-200 rounded-xl p-6">
    @csrf @method('PUT')

    {{-- Name --}}
    <div>
      <label class="block text-sm font-medium text-gray-700 mb-1">Name <span class="text-rose-600">*</span></label>
      <input type="text" name="name" value="{{ old('name', $institute->name) }}" required
             class="w-full rounded-lg border-gray-300 focus:border-gray-900 focus:ring-gray-900"/>
    </div>

    {{-- Flags --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
      <div class="flex items-center gap-2">
        <input type="hidden" name="has_faculty" value="0">
        <input id="has_faculty" type="checkbox" name="has_faculty" value="1"
               @checked(old('has_faculty', (bool)$institute->has_faculty))
               class="rounded border-gray-300 text-gray-900 focus:ring-gray-900">
        <label for="has_faculty" class="text-sm text-gray-700">Has Faculty?</label>
      </div>

      <div class="flex items-center gap-2">
        <input type="hidden" name="has_discipline" value="0">
        <input id="has_discipline" type="checkbox" name="has_discipline" value="1"
               @checked(old('has_discipline', (bool)$institute->has_discipline))
               class="rounded border-gray-300 text-gray-900 focus:ring-gray-900">
        <label for="has_discipline" class="text-sm text-gray-700">Has Discipline?</label>
      </div>
    </div>

    {{-- Actions --}}
    <div class="flex items-center gap-3">
      <button class="inline-flex items-center px-4 py-2 rounded-lg bg-gray-900 text-white hover:bg-black">Update</button>
      <a href="{{ route('institutes.index') }}" class="px-4 py-2 rounded-lg border border-gray-300 hover:bg-gray-50">Back</a>
    </div>
  </form>
</div>
</x-app-layout>
