@props(['type' => 'Receipt', 'showLogo' => false])

<div class="header">
    @if($showLogo && isset($branding['logo_url']) && $branding['logo_url'])
        <div class="hospital-logo">
            <img src="{{ $branding['logo_url'] }}" alt="{{ $branding['business_name'] ?? $settings['hospital_name'] ?? 'Hospital Logo' }}" style="max-height: 60px; max-width: 200px;">
        </div>
    @endif
    
    <div class="hospital-name">{{ $branding['business_name'] ?? $settings['hospital_name'] ?? ($hospitalBranding['name'] ?? 'Hospital') }}</div>
    <div class="hospital-address">
        {{ $branding['business_address'] ?? $settings['hospital_address'] ?? '123 Healthcare Street, Accra, Ghana' }}<br>
        Tel: {{ $branding['business_phone'] ?? $settings['hospital_phone'] ?? '+233 123 456 789' }}<br>
        @if(isset($branding['business_email']) || isset($settings['hospital_email']))
        Email: {{ $branding['business_email'] ?? $settings['hospital_email'] }}<br>
        @endif
        @if(isset($branding['business_website']) || isset($settings['hospital_website']))
        Website: {{ $branding['business_website'] ?? $settings['hospital_website'] }}
        @endif
    </div>
    <div class="receipt-title">{{ $type }}</div>
</div>
