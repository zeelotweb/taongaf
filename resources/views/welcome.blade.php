<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-white dark:bg-zinc-800">

        {{-- Navigation --}}
        <nav class="border-b border-zinc-100 dark:border-zinc-800 bg-white dark:bg-zinc-900 sticky top-0 z-50">
            <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex items-center justify-between h-16">

                    <a href="{{ route('home') }}" class="text-lg font-medium tracking-tight text-zinc-900 dark:text-white">
                        taongaf
                    </a>

                    <div class="hidden md:flex items-center gap-6">
                        <a href="{{ route('editorials') }}"
                           class="text-sm text-zinc-500 hover:text-zinc-900 dark:hover:text-white transition-colors">
                            Editorials
                        </a>
                        <a href="{{ route('books') }}"
                           class="text-sm text-zinc-500 hover:text-zinc-900 dark:hover:text-white transition-colors">
                            Books
                        </a>
                        <a href="#community"
                           class="text-sm text-zinc-500 hover:text-zinc-900 dark:hover:text-white transition-colors">
                            Community
                        </a>
                    </div>

                    <div class="flex items-center gap-3">
                        @auth
                            <a href="{{ route('dashboard') }}"
                               class="text-sm text-zinc-500 hover:text-zinc-900 dark:hover:text-white transition-colors">
                                Dashboard
                            </a>
                            @if(in_array(auth()->user()->role, ['superadmin', 'admin', 'staff']))
                                <a href="{{ route('admin.panel') }}"
                                   class="text-sm text-zinc-500 hover:text-zinc-900 dark:hover:text-white transition-colors">
                                    Admin
                                </a>
                            @endif
                        @else
                            <a href="{{ route('login') }}"
                               class="text-sm text-zinc-500 hover:text-zinc-900 dark:hover:text-white transition-colors">
                                Sign in
                            </a>
                            <a href="{{ route('register') }}"
                               class="text-sm px-4 py-2 bg-zinc-900 dark:bg-white text-white dark:text-zinc-900 rounded-lg hover:bg-zinc-700 transition-colors">
                                Get started
                            </a>
                        @endauth
                    </div>

                </div>
            </div>
        </nav>

        {{-- Landing content --}}
        <livewire:public.landing />

        {{-- Footer --}}
        <footer class="border-t border-zinc-100 dark:border-zinc-800 mt-24">
            <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
                <div class="flex flex-col md:flex-row items-center justify-between gap-4">
                    <p class="text-sm font-medium text-zinc-900 dark:text-white">taongaf</p>
                    <div class="flex gap-6">
                        <a href="{{ route('editorials') }}" class="text-sm text-zinc-400 hover:text-zinc-600">Editorials</a>
                        <a href="{{ route('books') }}" class="text-sm text-zinc-400 hover:text-zinc-600">Books</a>
                        <a href="#community" class="text-sm text-zinc-400 hover:text-zinc-600">Community</a>
                        <a href="#" class="text-sm text-zinc-400 hover:text-zinc-600">About</a>
                    </div>
                    <p class="text-xs text-zinc-400">© {{ date('Y') }} Taongaf. All rights reserved.</p>
                </div>
            </div>
        </footer>

        @fluxScripts
    </body>
</html>