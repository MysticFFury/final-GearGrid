<?php
// Quick script to debug OAuth redirect URI

require_once __DIR__ . '/vendor/autoload.php';

use Symfony\Component\Dotenv\Dotenv;
use Symfony\Component\Routing\Router;

$dotenv = new Dotenv();
$dotenv->loadEnv(__DIR__ . '/.env');

echo "=== Google OAuth Redirect URI Debug ===\n";
echo "\nYour application generates this redirect URI:\n";
echo "  http://localhost:8000/connect/google/check\n";
echo "  (or with your actual domain/port)\n";

echo "\nTo fix the 'redirect_uri_mismatch' error:\n";
echo "\n1. Go to Google Cloud Console: https://console.cloud.google.com\n";
echo "2. Select your project (check GCP console for your project ID)\n";
echo "3. Go to OAuth 2.0 Client IDs (Credentials)\n";
echo "4. Edit the OAuth app\n";
echo "5. Under 'Authorized redirect URIs', add/verify:\n";
echo "   - http://localhost:8000/connect/google/check\n";
echo "   - (or http://localhost:80/connect/google/check if using port 80)\n";
echo "   - (or your production domain)\n";
echo "\n6. Click Save\n";
echo "7. Download the credentials JSON if needed\n";

echo "\n✓ Current .env settings:\n";
echo "  CLIENT_ID: " . substr($_ENV['GOOGLE_CLIENT_ID'] ?? 'NOT SET', 0, 20) . "...\n";
echo "  CLIENT_SECRET: " . (isset($_ENV['GOOGLE_CLIENT_SECRET']) ? '✓ SET' : '✗ NOT SET') . "\n";

echo "\n\nIf you're running locally with different ports, also add:\n";
echo "   - http://localhost/connect/google/check\n";
echo "   - http://localhost:3000/connect/google/check\n";
echo "   - http://localhost:8080/connect/google/check\n";
echo "   - etc.\n";
?>
