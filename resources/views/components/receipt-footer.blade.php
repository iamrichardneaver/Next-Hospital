@props(['showTimestamp' => true, 'customMessage' => null])

<div class="footer">
    <p>Thank you for choosing {{ $branding['business_name'] ?? $settings['hospital_name'] ?? ($hospitalBranding['name'] ?? 'Hospital') }}!</p>
    @if($customMessage)
        <p>{{ $customMessage }}</p>
    @else
        <p>This is a computer-generated receipt.</p>
    @endif
    @if($showTimestamp)
        <p>Generated on: {{ now()->format('d/m/Y H:i:s') }}</p>
    @endif
</div>
