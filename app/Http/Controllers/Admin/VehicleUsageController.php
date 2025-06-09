<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\MassDestroyVehicleUsageRequest;
use App\Http\Requests\StoreVehicleUsageRequest;
use App\Http\Requests\UpdateVehicleUsageRequest;
use App\Models\Driver;
use App\Models\VehicleItem;
use App\Models\VehicleUsage;
use Gate;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Yajra\DataTables\Facades\DataTables;

class VehicleUsageController extends Controller
{
    public function index(Request $request)
    {
        abort_if(Gate::denies('vehicle_usage_access'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        if ($request->ajax()) {
            $query = VehicleUsage::with(['driver', 'vehicle_item'])->select(sprintf('%s.*', (new VehicleUsage)->table));
            $table = Datatables::of($query);

            $table->addColumn('placeholder', '&nbsp;');
            $table->addColumn('actions', '&nbsp;');

            $table->editColumn('actions', function ($row) {
                $viewGate      = 'vehicle_usage_show';
                $editGate      = 'vehicle_usage_edit';
                $deleteGate    = 'vehicle_usage_delete';
                $crudRoutePart = 'vehicle-usages';

                return view('partials.datatablesActions', compact(
                    'viewGate',
                    'editGate',
                    'deleteGate',
                    'crudRoutePart',
                    'row'
                ));
            });

            $table->editColumn('id', function ($row) {
                return $row->id ? $row->id : '';
            });
            $table->addColumn('driver_name', function ($row) {
                return $row->driver ? $row->driver->name : '';
            });

            $table->addColumn('vehicle_item_license_plate', function ($row) {
                return $row->vehicle_item ? $row->vehicle_item->license_plate : '';
            });

            $table->rawColumns(['actions', 'placeholder', 'driver', 'vehicle_item']);

            return $table->make(true);
        }

        return view('admin.vehicleUsages.index');
    }

    public function create()
    {
        abort_if(Gate::denies('vehicle_usage_create'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $drivers = Driver::pluck('name', 'id')->prepend(trans('global.pleaseSelect'), '');

        $vehicle_items = VehicleItem::pluck('license_plate', 'id')->prepend(trans('global.pleaseSelect'), '');

        return view('admin.vehicleUsages.create', compact('drivers', 'vehicle_items'));
    }

    public function store(StoreVehicleUsageRequest $request)
    {
        $vehicleUsage = VehicleUsage::create($request->all());

        return redirect()->route('admin.vehicle-usages.index');
    }

    public function edit(VehicleUsage $vehicleUsage)
    {
        abort_if(Gate::denies('vehicle_usage_edit'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $drivers = Driver::pluck('name', 'id')->prepend(trans('global.pleaseSelect'), '');

        $vehicle_items = VehicleItem::pluck('license_plate', 'id')->prepend(trans('global.pleaseSelect'), '');

        $vehicleUsage->load('driver', 'vehicle_item');

        return view('admin.vehicleUsages.edit', compact('drivers', 'vehicleUsage', 'vehicle_items'));
    }

    public function update(UpdateVehicleUsageRequest $request, VehicleUsage $vehicleUsage)
    {
        $vehicleUsage->update($request->all());

        return redirect()->route('admin.vehicle-usages.index');
    }

    public function show(VehicleUsage $vehicleUsage)
    {
        abort_if(Gate::denies('vehicle_usage_show'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $vehicleUsage->load('driver', 'vehicle_item');

        return view('admin.vehicleUsages.show', compact('vehicleUsage'));
    }

    public function destroy(VehicleUsage $vehicleUsage)
    {
        abort_if(Gate::denies('vehicle_usage_delete'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $vehicleUsage->delete();

        return back();
    }

    public function massDestroy(MassDestroyVehicleUsageRequest $request)
    {
        $vehicleUsages = VehicleUsage::find(request('ids'));

        foreach ($vehicleUsages as $vehicleUsage) {
            $vehicleUsage->delete();
        }

        return response(null, Response::HTTP_NO_CONTENT);
    }

    public function usage()
    {
        $usages = VehicleUsage::with(['vehicle_item'])
            ->orderBy('start_date')
            ->get();

        $grouped = $usages->groupBy('vehicle_item.license_plate');

        $occupancyStats = [];

        foreach ($grouped as $plate => $usagesForVehicle) {
            $years = [];

            foreach ($usagesForVehicle as $usage) {
                $start = \Carbon\Carbon::parse($usage->start_date);
                $end = \Carbon\Carbon::parse($usage->end_date);

                for ($year = $start->year; $year <= $end->year; $year++) {
                    if (!isset($years[$year])) {
                        $years[$year] = [];
                    }

                    // Limita o período ao ano específico
                    $periodStart = $year == $start->year ? $start : \Carbon\Carbon::create($year, 1, 1);
                    $periodEnd = $year == $end->year ? $end : \Carbon\Carbon::create($year, 12, 31);

                    // Marca cada dia do ano como usado
                    $period = \Carbon\CarbonPeriod::create($periodStart, $periodEnd);
                    foreach ($period as $day) {
                        $years[$year][$day->format('Y-m-d')] = true;
                    }
                }
            }

            foreach ($years as $year => $usedDays) {
                $totalDays = \Carbon\Carbon::create($year, 1, 1)->daysInYear;
                $usedCount = count($usedDays);
                $occupancyStats[$plate][$year] = [
                    'used' => $usedCount,
                    'total' => $totalDays,
                    'percent' => round(($usedCount / $totalDays) * 100, 2),
                ];
            }
        }

        $sortedStats = [];

        foreach ($occupancyStats as $plate => $years) {
            foreach ($years as $year => $data) {
                $sortedStats[] = [
                    'label' => $plate . ' (' . $year . ')',
                    'percent' => $data['percent'],
                ];
            }
        }

        // Ordenar do maior para o menor
        usort($sortedStats, function ($a, $b) {
            return $b['percent'] <=> $a['percent'];
        });

        $availableYears = [];

        foreach ($occupancyStats as $plate => $years) {
            foreach ($years as $year => $data) {
                $availableYears[$year] = true;
            }
        }

        ksort($availableYears); // ordena os anos

        return view('admin.vehicleUsages.usage', compact(
            'grouped',
            'occupancyStats',
            'sortedStats',
            'availableYears'
        ));
    }
}
