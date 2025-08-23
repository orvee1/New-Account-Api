<div 
    class="relative px-2 py-2 bg-white rounded-xl border border-green-600" 
>
    <div
        class="flex gap-2 items-center justify-between"
    >
        <span class="bg-gray-100 px-1 py-1 rounded text-xs">
            Device ID : <b>{{ $device->id }}</b>
        </span>
        <div class="relative grow-0 shrink-0">
            @include('admin.doctor-device-log.partials.device-icon', [
                'is_smart_phone' => false,
            ])
        </div>
        <div
            class="grow shrink grid" 
        >
            <h3 class="text-base md:text-lg">
                {{ $device->name ?? '' }}
            </h3>
        </div>
        <span class="bg-gray-100 px-1 py-1 rounded text-xs">
            {{-- Last Active : <b>{{ $device->last_active }}</b> --}}
            Last Active : <b>{{ $device->last_used_at->format('d M Y, h:ia') }}</b>
        </span>
    </div>
    
    @if ($device->is_online ?? false)
    <span class="absolute bg-sky-600 w-3 h-3 rounded-full -top-1 -right-1"></span>
    <span class="absolute bg-sky-600 w-3 h-3 rounded-full -top-1 -right-1 animate-ping"></span>
    @endif
</div>