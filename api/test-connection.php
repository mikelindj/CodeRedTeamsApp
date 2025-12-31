<?php
/**
 * Test script to diagnose connection issues
 * Run this to check if your server can connect to Microsoft Graph API
 */

header('Content-Type: text/plain');

echo "=== Connection Test ===\n\n";

// Test 1: Check if cURL is available
echo "1. Checking cURL extension...\n";
if (function_exists('curl_version')) {
    $curlVersion = curl_version();
    echo "   ✓ cURL is available (version: {$curlVersion['version']})\n";
    echo "   SSL version: {$curlVersion['ssl_version']}\n";
} else {
    echo "   ✗ cURL is NOT available. Please install php-curl extension.\n";
    exit(1);
}

// Test 2: Test basic HTTPS connection
echo "\n2. Testing HTTPS connection to graph.microsoft.com...\n";
$ch = curl_init('https://graph.microsoft.com/v1.0');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
curl_setopt($ch, CURLOPT_NOBODY, true); // HEAD request only

$result = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
$curlErrno = curl_errno($ch);
curl_close($ch);

if ($curlErrno !== 0) {
    echo "   ✗ Connection failed!\n";
    echo "   Error code: {$curlErrno}\n";
    echo "   Error message: {$curlError}\n";
    echo "\n   Possible causes:\n";
    echo "   - Firewall blocking outbound HTTPS\n";
    echo "   - SSL certificate issues\n";
    echo "   - Network connectivity problems\n";
    echo "   - Proxy configuration needed\n";
} else if ($httpCode === 0) {
    echo "   ✗ Connection failed (HTTP 0)\n";
    echo "   This usually means:\n";
    echo "   - Server cannot reach external HTTPS endpoints\n";
    echo "   - SSL/TLS configuration issue\n";
    echo "   - Firewall blocking the connection\n";
} else {
    echo "   ✓ Connection successful (HTTP {$httpCode})\n";
}

// Test 3: Test Azure AD token endpoint
echo "\n3. Testing connection to Azure AD token endpoint...\n";
$tenantId = '6dff32de-1cd0-4ada-892b-2298e1f61698';
$tokenUrl = "https://login.microsoftonline.com/{$tenantId}/oauth2/v2.0/token";

$ch = curl_init($tokenUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
curl_setopt($ch, CURLOPT_NOBODY, true);

$result = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
$curlErrno = curl_errno($ch);
curl_close($ch);

if ($curlErrno !== 0) {
    echo "   ✗ Connection failed!\n";
    echo "   Error: {$curlError}\n";
} else if ($httpCode === 0) {
    echo "   ✗ Connection failed (HTTP 0)\n";
} else {
    echo "   ✓ Connection successful (HTTP {$httpCode})\n";
}

// Test 4: Check environment variables
echo "\n4. Checking Azure AD configuration...\n";
$clientId = getenv('AZURE_CLIENT_ID');
$clientSecret = getenv('AZURE_CLIENT_SECRET');
$tenantId = getenv('AZURE_TENANT_ID');

echo "   Client ID: {$clientId}\n";
echo "   Tenant ID: {$tenantId}\n";
if (empty($clientSecret)) {
    echo "   ✗ Client Secret: NOT SET (this is required!)\n";
} else {
    echo "   ✓ Client Secret: SET (length: " . strlen($clientSecret) . ")\n";
}

echo "\n=== Test Complete ===\n";
echo "\nIf all tests pass, try calling getSharePointList.php again.\n";
?>

