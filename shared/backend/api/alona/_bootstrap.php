<?php

declare(strict_types=1);

final class AlonaApi
{
    private ?PDO $db = null;
    private ?string $authenticatedUserId = null;

    public function __construct()
    {
        $this->sendCorsHeaders();
    }

    public function run(callable $handler): never
    {
        try {
            $this->requireAdmin();
            $this->db = $this->connect();
            $result = $handler($this->db);
            $this->respond($result['data'] ?? $result, $result['status'] ?? 200);
        } catch (InvalidArgumentException $exception) {
            $this->respond(['success' => false, 'message' => $exception->getMessage()], 400);
        } catch (RuntimeException $exception) {
            $this->respond(['success' => false, 'message' => $exception->getMessage()], 401);
        } catch (Throwable $exception) {
            error_log($exception->__toString());
            $this->respond(['success' => false, 'message' => 'An internal server error occurred.'], 500);
        }
    }

    public function runPublic(callable $handler): never
    {
        try {
            $this->db = $this->connect();
            $result = $handler($this->db);
            $this->respond($result['data'] ?? $result, $result['status'] ?? 200);
        } catch (InvalidArgumentException $exception) {
            $this->respond(['success' => false, 'message' => $exception->getMessage()], 400);
        } catch (Throwable $exception) {
            error_log($exception->__toString());
            $this->respond(['success' => false, 'message' => 'An internal server error occurred.'], 500);
        }
    }

    public function authenticatedUserId(): ?string
    {
        return $this->authenticatedUserId;
    }

    public function input(): array
    {
        $raw = file_get_contents('php://input') ?: '{}';
        $data = json_decode($raw, true);
        if (!is_array($data)) {
            throw new InvalidArgumentException('Request body must be valid JSON.');
        }

        return $data;
    }

    public function multipartInput(array $requiredFields = []): array
    {
        foreach ($requiredFields as $field) {
            if (!isset($_POST[$field]) || trim((string) $_POST[$field]) === '') {
                throw new InvalidArgumentException(sprintf('%s is required.', $field));
            }
        }
        return $_POST;
    }

    public function upload(string $field, array $allowedMimeTypes = ['image/jpeg', 'image/png', 'application/pdf']): string
    {
        if (!isset($_FILES[$field]) || $_FILES[$field]['error'] !== UPLOAD_ERR_OK) {
            throw new InvalidArgumentException(sprintf('%s document upload is required.', $field));
        }
        if ($_FILES[$field]['size'] > 5242880) {
            throw new InvalidArgumentException(sprintf('%s must not exceed 5MB.', $field));
        }
        $mime = (new finfo(FILEINFO_MIME_TYPE))->file($_FILES[$field]['tmp_name']);
        if (!in_array($mime, $allowedMimeTypes, true)) {
            throw new InvalidArgumentException(sprintf('%s must be a JPG, PNG, or PDF.', $field));
        }
        $directory = getenv('ALONA_UPLOAD_DIR') ?: dirname(__DIR__, 2) . '/storage/app/alona-registration';
        if (!is_dir($directory) && !mkdir($directory, 0750, true) && !is_dir($directory)) {
            throw new RuntimeException('Unable to create the document storage directory.');
        }
        $extension = match ($mime) { 'image/jpeg' => 'jpg', 'image/png' => 'png', default => 'pdf' };
        $filename = bin2hex(random_bytes(16)) . '.' . $extension;
        if (!move_uploaded_file($_FILES[$field]['tmp_name'], $directory . '/' . $filename)) {
            throw new RuntimeException('Unable to store the uploaded document.');
        }
        return (getenv('ALONA_UPLOAD_BASE_URL') ?: '/storage/alona-registration') . '/' . $filename;
    }

    public function method(): string
    {
        return strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
    }

    public function query(string $key): ?string
    {
        $value = $_GET[$key] ?? null;
        return is_string($value) && trim($value) !== '' ? trim($value) : null;
    }

    public function requiredString(array $data, string $key, int $maxLength = 255): string
    {
        $value = trim((string)($data[$key] ?? ''));
        if ($value === '' || mb_strlen($value) > $maxLength) {
            throw new InvalidArgumentException(sprintf('%s is required and must not exceed %d characters.', $key, $maxLength));
        }

        return $value;
    }

    public function optionalString(array $data, string $key, int $maxLength = 255): ?string
    {
        if (!array_key_exists($key, $data) || $data[$key] === null) {
            return null;
        }

        $value = trim((string)$data[$key]);
        if (mb_strlen($value) > $maxLength) {
            throw new InvalidArgumentException(sprintf('%s must not exceed %d characters.', $key, $maxLength));
        }

        return $value === '' ? null : $value;
    }

    public function uuid(array $data, string $key, bool $required = true): ?string
    {
        $value = $data[$key] ?? null;
        if ($value === null && !$required) {
            return null;
        }
        if (!is_string($value) || !preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[1-5][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i', $value)) {
            throw new InvalidArgumentException(sprintf('%s must be a valid UUID.', $key));
        }

        return strtolower($value);
    }

    public function enum(array $data, string $key, array $allowed, bool $required = true): ?string
    {
        $value = $data[$key] ?? null;
        if ($value === null && !$required) {
            return null;
        }
        $value = strtoupper(trim((string)$value));
        if (!in_array($value, $allowed, true)) {
            throw new InvalidArgumentException(sprintf('%s must be one of: %s.', $key, implode(', ', $allowed)));
        }

        return $value;
    }

    private function connect(): PDO
    {
        $dsn = getenv('SUPABASE_DB_DSN') ?: getenv('DB_DSN');
        if (!$dsn) {
            $host = getenv('DB_HOST') ?: '127.0.0.1';
            $port = getenv('DB_PORT') ?: '5432';
            $database = getenv('DB_DATABASE') ?: 'postgres';
            $dsn = sprintf('pgsql:host=%s;port=%s;dbname=%s', $host, $port, $database);
        }

        return new PDO($dsn, getenv('DB_USERNAME') ?: getenv('SUPABASE_DB_USER') ?: 'postgres', getenv('DB_PASSWORD') ?: getenv('SUPABASE_DB_PASSWORD') ?: '', [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
    }

    private function requireAdmin(): void
    {
        $header = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
        if (!preg_match('/^Bearer\s+(.+)$/i', $header, $matches)) {
            throw new RuntimeException('Authentication is required.');
        }

        $secret = getenv('SUPABASE_JWT_SECRET');
        if (!$secret) {
            throw new RuntimeException('Authentication is not configured.');
        }

        $parts = explode('.', $matches[1]);
        if (count($parts) !== 3) {
            throw new RuntimeException('Invalid access token.');
        }

        [$encodedHeader, $encodedPayload, $encodedSignature] = $parts;
        $header = json_decode($this->base64UrlDecode($encodedHeader), true);
        $payload = json_decode($this->base64UrlDecode($encodedPayload), true);
        $signature = $this->base64UrlDecode($encodedSignature);
        $expected = hash_hmac('sha256', $encodedHeader . '.' . $encodedPayload, $secret, true);

        if (!is_array($header) || !is_array($payload) || ($header['alg'] ?? '') !== 'HS256' || !hash_equals($expected, $signature)) {
            throw new RuntimeException('Invalid access token.');
        }
        if (isset($payload['exp']) && (int)$payload['exp'] < time()) {
            throw new RuntimeException('Access token has expired.');
        }

        $appMetadata = is_array($payload['app_metadata'] ?? null) ? $payload['app_metadata'] : [];
        $roles = $appMetadata['roles'] ?? [];
        $roles = is_array($roles) ? $roles : [$roles];
        $roles[] = $appMetadata['role'] ?? null;
        $roles[] = $payload['role'] ?? null;

        if (!in_array('admin', array_map('strtolower', array_filter($roles, 'is_string')), true)) {
            throw new RuntimeException('Administrator access is required.');
        }

        $subject = $payload['sub'] ?? null;
        if (is_string($subject) && preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[1-5][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i', $subject)) {
            $this->authenticatedUserId = strtolower($subject);
        }
    }

    private function sendCorsHeaders(): void
    {
        $origin = $_SERVER['HTTP_ORIGIN'] ?? '';
        $allowed = array_filter(array_map('trim', explode(',', getenv('CORS_ALLOWED_ORIGINS') ?: 'http://localhost:5173')));
        if ($origin !== '' && in_array($origin, $allowed, true)) {
            header('Access-Control-Allow-Origin: ' . $origin);
            header('Access-Control-Allow-Credentials: true');
        }
        header('Vary: Origin');
        header('Access-Control-Allow-Headers: Authorization, Content-Type, Accept');
        header('Access-Control-Allow-Methods: GET, POST, PATCH, OPTIONS');
        header('Content-Type: application/json; charset=utf-8');
        if (($_SERVER['REQUEST_METHOD'] ?? '') === 'OPTIONS') {
            http_response_code(204);
            exit;
        }
    }

    private function respond(array $data, int $status): never
    {
        http_response_code($status);
        echo json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        exit;
    }

    private function base64UrlDecode(string $value): string
    {
        $decoded = base64_decode(strtr($value, '-_', '+/') . str_repeat('=', (4 - strlen($value) % 4) % 4), true);
        if ($decoded === false) {
            throw new RuntimeException('Invalid access token.');
        }

        return $decoded;
    }
}

function alonaApi(): AlonaApi
{
    static $api;
    return $api ??= new AlonaApi();
}