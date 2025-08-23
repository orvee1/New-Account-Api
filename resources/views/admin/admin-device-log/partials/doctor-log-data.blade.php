<div class="w-full flex justify-between px-2">
    {{ $users->links('components.search-method-paginator') }}
</div>

<div class="w-full grid md:grid-cols-3 xl:grid-cols-5 gap-4 px-2 py-2">
    @foreach ($users as $user)
    <a 
        href="{{ url('/admin/admin-device-log') }}/{{ $user->id }}"
        class="grid hover:scale-105 transition-all"
    >
        @include('admin.admin-device-log.partials.admin-card', [
            'user' => $user,  
        ])
    </a>
    @endforeach
</div>

<hr class="my-3 print:hidden">

<div class="w-full flex justify-between px-2">
    {{ $users->links('components.search-method-paginator') }}
</div>
