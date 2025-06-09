<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Gate;
use Symfony\Component\HttpFoundation\Response;
use App\Http\Controllers\Traits\Reports;
use Illuminate\Http\Request;
use App\Models\CurrentAccount;
use App\Models\DriversBalance;
use App\Models\Driver;

class CompanyReportController extends Controller
{

    use Reports;

    public function index()
    {
        abort_if(Gate::denies('company_report_access'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $filter = $this->filter();
        $company_id = $filter['company_id'];
        $tvde_week_id = $filter['tvde_week_id'];
        $tvde_years = $filter['tvde_years'];
        $tvde_year_id = $filter['tvde_year_id'];
        $tvde_months = $filter['tvde_months'];
        $tvde_month_id = $filter['tvde_month_id'];
        $tvde_weeks = $filter['tvde_weeks'];

        $results = $this->getWeekReport($company_id, $tvde_week_id);

        return view('admin.companyReports.index')->with([
            'company_id' => $company_id,
            'tvde_years' => $tvde_years,
            'tvde_year_id' => $tvde_year_id,
            'tvde_months' => $tvde_months,
            'tvde_month_id' => $tvde_month_id,
            'tvde_weeks' => $tvde_weeks,
            'tvde_week_id' => $tvde_week_id,
            'drivers' => $results['drivers'],
            'totals' => $results['totals']
        ]);

    }

    public function validateData(Request $request)
    {

        foreach ($request->data as $data) {

            $current_account = new CurrentAccount;
            $current_account->tvde_week_id = $data['tvde_week_id'];
            $current_account->driver_id = $data['driver']['id'];
            $current_account->data = json_encode($data['driver']['earnings']);
            $current_account->save();

            $last_balance = DriversBalance::where([
                'driver_id' => $data['driver']['id'],
            ])
                ->orderBy('tvde_week_id', 'desc')->first();

            $driver_balance = new DriversBalance;
            $driver_balance->driver_id = $data['driver']['id'];
            $driver_balance->tvde_week_id = $data['tvde_week_id'];
            $driver_balance->value = $data['driver']['total'];
            $driver_balance->balance = $last_balance ? $last_balance->balance + $data['driver']['total'] : $data['driver']['total'];
            $driver_balance->drivers_balance = $last_balance ? $last_balance->balance + $data['driver']['total'] : $data['driver']['total'];
            $driver_balance->save();

            /*
            $email = $data['driver']['email'];

            
            Notification::route('mail', $email)
                ->notify(new ActivityLaunchesSend());
            */

        }
    }

    public function revalidateData(Request $request)
    {
        $driver_id = $request->driver_id;
        $company_id = Driver::find($driver_id)->company_id;
        $tvde_week_id = $request->tvde_week_id;
        $data = $request->data;

        $current_account = CurrentAccount::where([
            'tvde_week_id' => $tvde_week_id,
            'driver_id' => $driver_id
        ])->first();
        $current_account->data = json_encode($data);
        $current_account->save();

        DriversBalance::where([
            'tvde_week_id' => $tvde_week_id,
            'driver_id' => $driver_id
        ])->delete();

        $last_balance = DriversBalance::where([
            'driver_id' => $data['driver']['id'],
        ])
            ->orderBy('tvde_week_id', 'desc')->first();

        $driver_balance = new DriversBalance;
        $driver_balance->driver_id = $data['driver']['id'];
        $driver_balance->tvde_week_id = $data['tvde_week_id'];
        $driver_balance->value = $data['driver']['total'];
        $driver_balance->balance = $last_balance ? $last_balance->balance + $data['driver']['total'] : $data['driver']['total'];
        $driver_balance->drivers_balance = $last_balance ? $last_balance->balance + $data['driver']['total'] : $data['driver']['total'];
        $driver_balance->save();

    }

}
