<?php

namespace App\Http\Controllers;

use App\Models\Book;
use App\Models\Chapter;
use App\Models\Editorial;
use Illuminate\Http\Request;
//use App\Policies\EditorialPolicy;
//use App\Policies\BookPolicy;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;


class PublishController extends Controller
{
    Use AuthorizesRequests;

    public function editorialsIndex()
    {
        return view('publish.editorials.index');
    }

    public function editorialsCreate()
    {
        return view('publish.editorials.create');
    }

    public function editorialsEdit(Editorial $editorial)
    { 
        $this->authorize('update', $editorial);
        return view('publish.editorials.edit', compact('editorial'));
    }

    public function booksIndex()
    {
        return view('publish.books.index');
    }

    public function booksCreate()
    {
        return view('publish.books.create');
    }

    public function booksEdit(Book $book)
    {
        $this->authorize('update', $book);
        return view('publish.books.edit', compact('book'));
    }

    public function chaptersCreate(Book $book)
    {
        $this->authorize('update', $book);
        return view('publish.books.chapters.create', compact('book'));
    }

    public function chaptersEdit(Book $book, Chapter $chapter)
    {
        $this->authorize('update', $book);
        return view('publish.books.chapters.edit', compact('book', 'chapter'));
    }
}