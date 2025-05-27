<?php
/**
 * Booking related functions
 */

/**
 * Create a new booking
 *
 * @param int $userId User ID
 * @param int $chargingPointId Charging point ID
 * @param string $bookingDatetime Booking datetime
 * @return int|bool Booking ID on success, false on failure
 */
function createBooking($userId, $chargingPointId, $bookingDatetime) {
    // Check if the charging point is available
    if (!isChargingPointAvailable($chargingPointId, $bookingDatetime)) {
        return false;
    }

    // Insert booking data
    $bookingData = [
        'user_id' => $userId,
        'charging_point_id' => $chargingPointId,
        'booking_datetime' => $bookingDatetime
    ];

    $bookingId = insert('Bookings', $bookingData);

    if ($bookingId) {
        // Update charging point status
        update('Charging_Points',
            ['charging_point_state' => 'reserved'],
            'charging_point_id = ?',
            [$chargingPointId]
        );

        return $bookingId;
    }

    return false;
}

/**
 * Cancel a booking
 *
 * @param int $bookingId Booking ID
 * @param int $userId User ID (for verification)
 * @return bool True on success, false on failure
 */
function cancelBooking($bookingId, $userId) {
    // Verify that the booking belongs to the user
    $booking = fetchOne(
        "SELECT * FROM Bookings WHERE booking_id = ? AND user_id = ?",
        [$bookingId, $userId]
    );

    if (!$booking) {
        return false;
    }

    // Delete the booking
    $deleted = delete('Bookings', 'booking_id = ?', [$bookingId]);

    if ($deleted) {
        // Update charging point status back to available
        update('Charging_Points',
            ['charging_point_state' => 'available'],
            'charging_point_id = ?',
            [$booking['charging_point_id']]
        );

        return true;
    }

    return false;
}

/**
 * Get booking details
 *
 * @param int $bookingId Booking ID
 * @return array|null Booking details or null if not found
 */
function getBookingDetails($bookingId) {
    $sql = "SELECT b.*, cp.charging_point_state, cp.slots_num,
                  s.address_street, s.address_city, s.address_municipality,
                  u.name as user_name, u.email as user_email
            FROM Bookings b
            JOIN Charging_Points cp ON b.charging_point_id = cp.charging_point_id
            JOIN Stations s ON cp.station_id = s.station_id
            JOIN Users u ON b.user_id = u.user_id
            WHERE b.booking_id = ?";

    return fetchOne($sql, [$bookingId]);
}

/**
 * Get current and upcoming bookings for a user
 *
 * @param int $userId User ID
 * @return array Array of current and upcoming bookings
 */
function getUserUpcomingBookings($userId) {
    $currentDatetime = date('Y-m-d H:i:s');

    $sql = "SELECT b.*, cp.charging_point_state, cp.slots_num,
                  s.address_street, s.address_city
            FROM Bookings b
            JOIN Charging_Points cp ON b.charging_point_id = cp.charging_point_id
            JOIN Stations s ON cp.station_id = s.station_id
            WHERE b.user_id = ? 
            AND b.booking_datetime >= ?
            ORDER BY b.booking_datetime";

    return fetchAll($sql, [$userId, $currentDatetime]);
}

/**
 * Check if a charging point is available for booking
 *
 * @param int $chargingPointId Charging point ID
 * @param string $bookingDatetime Booking datetime
 * @return bool True if available, false if not
 */
function isChargingPointAvailable($chargingPointId, $bookingDatetime) {
    // Check charging point status
    $point = fetchOne(
        "SELECT charging_point_state FROM Charging_Points WHERE charging_point_id = ?",
        [$chargingPointId]
    );

    if (!$point || $point['charging_point_state'] !== 'available') {
        return false;
    }

    // Check for existing bookings
    $sql = "SELECT COUNT(*) as count FROM Bookings 
            WHERE charging_point_id = ? 
            AND booking_datetime = ?";

    $result = fetchOne($sql, [$chargingPointId, $bookingDatetime]);

    return $result['count'] == 0;
}

/**
 * Start a charging session
 *
 * @param int $bookingId Booking ID
 * @return int|bool Charging log ID on success, false on failure
 */
function startChargingSession($bookingId) {
    // Get booking details
    $booking = getBookingDetails($bookingId);

    if (!$booking) {
        return false;
    }

    // Create charging log
    $logData = [
        'booking_id' => $bookingId,
        'user_id' => $booking['user_id'],
        'charging_point_id' => $booking['charging_point_id'],
        'start_time' => date('Y-m-d H:i:s'),
        'status' => 'in_progress'
    ];

    return insert('Charging_Logs', $logData);
}

/**
 * End a charging session
 *
 * @param int $logsId Charging log ID
 * @param float $energyConsumed Energy consumed in kWh
 * @param float $cost Total cost
 * @return bool True on success, false on failure
 */
function endChargingSession($logsId, $energyConsumed, $cost) {
    // Update charging log
    $logData = [
        'end_time' => date('Y-m-d H:i:s'),
        'energy_consumed' => $energyConsumed,
        'cost' => $cost
    ];

    return update('Charging_Logs', $logData, 'logs_id = ?', [$logsId]);
}