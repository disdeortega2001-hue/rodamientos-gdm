<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Setting;
use App\Support\WhatsApp;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;
use Illuminate\View\View;

class DeliveryNoteController extends Controller
{
    public function show(string $token): View
    {
        return view('notes.show', $this->data($token));
    }

    public function pdf(string $token): Response
    {
        $data = $this->data($token);

        return Pdf::loadView('notes.pdf', $data)->download($data['order']->delivery_note_number.'.pdf');
    }

    private function data(string $token): array
    {
        return [
            'order' => Order::where('delivery_note_token', $token)->with(['customer', 'items', 'payments'])->firstOrFail(),
            'business' => Setting::get('business_name', 'Rodamientos GDM'),
            'city' => Setting::get('city', 'Valle de la Pascua, Guárico'),
            'rif' => Setting::get('rif'),
            'address' => Setting::get('address'),
            'phone' => Setting::get('whatsapp_number', '584129477195'),
        ];
    }
}
