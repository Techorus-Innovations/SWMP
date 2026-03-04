@if(new Harris21\Fuse\CircuitBreaker('sap')->isOpen())
    <div class="bg-danger-600 text-white px-4 py-2 text-center">
        External service is currently unavailable. Some features are disabled.
    </div>
@endif