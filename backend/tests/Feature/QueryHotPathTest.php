<?php

use App\Models\Configuration;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    foreach (['hr', 'finance', 'employee', 'auditor'] as $role) {
        Role::findOrCreate($role);
    }
    $this->seed(DatabaseSeeder::class);
});

it('loads company config once per request on dashboard', function () {
    $hr = User::where('email', 'hr@enigma.test')->firstOrFail();

    DB::flushQueryLog();
    DB::enableQueryLog();

    $this->actingAs($hr)->get(route('dashboard'))->assertOk();

    $configHits = collect(DB::getQueryLog())
        ->filter(fn (array $q) => str_contains($q['query'], 'from `configurations`')
            || str_contains($q['query'], 'from "configurations"'))
        ->count();

    expect($configHits)->toBeLessThanOrEqual(1);
});

it('keeps configuration bag fresh after setValue', function () {
    Configuration::setValue('company_name', 'Speed Co');

    expect(Configuration::getValue('company_name'))->toBe('Speed Co');
});
