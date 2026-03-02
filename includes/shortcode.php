<?php
/**
 * Cryptographically-safe short code generator.
 *
 * Uses random_bytes() (CSPRNG) — NOT auto-increment, NOT md5(time()).
 * Base62 alphabet: a-z A-Z 0-9
 * Collision handling: retry up to SHORT_CODE_MAX_RETRIES times.
 */

require_once __DIR__ . '/../config/db.php';

/**
 * Generate a random Base62 string of the given length.
 *
 * @param int $length
 * @return string
 */
function generate_random_code(int $length = SHORT_CODE_LENGTH): string
{
    $alphabet = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    $alphabetSize = strlen($alphabet); // 62
    $code = '';

    // Use random_bytes → unbiased modulo via rejection would be ideal,
    // but for short codes random_int per char is cleanest & unbiased.
    for ($i = 0; $i < $length; $i++) {
        $code .= $alphabet[random_int(0, $alphabetSize - 1)];
    }

    return $code;
}

/**
 * Generate a unique short code that does not collide with existing rows.
 *
 * @param int $length
 * @return string
 * @throws RuntimeException if max retries exceeded (astronomically unlikely)
 */
function generate_unique_short_code(int $length = SHORT_CODE_LENGTH): string
{
    $pdo = db();
    $stmt = $pdo->prepare('SELECT 1 FROM links WHERE short_code = ? LIMIT 1');

    for ($attempt = 0; $attempt < SHORT_CODE_MAX_RETRIES; $attempt++) {
        $code = generate_random_code($length);
        $stmt->execute([$code]);

        if ($stmt->fetchColumn() === false) {
            return $code; // unique — no collision
        }
        // collision happened → loop and retry
    }

    throw new RuntimeException(
        "Failed to generate a unique short code after " . SHORT_CODE_MAX_RETRIES . " attempts."
    );
}
