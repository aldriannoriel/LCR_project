<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\ReportAnalyticsService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use League\Csv\Writer;
use SplTempFileObject;

class ReportController extends Controller
{
    private function filters(Request $request): array
    {
        return $request->validate(['date_from' => ['nullable', 'date'], 'date_to' => ['nullable', 'date', 'after_or_equal:date_from'], 'region' => ['nullable', 'string'], 'hub_id' => ['nullable', 'integer', 'exists:hubs,id']]);
    }

    public function analytics(Request $request, ReportAnalyticsService $service)
    {
        $this->requireAnyRole($request, ['Admin', 'Super Admin', 'Hub Manager', 'Dispatcher']);

        return response()->json($service->analytics($this->filters($request)));
    }

    public function csv(Request $request, ReportAnalyticsService $service)
    {
        $this->requireAnyRole($request, ['Admin', 'Super Admin', 'Hub Manager', 'Dispatcher']);

        $writer = Writer::createFromFileObject(new SplTempFileObject());
        $writer->insertOne(['AWB', 'Status', 'Hub', 'Created', 'Sorting Hours', 'Final Mile Hours', 'SLA Status']);
        foreach ($service->query($this->filters($request))->get() as $order) { $sla = $service->sla($order); $writer->insertOne([$order->awb_number, $order->status, $order->currentHub?->name ?? $order->hub?->name, $order->created_at->toDateTimeString(), $sla['sorting_hours'], $sla['final_mile_hours'], $sla['status']]); }
        return response()->streamDownload(fn () => print $writer->toString(), 'logistics-report.csv', ['Content-Type' => 'text/csv']);
    }

    public function pdf(Request $request, ReportAnalyticsService $service)
    {
        $this->requireAnyRole($request, ['Admin', 'Super Admin', 'Hub Manager', 'Dispatcher']);

        $filters = $this->filters($request); return Pdf::loadView('reports.summary', ['analytics' => $service->analytics($filters), 'filters' => $filters])->setPaper('a4')->stream('logistics-report.pdf');
    }
}