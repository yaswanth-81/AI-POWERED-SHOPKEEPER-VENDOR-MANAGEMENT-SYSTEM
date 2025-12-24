<?php
header('Content-Type: application/json');
$config = require __DIR__ . '/config.php';
$gq = $config['providers']['groq'] ?? [];
$key = trim($gq['api_key'] ?? '');
$model = $gq['model'] ?? 'llama-3.1-8b-instant';
$base = rtrim($gq['api_base'] ?? 'https://api.groq.com', '/');
$ver = $gq['api_version'] ?? 'v1';

$result = [
	'has_key' => ($key !== ''),
	'model' => $model,
	'url' => $base . '/openai/v1/chat/completions',
	'http_status' => null,
	'curl_error' => null,
	'body' => null
];

$payload = [
	'model' => $model,
	'messages' => [
		['role' => 'system', 'content' => 'Say hello and mention the word GROQ-TEST once.'],
		['role' => 'user', 'content' => 'Test message']
	],
	'temperature' => 0.2,
	'max_tokens' => 16
];

$ch = curl_init($result['url']);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
	'Content-Type: application/json',
	'Authorization: Bearer ' . $key
]);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
$body = curl_exec($ch);
$result['curl_error'] = curl_error($ch) ?: null;
$result['http_status'] = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

$result['body'] = $body ? substr($body, 0, 800) : null;
echo json_encode($result);
