@php
  $u = $companyUser;
@endphp

@if ($errors->any())
  <div class="mb-4 rounded-md bg-red-50 border border-red-200 px-4 py-3 text-red-700">
    <ul class="list-disc pl-5">
      @foreach ($errors->all() as $e) <li>{{ $e }}</li> @endforeach
    </ul>
  </div>
@endif

<form action="{{ $route }}" method="POST" enctype="multipart/form-data" class="bg-white rounded-2xl shadow border border-gray-100 p-6 space-y-6">
  @csrf
  @if (strtoupper($method) !== 'POST') @method($method) @endif

  <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
    {{-- Company --}}
    <div>
      <label class="block text-sm font-medium text-gray-700 mb-1">Company <span class="text-red-500">*</span></label>
      <select name="company_id" required
              class="w-full rounded-lg border py-2 px-3 border-gray-300 focus:ring-2 focus:ring-emerald-500">
        <option value="">— Select company —</option>
        @foreach ($companies as $c)
          <option value="{{ $c->id }}"
            @selected(old('company_id', $u->company_id ?? '') == $c->id)>{{ $c->name }}</option>
        @endforeach
      </select>
      @error('company_id') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
    </div>

    {{-- Name --}}
    <div>
      <label class="block text-sm font-medium text-gray-700 mb-1">Full Name <span class="text-red-500">*</span></label>
      <input type="text" name="name" value="{{ old('name', $u->name ?? '') }}" required
             class="w-full rounded-lg border py-2 px-3 border-gray-300 focus:ring-2 focus:ring-emerald-500" />
      @error('name') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
    </div>

    {{-- Email --}}
    <div>
      <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
      <input type="email" name="email" value="{{ old('email', $u->email ?? '') }}"
             class="w-full rounded-lg border py-2 px-3 border-gray-300 focus:ring-2 focus:ring-emerald-500" />
      @error('email') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
      <p class="text-xs text-gray-400 mt-1">Unique within the selected company.</p>
    </div>

    {{-- Phone --}}
    <div>
      <label class="block text-sm font-medium text-gray-700 mb-1">Phone Number <span class="text-red-500">*</span></label>
      <input type="text" name="phone_number" value="{{ old('phone_number', $u->phone_number ?? '') }}" required
             class="w-full rounded-lg border py-2 px-3 border-gray-300 focus:ring-2 focus:ring-emerald-500" />
      @error('phone_number') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
      <p class="text-xs text-gray-400 mt-1">Unique within the selected company.</p>
    </div>

    {{-- Role --}}
    <div>
      <label class="block text-sm font-medium text-gray-700 mb-1">Role <span class="text-red-500">*</span></label>
      <select name="role" required class="w-full rounded-lg border py-2 px-3 border-gray-300 focus:ring-2 focus:ring-emerald-500">
        @foreach ($roles as $r)
          <option value="{{ $r }}" @selected(old('role', $u->role ?? '') == $r)>{{ ucfirst($r) }}</option>
        @endforeach
      </select>
      @error('role') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
    </div>

    {{-- Status --}}
    <div>
      <label class="block text-sm font-medium text-gray-700 mb-1">Status <span class="text-red-500">*</span></label>
      <select name="status" required class="w-full rounded-lg border py-2 px-3 border-gray-300 focus:ring-2 focus:ring-emerald-500">
        @foreach ($statuses as $s)
          <option value="{{ $s }}" @selected(old('status', $u->status ?? '') == $s)>{{ ucfirst($s) }}</option>
        @endforeach
      </select>
      @error('status') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
      <p class="text-xs text-gray-400 mt-1">If set to Active and joined_at is empty, it will be auto-filled.</p>
    </div>

    {{-- Primary --}}
    <div class="md:col-span-2">
      <label class="inline-flex items-center gap-2">
        <input type="checkbox" name="is_primary" value="1"
               @checked(old('is_primary', $u->is_primary ?? false)) class="rounded border-gray-300 text-emerald-600 focus:ring-emerald-500">
        <span class="text-sm text-gray-700">Mark as primary user for this company</span>
      </label>
      @error('is_primary') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
    </div>

    {{-- Photo --}}
    <div class="md:col-span-2">
      <label class="block text-sm font-medium text-gray-700 mb-1">Photo</label>
      <div class="flex items-center gap-4">
        <div class="h-16 w-16 rounded-full bg-gray-100 overflow-hidden ring-1 ring-gray-200">
          <img id="photoPreview" src="{{ $u?->photo_url }}" class="h-full w-full object-cover" alt="">
        </div>
        <input type="file" name="photo" accept="image/*"
               class="w-full rounded-lg border py-2 px-3 border-gray-300 focus:ring-2 focus:ring-emerald-500"
               onchange="previewPhoto(event)">
      </div>
      @error('photo') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror

      @if ($u && $u->photo)
        <label class="mt-3 inline-flex items-center gap-2">
          <input type="checkbox" name="remove_photo" value="1" class="rounded border-gray-300 text-red-600 focus:ring-red-500">
          <span class="text-sm text-gray-700">Remove existing photo</span>
        </label>
      @endif
    </div>

    {{-- Passwords --}}
    <div>
      <label class="block text-sm font-medium text-gray-700 mb-1">
        Password <span class="text-red-500">{{ $u ? '(leave blank to keep unchanged)' : '*' }}</span>
      </label>
      <input type="password" name="password"
             class="w-full rounded-lg border py-2 px-3 border-gray-300 focus:ring-2 focus:ring-emerald-500" @unless($u) required @endunless />
      @error('password') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
    </div>
    <div>
      <label class="block text-sm font-medium text-gray-700 mb-1">Confirm Password {{ $u ? '' : '*' }}</label>
      <input type="password" name="password_confirmation" @unless($u) required @endunless
             class="w-full rounded-lg border py-2 px-3 border-gray-300 focus:ring-2 focus:ring-emerald-500" />
    </div>

    {{-- Permissions (JSON or array) --}}
    <div class="md:col-span-2">
      <label class="block text-sm font-medium text-gray-700 mb-1">Permissions (JSON)</label>
      <textarea name="permissions" rows="5"
        class="w-full rounded-lg border py-2 px-3 border-gray-300 focus:ring-2 focus:ring-emerald-500"
        placeholder='{"billing.create": true, "invoice.view": true}'>{{ old('permissions', is_array($u->permissions ?? null) ? json_encode($u->permissions, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES) : ($u->permissions ?? '')) }}</textarea>
      <div class="mt-2 flex items-center gap-2">
        <button type="button" onclick="formatJson('permissions')" class="px-3 py-1.5 text-xs rounded border border-gray-300 hover:bg-gray-50">
          Beautify JSON
        </button>
        <span class="text-xs text-gray-400">You can paste JSON or leave empty.</span>
      </div>
      @error('permissions') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
    </div>
  </div>

  {{-- Actions --}}
  <div class="flex items-center justify-between pt-4 border-t">
    <a href="{{ route('company-users.index') }}" class="px-4 py-2 rounded-lg border border-gray-300 hover:bg-gray-50">Cancel</a>
    <button class="px-5 py-2 rounded-lg bg-emerald-600 text-white hover:bg-emerald-700">
      {{ $u ? 'Update User' : 'Create User' }}
    </button>
  </div>
</form>

{{-- Tiny JS for photo preview & JSON format --}}
@push('scripts')
<script>
  function previewPhoto(e){
    const [file] = e.target.files || [];
    if(!file) return;
    const img = document.getElementById('photoPreview');
    img.src = URL.createObjectURL(file);
  }
  function formatJson(fieldName){
    const ta = document.querySelector(`textarea[name="${fieldName}"]`);
    if(!ta) return;
    try {
      const val = ta.value.trim();
      if(!val) return;
      const parsed = JSON.parse(val);
      ta.value = JSON.stringify(parsed, null, 2);
    } catch(e) {
      alert('Invalid JSON');
    }
  }
</script>
@endpush
