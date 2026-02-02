<!doctype html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title>Extrato</title>
    <style>
        html {
            font-family: sans-serif;
            font-size: 10px;
        }

        table {
            border-collapse: collapse;
            width: 100%;
        }

        th,
        td {
            padding: 8px;
        }

        @page {
            margin-top: 40px;
            margin-bottom: 0;
            margin-left: 40px;
            margin-right: 40px;
        }

        body {
            margin: 0;
        }

        footer {
            position: fixed;
            bottom: -0px;
            left: 0px;
            right: 0px;
            height: 50px;
            line-height: 35px;
        }

        table.bordered {
            border-collapse: collapse;
        }

        table.bordered th {
            border: solid 1px #ccc;
        }

        table.bordered td {
            border: solid 1px #ccc;
        }

        table.bordered thead th {
            background: #eeeeee;
        }
    </style>
</head>

<body>
    <table>
        <tbody>
            <tr>
                <td style="vertical-align: top; width: 50%;">
                    <h1>{{ $company->name }}</h1>
                    <p>{{ $company->vat }}<br>
                        {{ $company->address }}, {{ $company->zip }}<br>
                        {{ $company->location }}<br>
                        {{ $company->email }}
                    </p>
                </td>
                <td style="vertical-align: top; width: 50%;">
                    <h1>{{ $tvde_week->start_date }} a {{ $tvde_week->end_date }}</h1>
                    <p>
                        <strong>{{ $driver->name }}</strong><br>
                        {{ $driver->address != null ?? $driver->address . ',' . $driver->zip . '<br>'}}
                        {{ $driver->city != null ?? $driver->city . '<br>' }}
                        {{ $driver->phone != null ?? $driver->phone . '<br>' }}
                        {{ $driver->email }}<br>
                        <strong>{{ $driver->brand }} {{ $driver->model }} <small>({{ $driver->license_plate }})</small></strong>
                    </p>
                </td>
            </tr>
        </tbody>
    </table>

    <table>
        <tbody>
            <tr>
                <td style="vertical-align: top; width: 50%;">
                    <table class="bordered">
                        <thead>
                            <tr>
                                <th colspan="4" style="text-align: left; text-transform: uppercase;">Atividades por operador</th>
                            </tr>
                            <tr>
                                <th style="text-align: left;"></th>
                                <th style="text-align: right;">Ganhos</th>
                                <th style="text-align: right;">Gorjetas</th>
                                <th style="text-align: right;">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <th style="text-align: left;">UBER</th>
                                <td style="text-align: right;">{{ $total_earnings_uber }}&euro;</td>
                                <td style="text-align: right;">{{ $total_tips_uber }}&euro;</td>
                                <td style="text-align: right;">{{ number_format($uber_net, 2, '.', '') }}&euro;</td>
                            </tr>
                            <tr>
                                <th style="text-align: left;">BOLT</th>
                                <td style="text-align: right;">{{ $total_earnings_bolt }}&euro;</td>
                                <td style="text-align: right;">{{ $total_tips_bolt }}&euro;</td>
                                <td style="text-align: right;">{{ number_format($bolt_net, 2, '.', '') }}&euro;</td>
                            </tr>
                            <tr>
                                <th style="text-align: left;">Total</th>
                                <th style="text-align: right;">{{ number_format(((float)$total_earnings_uber + (float)$total_earnings_bolt), 2, '.', '') }}&euro;</th>
                                <th style="text-align: right;">{{ number_format($total_gross, 2, '.', '') }}&euro;</th>
                                <th style="text-align: right;">{{ number_format($total_net, 2, '.', '') }}&euro;</th>
                            </tr>
                            <tr>
                                <th style="text-align: left;">IVA</th>
                                <td></td>
                                <td style="text-align: right;">- {{ number_format($vat_value, 2, '.', '') }}&euro;</td>
                                <td></td>
                            </tr>
                            <tr>
                                <th style="text-align: left;">Total após IVA</th>
                                <td></td>
                                <td></td>
                                <th style="text-align: right;">{{ number_format($total_after_vat, 2, '.', '') }}&euro;</th>
                            </tr>
                        </tbody>
                    </table>

                    <table class="bordered" style="margin-top: 20px;">
                        <thead>
                            <tr>
                                <th style="text-transform: uppercase; text-align: left" colspan="3">
                                    Abastecimento
                                    <small style="float: right">Total: {{ number_format($fuel_transactions, 2, '.', '') }}&euro;</small>
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <th></th>
                                <th style="text-align: right;">Quantidade</th>
                                <th style="text-align: right;">Custo</th>
                            </tr>
                            @if ($electric_expenses)
                            <tr>
                                <th style="text-align: left;">Elétrico</th>
                                <td style="text-align: right;">{{ $electric_expenses['amount'] }}</td>
                                <td style="text-align: right;">{{ $electric_expenses['total'] }}</td>
                            </tr>
                            @endif
                            @if ($combustion_expenses)
                            <tr>
                                <th style="text-align: left;">Combustível</th>
                                <td style="text-align: right;">{{ $combustion_expenses['amount'] }}</td>
                                <td style="text-align: right;">{{ $combustion_expenses['total'] }}</td>
                            </tr>
                            @endif
                        </tbody>
                    </table>
                </td>

                <td style="vertical-align: top; width: 50%;">
                    <table class="bordered">
                        <thead>
                            <tr>
                                <th colspan="3" style="text-align: left; text-transform: uppercase;">Cálculo</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <th style="text-align: left;">Total após IVA</th>
                                <td></td>
                                <td style="text-align: right;">{{ number_format($total_after_vat, 2, '.', '') }}&euro;</td>
                            </tr>
                            <tr>
                                <th style="text-align: left;">Abastecimento</th>
                                <td style="text-align: right;">-</td>
                                <td style="text-align: right;">{{ number_format($fuel_transactions, 2, '.', '') }}&euro;</td>
                            </tr>
                            <tr>
                                <th style="text-align: left;">Ajustamentos</th>
                                <td style="text-align: right;">{{ $adjustments_total >= 0 ? '+' : '-' }}</td>
                                <td style="text-align: right;">{{ number_format(abs($adjustments_total), 2, '.', '') }}&euro;</td>
                            </tr>
                            <tr>
                                <th style="text-align: left;">Car Track</th>
                                <td style="text-align: right;">-</td>
                                <td style="text-align: right;">{{ number_format($car_track, 2, '.', '') }}&euro;</td>
                            </tr>
                            <tr>
                                <th style="text-align: left;">Aluguer</th>
                                <td style="text-align: right;">-</td>
                                <td style="text-align: right;">{{ number_format($car_hire, 2, '.', '') }}&euro;</td>
                            </tr>
                            <tr>
                                <th style="text-align: left;">Despesa empresa</th>
                                <td style="text-align: right;">-</td>
                                <td style="text-align: right;">{{ number_format($company_expense, 2, '.', '') }}&euro;</td>
                            </tr>
                            <tr>
                                <th style="text-align: left;">Valor a pagar</th>
                                <th colspan="2" style="text-align: right;">{{ number_format($total_to_pay, 2, '.', '') }}&euro;</th>
                            </tr>
                        </tbody>
                    </table>

                    <table class="bordered" style="margin-top: 20px;">
                        <tbody>
                            <tr>
                                <td style="text-align: center; background: #eeeeee;">
                                    <h2>Valor a pagar: {{ number_format($total_to_pay, 2, '.', '') }}&euro;</h2>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </td>
            </tr>
        </tbody>
    </table>

    <table>
        <tr>
            <td style="vertical-align: top;">
                <table class="bordered">
                    <thead>
                        <tr>
                            <th style="text-align: left; text-transform: uppercase;">Origem dos ganhos</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>
                                <img src="{{ $chart2 }}" style="width: 100%">
                            </td>
                        </tr>
                    </tbody>
                </table>
            </td>
            <td style="vertical-align: top; width: 66%;">
                <table class="bordered">
                    <thead>
                        <tr style="text-align: left; text-transform: uppercase;">
                            <th>Ranking de faturação semanal por motoristas</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><img src="{{ $chart1 }}" style="width: 100%"></td>
                        </tr>
                    </tbody>
                </table>
            </td>
        </tr>
    </table>

    <footer>
        Tribos&Montanhas &copy;
        <?php echo date("Y");?>
    </footer>
</body>

</html>
