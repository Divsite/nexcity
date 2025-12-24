<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('system.name', config('app.name'));
        $this->migrator->add('system.logo_sm', config('logo.main_sm'));
        $this->migrator->add('system.logo_lg', config('logo.main'));
        $this->migrator->add('system.favicon', config('logo.favicon'));
    }

    public function down(): void
    {
        $this->migrator->delete('system.name');
        $this->migrator->delete('system.logo_sm');
        $this->migrator->delete('system.logo_lg');
        $this->migrator->delete('system.favicon');
    }
};
