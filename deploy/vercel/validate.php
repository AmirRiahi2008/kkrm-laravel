<?php

declare(strict_types=1);

$fail = static function (string $message): never {
    fwrite(STDERR, $message.PHP_EOL);
    exit(1);
};

$key = (string) getenv('APP_KEY');
if (str_starts_with($key, 'base64:')) {
    $key = base64_decode(substr($key, 7), true);
}

if (! is_string($key) || strlen($key) !== 32) {
    $fail('APP_KEY must be the valid AES-256 key of the existing application.');
}

$url = parse_url((string) getenv('APP_URL'));
if (! is_array($url) || ($url['scheme'] ?? '') !== 'https' || empty($url['host']) || isset($url['user']) || isset($url['pass']) || ! in_array($url['path'] ?? '', ['', '/'], true) || isset($url['query']) || isset($url['fragment'])) {
    $fail('APP_URL must be the HTTPS origin of the deployed site.');
}

$limit = getenv('MEDIA_MAX_KB');
$limit = $limit === false || $limit === '' ? '3840' : $limit;
if (filter_var($limit, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 3840]]) === false) {
    $fail('MEDIA_MAX_KB must be between 1 and 3840 for proxied uploads.');
}

$endpoint = getenv('AWS_ENDPOINT');
if ($endpoint !== false && $endpoint !== '') {
    $storage = parse_url($endpoint);
    if (! is_array($storage) || ($storage['scheme'] ?? '') !== 'https' || empty($storage['host']) || isset($storage['user']) || isset($storage['pass']) || isset($storage['query']) || isset($storage['fragment'])) {
        $fail('AWS_ENDPOINT must be an HTTPS S3 endpoint without embedded credentials.');
    }
}
