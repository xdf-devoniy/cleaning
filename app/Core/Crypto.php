<?php
namespace App\Core;

class Crypto
{
    private string $key;

    public function __construct(?string $key = null)
    {
        $config = require __DIR__ . '/../Config/config.php';
        $this->key = $key ?? $config['app_key'];
    }

    public function encrypt(string $plaintext): string
    {
        $iv = random_bytes(16);
        $ciphertext = openssl_encrypt($plaintext, 'aes-256-gcm', $this->key, 0, $iv, $tag);
        return base64_encode($iv . $tag . $ciphertext);
    }

    public function decrypt(string $encoded): string
    {
        $decoded = base64_decode($encoded, true);
        if ($decoded === false || strlen($decoded) < 32) {
            throw new \RuntimeException('Invalid payload');
        }
        $iv = substr($decoded, 0, 16);
        $tag = substr($decoded, 16, 16);
        $ciphertext = substr($decoded, 32);
        $plaintext = openssl_decrypt($ciphertext, 'aes-256-gcm', $this->key, 0, $iv, $tag);
        if ($plaintext === false) {
            throw new \RuntimeException('Unable to decrypt');
        }
        return $plaintext;
    }
}
