@props([
    'module' => 'default',
    'columns' => [],
    'filters' => [],
    'enabled' => true,
    'emptyMessage' => 'No data available'
])

<div 
    data-realtime-module="{{ $module }}"
    data-realtime-update="replace"
    data-realtime-template="{{ $template ?? '' }}"
    @if($enabled) data-realtime-enabled="true" @endif
    class="realtime-table-container"
>
    <div class="table-responsive">
        <table class="table table-striped table-hover">
            @if(!empty($columns))
            <thead>
                <tr>
                    @foreach($columns as $column)
                        <th>{{ $column['label'] ?? $column }}</th>
                    @endforeach
                </tr>
            </thead>
            @endif
            <tbody data-realtime-module="{{ $module }}" data-realtime-update="replace">
                {{ $slot }}
            </tbody>
        </table>
    </div>
    
    <div class="realtime-loading d-none">
        <div class="text-center py-3">
            <div class="spinner-border spinner-border-sm" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
            <span class="ms-2">Updating data...</span>
        </div>
    </div>
    
    <div class="realtime-empty d-none">
        <div class="text-center py-4 text-muted">
            <i class="bi bi-inbox fs-1"></i>
            <p class="mt-2">{{ $emptyMessage }}</p>
        </div>
    </div>
</div>

@if($enabled)
<script>
document.addEventListener('DOMContentLoaded', function() {
    if (window.realTimeDataService) {
        const filters = @json($filters);
        
        // Add data change listener for this table
        window.realTimeDataService.addDataChangeListener('{{ $module }}', function(data) {
            const tbody = document.querySelector(`[data-realtime-module="{{ $module }}"] tbody`);
            const loading = document.querySelector(`[data-realtime-module="{{ $module }}"] .realtime-loading`);
            const empty = document.querySelector(`[data-realtime-module="{{ $module }}"] .realtime-empty`);
            
            if (loading) loading.classList.add('d-none');
            
            if (data.data && data.data.length > 0) {
                if (empty) empty.classList.add('d-none');
                
                // Update table body
                if (tbody) {
                    tbody.innerHTML = '';
                    data.data.forEach(item => {
                        const row = createTableRow(item, @json($columns));
                        tbody.appendChild(row);
                    });
                }
            } else {
                if (empty) empty.classList.remove('d-none');
                if (tbody) tbody.innerHTML = '';
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

function createTableRow(item, columns) {
    const row = document.createElement('tr');
    
    if (columns && columns.length > 0) {
        columns.forEach(column => {
            const cell = document.createElement('td');
            const key = column['key'] || column;
            const value = item[key] || '';
            
            if (column['formatter']) {
                cell.innerHTML = formatValue(value, column['formatter'], item);
            } else {
                cell.textContent = value;
            }
            
            row.appendChild(cell);
        });
    } else {
        // Default row generation
        Object.values(item).slice(0, 5).forEach(value => {
            const cell = document.createElement('td');
            cell.textContent = value || '';
            row.appendChild(cell);
        });
    }
    
    return row;
}

function formatValue(value, formatter, item) {
    switch (formatter) {
        case 'date':
            return new Date(value).toLocaleDateString();
        case 'datetime':
            return new Date(value).toLocaleString();
        case 'currency':
            return new Intl.NumberFormat('en-GH', { 
                style: 'currency', 
                currency: 'GHS' 
            }).format(value);
        case 'badge':
            return `<span class="badge bg-${getBadgeClass(value)}">${value}</span>`;
        case 'status':
            return `<span class="badge bg-${getStatusClass(value)}">${value}</span>`;
        default:
            return value;
    }
}

function getBadgeClass(value) {
    const classes = {
        'active': 'success',
        'inactive': 'secondary',
        'pending': 'warning',
        'completed': 'success',
        'cancelled': 'danger'
    };
    return classes[value?.toLowerCase()] || 'primary';
}

function getStatusClass(value) {
    const classes = {
        'scheduled': 'info',
        'completed': 'success',
        'cancelled': 'danger',
        'no-show': 'warning',
        'in-progress': 'primary'
    };
    return classes[value?.toLowerCase()] || 'secondary';
}
</script>
@endif
