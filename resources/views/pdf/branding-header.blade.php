{{--
    Shared PDF branding header for dompdf templates.
    Expects $branding array from SettingsService::getBrandingSettings() or BrandingHelper::getPdfBrandingData().
    Optional: $logo_full_path, $branch, $documentTitle, $documentNumber, $documentDate
--}}
@php
    $branding = $branding ?? [];
    $branch = $branch ?? null;

    $hospitalName = $branding['platform_name']
        ?? $branding['business_name']
        ?? config('app.name', 'Hospital');

    $tagline = $branding['tagline']
        ?? $branding['business_name']
        ?? '';

    if ($tagline === $hospitalName) {
        $tagline = '';
    }

    $businessAddress = $branding['business_address'] ?? ($branch->address ?? '');
    $businessPhone = $branding['business_phone'] ?? ($branch->phone ?? '');
    $businessEmail = $branding['business_email'] ?? ($branch->email ?? '');
    $businessWebsite = $branding['business_website'] ?? '';
    $primaryColor = $branding['primary_color'] ?? '#2c5aa0';

    $logoSrc = \App\Services\CrossPlatformService::resolvePdfLogoSrc(
        $branding,
        $logo_full_path ?? null
    );
@endphp

<div class="pdf-branding-header">
    @if($logoSrc)
        <div class="pdf-branding-logo">
            <img src="{{ $logoSrc }}" alt="{{ $hospitalName }}" class="pdf-branding-logo-img" />
        </div>
    @endif

    <div class="pdf-branding-name">{{ $hospitalName }}</div>

    @if($tagline)
        <div class="pdf-branding-tagline">{{ $tagline }}</div>
    @endif

    <div class="pdf-branding-details">
        @if($businessAddress)
            <div>{{ $businessAddress }}</div>
        @endif
        <div>
            @if($businessPhone)
                <strong>Tel:</strong> {{ $businessPhone }}
            @endif
            @if($businessEmail)
                @if($businessPhone) | @endif
                <strong>Email:</strong> {{ $businessEmail }}
            @endif
            @if($businessWebsite)
                @if($businessPhone || $businessEmail)<br>@endif
                <strong>Web:</strong> {{ $businessWebsite }}
            @endif
        </div>
    </div>

    @if(!empty($documentTitle) || !empty($documentNumber) || !empty($documentDate))
        <div class="pdf-branding-doc-meta">
            @if(!empty($documentTitle))
                <div class="pdf-branding-doc-title">{{ $documentTitle }}</div>
            @endif
            @if(!empty($documentNumber))
                <div><strong>No:</strong> {{ $documentNumber }}</div>
            @endif
            @if(!empty($documentDate))
                <div><strong>Date:</strong> {{ $documentDate }}</div>
            @endif
        </div>
    @endif
</div>

<style>
    .pdf-branding-header {
        text-align: center;
        border-bottom: 3px solid {{ $primaryColor }};
        padding-bottom: 12px;
        margin-bottom: 15px;
        width: 100%;
    }

    .pdf-branding-logo {
        margin-bottom: 8px;
    }

    .pdf-branding-logo-img {
        height: 55px;
        max-width: 200px;
        object-fit: contain;
        display: block;
        margin: 0 auto;
    }

    .pdf-branding-name {
        font-size: 22pt;
        font-weight: bold;
        color: {{ $primaryColor }};
        margin: 6px 0 4px 0;
        line-height: 1.2;
    }

    .pdf-branding-tagline {
        font-size: 10pt;
        color: #666;
        font-style: italic;
        margin-bottom: 6px;
    }

    .pdf-branding-details {
        font-size: 9pt;
        color: #555;
        line-height: 1.4;
    }

    .pdf-branding-doc-meta {
        margin-top: 10px;
        font-size: 9pt;
        color: #666;
    }

    .pdf-branding-doc-title {
        font-size: 11pt;
        font-weight: bold;
        color: {{ $primaryColor }};
        margin-bottom: 4px;
    }
</style>
