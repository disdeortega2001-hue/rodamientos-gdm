<?php

namespace App\Http\Middleware;

use App\Models\Setting;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\Response;

class PortalVisibility
{
    /** Staff screens and links already handed to customers stay reachable while the portal is closed. */
    private const ALWAYS_OPEN = ['panel', 'panel/*', 'nota/*', 'cotizacion/*', 'media/*'];

    private const PREVIEW_COOKIE = 'gdm_vista';

    private const PREVIEW_DAYS = 30;

    public function handle(Request $request, Closure $next): Response
    {
        if (! config('gdm.portal_private') || $request->is(self::ALWAYS_OPEN) || $request->user() || Setting::get('portal_public') === '1') {
            return $next($request);
        }

        if ($this->validPreviewCode($request)) {
            return redirect($request->url())->cookie(self::PREVIEW_COOKIE, '1', self::PREVIEW_DAYS * 24 * 60);
        }

        if ($request->cookie(self::PREVIEW_COOKIE) === '1' && config('gdm.preview_code_hash')) {
            return $this->preview($request, $next);
        }

        if (! $request->isMethod('GET') && ! $request->isMethod('HEAD')) {
            abort(403);
        }

        return response()->view('coming-soon')->header('X-Robots-Tag', 'noindex, nofollow');
    }

    private function preview(Request $request, Closure $next): Response
    {
        if ($request->is('pedido')) {
            return response()->json(['errors' => ['portal' => ['Esta es una vista previa: todavía no se reciben pedidos.']]], 422);
        }

        View::share('previewMode', true);

        $response = $next($request);
        $response->headers->set('X-Robots-Tag', 'noindex, nofollow');

        return $response;
    }

    private function validPreviewCode(Request $request): bool
    {
        $code = $request->query('vista');
        $hash = config('gdm.preview_code_hash');

        return is_string($code) && $hash && hash_equals($hash, hash('sha256', $code));
    }
}
