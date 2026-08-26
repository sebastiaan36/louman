<?php

use Illuminate\Support\Str;

/**
 * A foreign key can only be created once the table it points at exists.
 * Laravel runs migrations in filename order, so a migration that constrains a
 * column must sort after the one that creates the referenced table.
 *
 * SQLite accepts a foreign key to a table that does not exist yet, MySQL does
 * not. The test suite runs on SQLite, so without this check the mistake only
 * surfaces during a deploy.
 */
test('elke foreign key verwijst naar een tabel die eerder wordt aangemaakt', function () {
    $files = glob(dirname(__DIR__, 2).'/database/migrations/*.php');
    sort($files);

    /** @var array<string, int> $createdAt table name => index of the migration creating it */
    $createdAt = [];
    $problems = [];

    foreach ($files as $index => $file) {
        $source = file_get_contents($file);
        $name = basename($file);

        preg_match_all("/->constrained\(\s*(?:'([^']+)')?/", $source, $constrained, PREG_SET_ORDER);
        preg_match_all("/foreignId\(\s*'([^']+)'/", $source, $columns, PREG_SET_ORDER);

        foreach ($constrained as $position => $match) {
            $referenced = $match[1] ?? null;

            if ($referenced === null) {
                // Without an explicit table, Laravel derives it from the column:
                // webhook_endpoint_id -> webhook_endpoints.
                $column = $columns[$position][1] ?? null;

                if ($column === null) {
                    continue;
                }

                $referenced = Str::plural(Str::beforeLast($column, '_id'));
            }

            if (! isset($createdAt[$referenced])) {
                $problems[] = "{$name} verwijst naar '{$referenced}', maar die tabel is op dat moment nog niet aangemaakt.";

                continue;
            }

            if ($createdAt[$referenced] > $index) {
                $problems[] = "{$name} verwijst naar '{$referenced}', die pas later wordt aangemaakt.";
            }
        }

        preg_match_all("/Schema::create\(\s*'([^']+)'/", $source, $created, PREG_SET_ORDER);

        foreach ($created as $match) {
            $createdAt[$match[1]] ??= $index;
        }
    }

    expect($problems)->toBe([]);
});
