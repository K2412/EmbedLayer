<div class="flex h-full w-full flex-1 flex-col gap-6 rounded-xl">
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <flux:text>
                <a
                    href="{{ route('analytics.data-sources.index') }}"
                    class="text-sm text-neutral-500 hover:text-neutral-700 dark:hover:text-neutral-300"
                    wire:navigate
                >
                    &larr; {{ __('Back to data sources') }}
                </a>
            </flux:text>
            <flux:heading size="xl" class="mt-2">{{ $dataSource->name }}</flux:heading>
            <div class="mt-2 flex items-center gap-2">
                <flux:badge size="sm">{{ $dataSource->driver }}</flux:badge>
                <flux:text class="text-sm">
                    {{ __('Last tested:') }}
                    <span data-test="last-tested-at">
                        {{ $dataSource->last_tested_at?->diffForHumans() ?? __('Never') }}
                    </span>
                </flux:text>
                <flux:text class="text-sm">
                    {{ __('Last introspected:') }}
                    <span data-test="last-introspected-at">
                        {{ $dataSource->last_introspected_at?->diffForHumans() ?? __('Never') }}
                    </span>
                </flux:text>
            </div>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <flux:button
                wire:click="testConnection"
                wire:loading.attr="disabled"
                wire:target="testConnection"
                icon="bolt"
                variant="filled"
                data-test="test-connection-button"
            >
                {{ __('Test connection') }}
            </flux:button>

            <flux:button
                wire:click="introspect"
                wire:loading.attr="disabled"
                wire:target="introspect"
                icon="arrow-path"
                variant="primary"
                data-test="introspect-button"
            >
                {{ __('Introspect schema') }}
            </flux:button>
        </div>
    </div>

    <div class="rounded-xl border border-neutral-200 p-6 dark:border-neutral-700">
        <div class="flex items-center justify-between">
            <flux:heading size="lg">{{ __('Schema') }}</flux:heading>
            @if (! empty($schemaTables))
                <flux:text class="text-sm">
                    {{ trans_choice('{0} no tables|{1} 1 table|[2,*] :count tables', count($schemaTables), ['count' => count($schemaTables)]) }}
                </flux:text>
            @endif
        </div>

        @if (empty($schemaTables))
            <div class="mt-6 flex flex-col items-center justify-center gap-2 rounded-lg border border-dashed border-neutral-300 px-4 py-10 text-center dark:border-neutral-700" data-test="schema-empty-state">
                <flux:text>{{ __('No schema cached yet. Click "Introspect schema" to fetch it.') }}</flux:text>
            </div>
        @else
            <div class="mt-4 flex flex-col gap-2" data-test="schema-tree">
                @php
                    $tablesBySchema = collect($schemaTables)->groupBy('schema');
                @endphp

                @foreach ($tablesBySchema as $schemaName => $tables)
                    <details class="rounded-lg border border-neutral-200 dark:border-neutral-700" open>
                        <summary class="flex cursor-pointer items-center gap-2 px-4 py-3 text-sm font-medium text-neutral-800 hover:bg-neutral-50 dark:text-neutral-100 dark:hover:bg-neutral-900">
                            <flux:icon.folder class="size-4 text-neutral-500" />
                            <span>{{ $schemaName }}</span>
                            <span class="text-xs text-neutral-500">({{ count($tables) }})</span>
                        </summary>

                        <div class="border-t border-neutral-200 dark:border-neutral-700">
                            @foreach ($tables as $table)
                                <details class="border-b border-neutral-100 last:border-b-0 dark:border-neutral-800">
                                    <summary class="flex cursor-pointer items-center gap-2 px-6 py-2 text-sm text-neutral-800 hover:bg-neutral-50 dark:text-neutral-100 dark:hover:bg-neutral-900">
                                        <flux:icon.table-cells class="size-4 text-neutral-500" />
                                        <span class="font-medium">{{ $table['name'] }}</span>
                                        <span class="text-xs text-neutral-500">({{ count($table['columns']) }} {{ __('columns') }})</span>
                                    </summary>

                                    <table class="w-full text-xs text-neutral-700 dark:text-neutral-300">
                                        <thead class="bg-neutral-50 dark:bg-neutral-900">
                                            <tr>
                                                <th class="px-8 py-2 text-left font-semibold uppercase tracking-wide text-neutral-500">{{ __('Column') }}</th>
                                                <th class="px-4 py-2 text-left font-semibold uppercase tracking-wide text-neutral-500">{{ __('Type') }}</th>
                                                <th class="px-4 py-2 text-left font-semibold uppercase tracking-wide text-neutral-500">{{ __('Nullable') }}</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($table['columns'] as $column)
                                                <tr class="border-t border-neutral-100 dark:border-neutral-800">
                                                    <td class="px-8 py-2 font-mono">{{ $column['name'] }}</td>
                                                    <td class="px-4 py-2">{{ $column['data_type'] }}</td>
                                                    <td class="px-4 py-2">{{ $column['nullable'] ? __('Yes') : __('No') }}</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </details>
                            @endforeach
                        </div>
                    </details>
                @endforeach
            </div>
        @endif
    </div>
</div>
