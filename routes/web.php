<?php

use App\Http\Controllers\Admin\MediaController as AdminMediaController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\Admin\ArticleController as AdminArticleController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\ArticleController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PageController;
use App\Http\Controllers\Admin\PageController as AdminPageController;
use App\Http\Controllers\Admin\PageBlockController as AdminPageBlockController;
use App\Http\Controllers\SliderController;
use App\Http\Controllers\Admin\SliderController as AdminSliderController;
use App\Http\Controllers\Admin\NewsTickerController as AdminNewsTickerController;
use App\Http\Controllers\Admin\MenuController as AdminMenuController;
use App\Http\Controllers\Admin\SiteSettingController;
use App\Http\Controllers\Admin\CategoryController as AdminCategoryController;
use App\Http\Controllers\PdfDocumentController;
use App\Http\Controllers\Admin\PdfCategoryController as AdminPdfCategoryController;
use App\Http\Controllers\Admin\PdfDocumentController as AdminPdfDocumentController;

Route::get('/', function () {
    return view('welcome');
});
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
Route::get('/sitemap.xml', [\App\Http\Controllers\SitemapController::class, 'index'])->name('sitemap');
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
    Route::resource('admin/articles', AdminArticleController::class)->names('admin.articles');
    Route::resource('admin/pages', AdminPageController::class)->names('admin.pages');
    Route::prefix('admin/pages/{page}/blocks')->name('admin.pages.blocks.')->group(function () {
        Route::get('/', [AdminPageBlockController::class, 'index'])->name('index');
        Route::get('/create/{type}', [AdminPageBlockController::class, 'create'])->name('create');
        Route::post('/', [AdminPageBlockController::class, 'store'])->name('store');
        Route::get('/{blockId}/edit', [AdminPageBlockController::class, 'edit'])->name('edit');
        Route::put('/{blockId}', [AdminPageBlockController::class, 'update'])->name('update');
        Route::delete('/{blockId}', [AdminPageBlockController::class, 'destroy'])->name('destroy');
        Route::post('/reorder', [AdminPageBlockController::class, 'reorder'])->name('reorder');

        Route::prefix('{blockId}/columns/{slotIndex}')->name('columns.')->group(function () {
            Route::get('/create/{type}', [AdminPageBlockController::class, 'createChild'])->name('create');
            Route::post('/', [AdminPageBlockController::class, 'storeChild'])->name('store');
            Route::get('/{childId}/edit', [AdminPageBlockController::class, 'editChild'])->name('edit');
            Route::put('/{childId}', [AdminPageBlockController::class, 'updateChild'])->name('update');
            Route::delete('/{childId}', [AdminPageBlockController::class, 'destroyChild'])->name('destroy');
        });

        Route::prefix('{blockId}/items')->name('items.')->group(function () {
            Route::post('/', [AdminPageBlockController::class, 'storeAccordionItem'])->name('store');
            Route::get('/{itemId}/edit', [AdminPageBlockController::class, 'editAccordionItem'])->name('edit');
            Route::put('/{itemId}', [AdminPageBlockController::class, 'updateAccordionItem'])->name('update');
            Route::delete('/{itemId}', [AdminPageBlockController::class, 'destroyAccordionItem'])->name('destroy');
            Route::post('/reorder', [AdminPageBlockController::class, 'reorderAccordionItems'])->name('reorder');

            Route::prefix('{itemId}/content')->name('content.')->group(function () {
                Route::get('/create/{type}', [AdminPageBlockController::class, 'createItemContent'])->name('create');
                Route::post('/', [AdminPageBlockController::class, 'storeItemContent'])->name('store');
                Route::get('/{contentId}/edit', [AdminPageBlockController::class, 'editItemContent'])->name('edit');
                Route::put('/{contentId}', [AdminPageBlockController::class, 'updateItemContent'])->name('update');
                Route::delete('/{contentId}', [AdminPageBlockController::class, 'destroyItemContent'])->name('destroy');
            });
        });
    });
    Route::resource('admin/sliders', AdminSliderController::class)->names('admin.sliders');
    Route::resource('admin/news-tickers', AdminNewsTickerController::class)->names('admin.news-tickers');
    Route::get('admin/media', [AdminMediaController::class, 'index'])->name('admin.media.index');
    Route::get('admin/media/picker', [AdminMediaController::class, 'picker'])->name('admin.media.picker');
    Route::post('admin/media/reorder', [\App\Http\Controllers\Admin\MediaOrderController::class, 'update'])->name('admin.media.reorder');
});

// Accès restreint : Super Admin uniquement (structure du site, réglages)
Route::middleware(['auth', 'role:Super Admin'])->group(function () {
    Route::resource('admin/menus', AdminMenuController::class)->names('admin.menus');
    Route::resource('admin/categories', AdminCategoryController::class)->names('admin.categories');
    Route::resource('admin/pdf-categories', AdminPdfCategoryController::class)->names('admin.pdf-categories');
    Route::resource('admin/pdf-documents', AdminPdfDocumentController::class)->names('admin.pdf-documents');
    Route::get('admin/settings', [SiteSettingController::class, 'edit'])->name('admin.settings.edit');
    Route::put('admin/settings', [SiteSettingController::class, 'update'])->name('admin.settings.update');
    Route::resource('admin/users', \App\Http\Controllers\Admin\UserController::class)->names('admin.users');
    Route::patch('admin/users/{user}/reset-password', [\App\Http\Controllers\Admin\UserController::class, 'resetPassword'])
        ->name('admin.users.reset-password');
});

require __DIR__.'/auth.php';