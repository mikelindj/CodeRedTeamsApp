<?php
/**
 * Test script to verify Graph API access
 * This will help identify which step is failing
 */

header('Content-Type: text/plain');

// Azure AD Configuration - must be set as environment variables
$CLIENT_ID = getenv('AZURE_CLIENT_ID');
$CLIENT_SECRET = getenv('AZURE_CLIENT_SECRET');
$TENANT_ID = getenv('AZURE_TENANT_ID');

if (empty($CLIENT_ID) || empty($CLIENT_SECRET) || empty($TENANT_ID)) {
    echo "✗ Error: Azure AD environment variables not set!\n";
    echo "  Please set: AZURE_CLIENT_ID, AZURE_CLIENT_SECRET, AZURE_TENANT_ID\n";
    exit(1);
}

echo "=== Graph API Test ===\n\n";

// Step 1: Get token
echo "Step 1: Getting access token...\n";
$tokenUrl = "https://login.microsoftonline.com/{$TENANT_ID}/oauth2/v2.0/token";
$data = [
    'client_id' => $CLIENT_ID,
    'client_secret' => $CLIENT_SECRET,
    'scope' => 'https://graph.microsoft.com/.default',
    'grant_type' => 'client_credentials'
];

$ch = curl_init($tokenUrl);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
$curlErrno = curl_errno($ch);
curl_close($ch);

if ($curlErrno !== 0 || $httpCode !== 200) {
    echo "✗ Token request failed!\n";
    echo "  Code: {$httpCode}, Error: {$curlError}, Errno: {$curlErrno}\n";
    exit(1);
}

$tokenData = json_decode($response, true);
$accessToken = $tokenData['access_token'] ?? null;

if (!$accessToken) {
    echo "✗ No access token in response!\n";
    echo "  Response: " . substr($response, 0, 200) . "\n";
    exit(1);
}

echo "✓ Token obtained (length: " . strlen($accessToken) . ")\n\n";

// Step 2: Get site ID
echo "Step 2: Getting SharePoint site ID...\n";
$siteUrl = "https://graph.microsoft.com/v1.0/sites/acsacademysg.sharepoint.com:/sites/allstaff";

$ch = curl_init($siteUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer ' . $accessToken,
    'Accept: application/json'
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
$curlErrno = curl_errno($ch);
curl_close($ch);

if ($curlErrno !== 0 || $httpCode !== 200) {
    echo "✗ Site ID request failed!\n";
    echo "  Code: {$httpCode}, Error: {$curlError}, Errno: {$curlErrno}\n";
    echo "  Response: " . substr($response, 0, 500) . "\n";
    exit(1);
}

$siteData = json_decode($response, true);
$siteId = $siteData['id'] ?? null;

if (!$siteId) {
    echo "✗ No site ID in response!\n";
    echo "  Response: " . substr($response, 0, 500) . "\n";
    exit(1);
}

echo "✓ Site ID obtained: " . substr($siteId, 0, 30) . "...\n\n";

// Step 3: Get list items (simplified URL)
echo "Step 3: Getting list items...\n";
$listUrl = "https://graph.microsoft.com/v1.0/sites/{$siteId}/lists/Form%20Teachers/items?\$select=id,fields&\$expand=fields";
echo "  URL: {$listUrl}\n";

$ch = curl_init($listUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer ' . $accessToken,
    'Accept: application/json'
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
$curlErrno = curl_errno($ch);
$curlInfo = curl_getinfo($ch);
curl_close($ch);

echo "  Code: {$httpCode}, Errno: {$curlErrno}, Error: {$curlError}\n";

if ($curlErrno !== 0) {
    echo "✗ cURL error!\n";
    echo "  Details: " . print_r($curlInfo, true) . "\n";
    exit(1);
}

if ($httpCode === 0) {
    echo "✗ HTTP 0 - Connection failed!\n";
    echo "  cURL info: " . print_r($curlInfo, true) . "\n";
    exit(1);
}

if ($httpCode !== 200) {
    echo "✗ Request failed!\n";
    echo "  Response: " . substr($response, 0, 500) . "\n";
    exit(1);
}

$listData = json_decode($response, true);
$itemCount = isset($listData['value']) ? count($listData['value']) : 0;

echo "✓ List items retrieved! Count: {$itemCount}\n\n";

echo "=== Test Complete ===\n";
echo "All steps passed successfully!\n";
?>

