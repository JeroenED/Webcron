<?php


namespace App\Service;

class Secret
{
    /**
     * Encrypt plaintext string based with password string
     *
     * @param $plaintext
     * @return string
     */
    static function encrypt($plaintext): string
    {
        $password = $_ENV['APP_SECRET'];
        $method = $_ENV['ENCRYPTION_METHOD'];
        $key = hash($_ENV['HASHING_METHOD'], $password, true);
        $iv = openssl_random_pseudo_bytes(16);

        $ciphertext = openssl_encrypt($plaintext, $method, $key, OPENSSL_RAW_DATA, $iv);
        $hash = hash_hmac($_ENV['HASHING_METHOD'], $ciphertext . $iv, $key, true);

        return $iv . $hash . $ciphertext;
    }

    /**
     * Decrypt encrypted message
     *
     * @param $ivHashCiphertext
     * @return string
     */
    static function decrypt($ivHashCiphertext): string
    {
        $password = $_ENV['APP_SECRET'];
        $method = $_ENV['ENCRYPTION_METHOD'];
        $iv = substr($ivHashCiphertext, 0, 16);
        $hash = substr($ivHashCiphertext, 16, 32);
        $ciphertext = substr($ivHashCiphertext, 48);
        $key = hash($_ENV['HASHING_METHOD'], $password, true);

        if (!hash_equals(hash_hmac($_ENV['HASHING_METHOD'], $ciphertext . $iv, $key, true), $hash)) return null;

        return openssl_decrypt($ciphertext, $method, $key, OPENSSL_RAW_DATA, $iv);
    }
}