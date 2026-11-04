<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductImage;
use App\Models\Setting;
use App\Public\PublicProduct;
use App\Support\WhatsApp;
use Illuminate\View\View;

class PageController extends Controller
{
    public function home(): View
    {
        $rate = Setting::usdRate();
        $featured = Product::visible()->with(['images', 'category'])
            ->orderByDesc('is_featured')->orderBy('code')->limit(8)->get()
            ->map(fn (Product $p) => PublicProduct::fromModel($p, $rate));

        return view('home', [
            'featured' => $featured,
            'homeText' => Setting::get('home_text'),
            'whatsappUrl' => WhatsApp::url('Hola, Rodamientos GDM. Necesito ayuda para ubicar una pieza.'),
        ]);
    }

    public function guide(): View
    {
        return view('guide', [
            'whatsappUrl' => WhatsApp::url('Hola, Rodamientos GDM. Necesito ayuda para identificar una pieza.'),
        ]);
    }

    public function company(): View
    {
        return view('company', [
            'rif' => Setting::get('rif'),
            'address' => Setting::get('address'),
            'hours' => Setting::get('hours'),
            'paymentMethods' => Setting::get('payment_methods'),
            'city' => Setting::get('city', 'Valle de la Pascua, Guárico'),
            'phone' => Setting::get('whatsapp_number', '584129477195'),
            'instagram' => Setting::get('instagram'),
            'whatsappUrl' => WhatsApp::url('Hola, Rodamientos GDM. Quiero hacer una consulta.'),
        ]);
    }

    public function credits(): View
    {
        $products = ProductImage::with('product')->whereNotNull('credit_author')->get()
            ->map(fn (ProductImage $i) => [
                'label' => 'Foto de '.$i->product->code,
                'author' => $i->credit_author,
                'license' => $i->credit_license,
                'url' => $i->credit_url,
            ]);

        return view('credits', ['credits' => collect((require database_path('data/images.php'))['site'])->concat($products)]);
    }
}
