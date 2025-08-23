@php
    $search = request()->search;
@endphp

<div class="w-full max-w-md mx-auto bg-white rounded-xl shadow-md p-4 space-y-4">
    <div class="flex items-center space-x-4">
        <img class="w-12 h-12 rounded-full object-cover border" 
             {{-- src="https://gen-file.s3.ap-southeast-1.amazonaws.com/storage/2025/04/12/f0csW6IVq8H9tGb0QYjl.jpg"  --}}
             src="https://gen-file.s3.ap-southeast-1.amazonaws.com/storage/2025/04/12/lhjwCIjWGRlzuDqXKqWD.jpg" 
             alt="Photo"
             onerror="imageOnErrorHandler(this)" />

        <div>
            <h2 class="text-md pl-2 font-semibold text-gray-800">
                {!! str_ireplace($search, "<mark class='bg-yellow-200'>$search</mark>", $user->name ?? '') !!}
            </h2>
        </div>
    </div>

    <div class="space-y-2">
        <div class="flex items-center space-x-2 text-gray-600">
            <svg class="w-5 h-5 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                 stroke-width="1.5" xmlns="http://www.w3.org/2000/svg">
                <path stroke-linecap="round" stroke-linejoin="round"
                      d="M15 9h3.75M15 12h3.75M15 15h3.75M4.5 19.5h15a2.25 2.25 0 002.25-2.25V6.75A2.25 
                      2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 
                      2.25v10.5A2.25 2.25 0 004.5 19.5zm6-10.125a1.875 
                      1.875 0 11-3.75 0 1.875 1.875 0 013.75 
                      0zm1.294 6.336a6.721 6.721 0 01-3.17.789 
                      6.721 6.721 0 01-3.168-.789 3.376 3.376 0 
                      016.338 0z"/>
            </svg>
            <span class="text-sm">
                {!! str_ireplace($search, "<mark class='bg-yellow-200'>$search</mark>", $user->email ?? '') !!}
            </span>
        </div>

        <div class="flex items-center space-x-2 text-gray-600">
            <svg class="w-5 h-5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                 stroke-width="1.5" xmlns="http://www.w3.org/2000/svg">
                <path stroke-linecap="round" stroke-linejoin="round"
                      d="M2.25 6.75c0 8.284 6.716 15 15 
                      15h2.25a2.25 2.25 0 002.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 
                      1.293c-.282.376-.769.542-1.21.38a12.035 12.035 
                      0 01-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.271.527-.734.417-1.173L6.963 
                      3.102a1.125 1.125 0 00-1.091-.852H4.5A2.25 
                      2.25 0 002.25 4.5v2.25z"/>
            </svg>
            <span class="text-sm">
                {!! str_ireplace($search, "<mark class='bg-yellow-200'>$search</mark>", $user->phone_number ?? '') !!}
            </span>
        </div>
    </div>
</div>
