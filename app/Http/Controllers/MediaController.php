<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Storage;
use League\Flysystem\PathTraversalDetected;
use Symfony\Component\HttpFoundation\Response;

class MediaController extends Controller
{
    /** Serves uploaded photos from the public disk, which may live on a volume outside public/. */
    public function show(string $path): Response
    {
        try {
            abort_unless(Storage::disk('public')->exists($path), 404);
        } catch (PathTraversalDetected) {
            abort(404);
        }

        return Storage::disk('public')->response($path, null, ['Cache-Control' => 'public, max-age=604800']);
    }
}
