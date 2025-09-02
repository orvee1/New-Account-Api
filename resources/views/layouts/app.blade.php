<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Laravel') }}</title>
    <link rel="icon" href="{{ asset('images/logo.png') }}" type="image/png" sizes="16x16">
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.6.3/css/all.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.1.0/css/font-awesome.css" />
    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="font-sans antialiased">

    <!-- Page Content -->
    <div class="w-screen h-screen flex bg-gray-100" x-data="{ navigationTrigger: false }">
        <nav x-bind:class="navigationTrigger ? 'flex lg:hidden' : 'hidden lg:flex'"
            class="shrink-0 bg-gray-50 flex-col w-full lg:w-auto">
            <div class="shrink-0 grow-0 bg-sky-600 z-30 px-2 h-14 flex items-center gap-2 lg:px-3 print:hidden">
                <a href="/admin" class="w-10 h-10 bg-white rounded-full p-1">
                    <img src="{{ asset('images/logo.png') }}" class="w-full h-full mt-px" alt="Logo">
                </a>
                <input id="navigationSearch" class="px-3 py-1 bg-white rounded-md focus:outline-none border shadow grow"
                    type="text" placeholder="Search..." autocomplete="off">
                <div @click="navigationTrigger = !navigationTrigger"
                    class="lg:hidden w-8 border rounded text-center text-xl cursor-pointer bg-white text-sky-600">
                    &#9776;
                </div>
            </div>
            <ul class="grow bg-white overflow-y-auto px-2 print:hidden border-r" id="navigationMenu">
                <li class="pl-2 font-semibold">
                    <a href="{{ url('admin') }}"
                        class="h-8 flex items-center justify-between gap-2 cursor-pointer {{ Request::path() == 'admin' ? 'text-sky-700' : '' }}">
                        <i class="w-5 text-center fas fa-home"></i>
                        <span class="grow menu__title">Dashboard</span>
                    </a>
                </li>
                @hasanyrole('Administrator|Developer')
                <li class="pl-2 font-semibold">
                    <a onclick="submenuToggle(this)" class="h-8 flex items-center justify-between gap-2 cursor-pointer">
                        <i class="w-5 text-center fas fa-user"></i>
                        <span class="grow menu__title">Administrator</span>
                        <i class="w-5 text-center icon-arrow-down"></i>
                    </a>
                    <ul class="hidden pl-2 py-1">
                        <li class="pl-2 font-semibold">
                            <a href="{{ url('admin/administrator') }}"
                                class="h-8 flex items-center justify-between gap-2 cursor-pointer {{ Request::path() == 'admin/administrator' ? 'text-sky-700' : '' }}">
                                <i class="w-5 text-center fas fa-user"></i>
                                <span class="grow menu__title">Administrator List</span>
                            </a>
                        </li>
                        <li class="pl-2 font-semibold">
                            <a href="{{ route('administrator.create') }}"
                                class="h-8 flex items-center justify-between gap-2 cursor-pointer {{ Request::path() == 'admin/administrator/create' ? 'text-sky-700' : '' }}">
                                <i class="w-5 text-center fas fa-plus"></i>
                                <span class="grow menu__title">Add Administrator</span>
                            </a>
                        </li>
                    </ul>
                </li>
                <li class="pl-2 font-semibold">
                    <a onclick="submenuToggle(this)" class="h-8 flex items-center justify-between gap-2 cursor-pointer">
                        <i class="w-5 text-center fas fa-user"></i>
                        <span class="grow menu__title">Roles</span>
                        <i class="w-5 text-center icon-arrow-down"></i>
                    </a>
                    <ul class="hidden pl-2 py-1">
                        <li class="pl-2 font-semibold">
                            <a href="{{ url('admin/roles') }}"
                                class="h-8 flex items-center justify-between gap-2 cursor-pointer {{ Request::path() == 'admin/roles' ? 'text-sky-700' : '' }}">
                                <i class="w-5 text-center fas fa-user"></i>
                                <span class="grow menu__title">Role List</span>
                            </a>
                        </li>
                        <li class="pl-2 font-semibold">
                            <a href="{{ route('roles.create') }}"
                                class="h-8 flex items-center justify-between gap-2 cursor-pointer {{ Request::path() == 'admin/roles/create' ? 'text-sky-700' : '' }}">
                                <i class="w-5 text-center fas fa-plus"></i>
                                <span class="grow menu__title">Add Role</span>
                            </a>
                        </li>
                    </ul>
                </li>
                <li class="pl-2 font-semibold">
                    <a onclick="submenuToggle(this)" class="h-8 flex items-center justify-between gap-2 cursor-pointer">
                        <i class="w-5 text-center fas fa-lock"></i>
                        <span class="grow menu__title">Permissions</span>
                        <i class="w-5 text-center icon-arrow-down"></i>
                    </a>
                    <ul class="hidden pl-2 py-1">
                        <li class="pl-2 font-semibold">
                            <a href="{{ url('admin/permissions') }}"
                                class="h-8 flex items-center justify-between gap-2 cursor-pointer {{ Request::path() == 'admin/permissions' ? 'text-sky-700' : '' }}">
                                <i class="w-5 text-center fas fa-lock"></i>
                                <span class="grow menu__title">Permission List</span>
                            </a>
                        </li>
                        <li class="pl-2 font-semibold">
                            <a href="{{ route('permissions.create') }}"
                                class="h-8 flex items-center justify-between gap-2 cursor-pointer {{ Request::path() == 'admin/permissions/create' ? 'text-sky-700' : '' }}">
                                <i class="w-5 text-center fas fa-plus"></i>
                                <span class="grow menu__title">Add Permission</span>
                            </a>
                        </li>
                    </ul>
                </li>
                @endhasanyrole

                @foreach ($menus as $menu)
                @can($menu->permission)
                <li class="pl-2 font-semibold">
                    <a href="{{ count($menu->submenu) ? '#' : url($menu->url) }}" onclick="submenuToggle(this)"
                        class="h-8 flex items-center justify-between gap-2 cursor-pointer {{ Request::is($menu->url . '*') ? 'text-sky-700' : '' }}">
                        <i class="w-5 text-center {{ $menu->icon }}"></i>
                        <span class="grow menu__title">{{ $menu->title }}</span>
                        @if (count($menu->submenu))
                        <i class="w-5 text-center icon-arrow-down"></i>
                        @endif
                    </a>
                    @if (count($menu->submenu))
                    <ul class="hidden pl-2 py-1 __child__container__{{ $menu->id }}">
                        @foreach ($menu->submenu as $submenu)
                        @can($submenu->permission)
                        @if (Request::is($submenu->url . '*'))
                        <script>
                            document.querySelector('.__child__container__{{ $menu->id }}').classList.remove('hidden')
                        </script>
                        @endif
                        <li class="pl-2 font-semibold">
                            <a href="{{ count($submenu->thirdmenu) ? '#' : url($submenu->url) }}"
                                onclick="submenuToggle(this)"
                                class="h-8 flex items-center justify-between gap-2 cursor-pointer {{ Request::is($submenu->url . '*') ? 'text-sky-700' : '' }}">
                                <i class="w-5 text-center {{ $submenu->icon }}"></i>
                                <span class="grow menu__title">{{ $submenu->title }}</span>
                                @if (count($submenu->thirdmenu))
                                <i class="w-5 text-center icon-arrow-down"></i>
                                @endif
                            </a>
                            @if (count($submenu->thirdmenu))
                            <ul class="hidden pl-2 py-1 __child__container__{{ $submenu->id }}">
                                @foreach ($submenu->thirdmenu as $thirdmenu)
                                @if (Request::is($thirdmenu->url . '*'))
                                <script>
                                    document.querySelector('.__child__container__{{ $menu->id }}').classList.remove('hidden')
                                </script>
                                @endif
                                <li class="pl-2 font-semibold">
                                    <a href="{{ url($thirdmenu->url) }}"
                                        class="h-8 flex items-center justify-between gap-2 cursor-pointer {{ Request::is($thirdmenu->url . '*') ? 'text-sky-700' : '' }}">
                                        <i class="w-5 text-center {{ $submenu->icon }}"></i>
                                        <span class="grow menu__title">{{ $thirdmenu->title }}</span>
                                    </a>
                                </li>
                                @endforeach
                            </ul>
                            @endif
                        </li>
                        @endcan
                        @endforeach
                    </ul>
                    @endif
                </li>
                @endcan
                @endforeach

                @hasanyrole('Administrator|Developer')
                <li class="pl-2 font-semibold">
                    <a href="{{ url('admin/menus') }}"
                        class="h-8 flex items-center justify-between gap-2 cursor-pointer {{ Request::path() == 'admin/menus' ? 'text-sky-700' : '' }}">
                        <i class="w-5 text-center fa fa-cog"></i>
                        <span class="grow menu__title">Menu Settings</span>
                    </a>
                </li>
                @endhasanyrole

            </ul>

            <div class="text-center bg-sky-600 text-white py-3 print:hidden">
                &copy; {{ date('Y') }} CloudBook
            </div>
        </nav>
        <div class="shrink grow print:overflow-visible">
            <header
                class="w-full z-30 px-4shadow h-14 flex items-center bg-sky-600 px-2 lg:px-3 print:hidden sticky top-0">
                <div class="w-full flex justify-between">
                    <div @click="navigationTrigger = !navigationTrigger"
                        x-bind:class="navigationTrigger ? '' : 'lg:-ml-2'"
                        class="w-8 border rounded text-center text-xl cursor-pointer z-40 bg-white text-sky-600">
                        &#9776;
                    </div>



                    @if ($admin_name = auth()->user()->name ?? 'Admin')
                    <div>
                        <div onclick="submenuToggle(this)"
                            class="flex items-center gap-2 px-2 cursor-pointer text-white">
                            <div
                                class="w-7 h-7 rounded-full flex items-center justify-center bg-white text-sky-600 text-lg">
                                {{ mb_strtoupper(mb_substr($admin_name, 0, 1, 'utf-8'), 'utf-8') }}
                            </div>
                            <div class="hidden lg:block">
                                {{ mb_convert_case(mb_strtolower($admin_name, 'utf-8'), MB_CASE_TITLE, 'UTF-8') }}
                            </div>
                            <i class="text-xs text-center icon-arrow-down"></i>
                        </div>
                        <div class="hidden relative z-40">
                            <ul
                                class="absolute min-w-max top-2 right-2 bg-gray-100 shadow rounded px-4 py-3 grid gap-2">
                                <a class="text-left flex gap-2 items-center" href="{{ url('admin/profile') }}">
                                    <i class="fa fa-user"></i>
                                    <span>My Profile</span>
                                </a>
                                <hr>
                                <button type="button" onclick="window.print()"
                                    class="text-left flex gap-2 items-center text-gray-700">
                                    <i class="fa fa-print"></i>
                                    <span>Print</span>
                                </button>
                                <hr>
                                <form class="block" action="{{ route('logout') }}" method="POST">
                                    {{ csrf_field() }}
                                    <button type="submit" class="text-left flex gap-2 items-center text-red-500">
                                        <i class="fa fa-key"></i>
                                        <span>Log Out</span>
                                    </button>
                                </form>
                            </ul>
                        </div>
                    </div>
                    @endif
                </div>
            </header>
            <main class="p-2 md:p-4 w-full print:block bg-gray-100">

                @if ($errors->any())
                <div class="w-full bg-red-200 p-4 text-red-600 mb-4 flex justify-between items-start gap-2 lg:gap-4">
                    <div onclick="this.parentElement.classList.add('hidden')"
                        class="-mt-1 grow-0 text-2xl cursor-pointer">&times;</div>
                    <ul class="grow text-left pt-1">
                        @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
                @endif

                @if (Session::has('message'))
                <div class="px-3 py-2 rounded bg-cyan-100 text-cyan-600 mb-4">
                    {{ Session::get('message') }}
                </div>
                @endif

                {{ $slot }}
            </main>
        </div>
    </div>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
        // init (safe-guarded)

            document.getElementById("navigationSearch").addEventListener("keyup", function() {
                const value = this.value.trim().toLowerCase();

                function searchItem(selectors, reference) {
                    Object.values(document.querySelectorAll(selectors)).forEach((item) => {
                        if (item.textContent.toLowerCase().indexOf(value) > reference) {
                            return item.classList.remove('hidden');
                        }
                        return item.classList.add('hidden');
                    })
                }

                searchItem("#navigationMenu li", -1);

                searchItem("#navigationMenu ul", 1);
            });
            // expose for inline onclick, e.g. onclick="submenuToggle(this)"
            window.submenuToggle = function (parent) {
                const wrapper = parent?.nextElementSibling;
                if (wrapper) wrapper.classList.toggle('hidden'); // uses your Tailwind "hidden"
            };

            // $('.sub-menu').find('.active').parent().parent().addClass('active');
            document.querySelectorAll('.sub-menu .active').forEach((el) => {
                const gp = el.parentElement?.parentElement;
                if (gp) gp.classList.add('active');
            });

            // $("#myInput").on("keyup", ...)
            const myInput = document.getElementById('myInput');
            if (myInput) {
                myInput.addEventListener('keyup', function (e) {
                const value = e.target.value.toLowerCase();

                // $("#myTable li").filter(...).toggle(...)
                document.querySelectorAll('#myTable li').forEach((li) => {
                    const show = li.textContent.toLowerCase().indexOf(value) > -1;
                    li.style.display = show ? '' : 'none';
                });

                // $("#myTable ul").filter(...).toggle(...)
                document.querySelectorAll('#myTable ul').forEach((ul) => {
                    const show = ul.textContent.toLowerCase().indexOf(value) > 1; // keep original logic
                    ul.style.display = show ? '' : 'none';
                });
                });
            }
        });
    </script>

</body>

</html>