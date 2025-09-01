@php
  $isEdit = isset($companyUser) && $companyUser?->exists;
@endphp

<form method="POST" action="{{ $action }}" enctype="multipart/form-data" class="space-y-6">
  @csrf
  @if (strtoupper($method) !== 'POST')
    @method($method)
  @endif

  <div class="bg-white shadow-sm ring-1 ring-gray-200 rounded-lg p-6 space-y-6">
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
      <div>
        <label class="block text-sm font-medium text-gray-700">Company <span class="text-red-500">*</span></label>
        <select name="company_id" required
                class="mt-1 w-full rounded-md border-gray-300 focus:border-indigo-500 focus:ring-indigo-500">
          <option value="">Select company</option>
          @foreach($companies as $c)
            <option value="{{ $c->id }}" @selected(old('company_id', $companyUser->company_id ?? '') == $c->id)>
              {{ $c->name }}
            </option>
          @endforeach
        </select>
      </div>

      <div>
        <label class="block text-sm font-medium text-gray-700">Role <span class="text-red-500">*</span></label>
        @php $role = old('role', $companyUser->role ?? 'viewer'); @endphp
        <select name="role" class="mt-1 w-full rounded-md border-gray-300 focus:border-indigo-500 focus:ring-indigo-500" required>
          @foreach($roles as $r)
            <option value="{{ $r }}" @selected($role===$r)>{{ ucfirst($r) }}</option>
          @endforeach
        </select>
      </div>

      <div>
        <label class="block text-sm font-medium text-gray-700">Name <span class="text-red-500">*</span></label>
        <input type="text" name="name" value="{{ old('name', $companyUser->name ?? '') }}"
               class="mt-1 w-full rounded-md border-gray-300 focus:border-indigo-500 focus:ring-indigo-500" required>
      </div>

      <div>
        <label class="block text-sm font-medium text-gray-700">Email</label>
        <input type="email" name="email" value="{{ old('email', $companyUser->email ?? '') }}"
               class="mt-1 w-full rounded-md border-gray-300 focus:border-indigo-500 focus:ring-indigo-500">
      </div>

      <div>
        <label class="block text-sm font-medium text-gray-700">Phone Number <span class="text-red-500">*</span></label>
        <input type="text" name="phone_number" value="{{ old('phone_number', $companyUser->phone_number ?? '') }}"
               class="mt-1 w-full rounded-md border-gray-300 focus:border-indigo-500 focus:ring-indigo-500" required>
      </div>

      <div>
        <label class="block text-sm font-medium text-gray-700">Status <span class="text-red-500">*</span></label>
        @php $status = old('status', $companyUser->status ?? 'active'); @endphp
        <select name="status" class="mt-1 w-full rounded-md border-gray-300 focus:border-indigo-500 focus:ring-indigo-500" required>
          @foreach($statuses as $s)
            <option value="{{ $s }}" @selected($status===$s)>{{ ucfirst($s) }}</option>
          @endforeach
        </select>
      </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 items-start">
      <div>
        <label class="block text-sm font-medium text-gray-700">Photo</label>
        <input type="file" name="photo" accept="image/*"
               class="mt-1 w-full rounded-md border-gray-300 focus:border-indigo-500 focus:ring-indigo-500">
        <p class="mt-1 text-xs text-gray-500">JPG, PNG, WEBP, AVIF up to 2MB</p>
      </div>

      <div class="flex items-center gap-4">
        @if($isEdit && ($companyUser->photo_url ?? null))
          <img src="{{ $companyUser->photo_url }}" alt="photo" class="h-16 w-16 rounded-full object-cover ring-1 ring-gray-200">
        @else
          <div class="h-16 w-16 rounded-full bg-gray-100 ring-1 ring-gray-200"></div>
        @endif

        @if($isEdit && ($companyUser->photo ?? null))
          <label class="inline-flex items-center gap-2 text-sm">
            <input type="checkbox" name="remove_photo" value="1" class="rounded border-gray-300">
            Remove current photo
          </label>
        @endif
      </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
      <div class="flex items-center gap-2">
        <input type="checkbox" name="is_primary" value="1" id="is_primary"
               class="rounded border-gray-300"
               @checked(old('is_primary', $companyUser->is_primary ?? false))>
        <label for="is_primary" class="text-sm text-gray-700">Mark as Primary user for this company</label>
      </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
      <div>
        <label class="block text-sm font-medium text-gray-700">
          Password {{ $isEdit ? '(leave blank to keep)' : '' }}
          @unless($isEdit) <span class="text-red-500">*</span> @endunless
        </label>
        <input type="password" name="password"
               class="mt-1 w-full rounded-md border-gray-300 focus:border-indigo-500 focus:ring-indigo-500" {{ $isEdit ? '' : 'required' }}>
      </div>
      <div>
        <label class="block text-sm font-medium text-gray-700">Confirm Password {{ $isEdit ? '' : '*' }}</label>
        <input type="password" name="password_confirmation"
               class="mt-1 w-full rounded-md border-gray-300 focus:border-indigo-500 focus:ring-indigo-500" {{ $isEdit ? '' : 'required' }}>
      </div>
    </div>

    <div>
      <label class="block text-sm font-medium text-gray-700">Permissions (JSON)</label>
      <textarea name="permissions" rows="6"
                class="mt-1 w-full rounded-md border-gray-300 focus:border-indigo-500 focus:ring-indigo-500"
                placeholder='{"invoices":{"create":true,"delete":false},"reports.view":true}'>{{ old('permissions',
json_encode($companyUser->permissions ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) }}</textarea>
      <p class="mt-1 text-xs text-gray-500">Provide a valid JSON. Nested keys supported (e.g. <code>reports.view</code>).</p>
    </div>
  </div>

  <div class="flex items-center justify-end gap-3">
    <a href="{{ route('admin.company_user.index') }}"
       class="px-4 py-2 rounded-md border border-gray-300 text-gray-700 hover:bg-gray-50">Cancel</a>
    <button type="submit"
            class="px-4 py-2 rounded-md bg-indigo-600 text-white hover:bg-indigo-700">
      {{ $submitLabel ?? ($isEdit ? 'Update User' : 'Create User') }}
    </button>
  </div>
</form>
