@php
  $isEdit = isset($company) && $company?->exists;
@endphp

<form method="POST" action="{{ $action }}" enctype="multipart/form-data" class="space-y-6">
  @csrf
  @if (strtoupper($method) !== 'POST')
    @method($method)
  @endif

  <div class="bg-white shadow-sm ring-1 ring-gray-200 rounded-lg p-6 space-y-6">
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
      <div>
        <label class="block text-sm font-medium text-gray-700">Name <span class="text-red-500">*</span></label>
        <input type="text" name="name" value="{{ old('name', $company->name ?? '') }}"
               class="mt-1 w-full rounded-md border-gray-300 focus:border-indigo-500 focus:ring-indigo-500" required>
      </div>

      <div>
        <label class="block text-sm font-medium text-gray-700">Email</label>
        <input type="email" name="email" value="{{ old('email', $company->email ?? '') }}"
               class="mt-1 w-full rounded-md border-gray-300 focus:border-indigo-500 focus:ring-indigo-500">
      </div>

      <div>
        <label class="block text-sm font-medium text-gray-700">Phone</label>
        <input type="text" name="phone" value="{{ old('phone', $company->phone ?? '') }}"
               class="mt-1 w-full rounded-md border-gray-300 focus:border-indigo-500 focus:ring-indigo-500">
      </div>

      <div>
        <label class="block text-sm font-medium text-gray-700">Industry Type</label>
        <input type="text" name="industry_type" value="{{ old('industry_type', $company->industry_type ?? '') }}"
               class="mt-1 w-full rounded-md border-gray-300 focus:border-indigo-500 focus:ring-indigo-500">
      </div>

      <div>
        <label class="block text-sm font-medium text-gray-700">Registration No</label>
        <input type="text" name="registration_no" value="{{ old('registration_no', $company->registration_no ?? '') }}"
               class="mt-1 w-full rounded-md border-gray-300 focus:border-indigo-500 focus:ring-indigo-500">
      </div>

      <div>
        <label class="block text-sm font-medium text-gray-700">Website</label>
        <input type="url" name="website" value="{{ old('website', $company->website ?? '') }}"
               class="mt-1 w-full rounded-md border-gray-300 focus:border-indigo-500 focus:ring-indigo-500" placeholder="https://">
      </div>
    </div>

    <div>
      <label class="block text-sm font-medium text-gray-700">Address</label>
      <textarea name="address" rows="3"
                class="mt-1 w-full rounded-md border-gray-300 focus:border-indigo-500 focus:ring-indigo-500"
      >{{ old('address', $company->address ?? '') }}</textarea>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 items-start">
      <div>
        <label class="block text-sm font-medium text-gray-700">Logo</label>
        <input type="file" name="logo" accept="image/*"
               class="mt-1 w-full rounded-md border-gray-300 focus:border-indigo-500 focus:ring-indigo-500">
        <p class="mt-1 text-xs text-gray-500">JPG, PNG, WEBP, AVIF up to 2MB</p>
      </div>

      <div class="flex items-center gap-4">
        @if($isEdit && ($company->logo_url ?? null))
          <img src="{{ $company->logo_url }}" alt="logo" class="h-16 w-16 rounded object-cover ring-1 ring-gray-200">
        @else
          <div class="h-16 w-16 rounded bg-gray-100 ring-1 ring-gray-200"></div>
        @endif

        @if($isEdit && ($company->logo ?? null))
          <label class="inline-flex items-center gap-2 text-sm">
            <input type="checkbox" name="remove_logo" value="1" class="rounded border-gray-300">
            Remove current logo
          </label>
        @endif
      </div>
    </div>

    <div>
      <label class="block text-sm font-medium text-gray-700">Status</label>
      @php $status = old('status', $company->status ?? 'active'); @endphp
      <select name="status" class="mt-1 w-full rounded-md border-gray-300 focus:border-indigo-500 focus:ring-indigo-500">
        <option value="active" {{ $status==='active' ? 'selected' : '' }}>Active</option>
        <option value="inactive" {{ $status==='inactive' ? 'selected' : '' }}>Inactive</option>
        <option value="suspended" {{ $status==='suspended' ? 'selected' : '' }}>Suspended</option>
      </select>
    </div>
  </div>

  <div class="flex items-center justify-end gap-3">
    <a href="{{ route('admin.companies.index') }}"
       class="px-4 py-2 rounded-md border border-gray-300 text-gray-700 hover:bg-gray-50">Cancel</a>
    <button type="submit"
            class="px-4 py-2 rounded-md bg-indigo-600 text-white hover:bg-indigo-700">
      {{ $submitLabel ?? ($isEdit ? 'Update Company' : 'Create Company') }}
    </button>
  </div>
</form>
