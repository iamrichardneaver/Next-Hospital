<footer class="py-4 px-4 border-top" style="background: #ffffff;">
    <div class="row">
        <div class="col-md-6 text-center text-md-start">
            <small class="text-secondary">
                © {{ date('Y') }} {{ $hospitalBranding['name'] ?? 'Hospital' }}. All rights reserved.
            </small>
        </div>
        <div class="col-md-6 text-center text-md-end">
            <small class="text-secondary">
                Version 1.0 | Powered by Next Code Systems {{ app()->version() }}
            </small>
        </div>
    </div>
</footer>
