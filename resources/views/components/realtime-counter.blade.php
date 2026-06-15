@props([
    'module' => 'default',
    'field' => 'total_count',
    'filters' => [],
    'enabled' => true,
    'prefix' => '',
    'suffix' => '',
    'class' => 'badge bg-primary'
])

<div 
    data-realtime-module="{{ $module }}"
    data-realtime-update="count"
    data-realtime-field="{{ $field }}"
    @if($enabled) data-realtime-enabled="true" @endif
    class="realtime-counter {{ $class }}"
>
    {{ $prefix }}{{ $slot }}{{ $suffix }}
</div>

@if($enabled)
<script>
document.addEventListener('DOMContentLoaded', function() {
    if (window.realTimeDataService) {
        const filters = @json($filters);
        
        // Add data change listener for this counter
        window.realTimeDataService.addDataChangeListener('{{ $module }}', function(data) {
            const counter = document.querySelector(`[data-realtime-module="{{ $module }}"]`);
            if (counter) {
                const field = counter.dataset.realtimeField || 'total_count';
                const count = data[field] || (data.data ? data.data.length : 0);
                const prefix = '{{ $prefix }}';
                const suffix = '{{ $suffix }}';
                
                counter.textContent = prefix + count + suffix;
                
                // Add animation class
                counter.classList.add('counter-updated');
                setTimeout(() => {
                    counter.classList.remove('counter-updated');
                }, 1000);
            }
        });
        
        // Register module with custom filters if provided
        if (Object.keys(filters).length > 0) {
            window.realTimeDataService.registerModule('{{ $module }}', {
                filters: filters,
                enabled: true
            });
        }
    }
});
</script>

<style>
.counter-updated {
    animation: counterPulse 0.5s ease-in-out;
}

@keyframes counterPulse {
    0% { transform: scale(1); }
    50% { transform: scale(1.1); }
    100% { transform: scale(1); }
}
</style>
@endif
