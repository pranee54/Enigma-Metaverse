<?php

use App\Models\Configuration;
use App\Models\StatutoryRuleVersion;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    foreach (['hr', 'finance', 'employee', 'auditor'] as $role) {
        Role::findOrCreate($role);
    }
    $this->seed(DatabaseSeeder::class);
});

it('lets hr update payroll configurations from settings', function () {
    $hr = User::where('email', 'hr@enigma.test')->firstOrFail();

    $this->actingAs($hr)
        ->put(route('settings.configs'), [
            'company_name' => 'Acme Payroll Pvt Ltd',
            'company_state' => 'Telangana',
            'currency' => 'INR',
            'proration_divisor' => '30',
            'overtime_multiplier' => '2.5',
            'deduction_cap_percent' => '40',
        ])
        ->assertRedirect()
        ->assertSessionHas('success');

    expect(Configuration::getValue('company_name'))->toBe('Acme Payroll Pvt Ltd');
    expect((float) Configuration::getValue('overtime_multiplier'))->toBe(2.5);
    expect((string) Configuration::getValue('proration_divisor'))->toBe('30');
});

it('lets finance add a statutory rule version using percent inputs', function () {
    $finance = User::where('email', 'finance@enigma.test')->firstOrFail();

    $this->actingAs($finance)
        ->post(route('settings.statutory'), [
            'code' => 'default',
            'effective_from' => '2027-01-01',
            'notes' => 'Next year rates',
            'epf_ceiling' => 25000,
            'epf_employee_rate_pct' => 12,
            'epf_employer_rate_pct' => 12,
            'eps_rate_pct' => 8.33,
            'esi_threshold' => 21000,
            'esi_employee_rate_pct' => 0.75,
            'esi_employer_rate_pct' => 3.25,
            'pt_slab_1_upto' => 15000,
            'pt_slab_1_amount' => 0,
            'pt_slab_2_upto' => 20000,
            'pt_slab_2_amount' => 150,
            'pt_slab_3_amount' => 200,
        ])
        ->assertRedirect()
        ->assertSessionHas('success');

    $rule = StatutoryRuleVersion::query()
        ->where('code', 'default')
        ->whereDate('effective_from', '2027-01-01')
        ->firstOrFail();

    expect((float) $rule->payload_json['epf_employee_rate'])->toBe(0.12);
    expect((float) $rule->payload_json['esi_employee_rate'])->toBe(0.0075);
});

it('lets hr edit and delete an unused statutory version', function () {
    $hr = User::where('email', 'hr@enigma.test')->firstOrFail();

    $rule = StatutoryRuleVersion::query()->create([
        'code' => 'default',
        'effective_from' => '2028-01-01',
        'notes' => 'Temp version',
        'payload_json' => [
            'epf_ceiling' => 25000,
            'epf_employee_rate' => 0.12,
            'epf_employer_rate' => 0.12,
            'eps_rate' => 0.0833,
            'esi_threshold' => 21000,
            'esi_employee_rate' => 0.0075,
            'esi_employer_rate' => 0.0325,
            'pt_slabs' => [
                ['upto' => 15000, 'amount' => 0],
                ['upto' => 20000, 'amount' => 150],
                ['upto' => null, 'amount' => 200],
            ],
        ],
    ]);

    $this->actingAs($hr)
        ->put(route('settings.statutory.update', $rule), [
            'code' => 'default',
            'effective_from' => '2028-01-01',
            'notes' => 'Updated note',
            'epf_ceiling' => 26000,
            'epf_employee_rate_pct' => 12,
            'epf_employer_rate_pct' => 12,
            'eps_rate_pct' => 8.33,
            'esi_threshold' => 21000,
            'esi_employee_rate_pct' => 0.75,
            'esi_employer_rate_pct' => 3.25,
            'pt_slab_1_upto' => 15000,
            'pt_slab_1_amount' => 0,
            'pt_slab_2_upto' => 20000,
            'pt_slab_2_amount' => 150,
            'pt_slab_3_amount' => 200,
        ])
        ->assertRedirect(route('settings.index'));

    expect((float) $rule->fresh()->payload_json['epf_ceiling'])->toBe(26000.0);

    $this->actingAs($hr)
        ->delete(route('settings.statutory.destroy', $rule))
        ->assertRedirect(route('settings.index'));

    expect(StatutoryRuleVersion::query()->whereKey($rule->id)->exists())->toBeFalse();
});

it('blocks employees from settings', function () {
    $employee = User::where('email', 'emp@enigma.test')->firstOrFail();

    $this->actingAs($employee)
        ->get(route('settings.index'))
        ->assertForbidden();
});
