{{-- resources/views/institutes/create.blade.php --}}
<x-app-layout>
  <div class="max-w-3xl mx-auto p-6">


    <form action="{{ route('institutes.store') }}" method="POST"
      class="space-y-6 bg-white border border-gray-200 rounded-xl px-6 py-3">
      @csrf

      <div class="mb-3">
        <h1 class="text-2xl font-semibold text-gray-900">Create Institute</h1>
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
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Name <span class="text-rose-600">*</span></label>
        <input type="text" name="name" value="{{ old('name') }}" required
          class="w-full rounded-lg border-gray-300 focus:border-gray-900 focus:ring-gray-900" />
      </div>

      {{-- Flags --}}
      <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div class="flex items-center gap-2">
          <label for="has_faculty"
            class="text-lg text-gray-700 cursor-pointer hover:text-sky-600 flex items-center gap-2">
            <input id="has_faculty" type="checkbox" name="has_faculty" value="1" @checked(old('has_faculty'))
              class="rounded cursor-pointer border-gray-300 text-gray-900 focus:ring-gray-900 h-5 w-5">
            <span>Has Faculty?</span>
          </label>
        </div>

        <div class="flex items-center gap-2">
          <label for="has_discipline"
            class="text-lg text-gray-700 cursor-pointer hover:text-sky-600 flex items-center gap-2">
            <input id="has_discipline" type="checkbox" name="has_discipline" value="1" @checked(old('has_discipline'))
              class="rounded cursor-pointer border-gray-300 text-gray-900 focus:ring-gray-900 h-5 w-5">
            <span>Has Discipline?</span>
          </label>
        </div>
      </div>


      {{-- Actions --}}
      <div class="flex items-center gap-3">
        <button
          class="inline-flex items-center px-4 py-2 rounded-lg bg-green-700 text-white hover:bg-green-600">Save</button>
        <a href="{{ route('institutes.index') }}"
          class="px-4 py-2 rounded-lg border bg-orange-600 hover:bg-orange-500 text-white">Cancel</a>
      </div>
    </form>
  </div>
</x-app-layout>