<?php

declare(strict_types=1);

function servePublicAsset(): void
{
	$requestPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
	$relativePath = ltrim($requestPath, '/');

	if (str_starts_with($relativePath, 'public/')) {
		$relativePath = substr($relativePath, 7);
	}

	if (!preg_match('#^(css|js|img|fonts)/[a-zA-Z0-9._/-]+$#', $relativePath)) {
		return;
	}

	$assetPath = dirname(__DIR__) . '/public/' . $relativePath;
	$publicRoot = realpath(dirname(__DIR__) . '/public');
	$resolvedPath = realpath($assetPath);

	if ($publicRoot === false || $resolvedPath === false || !str_starts_with($resolvedPath, $publicRoot . DIRECTORY_SEPARATOR) || !is_file($resolvedPath)) {
		return;
	}

	$mimeTypes = [
		'css' => 'text/css; charset=utf-8',
		'js' => 'application/javascript; charset=utf-8',
		'svg' => 'image/svg+xml',
		'png' => 'image/png',
		'jpg' => 'image/jpeg',
		'jpeg' => 'image/jpeg',
		'webp' => 'image/webp',
		'woff' => 'font/woff',
		'woff2' => 'font/woff2',
	];
	$extension = strtolower(pathinfo($resolvedPath, PATHINFO_EXTENSION));

	if (!isset($mimeTypes[$extension])) {
		return;
	}

	header('Content-Type: ' . $mimeTypes[$extension]);
	header('Cache-Control: public, max-age=31536000, immutable');
	readfile($resolvedPath);
	exit;
}

servePublicAsset();

require_once dirname(__DIR__) . '/index.php';
