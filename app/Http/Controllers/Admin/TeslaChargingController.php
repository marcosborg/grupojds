<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\CsvImportTrait;
use App\Http\Requests\MassDestroyTeslaChargingRequest;
use App\Http\Requests\StoreTeslaChargingRequest;
use App\Http\Requests\UpdateTeslaChargingRequest;
use App\Models\TeslaCharging;
use App\Models\TvdeWeek;
use Gate;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TeslaChargingController extends Controller
{
    use CsvImportTrait;

    public function index()
    {
        abort_if(Gate::denies('tesla_charging_access'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $teslaChargings = TeslaCharging::with(['tvde_week'])->get();

        return view('admin.teslaChargings.index', compact('teslaChargings'));
    }

    public function create()
    {
        abort_if(Gate::denies('tesla_charging_create'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $tvde_weeks = TvdeWeek::orderBy('id', 'desc')->pluck('start_date', 'id')->prepend(trans('global.pleaseSelect'), '');

        return view('admin.teslaChargings.create', compact('tvde_weeks'));
    }

    public function store(StoreTeslaChargingRequest $request)
    {
        $teslaCharging = TeslaCharging::create($request->all());

        return redirect()->route('admin.tesla-chargings.index');
    }

    public function edit(TeslaCharging $teslaCharging)
    {
        abort_if(Gate::denies('tesla_charging_edit'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $tvde_weeks = TvdeWeek::pluck('start_date', 'id')->prepend(trans('global.pleaseSelect'), '');

        $teslaCharging->load('tvde_week');

        return view('admin.teslaChargings.edit', compact('teslaCharging', 'tvde_weeks'));
    }

    public function update(UpdateTeslaChargingRequest $request, TeslaCharging $teslaCharging)
    {
        $teslaCharging->update($request->all());

        return redirect()->route('admin.tesla-chargings.index');
    }

    public function show(TeslaCharging $teslaCharging)
    {
        abort_if(Gate::denies('tesla_charging_show'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $teslaCharging->load('tvde_week');

        return view('admin.teslaChargings.show', compact('teslaCharging'));
    }

    public function destroy(TeslaCharging $teslaCharging)
    {
        abort_if(Gate::denies('tesla_charging_delete'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $teslaCharging->delete();

        return back();
    }

    public function massDestroy(MassDestroyTeslaChargingRequest $request)
    {
        $teslaChargings = TeslaCharging::find(request('ids'));

        foreach ($teslaChargings as $teslaCharging) {
            $teslaCharging->delete();
        }

        return response(null, Response::HTTP_NO_CONTENT);
    }
}
