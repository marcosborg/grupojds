<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Adjustment;
use App\Models\Card;
use App\Models\CombustionTransaction;
use App\Models\Company;
use App\Models\ContractTypeRank;
use App\Models\Driver;
use App\Models\DriversBalance;
use App\Models\Electric;
use App\Models\ElectricTransaction;
use App\Models\TvdeActivity;
use App\Models\TvdeMonth;
use App\Models\TvdeWeek;
use App\Models\TvdeYear;
use App\Models\CurrentAccount;
use Gate;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Http\Controllers\Traits\Reports;

class FinancialStatementController extends Controller
{

    use Reports;

    public function index()
    {

        abort_if(Gate::denies('financial_statement_access'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $filter = $this->filter();
        $company_id = $filter['company_id'];
        $tvde_week_id = $filter['tvde_week_id'];
        $tvde_years = $filter['tvde_years'];
        $tvde_year_id = $filter['tvde_year_id'];
        $tvde_months = $filter['tvde_months'];
        $tvde_month_id = $filter['tvde_month_id'];
        $tvde_weeks = $filter['tvde_weeks'];
        $drivers = $filter['drivers'];

        $driver_id = session()->get('driver_id') ? session()->get('driver_id') : 0;
        $results = null;

        if ($driver_id != 0 && $tvde_week_id) {
            $results = CurrentAccount::where([
                'tvde_week_id' => $tvde_week_id,
                'driver_id' => $driver_id
            ])->first();

            if ($results) {
                $results = json_decode($results->data);
            }
        }

        $driver_balance = null;
        if ($driver_id != 0 && $tvde_week_id) {
            $driver_balance = DriversBalance::where([
                'driver_id' => $driver_id,
                'tvde_week_id' => $tvde_week_id
            ])->first();
        }

        return view('admin.financialStatements.index')->with([
            'company_id' => $company_id,
            'tvde_year_id' => $tvde_year_id,
            'tvde_years' => $tvde_years,
            'tvde_months' => $tvde_months,
            'tvde_month_id' => $tvde_month_id,
            'tvde_weeks' => $tvde_weeks,
            'tvde_week_id' => $tvde_week_id,
            'drivers' => $drivers,
            'driver_id' => $driver_id,
            'uber_gross' => isset($results) ? $results->uber->uber_gross : 0,
            'bolt_gross' => isset($results) ? $results->bolt->bolt_gross : 0,
            'uber_net' => isset($results) ? $results->uber->uber_net : 0,
            'bolt_net' => isset($results) ? $results->bolt->bolt_net : 0,
            'total_gross' => isset($results) ? $results->total_gross : 0,
            'total_net' => isset($results) ? $results->total_net : 0,
            'adjustments' => isset($results) ? $results->adjustments : 0,
            'total' => isset($results) ? $results->total : 0,
            'vat_value' => isset($results) ? $results->vat_value : 0,
            'car_track' => isset($results) ? $results->car_track : 0,
            'car_hire' => isset($results) ? $results->car_hire : 0,
            'fuel_transactions' => isset($results) ? $results->fuel_transactions : 0,
            'driver_balance' => $driver_balance ?? null,
        ]);
    }

    public function year($tvde_year_id)
    {
        session()->put('tvde_year_id', $tvde_year_id);
        session()->put('tvde_month_id', TvdeMonth::orderBy('number', 'desc')->where('year_id', session()->get('tvde_year_id'))->first()->id);
        session()->put('tvde_week_id', TvdeWeek::orderBy('number', 'desc')->where('tvde_month_id', session()->get('tvde_month_id'))->first()->id);
        return back();
    }

    public function month($tvde_month_id)
    {
        session()->put('tvde_month_id', $tvde_month_id);
        session()->put('tvde_week_id', TvdeWeek::orderBy('number', 'desc')->where('tvde_month_id', $tvde_month_id)->first()->id);
        return back();
    }

    public function week($tvde_week_id)
    {
        session()->put('tvde_week_id', $tvde_week_id);
        return back();
    }

    public function driver($driver_id)
    {
        session()->put('driver_id', $driver_id);
        return back();
    }

    public function pdf(Request $request)
    {
        abort_if(Gate::denies('financial-pdf'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $tvde_week_id = session()->get('tvde_week_id');
        $driver_id = session()->get('driver_id');
        $company_id = session()->get('company_id');

        if (!$company_id || !$tvde_week_id || !$driver_id) {
            return back()->with('message', 'Selecione uma empresa, semana e motorista antes de gerar o PDF.');
        }

        $company = Company::find($company_id);
        $tvde_week = TvdeWeek::find($tvde_week_id);

        $weekReport = $this->getWeekReport($company_id, $tvde_week_id);
        $driver = $weekReport['drivers']->firstWhere('id', (int) $driver_id);

        if (!$driver || !$company || !$tvde_week) {
            abort(404);
        }

        $earningsData = $driver->earnings;

        $uberTips = (float) ($earningsData['uber']['uber_gross'] ?? 0);
        $boltTips = (float) ($earningsData['bolt']['bolt_gross'] ?? 0);
        $uberNet = (float) ($earningsData['uber']['uber_net'] ?? 0);
        $boltNet = (float) ($earningsData['bolt']['bolt_net'] ?? 0);

        $total_earnings_uber = number_format($uberNet - $uberTips, 2, '.', '');
        $total_earnings_bolt = number_format($boltNet - $boltTips, 2, '.', '');
        $total_tips_uber = number_format($uberTips, 2, '.', '');
        $total_tips_bolt = number_format($boltTips, 2, '.', '');
        $total_tips = (float) $total_tips_uber + (float) $total_tips_bolt;
        $uber_net = $uberNet;
        $bolt_net = $boltNet;
        $total_net = (float) ($earningsData['total_net'] ?? ($uberNet + $boltNet));
        $total_gross = (float) ($earningsData['total_gross'] ?? ($uberTips + $boltTips));
        $vat_value = (float) ($earningsData['vat_value'] ?? 0);
        $total_after_vat = (float) ($earningsData['total_after_vat'] ?? 0);
        $fuel_transactions = (float) ($earningsData['fuel_transactions'] ?? 0);
        $car_track = (float) ($earningsData['car_track'] ?? 0);
        $car_hire = (float) ($earningsData['car_hire'] ?? 0);
        $company_expense = (float) ($earningsData['company_expense'] ?? 0);
        $adjustments_total = (float) ($earningsData['adjustments'] ?? 0);
        $total_to_pay = (float) ($earningsData['total'] ?? 0);

        // FUEL EXPENSES

        $electric_expenses = null;
        if ($driver && $driver->electric_id) {
            $electric = Electric::find($driver->electric_id);
            if ($electric) {
                $electric_transactions = ElectricTransaction::where([
                    'card' => $electric->code,
                    'tvde_week_id' => $tvde_week_id
                ])->get();
                $electric_expenses = collect([
                    'amount' => number_format($electric_transactions->sum('amount'), 2, '.', '') . ' kWh',
                    'total' => number_format($electric_transactions->sum('total'), 2, '.', '') . ' €',
                    'value' => $electric_transactions->sum('total')
                ]);
            }
        }
        $combustion_expenses = null;
        if ($driver && $driver->card_id) {
            $card = Card::find($driver->card_id);
            if (!$card) {
                $code = 0;
            } else {
                $code = $card->code;
            }
            $combustion_transactions = CombustionTransaction::where([
                'card' => $code,
                'tvde_week_id' => $tvde_week_id
            ])->get();
            $combustion_expenses = collect([
                'amount' => number_format($combustion_transactions->sum('amount'), 2, '.', '') . ' L',
                'total' => number_format($combustion_transactions->sum('total'), 2, '.', '') . ' €',
                'value' => $combustion_transactions->sum('total')
            ]);
        }

        //GRAFICOS

        $labels = [];
        $earnings = [];

        foreach ($weekReport['drivers']->values() as $key => $d) {
            $isOwn = $d->id == $driver->id;
            $labels[] = $isOwn ? $driver->name : 'Motorista ' . ($key + 1);
            $earnings[] = number_format((float) ($d->earnings['total'] ?? 0), 2, '.', '');
        }

        $chart1 = "https://quickchart.io/chart?c={type:'bar',data:{labels:" . json_encode($labels) . ",datasets:[{borderWidth: 1, label:'Valor faturado',data:" . json_encode($earnings) . "}]}}";
        $chart2 = "https://quickchart.io/chart?c={type:'doughnut',data:{labels:['UBER', 'BOLT', 'GORJETAS'],datasets:[{label: 'Valor faturado', data: [" . $total_earnings_uber . ", " . $total_earnings_bolt . ", " . number_format($total_tips, 2, '.', '') . "]}]}}";

        /*

        return view('admin.financialStatements.pdf', compact([
            'company_id',
            'company',
            'tvde_week_id',
            'tvde_week',
            'driver_id',
            'bolt_activities',
            'uber_activities',
            'total_earnings_uber',
            'contract_type_rank',
            'total_uber',
            'total_earnings_bolt',
            'total_bolt',
            'total_tips_uber',
            'uber_tip_percent',
            'uber_tip_after_vat',
            'total_tips_bolt',
            'bolt_tip_percent',
            'bolt_tip_after_vat',
            'total_tips',
            'total_tip_after_vat',
            'adjustments',
            'total_earnings',
            'total_earnings_no_tip',
            'total',
            'total_after_vat',
            'gross_credits',
            'gross_debts',
            'final_total',
            'driver',
            'electric_expenses',
            'combustion_expenses',
            'combustion_racio',
            'electric_racio',
            'total_earnings_after_vat',
            'team_earnings',
            'txt_admin',
            'chart1',
            'chart2',
        ]));

        */

        $pdf = Pdf::loadView('admin.financialStatements.pdf', [
            'company_id' => $company_id,
            'company' => $company,
            'tvde_week_id' => $tvde_week_id,
            'tvde_week' => $tvde_week,
            'driver_id' => $driver_id,
            'total_earnings_uber' => $total_earnings_uber,
            'total_earnings_bolt' => $total_earnings_bolt,
            'total_tips_uber' => $total_tips_uber,
            'total_tips_bolt' => $total_tips_bolt,
            'total_tips' => $total_tips,
            'driver' => $driver,
            'electric_expenses' => $electric_expenses,
            'combustion_expenses' => $combustion_expenses,
            'uber_net' => $uber_net,
            'bolt_net' => $bolt_net,
            'total_net' => $total_net,
            'total_gross' => $total_gross,
            'vat_value' => $vat_value,
            'total_after_vat' => $total_after_vat,
            'fuel_transactions' => $fuel_transactions,
            'car_track' => $car_track,
            'car_hire' => $car_hire,
            'company_expense' => $company_expense,
            'adjustments_total' => $adjustments_total,
            'total_to_pay' => $total_to_pay,
            'chart1' => $chart1,
            'chart2' => $chart2,
        ])->setOption([
                    'isRemoteEnabled' => true,
                    'defaultFont' => 'DejaVu Sans',
                ]);


        if ($request->download) {

            $filename = strtolower(str_replace(' ', '_', preg_replace('/[^A-Za-z0-9\-]/', '', $driver->name . '-' . $tvde_week->start_date))) . '.pdf';

            return $pdf->download($filename);
        } else {
            return $pdf->stream();
        }

    }

    public function updateBalance(Request $request)
    {
        $request->validate([
            'balance' => 'required|numeric'
        ], [], [
            'balance' => 'Saldo'
        ]);

        $drivers_balance = DriversBalance::find($request->driver_balance_id);
        $drivers_balance->balance = $request->balance;
        $drivers_balance->drivers_balance = $request->balance;
        $drivers_balance->save();
    }

}
