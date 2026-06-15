@props(['platform' => 'web', 'size' => 'sm'])

@php
    $platformConfig = [
        'mobile' => [
            'icon' => 'fas fa-mobile-alt',
            'label' => 'Mobile App',
            'class' => 'badge-info',
            'color' => '#17a2b8'
        ],
        'web' => [
            'icon' => 'fas fa-desktop',
            'label' => 'Web Portal',
            'class' => 'badge-success',
            'color' => '#28a745'
        ],
        'api' => [
            'icon' => 'fas fa-code',
            'label' => 'API',
            'class' => 'badge-primary',
            'color' => '#007bff'
        ],
        'webhook' => [
            'icon' => 'fas fa-exchange-alt',
            'label' => 'Webhook',
            'class' => 'badge-warning',
            'color' => '#ffc107'
        ],
        'system' => [
            'icon' => 'fas fa-cog',
            'label' => 'System',
            'class' => 'badge-secondary',
            'color' => '#6c757d'
        ]
    ];
    
    $config = $platformConfig[strtolower($platform)] ?? $platformConfig['web'];
    $badgeSize = $size === 'lg' ? 'badge-lg' : '';
@endphp

<span class="badge {{ $config['class'] }} {{ $badgeSize }}" 
      title="Payment initiated from {{ $config['label'] }}"
      style="font-size: 0.75rem;">
    <i class="{{ $config['icon'] }}"></i>
    {{ $config['label'] }}
</span>

