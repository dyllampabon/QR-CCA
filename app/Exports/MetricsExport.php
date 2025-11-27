<?php

namespace App\Exports;

use App\Models\ScanMetric;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class MetricsExport implements FromCollection, WithHeadings
{
    protected $from;
    protected $to;
    protected $merchantExternalId;

    public function __construct($from, $to, $merchantExternalId = null)
    {
        $this->from = $from;
        $this->to = $to;
        $this->merchantExternalId = $merchantExternalId;
    }

    public function collection()
    {
        $query = ScanMetric::whereBetween('created_at', [$this->from, $this->to]);

        if ($this->merchantExternalId) {
            $query->where('merchant_external_id', $this->merchantExternalId);
        }

        return $query->orderBy('created_at', 'desc')->get()->map(function ($metric) {
            return [
                'Fecha' => $metric->created_at->format('Y-m-d H:i:s'),
                'Comerciante' => $metric->merchant->rzsocial ?? $metric->merchant_external_id,
                'Comprador' => $metric->buyer_external_id,
                'Monto de Compra' => $metric->purchase_amount,
                'Porcentaje de Descuento' => $metric->discount_percent . '%',
                'Valor de Descuento' => $metric->discount_value,
                'IP' => $metric->ip,
                'Usuario Agente' => $metric->user_agent,
                'Dispositivo' => $metric->device,
                'Referencia' => $metric->referer,
            ];
        });
    }

    public function headings(): array
    {
        return [
            'Fecha',
            'Comerciante',
            'Comprador',
            'Monto de Compra',
            'Porcentaje de Descuento',
            'Valor de Descuento',
            'IP',
            'Usuario Agente',
            'Dispositivo',
            'Referencia',
        ];
    }
}
