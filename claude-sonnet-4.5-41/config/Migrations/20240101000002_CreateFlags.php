<?php
declare(strict_types=1);

use Migrations\AbstractMigration;

class CreateFlags extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table('flags');
        $table->addColumn('flag_key', 'string', [
            'limit' => 255,
            'null' => false,
        ]);
        $table->addColumn('name', 'string', [
            'limit' => 255,
            'null' => false,
        ]);
        $table->addColumn('description', 'text', [
            'default' => null,
            'null' => true,
        ]);
        $table->addColumn('enabled', 'boolean', [
            'default' => false,
            'null' => false,
        ]);
        $table->addColumn('config_value', 'text', [
            'default' => null,
            'null' => true,
        ]);
        $table->addColumn('created', 'datetime', [
            'default' => null,
            'null' => true,
        ]);
        $table->addColumn('modified', 'datetime', [
            'default' => null,
            'null' => true,
        ]);
        $table->addIndex(['flag_key'], ['unique' => true]);
        $table->create();
    }
}