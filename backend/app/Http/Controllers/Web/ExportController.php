<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Export;
use App\Models\PayrollRun;
use App\Services\ExportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ExportController extends Controller
{
    public function __construct(private ExportService $exports) {}

    public function index()
    {
        return view('exports.index', [
            'runs' => PayrollRun::query()->latest()->limit(50)->get(),
            'exports' => Export::query()->latest()->paginate(20),
        ]);
    }

    public function journal(Request $request)
    {
        $data = $request->validate([
            'payroll_run_id' => ['required', 'exists:payroll_runs,id'],
        ]);

        $run = PayrollRun::findOrFail($data['payroll_run_id']);
        $export = $this->exports->journal($run, $request->user(), 'approved');

        return redirect()->route('exports.download', $export);
    }

    public function bank(Request $request)
    {
        $data = $request->validate([
            'payroll_run_id' => ['required', 'exists:payroll_runs,id'],
        ]);

        $run = PayrollRun::findOrFail($data['payroll_run_id']);
        $export = $this->exports->bank($run, $request->user());

        return redirect()->route('exports.download', $export);
    }

    public function download(Export $export)
    {
        return Storage::disk('local')->download($export->payload_path);
    }
}
