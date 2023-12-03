<?php
// includes/functions.php

/**
 * Redirect to a specified URL.
 *
 * @param string $url The URL to redirect to.
 */
function redirect($url) {
    header("Location: $url");
    exit();
}

/**
 * Hash the provided password using bcrypt.
 *
 * @param string $password The password to hash.
 * @return string The hashed password.
 */
function hashPassword($password) {
    // Use password_hash with the BCRYPT algorithm
    return password_hash($password, PASSWORD_BCRYPT);
}

/**
 * Verify if a password matches its hashed version.
 *
 * @param string $password The password to verify.
 * @param string $hashedPassword The hashed password to compare against.
 * @return bool True if the password is correct, false otherwise.
 */
function verifyPassword($password, $hashedPassword) {
    // Use password_verify to check if the password matches the hashed version
    return password_verify($password, $hashedPassword);
}

// Add more functions as needed
?>
