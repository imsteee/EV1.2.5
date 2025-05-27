/**
 * Bookings JavaScript file
 * Handles booking-related functionality
 */

document.addEventListener('DOMContentLoaded', function() {
    initializeBookingForm();
});

/**
 * Initialize the booking form
 */
function initializeBookingForm() {
    const bookingForm = document.getElementById('booking-form');
    if (!bookingForm) return;

    const stationSelect = document.getElementById('station-id');
    const chargingPointSelect = document.getElementById('charging-point-id');
    const dateInput = document.getElementById('booking-date');
    const startTimeInput = document.getElementById('start-time');
    const endTimeInput = document.getElementById('end-time');
    const submitButton = document.querySelector('#booking-form button[type="submit"]');

    if (stationSelect) {
        stationSelect.addEventListener('change', function() {
            const stationId = this.value;
            updateChargingPoints(stationId);
        });

        // Trigger change event if station is pre-selected
        if (stationSelect.value) {
            updateChargingPoints(stationSelect.value);
        }
    }

    // Handle form submission
    if (bookingForm) {
        bookingForm.addEventListener('submit', function(e) {
            e.preventDefault();

            // Validate all required fields
            const requiredFields = [
                stationSelect,
                chargingPointSelect,
                dateInput,
                startTimeInput,
                endTimeInput
            ];

            let isValid = true;

            requiredFields.forEach(field => {
                if (!field || !field.value) {
                    isValid = false;
                    if (field) {
                        field.classList.add('is-invalid');
                    }
                } else {
                    if (field) {
                        field.classList.remove('is-invalid');
                    }
                }
            });

            if (!isValid) {
                showNotification('Please fill in all required fields.', 'error');
                return;
            }

            // Submit form
            bookingForm.submit();
        });
    }
}

/**
 * Update charging points for selected station
 */
function updateChargingPoints(stationId) {
    if (!stationId) return;

    const chargingPointSelect = document.getElementById('charging-point-id');
    if (!chargingPointSelect) return;

    // Clear current charging points
    chargingPointSelect.innerHTML = '<option value="">Select a charging point</option>';
    chargingPointSelect.disabled = true;

    // Show loading state
    const loadingElement = document.createElement('div');
    loadingElement.className = 'loading-indicator';
    loadingElement.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Loading charging points...';
    
    const form = document.getElementById('booking-form');
    if (form) {
        const existingLoading = form.querySelector('.loading-indicator');
        if (existingLoading) {
            existingLoading.remove();
        }
        form.insertBefore(loadingElement, chargingPointSelect.parentNode.nextSibling);
    }

    // Fetch charging points from the server
    fetch(`/api/stations/${stationId}/charging-points`)
        .then(response => response.json())
        .then(data => {
            loadingElement.remove();
            
            data.forEach(point => {
                const option = document.createElement('option');
                option.value = point.charging_point_id;
                option.textContent = `Point #${point.charging_point_id} (${point.slots_num} slots)`;
                chargingPointSelect.appendChild(option);
            });
            
            chargingPointSelect.disabled = false;
        })
        .catch(error => {
            console.error('Error fetching charging points:', error);
            
            // Fallback to mock data
            const mockPoints = [
                { charging_point_id: 1, slots_num: 2 },
                { charging_point_id: 2, slots_num: 2 },
                { charging_point_id: 3, slots_num: 2 }
            ];
            
            loadingElement.remove();
            
            mockPoints.forEach(point => {
                const option = document.createElement('option');
                option.value = point.charging_point_id;
                option.textContent = `Point #${point.charging_point_id} (${point.slots_num} slots)`;
                chargingPointSelect.appendChild(option);
            });
            
            chargingPointSelect.disabled = false;
        });
}

/**
 * Show a notification message
 */
function showNotification(message, type = 'info', duration = 5000) {
    const notification = document.createElement('div');
    notification.className = `notification notification-${type} slide-up`;
    notification.innerHTML = `
        <div class="notification-content">
            <div class="notification-message">${message}</div>
            <button class="notification-close">&times;</button>
        </div>
    `;
    
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.remove();
    }, duration);
}