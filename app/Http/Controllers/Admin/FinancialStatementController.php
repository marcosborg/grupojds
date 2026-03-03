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
        $driver = Driver::with(['card', 'cards', 'electric'])->find($driver_id);
        $currentAccount = CurrentAccount::where([
            'tvde_week_id' => $tvde_week_id,
            'driver_id' => $driver_id,
        ])->first();

        if (!$driver || !$company || !$tvde_week || !$currentAccount) {
            return back()->with('message', 'Nao foi possivel gerar o PDF: faltam dados validados para esta semana.');
        }

        $results = json_decode($currentAccount->data);

        if (!$results) {
            return back()->with('message', 'Nao foi possivel gerar o PDF: os dados do extrato estao invalidos.');
        }

        $toFloat = static function ($value) {
            return is_numeric($value) ? (float) $value : 0.0;
        };

        $uber_gross = $toFloat($results->uber->uber_gross ?? 0);
        $bolt_gross = $toFloat($results->bolt->bolt_gross ?? 0);
        $uber_net = $toFloat($results->uber->uber_net ?? 0);
        $bolt_net = $toFloat($results->bolt->bolt_net ?? 0);
        $total_gross = $toFloat($results->total_gross ?? 0);
        $total_net = $toFloat($results->total_net ?? 0);
        $vat_value = $toFloat($results->vat_value ?? 0);
        $fuel_transactions = $toFloat($results->fuel_transactions ?? 0);
        $car_track = $toFloat($results->car_track ?? 0);
        $car_hire = $toFloat($results->car_hire ?? 0);
        $adjustments_total = $toFloat($results->adjustments ?? 0);
        $total_to_pay = $toFloat($results->total ?? 0);

        $adjustments_credit = $adjustments_total > 0 ? $adjustments_total : 0;
        $adjustments_debit = $adjustments_total < 0 ? abs($adjustments_total) : 0;
        $total_credits = round($total_net + $adjustments_credit, 2);
        $total_debits = round($car_hire + $car_track + $fuel_transactions + $vat_value + $adjustments_debit, 2);
        $reconciled_total = round($total_credits - $total_debits, 2);
        $reconciliation_delta = round($total_to_pay - $reconciled_total, 2);

        $driver_balance = DriversBalance::where([
            'driver_id' => $driver_id,
            'tvde_week_id' => $tvde_week_id,
        ])->first();

        $previous_balance = $driver_balance ? round($total_to_pay - $driver_balance->drivers_balance, 2) : null;
        $current_balance = $driver_balance ? (float) $driver_balance->drivers_balance : null;

        $electric_expenses = null;

        if ($driver->electric_id) {
            $electric = Electric::find($driver->electric_id);

            if ($electric) {
                $electric_transactions = ElectricTransaction::where([
                    'card' => $electric->code,
                    'tvde_week_id' => $tvde_week_id,
                ])->get();

                $electric_expenses = collect([
                    'amount' => number_format($electric_transactions->sum('amount'), 2, '.', '') . ' kWh',
                    'total' => number_format($electric_transactions->sum('total'), 2, '.', ''),
                    'value' => $electric_transactions->sum('total'),
                ]);
            }
        }

        $combustion_expenses = null;
        $combustionCardCodes = collect();

        if ($driver->cards->count() > 0) {
            $combustionCardCodes = $driver->cards->pluck('code')->filter();
        } elseif ($driver->card_id) {
            $card = Card::find($driver->card_id);

            if ($card && $card->code) {
                $combustionCardCodes = collect([$card->code]);
            }
        }

        if ($combustionCardCodes->count() > 0) {
            $combustion_transactions = CombustionTransaction::where('tvde_week_id', $tvde_week_id)
                ->whereIn('card', $combustionCardCodes->values()->all())
                ->get();

            $combustion_expenses = collect([
                'amount' => number_format($combustion_transactions->sum('amount'), 2, '.', '') . ' L',
                'total' => number_format($combustion_transactions->sum('total'), 2, '.', ''),
                'value' => $combustion_transactions->sum('total'),
            ]);
        }

        $pdf = Pdf::loadView('admin.financialStatements.pdf', [
            'company' => $company,
            'tvde_week' => $tvde_week,
            'driver' => $driver,
            'electric_expenses' => $electric_expenses,
            'combustion_expenses' => $combustion_expenses,
            'uber_gross' => $uber_gross,
            'bolt_gross' => $bolt_gross,
            'uber_net' => $uber_net,
            'bolt_net' => $bolt_net,
            'total_net' => $total_net,
            'total_gross' => $total_gross,
            'vat_value' => $vat_value,
            'fuel_transactions' => $fuel_transactions,
            'car_track' => $car_track,
            'car_hire' => $car_hire,
            'adjustments_total' => $adjustments_total,
            'adjustments_credit' => $adjustments_credit,
            'adjustments_debit' => $adjustments_debit,
            'total_credits' => $total_credits,
            'total_debits' => $total_debits,
            'reconciled_total' => $reconciled_total,
            'reconciliation_delta' => $reconciliation_delta,
            'total_to_pay' => $total_to_pay,
            'driver_balance' => $driver_balance,
            'previous_balance' => $previous_balance,
            'current_balance' => $current_balance,
        ])->setOption([
            'isRemoteEnabled' => true,
            'defaultFont' => 'DejaVu Sans',
        ]);

        if ($request->download) {
            $filename = strtolower(str_replace(' ', '_', preg_replace('/[^A-Za-z0-9\-]/', '', $driver->name . '-' . $tvde_week->start_date))) . '.pdf';

            return $pdf->download($filename);
        }

        return $pdf->stream();
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
