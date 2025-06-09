@extends('layouts.admin')
@section('content')
<div class="content">

    <div class="row">
        <div class="col-lg-12">
            <div class="panel panel-default">
                <div class="panel-heading">
                    {{ trans('global.edit') }} {{ trans('cruds.teslaCharging.title_singular') }}
                </div>
                <div class="panel-body">
                    <form method="POST" action="{{ route("admin.tesla-chargings.update", [$teslaCharging->id]) }}" enctype="multipart/form-data">
                        @method('PUT')
                        @csrf
                        <div class="form-group {{ $errors->has('tvde_week') ? 'has-error' : '' }}">
                            <label class="required" for="tvde_week_id">{{ trans('cruds.teslaCharging.fields.tvde_week') }}</label>
                            <select class="form-control select2" name="tvde_week_id" id="tvde_week_id" required>
                                @foreach($tvde_weeks as $id => $entry)
                                    <option value="{{ $id }}" {{ (old('tvde_week_id') ? old('tvde_week_id') : $teslaCharging->tvde_week->id ?? '') == $id ? 'selected' : '' }}>{{ $entry }}</option>
                                @endforeach
                            </select>
                            @if($errors->has('tvde_week'))
                                <span class="help-block" role="alert">{{ $errors->first('tvde_week') }}</span>
                            @endif
                            <span class="help-block">{{ trans('cruds.teslaCharging.fields.tvde_week_helper') }}</span>
                        </div>
                        <div class="form-group {{ $errors->has('license') ? 'has-error' : '' }}">
                            <label class="required" for="license">{{ trans('cruds.teslaCharging.fields.license') }}</label>
                            <input class="form-control" type="text" name="license" id="license" value="{{ old('license', $teslaCharging->license) }}" required>
                            @if($errors->has('license'))
                                <span class="help-block" role="alert">{{ $errors->first('license') }}</span>
                            @endif
                            <span class="help-block">{{ trans('cruds.teslaCharging.fields.license_helper') }}</span>
                        </div>
                        <div class="form-group {{ $errors->has('value') ? 'has-error' : '' }}">
                            <label class="required" for="value">{{ trans('cruds.teslaCharging.fields.value') }}</label>
                            <input class="form-control" type="number" name="value" id="value" value="{{ old('value', $teslaCharging->value) }}" step="0.01" required>
                            @if($errors->has('value'))
                                <span class="help-block" role="alert">{{ $errors->first('value') }}</span>
                            @endif
                            <span class="help-block">{{ trans('cruds.teslaCharging.fields.value_helper') }}</span>
                        </div>
                        <div class="form-group">
                            <button class="btn btn-danger" type="submit">
                                {{ trans('global.save') }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>



        </div>
    </div>
</div>
@endsection