<?php

declare(strict_types=1);

namespace App\Livewire\Analytics;

use App\Models\DataSource;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Analytics data sources')]
final class DataSourceIndex extends Component
{
    public function mount(): void
    {
        $this->authorize('viewAny', DataSource::class);
    }

    public function render(): View
    {
        return view('livewire.analytics.data-source-index', [
            'dataSources' => $this->dataSources(),
        ]);
    }

    /**
     * @return Collection<int, DataSource>
     */
    private function dataSources(): Collection
    {
        /** @var string $organizationId */
        $organizationId = Auth::user()->organization_id;

        return DataSource::query()
            ->where('organization_id', $organizationId)
            ->orderByDesc('created_at')
            ->get();
    }
}
