// Aggiorna la funzione che gestisce il caricamento dei charging points
function updateChargingPoints(stationId) {
    const chargingPointSelect = document.getElementById('charging-point-id');
    if (!chargingPointSelect) return;

    // Clear current charging points
    chargingPointSelect.innerHTML = '<option value="">Select a charging point</option>';
    chargingPointSelect.disabled = true;

    if (!stationId) return;

    // Show loading state
    const loadingElement = document.createElement('div');
    loadingElement.className = 'loading-indicator';
    loadingElement.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Loading charging points...';
    const form = document.getElementById('booking-form');
    if (form) {
        form.insertBefore(loadingElement, chargingPointSelect.parentNode.nextSibling);
    }

    // Simulate API call with mock data
    setTimeout(() => {
        // Remove loading indicator
        loadingElement.remove();

        // Mock data for charging points
        const mockChargingPoints = [
            { charging_point_id: 1, slots_num: 2 },
            { charging_point_id: 2, slots_num: 2 },
            { charging_point_id: 3, slots_num: 2 },
            { charging_point_id: 4, slots_num: 2 },
            { charging_point_id: 5, slots_num: 2 }
        ];

        // Populate charging points dropdown
        mockChargingPoints.forEach(point => {
            const option = document.createElement('option');
            option.value = point.charging_point_id;
            option.textContent = `Point #${point.charging_point_id} (${point.slots_num} slots)`;
            chargingPointSelect.appendChild(option);
        });

        chargingPointSelect.disabled = false;
    }, 1000);
}

// Aggiorna l'event listener per il cambio della stazione
document.addEventListener('DOMContentLoaded', function() {
    const stationSelect = document.getElementById('station-id');
    if (stationSelect) {
        stationSelect.addEventListener('change', function() {
            updateChargingPoints(this.value);
        });

        // Trigger change event if station is pre-selected
        if (stationSelect.value) {
            updateChargingPoints(stationSelect.value);
        }
    }
});