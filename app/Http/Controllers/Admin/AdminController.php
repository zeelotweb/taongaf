<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Editorial;
use App\Models\Book;
use App\Models\Chapter;
use Illuminate\Http\Request;

class AdminController extends Controller
{
    public function index()
    {
        $user = auth()->user();

        $stats = [
            'editorials_count' => Editorial::where('user_id', $user->id)->count(),
            'books_count'      => Book::where('user_id', $user->id)->count(),
            'published_count'  => Editorial::where('user_id', $user->id)->where('status', 'published')->count()
                + Book::where('user_id', $user->id)->where('status', 'published')->count(),
            'draft_count' => Editorial::where('user_id', $user->id)->where('status', 'draft')->count()
                + Book::where('user_id', $user->id)->where('status', 'draft')->count(),
        ];

        $recentEditorials = Editorial::where('user_id', $user->id)->latest()->take(5)->get();
        $recentBooks      = Book::where('user_id', $user->id)->latest()->take(5)->get();

        return view('admin', compact('stats', 'recentEditorials', 'recentBooks'));
    }

    public function editorialsIndex()
    {
        return view('admin.editorials.index');
    }

    public function editorialsCreate()
    {
        return view('admin.editorials.create');
    }

    public function editorialsEdit(Editorial $editorial)
    {
        return view('admin.editorials.edit', compact('editorial'));
    }

    public function booksIndex()
    {
        return view('admin.books.index');
    }

    public function booksCreate()
    {
        return view('admin.books.create');
    }

    public function booksEdit(Book $book)
    {
        return view('admin.books.edit', compact('book'));
    }


    public function chaptersCreate(Book $book)
{
    return view('admin.books.chapters.create', compact('book'));
}

public function chaptersEdit(Book $book, Chapter $chapter)
{
    return view('admin.books.chapters.edit', compact('book', 'chapter'));
}

}