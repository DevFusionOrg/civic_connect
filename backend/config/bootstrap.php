<?php
require __DIR__ . '/../vendor/autoload.php';

use Dotenv\Dotenv;

$envPath = __DIR__ . '/../';

// In cloud runtimes (Render), env vars are injected by the platform and a local
// .env file may not exist. safeLoad avoids fatals when the file is missing.
if (class_exists(Dotenv::class)) {
	$dotenv = Dotenv::createImmutable($envPath);
	$dotenv->safeLoad();
}

// Existing code reads from $_ENV, so mirror process-level env vars into $_ENV.
$processEnv = getenv();
if (is_array($processEnv)) {
	foreach ($processEnv as $key => $value) {
		if (!array_key_exists($key, $_ENV)) {
			$_ENV[$key] = $value;
		}
	}
}
