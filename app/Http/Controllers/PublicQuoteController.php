<?php

namespace App\Http\Controllers;

use App\Models\Quote;
use App\Models\Setting;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;
use Illuminate\View\View;

class PublicQuoteController extends Controller
{
    public function show(string $token): View
    {
        return view('quotes.show', $this->data($token));
    }

    public function pdf(string $token): Response
    {
        $data = $this->data($token);

        return Pdf::loadView('quotes.pdf', $data)->download($data['quote']->number.'.pdf');
    }

    private function data(string $token): array
    {
        return [
            'quote' => Quote::where('public_token', $token)->with(['customer', 'items'])->firstOrFail(),
            'business' => Setting::get('business_name', 'Rodamientos GDM'),
            'city' => Setting::get('city', 'Valle de la Pascua, Guárico'),
            'rif' => Setting::get('rif'),
        ];
    }
}
