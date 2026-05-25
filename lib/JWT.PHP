<?php
// Este archivo es una versión simplificada de la popular biblioteca firebase/php-jwt
// para poder usarla sin Composer.
namespace Firebase\JWT;
use \DomainException;
use \InvalidArgumentException;
use \UnexpectedValueException;
use \DateTime;

class JWT
{
    public static function decode(string $jwt, $keyOrKeyArray, array $allowed_algs = []): object
    {
        $tks = \explode('.', $jwt);
        if (\count($tks) !== 3) {
            throw new UnexpectedValueException('Wrong number of segments');
        }
        list($headb64, $bodyb64, $cryptob64) = $tks;
        $headerRaw = static::urlsafeB64Decode($headb64);
        if (null === ($header = static::jsonDecode($headerRaw))) {
            throw new UnexpectedValueException('Invalid header encoding');
        }
        $payloadRaw = static::urlsafeB64Decode($bodyb64);
        if (null === ($payload = static::jsonDecode($payloadRaw))) {
            throw new UnexpectedValueException('Invalid claims encoding');
        }
        if (empty($header->alg)) {
            throw new UnexpectedValueException('Empty algorithm');
        }
        if (empty($allowed_algs)) {
            throw new UnexpectedValueException('No allowed algorithms configured');
        }
        if (!\in_array($header->alg, $allowed_algs)) {
            throw new UnexpectedValueException('Algorithm not supported');
        }
        
        $key = static::getKey($keyOrKeyArray, $header->kid ?? null);

        $sig = static::urlsafeB64Decode($cryptob64);
        if (!static::verify("{$headb64}.{$bodyb64}", $sig, $key, $header->alg)) {
            throw new UnexpectedValueException('Signature verification failed');
        }
        
        $nbf = $payload->nbf ?? null;
        if ($nbf && $nbf > \time()) {
            throw new UnexpectedValueException('Cannot handle token prior to ' . \date(DateTime::ISO8601, $nbf));
        }
        
        $iat = $payload->iat ?? null;
        if ($iat && $iat > \time()) {
            throw new UnexpectedValueException('Cannot handle token prior to ' . \date(DateTime::ISO8601, $iat));
        }

        $exp = $payload->exp ?? null;
        if ($exp && \time() >= $exp) {
            throw new UnexpectedValueException('Expired token');
        }
        
        return $payload;
    }

    private static function verify(string $msg, string $signature, $key, string $alg): bool
    {
        if (empty(static::$supported_algs[$alg])) {
            throw new DomainException('Algorithm not supported');
        }
        list($function, $algorithm) = static::$supported_algs[$alg];
        switch ($function) {
            case 'openssl':
                $success = \openssl_verify($msg, $signature, $key, $algorithm);
                if ($success === 1) return true;
                if ($success === 0) return false;
                throw new DomainException('OpenSSL error: ' . \openssl_error_string());
            case 'hash_hmac':
            default:
                $hash = \hash_hmac($algorithm, $msg, $key, true);
                return \hash_equals($signature, $hash);
        }
    }
    
    public static function jsonDecode(string $input)
    {
        $obj = \json_decode($input, false, 512, JSON_BIGINT_AS_STRING);
        if ($errno = \json_last_error()) {
            throw new DomainException('JSON error: ' . static::jsonLastErrorMsg());
        } elseif ($obj === null && $input !== 'null') {
            throw new DomainException('Null result with non-null input');
        }
        return $obj;
    }
    
    public static function urlsafeB64Decode(string $input): string
    {
        $remainder = \strlen($input) % 4;
        if ($remainder) {
            $padlen = 4 - $remainder;
            $input .= \str_repeat('=', $padlen);
        }
        return \base64_decode(\strtr($input, '-_', '+/'));
    }

    private static function getKey($keyOrKeyArray, ?string $kid)
    {
        if (!is_array($keyOrKeyArray)) return $keyOrKeyArray;
        if ($kid === null) throw new UnexpectedValueException('"kid" empty, unable to lookup key');
        if (!isset($keyOrKeyArray[$kid])) throw new UnexpectedValueException('kid not found in key set');
        return $keyOrKeyArray[$kid];
    }
    private static function jsonLastErrorMsg(): string
    {
       $errors = [JSON_ERROR_DEPTH => 'Maximum stack depth exceeded', JSON_ERROR_STATE_MISMATCH => 'Invalid or malformed JSON', JSON_ERROR_CTRL_CHAR => 'Control character error', JSON_ERROR_SYNTAX => 'Syntax error', JSON_ERROR_UTF8 => 'Malformed UTF-8 characters'];
       return $errors[\json_last_error()] ?? 'Unknown JSON error';
    }
    private static $supported_algs = [
        'HS256' => ['hash_hmac', 'sha256'], 'HS512' => ['hash_hmac', 'sha512'], 'HS384' => ['hash_hmac', 'sha384'],
        'RS256' => ['openssl', 'SHA256'], 'RS384' => ['openssl', 'SHA384'], 'RS512' => ['openssl', 'SHA512'],
    ];
}

class Key
{
    public function __construct(
        private string $keyMaterial,
        private string $algorithm
    ) {
    }
    public function getAlgorithm(): string
    {
        return $this->algorithm;
    }
    public function getKeyMaterial(): string
    {
        return $this->keyMaterial;
    }
}