<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class BrandingFileController extends Controller
{
    /**
     * Serve branding assets (logo, favicon) via Laravel when Apache/Plesk blocks the storage symlink.
     */
    public function show(string $filename): BinaryFileResponse
    {
        if (!preg_match('/^[A-Za-z0-9._-]+$/', $filename)) {
            abort(404);
        }

        $relativePath = 'branding/' . $filename;

        if (!Storage::disk('public')->exists($relativePath)) {
            abort(404);
        }

        $fullPath = Storage::disk('public')->path($relativePath);

        if (!is_readable($fullPath)) {
            abort(403, 'Branding file is not accessible.');
        }

        $mimeType = Storage::disk('public')->mimeType($relativePath) ?: 'application/octet-stream';

        return response()->file($fullPath, [
            'Content-Type' => $mimeType,
            'Content-Disposition' => 'inline; filename="' . $filename . '"',
            'Cache-Control' => 'public, max-age=31536000, immutable',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
