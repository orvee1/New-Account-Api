<x-app-layout>

    @if ($user)
        <div class="flex flex-col justify-center items-center gap-8 mb-4">
            <a class="w-full max-w-xs bg-white grid hover:scale-105 transition-all" href="/admin/users/{{ $user->id }}"
                target="_blank">
                @include('admin.admin-device-log.partials.admin-card', [
                    'user' => $user,
                ])
            </a>
        </div>
    @endif

    <div class="flex flex-col justify-center items-center">
        <h2 class="text-center text-2xl text-green-600 py-2 bg-green-600/20 w-full">
            Verified Devices (Device and Browser)
        </h2>
        <div class="w-full max-w-4xl grid gap-4 px-4 py-4">
            @foreach($active_devices as $active_device)
                @include('admin.admin-device-log.partials.device-card', [
                    "device" => $active_device,
                ])
            @endforeach
        </div>
    </div>
    @if($is_device_accept_permission ?? false)
    <div class="flex flex-col justify-center items-center ">
        <h2 class="text-center text-2xl text-rose-600 py-2 bg-rose-600/20 w-full">
            Request
        </h2>
        <div class="w-full max-w-4xl grid gap-3 p-3 border rounded-lg">
            @foreach($pending_user_devices as $pending_user_device)
                @include('admin.admin-device-log.partials.request-card', [
                    'requestable_user_device' => $pending_user_device,
                ])
            @endforeach
        </div>
    </div>
    @endif
    <h2 class="text-center text-2xl text-sky-600 py-2 bg-sky-600/20 w-full">
        History
    </h2>

    <table class="border-collapse table-auto w-full whitespace-no-wrap bg-white table-striped ">
        <thead>
            <tr>
                <th class="py-2 border border-gray-500/25">History No</th>
                <th class="py-2 border border-gray-500/25">Device ID</th>
                <th class="py-2 border border-gray-500/25">Device</th>
                <th class="py-2 border border-gray-500/25">Reason</th>
                <th class="py-2 border border-gray-500/25">Note</th>
                <th class="py-2 border border-gray-500/25">Last Active</th>
                <th class="py-2 border border-gray-500/25">ActionAt</th>
                <th class="py-2 border border-gray-500/25">ActionBy</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($device_requests as $device_request)
            
                <tr class="text-center {{ $device_request->type == 0 ? 'bg-rose-600/20': '' }}" >
                    <td class="py-2 border border-gray-500/25">
                        {{ $device_requests->count() - $loop->index }}
                    </td>
                    <td class="py-2 border border-gray-500/25">
                        {{ $device_request->admin_device->id ?? "" }}
                    </td>
                    <td class="py-2 border border-gray-500/25 px-2">
                        <div class="flex gap-2 items-center">
                            <div>
                                @include('admin.admin-device-log.partials.device-icon', [
                                    'is_smart_phone' => $device_request->admin_device->is_smart_phone ?? false,
                                ])
                            </div>
                            <div>
                                {{ $device_request->admin_device->name ?? "" }}
                            </div>
                        </div>
                    </td>
                    <td class="py-2 border border-gray-500/25 px-2">
                        <div 
                            class="whitespace-pre-line max-w-3xl text-justify"
                        >{{ $device_request->reason }}</div>
                    </td>
                    <td class="py-2 border border-gray-500/25 px-2">
                        <div 
                            class="whitespace-pre-line max-w-3xl text-justify"
                        >{{ $device_request->note }}</div>
                    </td>
                    <td class="py-2 border border-gray-500/25">
                        {{ $device_request->admin_device->last_active ?? '' }}
                    </td>
                    <td class="py-2 border border-gray-500/25">
                        @if ($device_request->accept_at)
                            
                        {{ $device_request->accept_at->format('d M y') }} <br>
                        {{ $device_request->accept_at->format('h:i:s a') }}
                        @endif
                    </td>
                    <td class="py-2 border border-gray-500/25">
                        @role('Administrator|Developer')
                            @if ($device_request->accept_by)
                            <a 
                                href="/admin/administrator/{{ $device_request->accept_by }}"
                                target="_blank"
                                class="hover:underline"
                            >
                                {{ $device_request->accept_by }} (Admin)
                            </a>
                            @endif
                        @else
                         <span>{{ $device_request->accept_by }} (Admin)</span>
                        @endrole

                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>


    <script>
        function imageOnErrorHandler(imgElement) {
            imgElement.src = 'https://edudent-file.s3.ap-southeast-1.amazonaws.com/img/doc_male.jpg';
        }
    </script>

</x-app-layout>
