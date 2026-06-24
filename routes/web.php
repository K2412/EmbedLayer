<?php

use App\Http\Controllers\Embed\ChartQueryController;
use App\Http\Controllers\Embed\DashboardController;
use App\Livewire\Analytics\CreateDataSource;
use App\Livewire\Analytics\DataSourceIndex;
use App\Livewire\Analytics\DataSourceShow;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::view('dashboard', 'dashboard')->name('dashboard');

    Route::get('analytics/data-sources', DataSourceIndex::class)
        ->name('analytics.data-sources.index');
    Route::get('analytics/data-sources/create', CreateDataSource::class)
        ->name('analytics.data-sources.create');
    Route::get('analytics/data-sources/{dataSource}', DataSourceShow::class)
        ->name('analytics.data-sources.show');
});

// Public embed iframe entry — the runtime fetches its data via the JSON API
// endpoints below, which are origin-guarded.
Route::get('embed/dashboards/{embedId}', [DashboardController::class, 'iframe'])
    ->name('embed.dashboards.iframe');

// JSON endpoints consumed by the embedded runtime (Plan §12).
Route::middleware('embed.origin')->prefix('api/embed')->group(function () {
    Route::get('dashboards/{embedId}', [DashboardController::class, 'show'])
        ->name('embed.api.dashboards.show');

    Route::post('charts/{chartId}/query', ChartQueryController::class)
        ->name('embed.api.charts.query');
});

require __DIR__.'/settings.php';
