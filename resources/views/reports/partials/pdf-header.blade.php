{{-- Reusable PDF header — delegates to shared branding partial --}}
@include('pdf.branding-header', [
    'documentTitle' => $documentTitle ?? null,
    'documentNumber' => $documentNumber ?? null,
    'documentDate' => $documentDate ?? null,
])
