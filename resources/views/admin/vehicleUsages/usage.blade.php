@extends('layouts.admin')
@section('content')
<div class="content">
    <div class="row">
        <div class="col-lg-12">
            <div class="panel panel-default">
                <div class="panel-heading">
                    {{ trans('cruds.vehicleUsage.title') }} - Visão Geral
                </div>

                <div class="panel-body">


<div>

  <!-- Nav tabs -->
  <ul class="nav nav-tabs" role="tablist">
    <li role="presentation" class="active"><a href="#home" aria-controls="home" role="tab" data-toggle="tab">Linha do Tempo das Viaturas</a></li>
    <li role="presentation"><a href="#profile" aria-controls="profile" role="tab" data-toggle="tab">Gráfico da Taxa de Ocupação</a></li>
    <li role="presentation"><a href="#messages" aria-controls="messages" role="tab" data-toggle="tab">Detalhe da Ocupação por Viatura</a></li>
  </ul>

  <!-- Tab panes -->
  <div class="tab-content">
    <div role="tabpanel" class="tab-pane active" id="home">
        {{-- 1. Timeline das Viaturas --}}
                    <h3>Linha do Tempo das Viaturas</h3>
                    <div id="timelineContainer" style="margin-bottom: 40px;">
                        <div id="timeline" style="height: auto;"></div>
                    </div>
    </div>
    <div role="tabpanel" class="tab-pane" id="profile">
        <div class="form-group mt-3">
    <label for="yearFilter">Selecionar Ano:</label>
    <select id="yearFilter" class="form-control" style="max-width: 200px;">
        <option value="all">Todos os anos</option>
        @foreach(array_keys($availableYears) as $year)
            <option value="{{ $year }}">{{ $year }}</option>
        @endforeach
    </select>
</div>

        {{-- 2. Gráfico da Taxa de Ocupação --}}
                    <h3 id="chartTitle">Gráfico da Taxa de Ocupação</h3>
                    <canvas id="occupancyChart" style="width: 100%; max-width: 100%; height: 400px;"></canvas>
    </div>
    <div role="tabpanel" class="tab-pane" id="messages">
        {{-- 3. Tabela Detalhada por Viatura/Ano --}}
                    <h3 class="mt-5">Detalhe da Ocupação por Viatura</h3>
                    @foreach($occupancyStats as $plate => $years)
                        <h4>{{ $plate }}</h4>
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>Ano</th>
                                    <th>Dias em uso</th>
                                    <th>Total de dias</th>
                                    <th>Taxa de ocupação (%)</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($years as $year => $data)
                                    <tr>
                                        <td>{{ $year }}</td>
                                        <td>{{ $data['used'] }}</td>
                                        <td>{{ $data['total'] }}</td>
                                        <td>{{ $data['percent'] }}%</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @endforeach
    </div>
  </div>

</div>

                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
    {{-- vis.js --}}
    <link href="https://unpkg.com/vis-timeline@latest/styles/vis-timeline-graph2d.min.css" rel="stylesheet" />
    <script src="https://unpkg.com/vis-timeline@latest/standalone/umd/vis-timeline-graph2d.min.js"></script>

    {{-- Chart.js --}}
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // Timeline
            const timelineItems = new vis.DataSet([
                @foreach($grouped as $plate => $records)
                    @foreach($records as $record)
                        {
                            id: {{ $record->id }},
                            content: '{{ $record->driver ? $record->driver->name : 'Sem motorista' }}',
                            start: '{{ \Carbon\Carbon::parse($record->start_date)->format('Y-m-d') }}',
                            end: '{{ \Carbon\Carbon::parse($record->end_date)->format('Y-m-d') }}',
                            group: '{{ $plate }}'
                        },
                    @endforeach
                @endforeach
            ]);

            const timelineGroups = new vis.DataSet([
                @foreach($grouped as $plate => $records)
                    { id: '{{ $plate }}', content: '{{ $plate }}' },
                @endforeach
            ]);

            const container = document.getElementById('timeline');
            const options = {
                stack: false,
                groupOrder: 'content',
                editable: false,
                margin: {
                    item: 10,
                    axis: 5
                },
                orientation: 'top'
            };

            new vis.Timeline(container, timelineItems, timelineGroups, options);

            // Gráfico de Ocupação com filtro por ano
            const ctx = document.getElementById('occupancyChart').getContext('2d');

            const allStats = [
                @foreach($sortedStats as $item)
                    {
                        label: '{{ $item['label'] }}',
                        year: '{{ explode("(", $item['label'])[1] }}'.replace(')', ''),
                        percent: {{ $item['percent'] }}
                    },
                @endforeach
            ];

            let chart;

            function updateChart(filteredStats) {
                const labels = filteredStats.map(item => item.label);
                const data = filteredStats.map(item => item.percent);

                if (chart) chart.destroy(); // Remove gráfico anterior

                chart = new Chart(ctx, {
                    type: 'bar',
                    data: {
                        labels: labels,
                        datasets: [{
                            label: 'Taxa de Ocupação (%)',
                            data: data,
                            backgroundColor: 'rgba(54, 162, 235, 0.5)',
                            borderColor: 'rgba(54, 162, 235, 1)',
                            borderWidth: 1
                        }]
                    },
                    options: {
                        indexAxis: 'y',
                        responsive: true,
                        scales: {
                            x: {
                                beginAtZero: true,
                                max: 100,
                                ticks: {
                                    callback: function (value) {
                                        return value + '%';
                                    }
                                }
                            }
                        },
                        plugins: {
                            tooltip: {
                                callbacks: {
                                    label: function (context) {
                                        return context.raw + '%';
                                    }
                                }
                            }
                        }
                    }
                });
            }

            // Inicializa com todos os dados
            updateChart(allStats);

            // Filtro por ano
            document.getElementById('yearFilter').addEventListener('change', function () {
                const selectedYear = this.value;

                const filtered = selectedYear === 'all'
                    ? allStats
                    : allStats.filter(item => item.year === selectedYear);

                updateChart(filtered);
            });

            // Espaçamento Dinâmico com ResizeObserver
            const timelineContainer = document.getElementById('timelineContainer');

            if ('ResizeObserver' in window && timelineContainer) {
                const observer = new ResizeObserver(entries => {
                    for (let entry of entries) {
                        const height = entry.contentRect.height;
                        timelineContainer.style.marginBottom = (height * 0.1 + 60) + 'px';
                    }
                });

                observer.observe(timelineContainer);
            } else {
                setTimeout(() => {
                    const height = timelineContainer.offsetHeight;
                    timelineContainer.style.marginBottom = (height * 0.1 + 60) + 'px';
                }, 500);
            }
        });
    </script>
@endsection

