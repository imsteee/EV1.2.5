<?php
/**
 * Database connection configuration file
 * 
 * This file handles the database connection and provides a reusable
 * database connection object throughout the application.
 */

// Database configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'ev_charging_db');

/**
 * Initialize database if it doesn't exist
 */
function initializeDatabase() {
    // Connect without database selected
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS);
    
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
    
    // Check if database exists
    $result = $conn->query("SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = '" . DB_NAME . "'");
    
    if ($result->num_rows === 0) {
        // Create database
        $conn->query("CREATE DATABASE IF NOT EXISTS " . DB_NAME);
        
        // Select the database
        $conn->select_db(DB_NAME);
        
        // Import schema
        $sql = file_get_contents(dirname(__DIR__) . '/supabase/migrations/schema.sql');
        
        // Split SQL into individual statements
        $statements = array_filter(array_map('trim', explode(';', $sql)));
        
        // Execute each statement
        foreach ($statements as $statement) {
            if (!empty($statement)) {
                $conn->query($statement);
            }
        }
        
        $conn->close();
        return true;
    }
    
    $conn->close();
    return false;
}

/**
 * Get database connection
 * 
 * @return mysqli Database connection object
 */
function getDbConnection() {
    static $conn;

    if ($conn === null) {
        // Initialize database if needed
        initializeDatabase();

        // Create connection
        $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

        // Check connection
        if ($conn->connect_error) {
            die("Connection failed: " . $conn->connect_error);
        }

        // Set charset to utf8
        $conn->set_charset("utf8");
    }

    return $conn;
}

/**
 * Execute a query and return the result
 * 
 * @param string $sql SQL query
 * @param array $params Parameters for prepared statement
 * @return mysqli_result|bool Query result
 */
function executeQuery($sql, $params = []) {
    $conn = getDbConnection();

    $stmt = $conn->prepare($sql);

    if ($stmt === false) {
        die("Error preparing statement: " . $conn->error);
    }

    if (!empty($params)) {
        $types = '';
        $bindParams = [];

        // Build types string
        foreach ($params as $param) {
            if (is_int($param)) {
                $types .= 'i';
            } elseif (is_float($param)) {
                $types .= 'd';
            } elseif (is_string($param)) {
                $types .= 's';
            } else {
                $types .= 'b';
            }
            $bindParams[] = $param;
        }

        // Create array of references
        $bindParams = array_merge([$types], $bindParams);
        $refs = [];
        foreach ($bindParams as $key => $value) {
            $refs[$key] = &$bindParams[$key];
        }

        call_user_func_array([$stmt, 'bind_param'], $refs);
    }

    $stmt->execute();
    $result = $stmt->get_result();

    return $result;
}

/**
 * Fetch all rows from a query result
 * 
 * @param string $sql SQL query
 * @param array $params Parameters for prepared statement
 * @return array Array of result rows
 */
function fetchAll($sql, $params = []) {
    $result = executeQuery($sql, $params);
    $rows = [];
    
    while ($row = $result->fetch_assoc()) {
        $rows[] = $row;
    }
    
    return $rows;
}

/**
 * Fetch a single row from a query result
 * 
 * @param string $sql SQL query
 * @param array $params Parameters for prepared statement
 * @return array|null Result row or null if no results
 */
function fetchOne($sql, $params = []) {
    $result = executeQuery($sql, $params);

    return $result->fetch_assoc();
}

/**
 * Insert data into a table
 * 
 * @param string $table Table name
 * @param array $data Associative array of column => value
 * @return int|bool Last insert ID or false on failure
 */
function insert($table, $data) {
    $conn = getDbConnection();

    $columns = array_keys($data);
    $values = array_values($data);
    $placeholders = array_fill(0, count($values), '?');

    $sql = "INSERT INTO $table (" . implode(', ', $columns) . ") 
            VALUES (" . implode(', ', $placeholders) . ")";

    $result = executeQuery($sql, $values);

    if ($result) {
        return $conn->insert_id;
    }

    return false;
}

/**
 * Update data in a table
 * 
 * @param string $table Table name
 * @param array $data Associative array of column => value
 * @param string $where Where clause
 * @param array $whereParams Parameters for where clause
 * @return bool True on success, false on failure
 */
function update($table, $data, $where, $whereParams = []) {
    $sets = [];
    $values = [];
    
    foreach ($data as $column => $value) {
        $sets[] = "$column = ?";
        $values[] = $value;
    }
    
    $sql = "UPDATE $table SET " . implode(', ', $sets) . " WHERE $where";
    
    $params = array_merge($values, $whereParams);
    $result = executeQuery($sql, $params);
    
    return $result !== false;
}

/**
 * Delete data from a table
 * 
 * @param string $table Table name
 * @param string $where Where clause
 * @param array $params Parameters for where clause
 * @return bool True on success, false on failure
 */
function delete($table, $where, $params = []) {
    $sql = "DELETE FROM $table WHERE $where";
    
    $result = executeQuery($sql, $params);
    
    return $result !== false;
}