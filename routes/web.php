<?php

use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;
use App\Http\Controllers\Admin\MediaUploadController;
use App\Http\Controllers\Upload\ChunkUploadController;
use App\Http\Controllers\Admin\AdminController;

use App\Http\Controllers\PublicController;
use App\Http\Controllers\TokenController;
use App\Http\Controllers\PublishController;
use App\Http\Controllers\StudioController;
use App\Http\Controllers\HustleController;
use App\Http\Controllers\MessagingController;
use App\Http\Controllers\ProfileController;








Route::view('/', 'welcome')->name('home');





// Admin routes
Route::middleware(['auth', 'admin', 'verified'])->prefix('admin')->name('admin.')->group(function () {

Route::view('dashboard', 'dashboard')->name('dashboard');
    Route::get('/dash', [AdminController::class, 'index'])->name('panel');

    // Editorials
    Route::get('/editorials', [AdminController::class, 'editorialsIndex'])->name('editorials.index');
    Route::get('/editorials/create', [AdminController::class, 'editorialsCreate'])->name('editorials.create');
    Route::get('/editorials/{editorial}/edit', [AdminController::class, 'editorialsEdit'])->name('editorials.edit');



// Books
Route::get('/books', [AdminController::class, 'booksIndex'])->name('books.index');
Route::get('/books/create', [AdminController::class, 'booksCreate'])->name('books.create');
Route::get('/books/{book}/edit', [AdminController::class, 'booksEdit'])->name('books.edit');

// Chapters
Route::get('/books/{book}/chapters/create', [AdminController::class, 'chaptersCreate'])->name('chapters.create');
Route::get('/books/{book}/chapters/{chapter}/edit', [AdminController::class, 'chaptersEdit'])->name('chapters.edit');








});





// Upload routes — auth protected

    Route::post('/upload/chunk', [ChunkUploadController::class, 'chunk'])->name('upload.chunk');
    Route::post('/upload/complete', [ChunkUploadController::class, 'complete'])->name('upload.complete');
    Route::delete('/upload/revert', [ChunkUploadController::class, 'revert'])->name('upload.revert');










Route::middleware(['auth', 'verified'])->group(function () {
    Route::view('dashboard', 'dashboard')->name('dashboard');




Route::get('/profile/{user}', [ProfileController::class, 'show'])->name('profile.show');

// Public routes
Route::get('/land', [PublicController::class, 'landing'])->name('home.land');


Route::get('/about', [PublicController::class, 'about'])->name('about');

Route::get('/community', [PublicController::class, 'communities'])->name('community');
Route::get('/editorials', [PublicController::class, 'editorials'])->name('editorials');
Route::get('/editorials/{slug}', [PublicController::class, 'editorial'])->name('editorial');
Route::get('/books', [PublicController::class, 'books'])->name('books');
Route::get('/books/{slug}', [PublicController::class, 'book'])->name('book');
Route::get('/books/{slug}/{chapterSlug}', [PublicController::class, 'chapter'])->name('chapter');


    Route::get('/bookmarks', function () {
        return view('bookmarks.index');
    })->name('bookmarks');


Route::get('/search', function () {
    return view('search.index');
})->name('search');

});
















// Publisher routes — any auth user
Route::middleware(['auth'])->prefix('publish')->name('publish.')->group(function () {
    // Editorials
    Route::get('/editorials', [PublishController::class, 'editorialsIndex'])->name('editorials.index');
    Route::get('/editorials/create', [PublishController::class, 'editorialsCreate'])->name('editorials.create');
    Route::get('/editorials/{editorial}/edit', [PublishController::class, 'editorialsEdit'])->name('editorials.edit');

    // Books
    Route::get('/books', [PublishController::class, 'booksIndex'])->name('books.index');
    Route::get('/books/create', [PublishController::class, 'booksCreate'])->name('books.create');
    Route::get('/books/{book}/edit', [PublishController::class, 'booksEdit'])->name('books.edit');

    // Chapters
    Route::get('/books/{book}/chapters/create', [PublishController::class, 'chaptersCreate'])->name('chapters.create');
    Route::get('/books/{book}/chapters/{chapter}/edit', [PublishController::class, 'chaptersEdit'])->name('chapters.edit');
});







// Token routes — auth required
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/wallet', [TokenController::class, 'indexwallet'])->name('wallet.index');
    Route::get('/tokens', [TokenController::class, 'index'])->name('tokens.index');
    Route::post('/tokens/checkout', [TokenController::class, 'checkout'])->name('tokens.checkout');
    Route::get('/tokens/success', [TokenController::class, 'success'])->name('tokens.success');
});









Route::middleware(['auth'])->group(function () {
    Route::get('/hustle', [HustleController::class, 'index'])->name('hustle.index');
    Route::get('/hustle/{profileOwner}/promote', [HustleController::class, 'promote'])->name('hustle.promote');
});






// Studio subscription + success — auth only, no studio middleware
Route::middleware(['auth'])->group(function () {
    Route::get('/studio/subscription', [StudioController::class, 'subscription'])
        ->name('studio.subscription');
    Route::get('/studio/success', [StudioController::class, 'success'])
        ->name('studio.success');
    Route::get('/studio/invite/{token}', [StudioController::class, 'acceptInvite'])
        ->name('studio.invite');
});

// Studio routes — auth + studio middleware
Route::middleware(['auth', 'studio'])->prefix('studio')->name('studio.')->group(function () {
    Route::get('/', [StudioController::class, 'index'])->name('index');
    Route::get('/staff', [StudioController::class, 'staff'])->name('staff');
    Route::get('/community', [StudioController::class, 'community'])->name('community');
    Route::get('/analytics', [StudioController::class, 'analytics'])->name('analytics');
    Route::get('/commerce', [StudioController::class, 'commerce'])->name('commerce');
    Route::get('/surveys', [StudioController::class, 'surveys'])->name('surveys');
    Route::get('/surveys/create', [StudioController::class, 'surveyCreate'])->name('surveys.create');
    Route::get('/surveys/{survey}/edit', [StudioController::class, 'surveyEdit'])->name('surveys.edit');
});










Route::middleware(['auth'])->group(function () {
    Route::get('/messages', [MessagingController::class, 'inbox'])->name('messages.inbox');
    Route::get('/chat-rooms', [MessagingController::class, 'chatRooms'])->name('messages.chat-rooms');
});




// Stripe webhook — no auth, no CSRF
Route::post('/stripe/webhook', [TokenController::class, 'webhook'])
    ->name('stripe.webhook')
    ->withoutMiddleware(['web']);


require __DIR__.'/settings.php';











