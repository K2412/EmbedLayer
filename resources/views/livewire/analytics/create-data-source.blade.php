<div class="flex h-full w-full flex-1 flex-col gap-6 rounded-xl">
    <div class="flex items-center justify-between">
        <div>
            <flux:heading size="xl">{{ __('New data source') }}</flux:heading>
            <flux:text class="mt-1">{{ __('Configure how EmbedLayer connects to your warehouse.') }}</flux:text>
        </div>

        <flux:button
            variant="ghost"
            :href="route('analytics.data-sources.index')"
            wire:navigate
        >
            {{ __('Cancel') }}
        </flux:button>
    </div>

    <form wire:submit="save" class="flex flex-col gap-6">
        <div class="rounded-xl border border-neutral-200 p-6 dark:border-neutral-700">
            <div class="grid gap-4 md:grid-cols-2">
                <flux:input
                    wire:model="name"
                    :label="__('Name')"
                    type="text"
                    placeholder="Production warehouse"
                    required
                    autofocus
                    data-test="data-source-name"
                />

                <flux:select
                    wire:model.live="driver"
                    :label="__('Driver')"
                    data-test="data-source-driver"
                >
                    <flux:select.option value="postgres">{{ __('PostgreSQL') }}</flux:select.option>
                    <flux:select.option value="mysql">{{ __('MySQL') }}</flux:select.option>
                    <flux:select.option value="bigquery">{{ __('BigQuery') }}</flux:select.option>
                    <flux:select.option value="snowflake">{{ __('Snowflake') }}</flux:select.option>
                </flux:select>
            </div>
        </div>

        <div class="rounded-xl border border-neutral-200 p-6 dark:border-neutral-700">
            <flux:heading size="lg">{{ __('Connection details') }}</flux:heading>
            <flux:text class="mt-1">{{ __('Credentials are encrypted at rest and never exposed back to the browser.') }}</flux:text>

            @if (in_array($driver, ['postgres', 'mysql'], true))
                <div class="mt-4 grid gap-4 md:grid-cols-2" data-test="sql-fields">
                    <flux:input wire:model="host" :label="__('Host')" type="text" placeholder="db.example.com" required />
                    <flux:input wire:model="port" :label="__('Port')" type="number" required />
                    <flux:input wire:model="database" :label="__('Database')" type="text" required />
                    <flux:input wire:model="username" :label="__('Username')" type="text" required />
                    <flux:input wire:model="password" :label="__('Password')" type="password" required viewable />
                </div>
            @elseif ($driver === 'bigquery')
                <div class="mt-4 grid gap-4 md:grid-cols-2" data-test="bigquery-fields">
                    <flux:input wire:model="project_id" :label="__('Project ID')" type="text" required />
                    <flux:input wire:model="dataset" :label="__('Dataset')" type="text" required />
                    <div class="md:col-span-2">
                        <flux:textarea
                            wire:model="service_account_json"
                            :label="__('Service account JSON')"
                            rows="8"
                            placeholder='{"type":"service_account",...}'
                            required
                        />
                    </div>
                </div>
            @elseif ($driver === 'snowflake')
                <div class="mt-4 grid gap-4 md:grid-cols-2" data-test="snowflake-fields">
                    <flux:input wire:model="account" :label="__('Account')" type="text" required />
                    <flux:input wire:model="user" :label="__('User')" type="text" required />
                    <flux:input wire:model="password" :label="__('Password')" type="password" required viewable />
                    <flux:input wire:model="warehouse" :label="__('Warehouse')" type="text" required />
                    <flux:input wire:model="database" :label="__('Database')" type="text" required />
                    <flux:input wire:model="schema" :label="__('Schema')" type="text" required />
                    <flux:input wire:model="role" :label="__('Role')" type="text" required />
                </div>
            @endif
        </div>

        <div class="flex items-center justify-end gap-3">
            <flux:button
                variant="ghost"
                :href="route('analytics.data-sources.index')"
                wire:navigate
                type="button"
            >
                {{ __('Cancel') }}
            </flux:button>

            <flux:button variant="primary" type="submit" data-test="save-data-source">
                {{ __('Create data source') }}
            </flux:button>
        </div>
    </form>
</div>
