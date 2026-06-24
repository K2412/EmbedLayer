<?php

declare(strict_types=1);

namespace App\Livewire\Analytics;

use App\Analytics\Actions\DataSources\CreateDataSource as CreateDataSourceAction;
use App\Models\DataSource;
use Flux\Flux;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\Features\SupportRedirects\Redirector;

#[Layout('layouts.app')]
#[Title('New data source')]
final class CreateDataSource extends Component
{
    /** @var list<string> */
    public const array DRIVERS = ['postgres', 'mysql', 'bigquery', 'snowflake'];

    public string $name = '';

    public string $driver = 'postgres';

    public string $host = '';

    public ?string $port = '5432';

    public string $database = '';

    public string $username = '';

    public string $password = '';

    public string $project_id = '';

    public string $dataset = '';

    public string $service_account_json = '';

    public string $account = '';

    public string $user = '';

    public string $warehouse = '';

    public string $schema = '';

    public string $role = '';

    public function mount(): void
    {
        $this->authorize('create', DataSource::class);
    }

    public function updatedDriver(string $value): void
    {
        $this->port = match ($value) {
            'postgres' => '5432',
            'mysql' => '3306',
            default => null,
        };
    }

    public function save(CreateDataSourceAction $action): Redirector
    {
        $this->authorize('create', DataSource::class);

        $validated = $this->validate($this->rulesForDriver($this->driver));

        $organization = Auth::user()?->organization;

        if ($organization === null) {
            abort(403);
        }

        $dataSource = $action->handle(
            organization: $organization,
            name: $validated['name'],
            driver: $validated['driver'],
            config: $this->buildConfig($this->driver, $validated),
        );

        Flux::toast(variant: 'success', text: __('Data source created.'));

        return redirect()->route('analytics.data-sources.show', $dataSource);
    }

    public function render(): View
    {
        return view('livewire.analytics.create-data-source');
    }

    /**
     * @return array<string, string>
     */
    private function rulesForDriver(string $driver): array
    {
        $base = [
            'name' => 'required|string|max:255',
            'driver' => 'required|string|in:postgres,mysql,bigquery,snowflake',
        ];

        return match ($driver) {
            'postgres', 'mysql' => $base + [
                'host' => 'required|string|max:255',
                'port' => 'required|integer|min:1|max:65535',
                'database' => 'required|string|max:255',
                'username' => 'required|string|max:255',
                'password' => 'required|string|max:1024',
            ],
            'bigquery' => $base + [
                'project_id' => 'required|string|max:255',
                'dataset' => 'required|string|max:255',
                'service_account_json' => 'required|string',
            ],
            'snowflake' => $base + [
                'account' => 'required|string|max:255',
                'user' => 'required|string|max:255',
                'password' => 'required|string|max:1024',
                'warehouse' => 'required|string|max:255',
                'database' => 'required|string|max:255',
                'schema' => 'required|string|max:255',
                'role' => 'required|string|max:255',
            ],
            default => $base,
        };
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    private function buildConfig(string $driver, array $validated): array
    {
        return match ($driver) {
            'postgres', 'mysql' => [
                'host' => $validated['host'],
                'port' => (int) $validated['port'],
                'database' => $validated['database'],
                'username' => $validated['username'],
                'password' => $validated['password'],
            ],
            'bigquery' => [
                'project_id' => $validated['project_id'],
                'dataset' => $validated['dataset'],
                'service_account_json' => $validated['service_account_json'],
            ],
            'snowflake' => [
                'account' => $validated['account'],
                'user' => $validated['user'],
                'password' => $validated['password'],
                'warehouse' => $validated['warehouse'],
                'database' => $validated['database'],
                'schema' => $validated['schema'],
                'role' => $validated['role'],
            ],
            default => [],
        };
    }
}
