<?php

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

require __DIR__.'/settings.php';
