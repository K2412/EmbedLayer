<div class="flex h-full w-full flex-1 flex-col gap-6 rounded-xl">
    <div class="flex items-center justify-between">
        <div>
            <flux:heading size="xl">{{ __('Data sources') }}</flux:heading>
            <flux:text class="mt-1">{{ __('Connect Postgres, MySQL, BigQuery, and Snowflake warehouses to power your analytics.') }}</flux:text>
        </div>

        <flux:button
            variant="primary"
            icon="plus"
            :href="route('analytics.data-sources.create')"
            wire:navigate
            data-test="new-data-source-button"
        >
            {{ __('New data source') }}
        </flux:button>
    </div>

    @if ($dataSources->isEmpty())
        <div
            class="flex flex-col items-center justify-center gap-3 rounded-xl border border-dashed border-neutral-300 px-6 py-16 text-center dark:border-neutral-700"
            data-test="data-sources-empty-state"
        >
            <flux:heading size="lg">{{ __('No data sources yet') }}</flux:heading>
            <flux:text>{{ __('Add your first warehouse to start exploring data.') }}</flux:text>
            <flux:button
                variant="primary"
                icon="plus"
                :href="route('analytics.data-sources.create')"
                wire:navigate
            >
                {{ __('New data source') }}
            </flux:button>
        </div>
    @else
        <div class="overflow-hidden rounded-xl border border-neutral-200 dark:border-neutral-700">
            <table class="min-w-full divide-y divide-neutral-200 dark:divide-neutral-700">
                <thead class="bg-neutral-50 dark:bg-neutral-900">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-neutral-500 dark:text-neutral-400">{{ __('Name') }}</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-neutral-500 dark:text-neutral-400">{{ __('Driver') }}</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-neutral-500 dark:text-neutral-400">{{ __('Last tested') }}</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-neutral-500 dark:text-neutral-400">{{ __('Last introspected') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-neutral-200 bg-white dark:divide-neutral-700 dark:bg-neutral-800">
                    @foreach ($dataSources as $dataSource)
                        <tr
                            class="cursor-pointer hover:bg-neutral-50 dark:hover:bg-neutral-900"
                            wire:click="$redirect('{{ route('analytics.data-sources.show', $dataSource) }}', true)"
                            data-test="data-source-row-{{ $dataSource->id }}"
                        >
                            <td class="px-4 py-3 text-sm font-medium text-neutral-900 dark:text-neutral-100">{{ $dataSource->name }}</td>
                            <td class="px-4 py-3 text-sm text-neutral-600 dark:text-neutral-300">
                                <flux:badge size="sm">{{ $dataSource->driver }}</flux:badge>
                            </td>
                            <td class="px-4 py-3 text-sm text-neutral-600 dark:text-neutral-300">
                                {{ $dataSource->last_tested_at?->diffForHumans() ?? __('Never') }}
                            </td>
                            <td class="px-4 py-3 text-sm text-neutral-600 dark:text-neutral-300">
                                {{ $dataSource->last_introspected_at?->diffForHumans() ?? __('Never') }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
