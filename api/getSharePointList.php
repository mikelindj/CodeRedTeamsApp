<?php
/**
 * SharePoint List API Endpoint
 * Fetches SharePoint list items using Microsoft Graph API
 * 
 * Required Azure AD Configuration:
 * - Sites.Read.All (Application permission)
 * - Client ID, Client Secret, Tenant ID configured
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Azure AD Configuration
// These must be set as environment variables on the server
$CLIENT_ID = getenv('AZURE_CLIENT_ID');
$CLIENT_SECRET = getenv('AZURE_CLIENT_SECRET');
$TENANT_ID = getenv('AZURE_TENANT_ID');

// SharePoint Configuration
$SHAREPOINT_HOSTNAME = 'acsacademysg.sharepoint.com';
$SITE_NAME = 'allstaff';
$LIST_NAME = 'Form Teachers';

// Validate configuration
if (empty($CLIENT_ID) || empty($CLIENT_SECRET) || empty($TENANT_ID)) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Azure AD configuration missing. Please set AZURE_CLIENT_ID, AZURE_CLIENT_SECRET, and AZURE_TENANT_ID environment variables.'
    ]);
    exit;
}

/**
 * Get Azure AD access token using client credentials flow
 */
function getAccessToken($tenantId, $clientId, $clientSecret) {
    $tokenUrl = "https://login.microsoftonline.com/{$tenantId}/oauth2/v2.0/token";
    
    $data = [
        'client_id' => $clientId,
        'client_secret' => $clientSecret,
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
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/x-www-form-urlencoded'
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    $curlErrno = curl_errno($ch);
    curl_close($ch);
    
    if ($curlErrno !== 0) {
        throw new Exception("cURL error getting token ({$curlErrno}): {$curlError}");
    }
    
    if ($httpCode === 0) {
        throw new Exception("Connection failed when getting token. cURL error: {$curlError}");
    }
    
    if ($httpCode !== 200) {
        throw new Exception("Token request failed: HTTP {$httpCode} - {$response}");
    }
    
    $tokenData = json_decode($response, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception("Invalid JSON in token response: " . json_last_error_msg());
    }
    
    if (!isset($tokenData['access_token'])) {
        throw new Exception("No access token in response: {$response}");
    }
    
    return $tokenData['access_token'];
}

/**
 * Get SharePoint site ID using Microsoft Graph API
 */
function getSiteId($accessToken, $hostname, $siteName) {
    $sitePath = "{$hostname}:/sites/{$siteName}";
    $siteUrl = "https://graph.microsoft.com/v1.0/sites/{$sitePath}";
    
    error_log("Getting site ID from: " . $siteUrl);
    
    $ch = curl_init($siteUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $accessToken,
        'Accept: application/json'
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    $curlErrno = curl_errno($ch);
    curl_close($ch);
    
    error_log("Site ID request - Code: {$httpCode}, Error: {$curlError}");
    
    if ($curlErrno !== 0) {
        throw new Exception("cURL error getting site ID ({$curlErrno}): {$curlError}. URL: {$siteUrl}");
    }
    
    if ($httpCode === 0) {
        throw new Exception("Connection failed when getting site ID. cURL error: {$curlError}");
    }
    
    if ($httpCode !== 200) {
        $errorDetails = $response ?: 'No response body';
        error_log("Site ID error response: " . $errorDetails);
        throw new Exception("Failed to get site ID: HTTP {$httpCode} - {$errorDetails}");
    }
    
    $siteData = json_decode($response, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception("Invalid JSON in site response: " . json_last_error_msg());
    }
    
    if (!isset($siteData['id'])) {
        throw new Exception("No site ID in response: {$response}");
    }
    
    return $siteData['id'];
}

/**
 * Get SharePoint list items using Microsoft Graph API
 */
function getListItems($accessToken, $siteId, $listName) {
    // Build URL with proper encoding
    // Graph API accepts list name in the path - need to URL encode it
    $encodedListName = rawurlencode($listName);
    
    // Build the full URL with query parameters
    // Note: $select and $expand need to be properly encoded
    $baseUrl = "https://graph.microsoft.com/v1.0/sites/{$siteId}/lists/{$encodedListName}/items";
    
    // Manually construct query string to ensure $ characters are preserved
    $queryString = '$select=id,fields&$expand=fields';
    $graphUrl = $baseUrl . '?' . $queryString;
    
    error_log("Requesting list items from: " . $graphUrl);
    error_log("Site ID length: " . strlen($siteId));
    error_log("List name: {$listName} (encoded: {$encodedListName})");
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $graphUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_MAXREDIRS, 5);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $accessToken,
        'Accept: application/json',
        'User-Agent: PHP-cURL'
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    $curlErrno = curl_errno($ch);
    $curlInfo = curl_getinfo($ch);
    curl_close($ch);
    
    error_log("cURL response - Code: {$httpCode}, Error: {$curlError}, Errno: {$curlErrno}");
    if ($httpCode === 0) {
        error_log("HTTP 0 details - URL: {$graphUrl}");
        error_log("cURL info: " . print_r($curlInfo, true));
    }
    
    if ($curlErrno !== 0) {
        error_log("cURL error details: " . print_r($curlInfo, true));
        throw new Exception("cURL error ({$curlErrno}): {$curlError}. URL: {$graphUrl}");
    }
    
    if ($httpCode === 0) {
        // HTTP 0 means connection failed - provide detailed error
        $errorMsg = "Connection failed. cURL error: {$curlError}";
        if (!empty($curlInfo)) {
            $errorMsg .= ". Details: " . json_encode($curlInfo);
        }
        $errorMsg .= ". URL: {$graphUrl}";
        throw new Exception($errorMsg);
    }
    
    if ($httpCode !== 200) {
        $errorDetails = $response ?: 'No response body';
        error_log("Graph API error response: " . substr($errorDetails, 0, 500));
        throw new Exception("Failed to get list items: HTTP {$httpCode} - " . substr($errorDetails, 0, 500));
    }
    
    $data = json_decode($response, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        error_log("JSON decode error: " . json_last_error_msg() . " - Response: " . substr($response, 0, 500));
        throw new Exception("Invalid JSON response: " . json_last_error_msg());
    }
    
    return $data;
}

// Main execution
try {
    // Step 1: Get access token
    error_log("Step 1: Getting access token...");
    $accessToken = getAccessToken($TENANT_ID, $CLIENT_ID, $CLIENT_SECRET);
    error_log("Step 1: Access token obtained successfully");
    
    // Step 2: Get SharePoint site ID
    error_log("Step 2: Getting SharePoint site ID...");
    $siteId = getSiteId($accessToken, $SHAREPOINT_HOSTNAME, $SITE_NAME);
    error_log("Step 2: Site ID obtained: " . substr($siteId, 0, 20) . "...");
    
    // Step 3: Get list items
    error_log("Step 3: Getting list items...");
    $listData = getListItems($accessToken, $siteId, $LIST_NAME);
    error_log("Step 3: List items retrieved successfully");
    
    // Step 4: Transform and sort items
    $items = [];
    if (isset($listData['value']) && is_array($listData['value'])) {
        foreach ($listData['value'] as $item) {
            $items[] = [
                'id' => $item['id'] ?? $item['fields']['id'] ?? null,
                'title' => $item['fields']['Title'] ?? $item['fields']['title'] ?? 'Untitled'
            ];
        }
        
        // Sort by title
        usort($items, function($a, $b) {
            return strcasecmp($a['title'], $b['title']);
        });
    }
    
    // Return success response
    http_response_code(200);
    echo json_encode([
        'items' => $items,
        'count' => count($items)
    ]);
    
} catch (Exception $e) {
    // Log the error
    error_log("SharePoint API Error: " . $e->getMessage());
    
    // Return error response with more details
    http_response_code(500);
    $errorResponse = [
        'error' => $e->getMessage(),
        'details' => 'Please check Azure AD configuration and permissions.'
    ];
    
    // Add debug info if cURL error
    if (strpos($e->getMessage(), 'cURL error') !== false) {
        $errorResponse['troubleshooting'] = [
            'Check if cURL is enabled: php -m | grep curl',
            'Check if server can reach graph.microsoft.com',
            'Check firewall rules for outbound HTTPS',
            'Verify SSL certificates are up to date',
            'Try: curl -v https://graph.microsoft.com'
        ];
    }
    
    echo json_encode($errorResponse, JSON_PRETTY_PRINT);
}
?>

