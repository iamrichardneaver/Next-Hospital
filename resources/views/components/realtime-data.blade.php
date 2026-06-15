@props([
    'module' => 'default',
    'updateType' => 'replace',
    'template' => null,
    'filters' => [],
    'enabled' => true
])

<div 
    data-realtime-module="{{ $module }}"
    data-realtime-update="{{ $updateType }}"
    @if($template) data-realtime-template="{{ $template }}" @endif
    @if($enabled) data-realtime-enabled="true" @endif
    class="realtime-data-container"
>
    {{ $slot }}
</div>

@if($enabled)
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Register this component with the real-time service
    if (window.realTimeDataService) {
        const filters = @json($filters);
        
        // Add data change listener for this module
        window.realTimeDataService.addDataChangeListener('{{ $module }}', function(data) {
            console.log('Real-time data updated for module: {{ $module }}', data);
            
            // Dispatch custom event for this specific component
            const event = new CustomEvent('realtime:{{ $module }}:updated', {
                detail: { data: data, module: '{{ $module }}' }
            });
            document.dispatchEvent(event);
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
@endif
