<?php

namespace App\Jobs;

use App\Models\TransferManifest;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;

class GenerateManifestPdfJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, SerializesModels;
    public function __construct(public int $manifestId) {}
    public function handle(): void
    {
        $manifest = TransferManifest::with(['originHub', 'destinationHub', 'orders'])->findOrFail($this->manifestId);
        $pdf = Pdf::loadView('manifests.transfer', ['manifest' => $manifest, 'orders' => $manifest->orders])->setPaper('a4');
        Storage::disk('local')->put('manifests/'.$manifest->manifest_number.'.pdf', $pdf->output());
    }
}