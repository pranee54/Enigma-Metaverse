<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Configuration;
use App\Models\StatutoryRuleVersion;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class SettingsController extends Controller
{
    public function index()
    {
        abort_unless(auth()->user()->hasAnyRole(['hr', 'finance']), 403);

        $configs = Configuration::query()->orderBy('key')->get()->keyBy('key');
        $rules = StatutoryRuleVersion::query()
            ->withCount(['payrollItems'])
            ->orderByDesc('effective_from')
            ->get();
        $latest = $rules->first();

        return view('settings.index', [
            'configs' => $configs,
            'rules' => $rules,
        ]);
    }

    public function createStatutory()
    {
        abort_unless(auth()->user()->hasAnyRole(['hr', 'finance']), 403);

        $latest = StatutoryRuleVersion::query()->orderByDesc('effective_from')->first();

        return view('settings.create-statutory', [
            'defaults' => $this->formDefaultsFromPayload($latest?->payload_json ?? [], $latest?->code ?? 'default'),
        ]);
    }

    public function editStatutory(StatutoryRuleVersion $statutory)
    {
        abort_unless(auth()->user()->hasAnyRole(['hr', 'finance']), 403);

        return view('settings.edit-statutory', [
            'rule' => $statutory,
            'defaults' => $this->formDefaultsFromPayload($statutory->payload_json ?? [], $statutory->code),
            'inUse' => $statutory->payrollItems()->exists(),
        ]);
    }

    public function updateConfigs(Request $request)
    {
        abort_unless(auth()->user()->hasAnyRole(['hr', 'finance']), 403);

        $data = $request->validate([
            'company_name' => ['required', 'string', 'max:150'],
            'company_state' => ['required', 'string', 'max:100'],
            'currency' => ['required', 'string', 'max:10'],
            'proration_divisor' => ['required', Rule::in(['calendar', '30', '26'])],
            'overtime_multiplier' => ['required', 'numeric', 'min:1', 'max:5'],
            'deduction_cap_percent' => ['required', 'numeric', 'min:1', 'max:100'],
        ]);

        Configuration::setValue('company_name', $data['company_name'], 'Company display name');
        Configuration::setValue('company_state', $data['company_state'], 'Default state for PT / compliance');
        Configuration::setValue('currency', $data['currency'], 'Payroll currency');
        Configuration::setValue('proration_divisor', $data['proration_divisor'], 'calendar|30|26');
        Configuration::setValue('overtime_multiplier', (float) $data['overtime_multiplier'], 'OT multiplier');
        Configuration::setValue('deduction_cap_percent', (float) $data['deduction_cap_percent'], 'Max wage deductions %');

        return back()->with('success', __('messages.flash.settings_saved'));
    }

    public function storeStatutory(Request $request)
    {
        abort_unless(auth()->user()->hasAnyRole(['hr', 'finance']), 403);

        $data = $this->validatedStatutory($request);

        StatutoryRuleVersion::query()->updateOrCreate(
            [
                'code' => $data['code'],
                'effective_from' => $data['effective_from'],
            ],
            [
                'notes' => $data['notes'] ?? null,
                'payload_json' => $this->payloadFromValidated($data),
            ]
        );

        return redirect()->route('settings.index')->with('success', __('messages.flash.statutory_saved'));
    }

    public function updateStatutory(Request $request, StatutoryRuleVersion $statutory)
    {
        abort_unless(auth()->user()->hasAnyRole(['hr', 'finance']), 403);

        $data = $this->validatedStatutory($request, $statutory);

        $statutory->update([
            'code' => $data['code'],
            'effective_from' => $data['effective_from'],
            'notes' => $data['notes'] ?? null,
            'payload_json' => $this->payloadFromValidated($data),
        ]);

        return redirect()->route('settings.index')->with('success', __('messages.flash.statutory_updated'));
    }

    public function destroyStatutory(StatutoryRuleVersion $statutory)
    {
        abort_unless(auth()->user()->hasAnyRole(['hr', 'finance']), 403);

        if ($statutory->payrollItems()->exists()) {
            throw ValidationException::withMessages([
                'statutory' => 'This version is used by payroll items — cannot delete. Add a new version instead.',
            ]);
        }

        if (StatutoryRuleVersion::query()->count() <= 1) {
            throw ValidationException::withMessages([
                'statutory' => 'At least one statutory version must remain.',
            ]);
        }

        $statutory->delete();

        return redirect()->route('settings.index')->with('success', __('messages.flash.statutory_deleted'));
    }

    private function validatedStatutory(Request $request, ?StatutoryRuleVersion $existing = null): array
    {
        return $request->validate([
            'code' => ['required', 'string', 'max:50'],
            'effective_from' => [
                'required',
                'date',
                Rule::unique('statutory_rule_versions', 'effective_from')
                    ->where(fn ($q) => $q->where('code', $request->input('code')))
                    ->ignore($existing?->id),
            ],
            'notes' => ['nullable', 'string', 'max:255'],
            'epf_ceiling' => ['required', 'numeric', 'min:0'],
            'epf_employee_rate_pct' => ['required', 'numeric', 'min:0', 'max:100'],
            'epf_employer_rate_pct' => ['required', 'numeric', 'min:0', 'max:100'],
            'eps_rate_pct' => ['required', 'numeric', 'min:0', 'max:100'],
            'esi_threshold' => ['required', 'numeric', 'min:0'],
            'esi_employee_rate_pct' => ['required', 'numeric', 'min:0', 'max:100'],
            'esi_employer_rate_pct' => ['required', 'numeric', 'min:0', 'max:100'],
            'pt_slab_1_upto' => ['required', 'numeric', 'min:0'],
            'pt_slab_1_amount' => ['required', 'numeric', 'min:0'],
            'pt_slab_2_upto' => ['required', 'numeric', 'min:0'],
            'pt_slab_2_amount' => ['required', 'numeric', 'min:0'],
            'pt_slab_3_amount' => ['required', 'numeric', 'min:0'],
        ]);
    }

    private function payloadFromValidated(array $data): array
    {
        return [
            'epf_ceiling' => (float) $data['epf_ceiling'],
            'epf_employee_rate' => $this->fromPercent($data['epf_employee_rate_pct']),
            'epf_employer_rate' => $this->fromPercent($data['epf_employer_rate_pct']),
            'eps_rate' => $this->fromPercent($data['eps_rate_pct']),
            'esi_threshold' => (float) $data['esi_threshold'],
            'esi_employee_rate' => $this->fromPercent($data['esi_employee_rate_pct']),
            'esi_employer_rate' => $this->fromPercent($data['esi_employer_rate_pct']),
            'pt_slabs' => [
                ['upto' => (float) $data['pt_slab_1_upto'], 'amount' => (float) $data['pt_slab_1_amount']],
                ['upto' => (float) $data['pt_slab_2_upto'], 'amount' => (float) $data['pt_slab_2_amount']],
                ['upto' => null, 'amount' => (float) $data['pt_slab_3_amount']],
            ],
        ];
    }

    private function formDefaultsFromPayload(array $payload, string $code): array
    {
        $slabs = $payload['pt_slabs'] ?? [
            ['upto' => 15000, 'amount' => 0],
            ['upto' => 20000, 'amount' => 150],
            ['upto' => null, 'amount' => 200],
        ];

        return [
            'code' => $code,
            'epf_ceiling' => $payload['epf_ceiling'] ?? 25000,
            'epf_employee_rate_pct' => $this->toPercent($payload['epf_employee_rate'] ?? 0.12),
            'epf_employer_rate_pct' => $this->toPercent($payload['epf_employer_rate'] ?? 0.12),
            'eps_rate_pct' => $this->toPercent($payload['eps_rate'] ?? 0.0833),
            'esi_threshold' => $payload['esi_threshold'] ?? 21000,
            'esi_employee_rate_pct' => $this->toPercent($payload['esi_employee_rate'] ?? 0.0075),
            'esi_employer_rate_pct' => $this->toPercent($payload['esi_employer_rate'] ?? 0.0325),
            'pt_slab_1_upto' => $slabs[0]['upto'] ?? 15000,
            'pt_slab_1_amount' => $slabs[0]['amount'] ?? 0,
            'pt_slab_2_upto' => $slabs[1]['upto'] ?? 20000,
            'pt_slab_2_amount' => $slabs[1]['amount'] ?? 150,
            'pt_slab_3_amount' => $slabs[2]['amount'] ?? 200,
        ];
    }

    private function toPercent(mixed $rate): float
    {
        return round(((float) $rate) * 100, 4);
    }

    private function fromPercent(mixed $percent): float
    {
        return round(((float) $percent) / 100, 6);
    }
}
