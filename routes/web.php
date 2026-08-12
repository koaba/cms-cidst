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
use App\Http\Controllers\SliderController;
use App\Http\Controllers\Admin\SliderController as AdminSliderController;
use App\Http\Controllers\Admin\NewsTickerController as AdminNewsTickerController;
use App\Http\Controllers\Admin\MenuController as AdminMenuController;
use App\Http\Controllers\Admin\SiteSettingController;
use App\Http\Controllers\Admin\CategoryController as AdminCategoryController;

Route::get('/', function () {
    return view('welcome');
});
Route::get('/recherche', [SearchController::class, 'index'])->name('search');
Route::get('/blog', [ArticleController::class, 'index'])->name('blog.index');
Route::get('/blog/{article:slug}', [ArticleController::class, 'show'])->name('blog.show');
Route::get('/blog/categorie/{category:slug}', [ArticleController::class, 'byCategory'])->name('blog.category');
Route::get('/pages', [PageController::class, 'index'])->name('pages.index');
Route::get('/pages/{page:slug}', [PageController::class, 'show'])->name('pages.show');
Route::get('/sliders', [SliderController::class, 'index'])->name('sliders.index');

Route::get('/dashboard', function () {
    return view('dashboard');
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
    Route::resource('admin/sliders', AdminSliderController::class)->names('admin.sliders');
    Route::resource('admin/news-tickers', AdminNewsTickerController::class)->names('admin.news-tickers');
    Route::get('admin/media', [AdminMediaController::class, 'index'])->name('admin.media.index');
    Route::get('admin/media/picker', [AdminMediaController::class, 'picker'])->name('admin.media.picker');
});

// Accès restreint : Super Admin uniquement (structure du site, réglages)
Route::middleware(['auth', 'role:Super Admin'])->group(function () {
    Route::resource('admin/menus', AdminMenuController::class)->names('admin.menus');
    Route::resource('admin/categories', AdminCategoryController::class)->names('admin.categories');
    Route::get('admin/settings', [SiteSettingController::class, 'edit'])->name('admin.settings.edit');
    Route::put('admin/settings', [SiteSettingController::class, 'update'])->name('admin.settings.update');
    Route::resource('admin/users', \App\Http\Controllers\Admin\UserController::class)->names('admin.users');
});

require __DIR__.'/auth.php';