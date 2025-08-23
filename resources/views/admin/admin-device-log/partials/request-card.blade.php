@php
    $border_class = "border-rose-600";
    $action_text = "Accept";
    $action_class = "bg-rose-600";
@endphp



<div class="border border-green-600 p-3 rounded-md bg-white grid {{ $border_class ?? '' }}">
    <div class="flex flex-auto gap-2 justify-between">
        <div class="flex gap-1 items-center">
            <div class="text-gray-400">ID:</div>
            <div class="font-bold">
                {{ $requestable_user_device->id ?? ''  }}
            </div>
        </div>

        <div class="flex gap-1 items-center">
            <div class="text-gray-400">
                @include('admin.doctor-device-log.partials.device-icon', [
                    'is_smart_phone' => $requestable_user_device->is_smart_phone ?? false,
                ])
            </div>
            <div class="font-bold">
                {{ $requestable_user_device->name ?? ''  }}
            </div>
        </div>

        <div class="flex gap-1 items-center">
            <span class="bg-gray-100 px-1 py-1 rounded text-sm">
                {{ $requestable_user_device->request->created_at ? $requestable_user_device->request->created_at->format("d-M-Y h:iA") : ''  }}
            </span>
        </div>

        <div class="flex gap-1 items-center justify-end">
            {{-- <div class="text-gray-400">Action:</div> --}}

                <button
                    type="submit"
                    class="border px-3 py-1 rounded-md text-white text-sm {{ $action_class ?? 'bg-gray-600' }}"
                    style="cursor: pointer;"
                    id='cancelModalShow_{{ $requestable_user_device->id ?? ''  }}'
                >
                    {{ 'Cancel' }}
                </button>

                <button
                    type="submit"
                    class="border px-3 py-1 rounded-md text-white text-sm bg-green-600"
                    style="cursor: pointer;"
                    id='acceptModalShow_{{ $requestable_user_device->id ?? ''  }}'
                >
                    {{ $action_text ?? 'Accept' }}
                </button>

        </div>
    </div>

    <hr class="my-3">

    <div 
        class="whitespace-pre-line"
    >{{ $requestable_user_device->request->reason ?? '' }}</div>

    <div
        class="w-full fixed inset-0 h-full justify-center bg-gray-200/75 overflow-auto hidden"
        style="margin-top: 100px; z-index:100;"
        id='acceptModal'
        >
        <div class="relative p-4 w-full max-w-3xl max-h-full">
        <!-- Modal content -->
        <form 
            method="POST"
            action="{{ route('admin-device.requests.update', [$requestable_user_device->id, $requestable_user_device->request->id]) }}"
            >
                @csrf
                @method('PUT')
                <div class="relative rounded-lg shadow bg-white border-2 border-blue-600">
                    <!-- Modal header -->
                    <div class="flex items-center justify-center p-4 md:p-5 border-b rounded-t title-bg bg-blue-600">
                    <h3 class="text-xl font-semibold text-white">Accept this request</h3>
                    </div>
                    <!-- Modal body -->
                    <div class="p-4 md:p-5 space-y-4">
                    <div class="overflow-auto">
                        <div class="w-full flex items-center py-1 text-lg">
                            <strong class="col-span-1 text-right" style="width: 28%; padding-right: 10px;">Accept Request Note: </strong>
                            <input class="px-2 py-1 rounded-md text-lg" style="width: 72%; border: 1px solid gray;" type="text" name='note' id="note" required/>
                        </div>
                    </div>
                    </div>
                    <!-- Modal footer -->
                    <div class="flex justify-end p-4 md:p-5 border-t border-gray-200 rounded-b">
                        <span
                        data-modal-hide="default-modal"
                        style="cursor: pointer;"
                        class="cancelButton text-white mr-2 bg-rose-700 hover:bg-rose-800 focus:ring-4 focus:outline-none focus:ring-blue-300 font-medium rounded-lg text-sm px-5 py-2.5 text-center dark:bg-blue-600 dark:hover:bg-blue-700 dark:focus:ring-blue-800"
                    >
                        Close
                    </span>

                    <button
                        data-modal-hide="default-modal"
                        type="submit"
                        class="text-white bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:outline-none focus:ring-blue-300 font-medium rounded-lg text-sm px-5 py-2.5 text-center dark:bg-blue-600 dark:hover:bg-blue-700 dark:focus:ring-blue-800"
                    >
                        Submit
                    </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
    <div
        class="w-full fixed inset-0 h-full justify-center bg-gray-200/75 overflow-auto hidden"
        style="margin-top: 100px; z-index:100;"
        id='cancelModal'
        >
        <div class="relative p-4 w-full max-w-3xl max-h-full">
        <!-- Modal content -->
        <form 
            method="POST"
            action="{{ route('admin-device.requests.cancel', [$requestable_user_device->id, $requestable_user_device->request->id]) }}"
            >
                @csrf
                @method('PUT')
                <div class="relative rounded-lg shadow bg-white border-2 border-blue-600">
                    <!-- Modal header -->
                    <div class="flex items-center justify-center p-4 md:p-5 border-b rounded-t title-bg bg-blue-600">
                    <h3 class="text-xl font-semibold text-white">Cancel this request</h3>
                    </div>
                    <!-- Modal body -->
                    <div class="p-4 md:p-5 space-y-4">
                    <div class="overflow-auto">
                        <div class="w-full flex items-center py-1 text-lg">
                            <strong class="col-span-1 text-right" style="width: 28%; padding-right: 10px;">Cancel Request Note: </strong>
                            <input class="px-2 py-1 rounded-md text-lg" style="width: 72%; border: 1px solid gray;" type="text" name='note' id="note" required/>
                        </div>
                    </div>
                    </div>
                    <!-- Modal footer -->
                    <div class="flex justify-end p-4 md:p-5 border-t border-gray-200 rounded-b">
                    <span
                        data-modal-hide="default-modal"
                        style="cursor: pointer;"
                        class="cancelButton text-white mr-2 bg-rose-700 hover:bg-rose-800 focus:ring-4 focus:outline-none focus:ring-blue-300 font-medium rounded-lg text-sm px-5 py-2.5 text-center dark:bg-blue-600 dark:hover:bg-blue-700 dark:focus:ring-blue-800"
                    >
                        Close
                    </span>

                    <button
                        data-modal-hide="default-modal"
                        type="submit"
                        class="text-white bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:outline-none focus:ring-blue-300 font-medium rounded-lg text-sm px-5 py-2.5 text-center dark:bg-blue-600 dark:hover:bg-blue-700 dark:focus:ring-blue-800"
                    >
                        Submit
                    </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
<script src="https://gen-file.s3.ap-southeast-1.amazonaws.com/assets/plugins/jquery-1.11.0.min.js"
type="text/javascript"></script>
<script src="https://gen-file.s3.ap-southeast-1.amazonaws.com/assets/plugins/jquery-migrate-1.2.1.min.js"
type="text/javascript"></script>
<script>
// jQuery code
$(document).ready(function() {
  // Define a function to handle the click event
  $('#cancelModalShow_{{ $requestable_user_device->id ?? ''  }}').click(function() {
    // Toggle the 'highlighted' class
    $("#cancelModal").removeClass("hidden");
    $("#cancelModal").addClass('flex');
  });

  $('#acceptModalShow_{{ $requestable_user_device->id ?? ''  }}').click(function() {
    // Toggle the 'highlighted' class
    $("#acceptModal").removeClass("hidden");
    $("#acceptModal").addClass('flex');
  });

  $('.cancelButton').on('click', function(){
        $("#acceptModal").removeClass("flex");
        $("#cancelModal").removeClass("flex");
        $("#acceptModal").addClass('hidden');
        $("#cancelModal").addClass('hidden');
  });
});
    </script>

