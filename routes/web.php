<?php

use App\Http\Controllers\Admin\AccordionItemContentController as AdminAccordionItemContentController;
use App\Http\Controllers\Admin\AccordionItemController as AdminAccordionItemController;
use App\Http\Controllers\Admin\ArticleController as AdminArticleController;
use App\Http\Controllers\Admin\CategoryController as AdminCategoryController;
use App\Http\Controllers\Admin\ColumnChildBlockController as AdminColumnChildBlockController;
use App\Http\Controllers\Admin\ContactMessageController as AdminContactMessageController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\MediaController as AdminMediaController;
use App\Http\Controllers\Admin\MediaOrderController;
use App\Http\Controllers\Admin\MenuController as AdminMenuController;
use App\Http\Controllers\Admin\NewsTickerController as AdminNewsTickerController;
use App\Http\Controllers\Admin\PageBlockController as AdminPageBlockController;
use App\Http\Controllers\Admin\PageBuilderController as AdminPageBuilderController;
use App\Http\Controllers\Admin\PageController as AdminPageController;
use App\Http\Controllers\Admin\PdfCategoryController as AdminPdfCategoryController;
use App\Http\Controllers\Admin\PdfDocumentController as AdminPdfDocumentController;
use App\Http\Controllers\Admin\SiteSettingController;
use App\Http\Controllers\Admin\SliderController as AdminSliderController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\ArticleController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\PdfDocumentController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RobotsController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\SitemapController;
use App\Http\Controllers\SliderController;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');
Route::get('/recherche', [SearchController::class, 'index'])->name('search');
Route::get('/blog', [ArticleController::class, 'index'])->name('blog.index');
Route::get('/blog/{article:slug}', [ArticleController::class, 'show'])
    ->middleware('track.view:article')
    ->name('blog.show');
Route::get('/blog/categorie/{category:slug}', [ArticleController::class, 'byCategory'])->name('blog.category');
Route::get('/pages', [PageController::class, 'index'])->name('pages.index');
Route::get('/pages/{page:slug}', [PageController::class, 'show'])->name('pages.show');
Route::get('/documents', [PdfDocumentController::class, 'index'])->name('documents.index');
Route::get('/documents/categorie/{pdfCategory:slug}', [PdfDocumentController::class, 'byCategory'])->name('documents.category');
Route::get('/documents/{pdfDocument:slug}', [PdfDocumentController::class, 'show'])->name('documents.show');
Route::get('/sliders', [SliderController::class, 'index'])->name('sliders.index');
Route::get('/contact', [ContactController::class, 'show'])->name('contact.show');
Route::post('/contact', [ContactController::class, 'store'])->middleware('throttle:5,1')->name('contact.store');
Route::get('/sitemap.xml', [SitemapController::class, 'index'])->name('sitemap');
Route::get('/robots.txt', RobotsController::class)->name('robots');
Route::get('/dashboard', function () {
    return redirect()->route('admin.dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// Accès partagé : Super Admin ET Publication (contenu éditorial)
Route::middleware(['auth', 'role:Super Admin|Publication'])->group(function () {
    Route::get('/admin', [DashboardController::class, 'index'])->name('admin.dashboard');
    Route::resource('admin/articles', AdminArticleController::class)->names('admin.articles')->middlewareFor('destroy', 'confirm.delete');
    Route::resource('admin/pages', AdminPageController::class)->names('admin.pages')->middlewareFor('destroy', 'confirm.delete');
    Route::prefix('admin/pages/{page}/blocks')->name('admin.pages.blocks.')->group(function () {
        Route::get('/', [AdminPageBlockController::class, 'index'])->name('index');
        Route::get('/create/{type}', [AdminPageBlockController::class, 'create'])->name('create');
        Route::post('/', [AdminPageBlockController::class, 'store'])->name('store');
        Route::get('/{blockId}/edit', [AdminPageBlockController::class, 'edit'])->name('edit');
        Route::put('/{blockId}', [AdminPageBlockController::class, 'update'])->name('update');
        Route::delete('/{blockId}', [AdminPageBlockController::class, 'destroy'])->name('destroy');
        Route::post('/reorder', [AdminPageBlockController::class, 'reorder'])->name('reorder');

        Route::prefix('{blockId}/columns/{slotIndex}')->name('columns.')->group(function () {
            Route::get('/create/{type}', [AdminColumnChildBlockController::class, 'create'])->name('create');
            Route::post('/', [AdminColumnChildBlockController::class, 'store'])->name('store');
            Route::get('/{childId}/edit', [AdminColumnChildBlockController::class, 'edit'])->name('edit');
            Route::put('/{childId}', [AdminColumnChildBlockController::class, 'update'])->name('update');
            Route::delete('/{childId}', [AdminColumnChildBlockController::class, 'destroy'])->name('destroy');
        });

        Route::prefix('{blockId}/items')->name('items.')->group(function () {
            Route::post('/', [AdminAccordionItemController::class, 'store'])->name('store');
            Route::get('/{itemId}/edit', [AdminAccordionItemController::class, 'edit'])->name('edit');
            Route::put('/{itemId}', [AdminAccordionItemController::class, 'update'])->name('update');
            Route::delete('/{itemId}', [AdminAccordionItemController::class, 'destroy'])->name('destroy');
            Route::post('/reorder', [AdminAccordionItemController::class, 'reorder'])->name('reorder');

            Route::prefix('{itemId}/content')->name('content.')->group(function () {
                Route::get('/create/{type}', [AdminAccordionItemContentController::class, 'create'])->name('create');
                Route::post('/', [AdminAccordionItemContentController::class, 'store'])->name('store');
                Route::get('/{contentId}/edit', [AdminAccordionItemContentController::class, 'edit'])->name('edit');
                Route::put('/{contentId}', [AdminAccordionItemContentController::class, 'update'])->name('update');
                Route::delete('/{contentId}', [AdminAccordionItemContentController::class, 'destroy'])->name('destroy');
            });
        });
    });
    Route::post('admin/pages/{page}/builder', [AdminPageBuilderController::class, 'save'])->name('admin.pages.builder.save');
    Route::resource('admin/sliders', AdminSliderController::class)->names('admin.sliders');
    Route::resource('admin/news-tickers', AdminNewsTickerController::class)->names('admin.news-tickers');
    Route::get('admin/media', [AdminMediaController::class, 'index'])->name('admin.media.index');
    Route::get('admin/media/picker', [AdminMediaController::class, 'picker'])->name('admin.media.picker');
    Route::post('admin/media/reorder', [MediaOrderController::class, 'update'])->name('admin.media.reorder');
});

// Accès restreint : Super Admin uniquement (structure du site, réglages)
Route::middleware(['auth', 'role:Super Admin'])->group(function () {
    Route::resource('admin/menus', AdminMenuController::class)->names('admin.menus');
    Route::resource('admin/categories', AdminCategoryController::class)->names('admin.categories');
    Route::resource('admin/pdf-categories', AdminPdfCategoryController::class)->names('admin.pdf-categories');
    Route::resource('admin/pdf-documents', AdminPdfDocumentController::class)->names('admin.pdf-documents');
    Route::get('admin/settings', [SiteSettingController::class, 'edit'])->name('admin.settings.edit');
    Route::put('admin/settings', [SiteSettingController::class, 'update'])->name('admin.settings.update');
    Route::resource('admin/messages', AdminContactMessageController::class)->only(['index', 'show'])->names('admin.messages');
    Route::resource('admin/users', UserController::class)->names('admin.users');
    Route::patch('admin/users/{user}/reset-password', [UserController::class, 'resetPassword'])
        ->name('admin.users.reset-password');
});

require __DIR__.'/auth.php';
