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
    try {
        // Check if the charging point exists and is available
        $point = fetchOne(
            "SELECT charging_point_id, charging_point_state FROM Charging_Points WHERE charging_point_id = ?",
            [$chargingPointId]
        );

        if (!$point || $point['charging_point_state'] !== 'available') {
            return false;
        }

        // Check for existing bookings at the same time
        $existingBooking = fetchOne(
            "SELECT booking_id FROM Bookings 
            WHERE charging_point_id = ? AND booking_datetime = ?",
            [$chargingPointId, $bookingDatetime]
        );

        if ($existingBooking) {
            return false;
        }

        // Insert booking data
        $bookingData = [
            'user_id' => $userId,
            'charging_point_id' => $chargingPointId,
            'booking_datetime' => $bookingDatetime
        ];

        $conn = getDbConnection();
        
        // Start transaction
        $conn->begin_transaction();

        try {
            // Insert booking
            $stmt = $conn->prepare("INSERT INTO Bookings (user_id, charging_point_id, booking_datetime) VALUES (?, ?, ?)");
            $stmt->bind_param("iis", $userId, $chargingPointId, $bookingDatetime);
            $stmt->execute();
            $bookingId = $conn->insert_id;

            // Update charging point status
            $stmt = $conn->prepare("UPDATE Charging_Points SET charging_point_state = 'reserved' WHERE charging_point_id = ?");
            $stmt->bind_param("i", $chargingPointId);
            $stmt->execute();

            // Commit transaction
            $conn->commit();
            return $bookingId;
        } catch (Exception $e) {
            // Rollback on error
            $conn->rollback();
            error_log("Booking creation error: " . $e->getMessage());
            return false;
        }
    } catch (Exception $e) {
        error_log("Booking error: " . $e->getMessage());
        return false;
    }
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