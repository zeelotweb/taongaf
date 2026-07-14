<x-layouts::app :title="$title ??  'Create-Books'">



    {{-- Navigation --}}




    {{-- Footer --}}
    <footer class="border-t border-zinc-100 dark:border-zinc-800 mt-24">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
            <div class="flex flex-col md:flex-row items-center justify-between gap-4">
                <p class="text-sm font-medium text-zinc-900 dark:text-white">taongaf</p>
                <div class="flex gap-6">
                    <a href="{{ route('editorials') }}" class="text-sm text-zinc-400 hover:text-zinc-600">Editorials</a>
                    <a href="{{ route('books') }}" class="text-sm text-zinc-400 hover:text-zinc-600">Books</a>
                    <a href="#" class="text-sm text-zinc-400 hover:text-zinc-600">Community</a>
                    <a href="#" class="text-sm text-zinc-400 hover:text-zinc-600">About</a>
                </div>
                <p class="text-xs text-zinc-400">© {{ date('Y') }} Taongaf. All rights reserved.</p>
            </div>
        </div>
    </footer>




</x-layouts::app>


