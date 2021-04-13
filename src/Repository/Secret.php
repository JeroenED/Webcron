<?php


namespace JeroenED\Webcron\Repository;


class Secret
{
    static function encrypt($plaintext) {
        $password = $_ENV['SECRET'];
        $method = $_ENV['ENCRYPTION_METHOD'];
        $key = hash($_ENV['HASHING_METHOD'], $password, true);
        $iv = openssl_random_pseudo_bytes(16);

        $ciphertext = openssl_encrypt($plaintext, $method, $key, OPENSSL_RAW_DATA, $iv);
        $hash = hash_hmac('sha256', $ciphertext . $iv, $key, true);

        return $iv . $hash . $ciphertext;
    }

    static function decrypt($ivHashCiphertext) {
        $password = $_ENV['SECRET'];
        $method = $_ENV['ENCRYPTION_METHOD'];
        $iv = substr($ivHashCiphertext, 0, 16);
        $hash = substr($ivHashCiphertext, 16, 32);
        $ciphertext = substr($ivHashCiphertext, 48);
        $key = hash($_ENV['HASHING_METHOD'], $password, true);

        if (!hash_equals(hash_hmac($_ENV['HASHING_METHOD'], $ciphertext . $iv, $key, true), $hash)) return null;

        return openssl_decrypt($ciphertext, $method, $key, OPENSSL_RAW_DATA, $iv);
    }
}