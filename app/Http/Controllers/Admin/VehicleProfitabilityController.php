<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\VehicleItem;
use Gate;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Http\Controllers\Traits\Reports;
use App\Models\TvdeWeek;
use App\Models\VehicleUsage;
use App\Models\VehicleExpense;
use App\Models\CurrentAccount;
use App\Models\ExpenseReimbursement;
use App\Models\Receipt;

class VehicleProfitabilityController extends Controller
{

    use Reports;

    public function index()
    {
        abort_if(Gate::denies('vehicle_profitability_access'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $filter = $this->filter();
        $company_id = $filter['company_id'];
        $tvde_week_id = $filter['tvde_week_id'];
        $tvde_years = $filter['tvde_years'];
        $tvde_year_id = $filter['tvde_year_id'];
        $tvde_months = $filter['tvde_months'];
        $tvde_month_id = $filter['tvde_month_id'];
        $tvde_weeks = $filter['tvde_weeks'];

        $vehicle_items = VehicleItem::all()->load('driver');

        if (!session()->has('vehicle_item_id')) {
            $vehicle_item = VehicleItem::first();
            if ($vehicle_item) {
                $vehicle_item_id = $vehicle_item->id;
            } else {
                $vehicle_item_id = 0;
            }
            session()->put('vehicle_item_id', $vehicle_item_id);
        } else {
            $vehicle_item_id = session()->get('vehicle_item_id');
            $vehicle_item = VehicleItem::find($vehicle_item_id);
        }

        $tvde_week = TvdeWeek::find($tvde_week_id);

        //IDENTIFICAR O MOTORISTA

        $vehicle_usage = VehicleUsage::where('vehicle_item_id', $vehicle_item_id)
            ->whereDate('start_date', '<=', $tvde_week->start_date)
            ->whereDate('end_date', '<=', $tvde_week->end_date)
            ->with('driver')
            ->first();

        if (!$vehicle_usage) {
            $vehicle_usage = VehicleUsage::first()->load('driver');
            session()->put('vehicle_item_id', $vehicle_usage->vehicle_item_id);
        }

        $driver = $vehicle_usage->driver;

        $results = CurrentAccount::where([
            'tvde_week_id' => $tvde_week_id,
            'driver_id' => $vehicle_usage->driver->id
        ])->first();

        $adjustments = [];

        if ($results) {
            $results = json_decode($results->data);
            
            //IVA A DEVOLVER
            $factor = $driver->contract_vat->iva / 100;
            $iva = number_format($results->total * $factor, 2);
            //RETENCAO
            $factor = $driver->contract_vat->rf / 100;
            $rf = number_format(($results->total * $factor), 2);
            //ADJUSTMENTS
            foreach ($results->adjustments_array as $adjustment) {
                if ($adjustment->company_expense) {
                    $adjustments[] = $adjustment->type = 'deduct' ? - $adjustment->amount : $adjustment->amount;
                }
            }
        } else {
            $iva = 0;
            $rf = 0;
        }

        $adjustments = array_sum($adjustments);

        $receipt = Receipt::where([
            'tvde_week_id' => $tvde_week_id,
            'driver_id' => $vehicle_usage->driver->id
        ])->first();

        $fuel_transactions_vat = $results && $results->fuel_transactions ? ($results->fuel_transactions / 1.23) * 0.23 : 0;

        // DESPESAS DA VIATURA
        $vehicle_expenses = VehicleExpense::where('vehicle_item_id', $vehicle_item->id)
            ->whereDate('date', '>=', $tvde_week->start_date)
            ->whereDate('date', '<=', $tvde_week->end_date)
            ->get();

        $vehicle_expenses_value = [];
        $vehicle_expenses_vat = [];

        if ($vehicle_expenses) {
            foreach ($vehicle_expenses as $vehicle_expense) {
                if ($vehicle_expense->vat > 0 || $vehicle_expense->vat !== NULL) {
                    $vehicle_expenses_vat[] = $vehicle_expense->value * ($vehicle_expense->vat / 100);
                    $vehicle_expenses_value[] = $vehicle_expense->value + ($vehicle_expense->value * ($vehicle_expense->vat / 100));
                } else {
                    $vehicle_expenses_value[] = $vehicle_expense->value;
                }
            }
        }

        $vehicle_expenses_value = array_sum($vehicle_expenses_value);
        $vehicle_expenses_vat = array_sum($vehicle_expenses_vat);

        $vehicle_expenses = compact('vehicle_expenses_value', 'vehicle_expenses_vat');

        //REEMBOLSOS DA VIATURA

        $expense_reimbursements = ExpenseReimbursement::where('vehicle_item_id', $vehicle_item->id)
            ->whereDate('date', '>=', $tvde_week->start_date)
            ->whereDate('date', '<=', $tvde_week->end_date)
            ->get();

        $expense_reimbursements_value = $expense_reimbursements ? $expense_reimbursements->sum('value') : 0;

        $total_treasury = ($results->total_net ?? 0) - ($results->car_track ?? 0) - ($results->fuel_transactions ?? 0) + ($adjustments ?? 0) - ($rf ?? 0) - ($receipt ? $receipt->amount_transferred : 0) - ($vehicle_expenses['vehicle_expenses_value'] ?? 0) + ($expense_reimbursements_value ?? 0);
        $total_taxes = - ($results->vat_value ?? 0) + ($iva ?? 0) + ($fuel_transactions_vat ?? 0) + ($vehicle_expenses['vehicle_expenses_vat'] ?? 0);
        $final_total = $total_treasury + $total_taxes;

        $total = [
            'total_treasury' => $total_treasury,
            'total_taxes' => $total_taxes,
            'final_total' => $final_total,
        ];

        return view('admin.vehicleProfitabilities.index', compact([
            'tvde_year_id',
            'tvde_years',
            'tvde_months',
            'tvde_month_id',
            'tvde_weeks',
            'tvde_week_id',
            'vehicle_items',
            'vehicle_item_id',
            'results',
            'vehicle_expenses',
            'expense_reimbursements_value',
            'driver',
            'fuel_transactions_vat',
            'rf',
            'iva',
            'receipt',
            'total',
            'adjustments',
        ]));
    }

    public function setVehicleItemId($vehicle_item_id)
    {
        session()->put('vehicle_item_id', $vehicle_item_id);
        return redirect()->back();
    }
}
