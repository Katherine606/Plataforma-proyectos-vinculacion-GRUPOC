<?php

class JwtHelper
{
    private string $secret;

    public function __construct(string $secret)
    {
        $this->secret = $secret;
    }

    public function generarToken(array $payload, int $ttlSegundos = 7200): string
    {
        $header = ['alg' => 'HS256', 'typ' => 'JWT'];
        $now = time();

        $payloadFinal = array_merge($payload, [
            'iat' => $now,
            'exp' => $now + $ttlSegundos
        ]);

        $headerB64 = $this->base64UrlEncode(json_encode($header));
        $payloadB64 = $this->base64UrlEncode(json_encode($payloadFinal));

        $firma = hash_hmac('sha256', $headerB64 . '.' . $payloadB64, $this->secret, true);
        $firmaB64 = $this->base64UrlEncode($firma);

        return $headerB64 . '.' . $payloadB64 . '.' . $firmaB64;
    }

    public function validarToken(string $token): ?array
    {
        $partes = explode('.', $token);
        if (count($partes) !== 3) {
            return null;
        }

        [$headerB64, $payloadB64, $firmaB64] = $partes;
        $firmaCalculada = $this->base64UrlEncode(
            hash_hmac('sha256', $headerB64 . '.' . $payloadB64, $this->secret, true)
        );

        if (!hash_equals($firmaCalculada, $firmaB64)) {
            return null;
        }

        $payloadJson = $this->base64UrlDecode($payloadB64);
        $payload = json_decode($payloadJson, true);

        if (!is_array($payload)) {
            return null;
        }

        if (!isset($payload['exp']) || time() >= (int)$payload['exp']) {
            return null;
        }

        return $payload;
    }

    private function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    private function base64UrlDecode(string $data): string
    {
        $remainder = strlen($data) % 4;
        if ($remainder > 0) {
            $data .= str_repeat('=', 4 - $remainder);
        }

        return base64_decode(strtr($data, '-_', '+/')) ?: '';
    }
}
