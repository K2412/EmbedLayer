<?php

declare(strict_types=1);

namespace App\Livewire\Analytics;

use App\Analytics\Actions\DataSources\IntrospectDataSource;
use App\Analytics\Actions\DataSources\TestDataSourceConnection;
use App\Models\DataSource;
use Flux\Flux;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Throwable;

#[Layout('layouts.app')]
#[Title('Data source')]
final class DataSourceShow extends Component
{
    public DataSource $dataSource;

    public function mount(DataSource $dataSource): void
    {
        $this->authorize('view', $dataSource);

        $this->dataSource = $dataSource;
    }

    public function testConnection(TestDataSourceConnection $action): void
    {
        $this->authorize('update', $this->dataSource);

        $result = $action->handle($this->dataSource);

        $this->dataSource->refresh();

        if ($result->success) {
            Flux::toast(variant: 'success', text: __('Connection succeeded.'));

            return;
        }

        Flux::toast(
            variant: 'danger',
            text: $result->errorMessage ?? __('Connection failed.'),
        );
    }

    public function introspect(IntrospectDataSource $action): void
    {
        $this->authorize('update', $this->dataSource);

        try {
            $action->handle($this->dataSource);
        } catch (Throwable $e) {
            Flux::toast(variant: 'danger', text: __('Schema introspection failed: :message', [
                'message' => $e->getMessage(),
            ]));

            return;
        }

        $this->dataSource->refresh();

        Flux::toast(variant: 'success', text: __('Schema updated.'));
    }

    public function render(): View
    {
        return view('livewire.analytics.data-source-show', [
            'schemaTables' => $this->schemaTables(),
        ]);
    }

    /**
     * @return list<array{schema: string, name: string, columns: list<array{name: string, data_type: string, nullable: bool}>}>
     */
    private function schemaTables(): array
    {
        $schema = $this->dataSource->last_introspected_schema;

        if (! is_array($schema) || ! isset($schema['tables']) || ! is_array($schema['tables'])) {
            return [];
        }

        /** @var list<array{schema: string, name: string, columns: list<array{name: string, data_type: string, nullable: bool}>}> */
        return array_values($schema['tables']);
    }
}
