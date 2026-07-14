<?php

namespace App\Http\Controllers;

use App\Models\Editorial;
use App\Models\Book;
use Illuminate\Http\Request;

class PublicController extends Controller
{
    public function landing()
    {
        $featuredEditorials = Editorial::published()
            ->latest('published_at')
            ->take(4)
            ->get();

        $featuredBooks = Book::published()
            ->latest('published_at')
            ->take(3)
            ->get();

        return view('public.landing', compact(
            'featuredEditorials',
            'featuredBooks',
        ));
    }

public function editorials()
{
    return view('public.editorials');
}


public function communities()
{
    return view('public.community');
}
 

public function about()
{
    return view('public.about');
}
    public function editorial(string $slug)
    {
        $editorial = Editorial::published()
            ->where('slug', $slug)
            ->firstOrFail();

        $editorial->increment('views_count');

        $related = Editorial::published()
            ->where('id', '!=', $editorial->id)
            ->latest('published_at')
            ->take(3)
            ->get();

        return view('public.editorial', compact('editorial', 'related'));
    }

    public function books()
    {
      

        return view('public.books');
    }

public function book(string $slug)
{
    $book = Book::published()
        ->where('slug', $slug)
        ->firstOrFail();

    $book->increment('views_count');

    return view('public.book', compact('book'));
}

public function chapter(string $slug, string $chapterSlug)
{
    $book = Book::published()
        ->where('slug', $slug)
        ->firstOrFail();

    $chapter = $book->chapters()
        ->published()
        ->where('slug', $chapterSlug)
        ->firstOrFail();

    $chapter->increment('views_count');

    return view('public.chapter', compact('book', 'chapter'));
}
}