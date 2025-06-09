@extends('layouts.admin')
@section('content')
<div class="content">
    @can('tesla_charging_create')
        <div style="margin-bottom: 10px;" class="row">
            <div class="col-lg-12">
                <a class="btn btn-success" href="{{ route('admin.tesla-chargings.create') }}">
                    {{ trans('global.add') }} {{ trans('cruds.teslaCharging.title_singular') }}
                </a>
                <button class="btn btn-warning" data-toggle="modal" data-target="#csvImportModal">
                    {{ trans('global.app_csvImport') }}
                </button>
                @include('csvImport.modal', ['model' => 'TeslaCharging', 'route' => 'admin.tesla-chargings.parseCsvImport'])
            </div>
        </div>
    @endcan
    <div class="row">
        <div class="col-lg-12">
            <div class="panel panel-default">
                <div class="panel-heading">
                    {{ trans('cruds.teslaCharging.title_singular') }} {{ trans('global.list') }}
                </div>
                <div class="panel-body">
                    <div class="table-responsive">
                        <table class=" table table-bordered table-striped table-hover datatable datatable-TeslaCharging">
                            <thead>
                                <tr>
                                    <th width="10">

                                    </th>
                                    <th>
                                        {{ trans('cruds.teslaCharging.fields.id') }}
                                    </th>
                                    <th>
                                        {{ trans('cruds.teslaCharging.fields.tvde_week') }}
                                    </th>
                                    <th>
                                        {{ trans('cruds.teslaCharging.fields.license') }}
                                    </th>
                                    <th>
                                        {{ trans('cruds.teslaCharging.fields.value') }}
                                    </th>
                                    <th>
                                        &nbsp;
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($teslaChargings as $key => $teslaCharging)
                                    <tr data-entry-id="{{ $teslaCharging->id }}">
                                        <td>

                                        </td>
                                        <td>
                                            {{ $teslaCharging->id ?? '' }}
                                        </td>
                                        <td>
                                            {{ $teslaCharging->tvde_week->start_date ?? '' }}
                                        </td>
                                        <td>
                                            {{ $teslaCharging->license ?? '' }}
                                        </td>
                                        <td>
                                            {{ $teslaCharging->value ?? '' }}
                                        </td>
                                        <td>
                                            @can('tesla_charging_show')
                                                <a class="btn btn-xs btn-primary" href="{{ route('admin.tesla-chargings.show', $teslaCharging->id) }}">
                                                    {{ trans('global.view') }}
                                                </a>
                                            @endcan

                                            @can('tesla_charging_edit')
                                                <a class="btn btn-xs btn-info" href="{{ route('admin.tesla-chargings.edit', $teslaCharging->id) }}">
                                                    {{ trans('global.edit') }}
                                                </a>
                                            @endcan

                                            @can('tesla_charging_delete')
                                                <form action="{{ route('admin.tesla-chargings.destroy', $teslaCharging->id) }}" method="POST" onsubmit="return confirm('{{ trans('global.areYouSure') }}');" style="display: inline-block;">
                                                    <input type="hidden" name="_method" value="DELETE">
                                                    <input type="hidden" name="_token" value="{{ csrf_token() }}">
                                                    <input type="submit" class="btn btn-xs btn-danger" value="{{ trans('global.delete') }}">
                                                </form>
                                            @endcan

                                        </td>

                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>



        </div>
    </div>
</div>
@endsection
@section('scripts')
@parent
<script>
    $(function () {
  let dtButtons = $.extend(true, [], $.fn.dataTable.defaults.buttons)
@can('tesla_charging_delete')
  let deleteButtonTrans = '{{ trans('global.datatables.delete') }}'
  let deleteButton = {
    text: deleteButtonTrans,
    url: "{{ route('admin.tesla-chargings.massDestroy') }}",
    className: 'btn-danger',
    action: function (e, dt, node, config) {
      var ids = $.map(dt.rows({ selected: true }).nodes(), function (entry) {
          return $(entry).data('entry-id')
      });

      if (ids.length === 0) {
        alert('{{ trans('global.datatables.zero_selected') }}')

        return
      }

      if (confirm('{{ trans('global.areYouSure') }}')) {
        $.ajax({
          headers: {'x-csrf-token': _token},
          method: 'POST',
          url: config.url,
          data: { ids: ids, _method: 'DELETE' }})
          .done(function () { location.reload() })
      }
    }
  }
  dtButtons.push(deleteButton)
@endcan

  $.extend(true, $.fn.dataTable.defaults, {
    orderCellsTop: true,
    order: [[ 1, 'desc' ]],
    pageLength: 100,
  });
  let table = $('.datatable-TeslaCharging:not(.ajaxTable)').DataTable({ buttons: dtButtons })
  $('a[data-toggle="tab"]').on('shown.bs.tab click', function(e){
      $($.fn.dataTable.tables(true)).DataTable()
          .columns.adjust();
  });
  
})

</script>
@endsection