<?php

declare(strict_types=1);

namespace App\Analytics\DataSources\DTOs;

use JsonSerializable;

final readonly class SchemaCatalog implements JsonSerializable
{
    /**
     * @param  list<TableCatalog>  $tables
     */
    public function __construct(
        public array $tables,
    ) {}

    /**
     * Build a SchemaCatalog from rows of:
     * { table_schema, table_name, column_name, data_type, is_nullable? }.
     *
     * Accepts arrays or stdClass rows (Laravel's DB::select returns objects).
     *
     * @param  iterable<int, array<string, mixed>|object>  $rows
     */
    public static function fromInformationSchemaRows(iterable $rows): self
    {
        /** @var array<string, array{schema: string, name: string, columns: list<ColumnCatalog>}> $byTable */
        $byTable = [];

        foreach ($rows as $row) {
            $array = is_array($row) ? $row : (array) $row;
            $schema = (string) ($array['table_schema'] ?? '');
            $tableName = (string) ($array['table_name'] ?? '');

            if ($schema === '' || $tableName === '') {
                continue;
            }

            $key = $schema.'.'.$tableName;

            if (! isset($byTable[$key])) {
                $byTable[$key] = ['schema' => $schema, 'name' => $tableName, 'columns' => []];
            }

            $nullableRaw = $array['is_nullable'] ?? null;
            $nullable = is_string($nullableRaw)
                ? strtoupper($nullableRaw) !== 'NO'
                : (bool) ($nullableRaw ?? true);

            $byTable[$key]['columns'][] = new ColumnCatalog(
                name: (string) ($array['column_name'] ?? ''),
                dataType: (string) ($array['data_type'] ?? ''),
                nullable: $nullable,
            );
        }

        $tables = array_values(array_map(
            fn (array $t): TableCatalog => new TableCatalog($t['schema'], $t['name'], $t['columns']),
            $byTable,
        ));

        return new self($tables);
    }

    public function jsonSerialize(): array
    {
        return [
            'tables' => $this->tables,
        ];
    }
}
