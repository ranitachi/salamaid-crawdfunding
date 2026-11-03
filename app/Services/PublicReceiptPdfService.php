<?php

namespace App\Services;

use Illuminate\Support\Str;
use Modules\Donation\Entities\Donation;
use Symfony\Component\Process\Process;

class PublicReceiptPdfService
{
    /** @return array{contents:string,filename:string} */
    public function generate(Donation $donation): array
    {
        $html = view('public.receipt-pdf', ['donation' => $donation])->render();
        $process = new Process([config('services.pdf.wkhtmltopdf_binary', 'wkhtmltopdf'), '--quiet', '-', '-']);
        $process->setInput($html);
        $process->setTimeout(30);
        $process->mustRun();

        $campaign = Str::slug($donation->campaign?->title ?: 'salamaid');

        return [
            'contents' => $process->getOutput(),
            'filename' => 'receipt-'.$campaign.'-'.Str::lower(Str::substr((string) $donation->id, 0, 8)).'.pdf',
        ];
    }
}
