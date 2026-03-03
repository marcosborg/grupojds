<!doctype html>
<html lang="pt">

<head>
    <meta charset="UTF-8" />
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title>Extrato Financeiro Semanal</title>
    <style>
        @page {
            margin: 30px 28px;
        }

        body {
            margin: 0;
            font-family: DejaVu Sans, sans-serif;
            font-size: 11px;
            color: #1f2937;
            line-height: 1.35;
        }

        h1,
        h2,
        h3,
        p {
            margin: 0;
        }

        .header {
            border-bottom: 2px solid #1f2937;
            padding-bottom: 10px;
            margin-bottom: 14px;
        }

        .header-title {
            font-size: 22px;
            font-weight: 700;
            margin-bottom: 4px;
        }

        .header-subtitle {
            font-size: 11px;
            color: #4b5563;
        }

        .meta-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 12px;
        }

        .meta-table td {
            width: 50%;
            vertical-align: top;
            padding: 8px 10px;
            border: 1px solid #d1d5db;
            background: #f9fafb;
        }

        .meta-title {
            font-size: 10px;
            text-transform: uppercase;
            color: #6b7280;
            margin-bottom: 3px;
        }

        .meta-value {
            font-size: 12px;
            font-weight: 700;
            color: #111827;
            margin-bottom: 4px;
        }

        .summary-card {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 14px;
            border: 1px solid #d1d5db;
        }

        .summary-card td {
            padding: 10px;
            vertical-align: top;
        }

        .summary-title {
            font-size: 10px;
            text-transform: uppercase;
            color: #6b7280;
        }

        .summary-value {
            font-size: 20px;
            font-weight: 700;
            margin-top: 2px;
            color: #0f172a;
        }

        .section {
            margin-bottom: 14px;
        }

        .section-title {
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            border-bottom: 1px solid #d1d5db;
            padding-bottom: 4px;
            margin-bottom: 6px;
        }

        table.data {
            width: 100%;
            border-collapse: collapse;
        }

        table.data th,
        table.data td {
            border: 1px solid #e5e7eb;
            padding: 6px 8px;
        }

        table.data th {
            background: #f3f4f6;
            font-size: 10px;
            text-transform: uppercase;
            color: #374151;
            text-align: left;
        }

        .text-right {
            text-align: right;
        }

        .text-center {
            text-align: center;
        }

        .muted {
            color: #6b7280;
        }

        .strong {
            font-weight: 700;
        }

        .badge-positive {
            color: #166534;
            font-weight: 700;
        }

        .badge-negative {
            color: #991b1b;
            font-weight: 700;
        }

        .two-col {
            width: 100%;
            border-collapse: collapse;
        }

        .two-col td {
            width: 50%;
            vertical-align: top;
            padding-right: 6px;
        }

        .two-col td:last-child {
            padding-right: 0;
            padding-left: 6px;
        }

        .footer {
            margin-top: 10px;
            border-top: 1px solid #d1d5db;
            padding-top: 6px;
            font-size: 9px;
            color: #6b7280;
        }
    </style>
</head>

<body>
    @php
        $money = static fn ($value) => number_format((float) $value, 2, ',', '.');
        $periodStart = \Carbon\Carbon::parse($tvde_week->start_date)->format('d/m/Y');
        $periodEnd = \Carbon\Carbon::parse($tvde_week->end_date)->format('d/m/Y');
    @endphp

    <div class="header">
        <div class="header-title">Extrato Financeiro Semanal</div>
        <div class="header-subtitle">Periodo {{ $periodStart }} a {{ $periodEnd }} | Documento gerado em {{ now()->format('d/m/Y H:i') }}</div>
    </div>

    <table class="meta-table">
        <tr>
            <td>
                <div class="meta-title">Empresa</div>
                <div class="meta-value">{{ $company->name }}</div>
                <div class="muted">NIF: {{ $company->vat ?: '-' }}</div>
                <div class="muted">{{ $company->address ?: '-' }} {{ $company->zip ? ', ' . $company->zip : '' }}</div>
                <div class="muted">{{ $company->location ?: '-' }}</div>
                <div class="muted">{{ $company->email ?: '-' }}</div>
            </td>
            <td>
                <div class="meta-title">Motorista</div>
                <div class="meta-value">{{ $driver->name }}</div>
                <div class="muted">{{ $driver->email ?: '-' }}</div>
                <div class="muted">{{ $driver->phone ?: '-' }}</div>
                <div class="muted">{{ $driver->brand ?: '-' }} {{ $driver->model ?: '' }}{{ $driver->license_plate ? ' (' . $driver->license_plate . ')' : '' }}</div>
                <div class="muted">NIF: {{ $driver->driver_vat ?: '-' }}</div>
            </td>
        </tr>
    </table>

    <table class="summary-card">
        <tr>
            <td>
                <div class="summary-title">Valor Semanal Sem Impostos</div>
                <div class="summary-value">{{ $money($total_to_pay) }} &euro;</div>
            </td>
            <td class="text-right">
                <div class="summary-title">Reconcilia&ccedil;&atilde;o (Cr&eacute;ditos - D&eacute;bitos)</div>
                <div class="summary-value">{{ $money($reconciled_total) }} &euro;</div>
                <div class="muted">Diferen&ccedil;a t&eacute;cnica: {{ $money($reconciliation_delta) }} &euro;</div>
            </td>
        </tr>
    </table>

    <table class="two-col">
        <tr>
            <td>
                <div class="section">
                    <div class="section-title">Atividades por Operador</div>
                    <table class="data">
                        <thead>
                            <tr>
                                <th>Operador</th>
                                <th class="text-right">Bruto</th>
                                <th class="text-right">Liquido</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>UBER</td>
                                <td class="text-right">{{ $money($uber_gross) }} &euro;</td>
                                <td class="text-right">{{ $money($uber_net) }} &euro;</td>
                            </tr>
                            <tr>
                                <td>BOLT</td>
                                <td class="text-right">{{ $money($bolt_gross) }} &euro;</td>
                                <td class="text-right">{{ $money($bolt_net) }} &euro;</td>
                            </tr>
                            <tr class="strong">
                                <td>Totais</td>
                                <td class="text-right">{{ $money($total_gross) }} &euro;</td>
                                <td class="text-right">{{ $money($total_net) }} &euro;</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="section">
                    <div class="section-title">Abastecimentos</div>
                    <table class="data">
                        <thead>
                            <tr>
                                <th>Tipo</th>
                                <th class="text-right">Quantidade</th>
                                <th class="text-right">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>Eletrico</td>
                                <td class="text-right">{{ $electric_expenses ? $electric_expenses['amount'] : '-' }}</td>
                                <td class="text-right">{{ $electric_expenses ? $money($electric_expenses['value']) : '-' }}@if($electric_expenses) &euro; @endif</td>
                            </tr>
                            <tr>
                                <td>Combustivel</td>
                                <td class="text-right">{{ $combustion_expenses ? $combustion_expenses['amount'] : '-' }}</td>
                                <td class="text-right">{{ $combustion_expenses ? $money($combustion_expenses['value']) : '-' }}@if($combustion_expenses) &euro; @endif</td>
                            </tr>
                            <tr class="strong">
                                <td>Total de abastecimentos</td>
                                <td class="text-right">-</td>
                                <td class="text-right">{{ $money($fuel_transactions) }} &euro;</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </td>

            <td>
                <div class="section">
                    <div class="section-title">Detalhe Financeiro</div>
                    <table class="data">
                        <thead>
                            <tr>
                                <th>Item</th>
                                <th class="text-center">Tipo</th>
                                <th class="text-right">Valor</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>Ganhos liquidos</td>
                                <td class="text-center"><span class="badge-positive">Credito</span></td>
                                <td class="text-right">{{ $money($total_net) }} &euro;</td>
                            </tr>
                            <tr>
                                <td>Acertos positivos</td>
                                <td class="text-center"><span class="badge-positive">Credito</span></td>
                                <td class="text-right">{{ $money($adjustments_credit) }} &euro;</td>
                            </tr>
                            <tr>
                                <td>Aluguer</td>
                                <td class="text-center"><span class="badge-negative">Debito</span></td>
                                <td class="text-right">{{ $money($car_hire) }} &euro;</td>
                            </tr>
                            <tr>
                                <td>Via Verde / Car Track</td>
                                <td class="text-center"><span class="badge-negative">Debito</span></td>
                                <td class="text-right">{{ $money($car_track) }} &euro;</td>
                            </tr>
                            <tr>
                                <td>Abastecimentos</td>
                                <td class="text-center"><span class="badge-negative">Debito</span></td>
                                <td class="text-right">{{ $money($fuel_transactions) }} &euro;</td>
                            </tr>
                            <tr>
                                <td>IVA</td>
                                <td class="text-center"><span class="badge-negative">Debito</span></td>
                                <td class="text-right">{{ $money($vat_value) }} &euro;</td>
                            </tr>
                            <tr>
                                <td>Acertos negativos</td>
                                <td class="text-center"><span class="badge-negative">Debito</span></td>
                                <td class="text-right">{{ $money($adjustments_debit) }} &euro;</td>
                            </tr>
                            <tr class="strong">
                                <td>Total de creditos</td>
                                <td class="text-center">-</td>
                                <td class="text-right">{{ $money($total_credits) }} &euro;</td>
                            </tr>
                            <tr class="strong">
                                <td>Total de debitos</td>
                                <td class="text-center">-</td>
                                <td class="text-right">{{ $money($total_debits) }} &euro;</td>
                            </tr>
                            <tr class="strong">
                                <td>Valor final semanal</td>
                                <td class="text-center">-</td>
                                <td class="text-right">{{ $money($total_to_pay) }} &euro;</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="section">
                    <div class="section-title">Saldo</div>
                    <table class="data">
                        <thead>
                            <tr>
                                <th>Descricao</th>
                                <th class="text-right">Valor</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>Saldo transitado (aprox.)</td>
                                <td class="text-right">{{ $previous_balance !== null ? $money($previous_balance) : '-' }}@if($previous_balance !== null) &euro; @endif</td>
                            </tr>
                            <tr>
                                <td>Saldo acumulado atual</td>
                                <td class="text-right">{{ $current_balance !== null ? $money($current_balance) : '-' }}@if($current_balance !== null) &euro; @endif</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </td>
        </tr>
    </table>

    <div class="footer">
        Este documento usa os mesmos valores validados e apresentados no ecr&atilde; de Extrato Financeiro.
    </div>
</body>

</html>
