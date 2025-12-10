document.addEventListener('DOMContentLoaded', function() {
    var selectRequest = document.getElementById('select-request');
    var providerInput = document.getElementById('provider-name');
    var providerIdInput = document.getElementById('provider-id');
    var salesRepInput = document.getElementById('sales-rep-name');
    var salesRepIdInput = document.getElementById('sales-rep-id');

    if (selectRequest) {
        selectRequest.addEventListener('change', function() {
            var selected = this.options[this.selectedIndex];
            // Comercial
            if (salesRepInput) salesRepInput.value = selected.getAttribute('data-sales-rep') || '';
            if (salesRepIdInput) salesRepIdInput.value = selected.getAttribute('data-sales-rep-id') || '';
            // Proveedor
            if (providerInput) providerInput.value = selected.getAttribute('data-provider-name') || '';
            if (providerIdInput) providerIdInput.value = selected.getAttribute('data-provider-id') || '';
        });
    }
});
