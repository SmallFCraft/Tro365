<?php
/**
 * Locations API - Using provinces.open-api.vn
 * Tro365 - Website thuê trọ
 */

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/constants.php';

use Tro365\Services\LocationService;

header('Content-Type: application/json');

try {
    $locationService = new LocationService();
    $method = $_SERVER['REQUEST_METHOD'];
    $path = $_SERVER['REQUEST_URI'];

    // Parse the path to get the action
    $pathParts = explode('/', trim($path, '/'));
    $action = end($pathParts);

    // Remove query string from action
    if (strpos($action, '?') !== false) {
        $action = substr($action, 0, strpos($action, '?'));
    }

    switch ($action) {
        case 'districts':
            if ($method === 'GET' && isset($_GET['province_id'])) {
                $provinceId = $_GET['province_id']; // Keep as string for API
                $districts = $locationService->getDistricts($provinceId);
                echo json_encode($districts);
            } else {
                http_response_code(400);
                echo json_encode(['error' => 'Missing province_id parameter']);
            }
            break;

        case 'wards':
            if ($method === 'GET' && isset($_GET['district_id'])) {
                $districtId = $_GET['district_id']; // Keep as string for API
                $wards = $locationService->getWards($districtId);
                echo json_encode($wards);
            } else {
                http_response_code(400);
                echo json_encode(['error' => 'Missing district_id parameter']);
            }
            break;

        case 'provinces':
            if ($method === 'GET') {
                $provinces = $locationService->getProvinces();
                echo json_encode($provinces);
            } else {
                http_response_code(405);
                echo json_encode(['error' => 'Method not allowed']);
            }
            break;

        case 'search':
            if ($method === 'GET') {
                $type = $_GET['type'] ?? '';
                $query = $_GET['q'] ?? '';

                if (empty($query)) {
                    http_response_code(400);
                    echo json_encode(['error' => 'Missing query parameter']);
                    break;
                }

                switch ($type) {
                    case 'provinces':
                        $results = $locationService->searchProvinces($query);
                        break;
                    case 'districts':
                        $provinceCode = $_GET['province_code'] ?? null;
                        $results = $locationService->searchDistricts($query, $provinceCode);
                        break;
                    case 'wards':
                        $districtCode = $_GET['district_code'] ?? null;
                        $results = $locationService->searchWards($query, $districtCode);
                        break;
                    default:
                        http_response_code(400);
                        echo json_encode(['error' => 'Invalid search type']);
                        return;
                }

                echo json_encode($results);
            } else {
                http_response_code(405);
                echo json_encode(['error' => 'Method not allowed']);
            }
            break;

        case 'reverse-geocode':
            if ($method === 'GET') {
                $lat = $_GET['lat'] ?? '';
                $lng = $_GET['lng'] ?? '';

                if (empty($lat) || empty($lng)) {
                    http_response_code(400);
                    echo json_encode(['error' => 'Missing lat or lng parameter']);
                    break;
                }

                // Validate coordinates
                if (!is_numeric($lat) || !is_numeric($lng)) {
                    http_response_code(400);
                    echo json_encode(['error' => 'Invalid coordinates']);
                    break;
                }

                $lat = floatval($lat);
                $lng = floatval($lng);

                // Check if coordinates are within Vietnam bounds
                if ($lat < 8.0 || $lat > 24.0 || $lng < 102.0 || $lng > 110.0) {
                    http_response_code(400);
                    echo json_encode(['error' => 'Coordinates outside Vietnam']);
                    break;
                }

                $result = $locationService->reverseGeocode($lat, $lng);
                echo json_encode($result);
            } else {
                http_response_code(405);
                echo json_encode(['error' => 'Method not allowed']);
            }
            break;

        default:
            http_response_code(404);
            echo json_encode(['error' => 'Endpoint not found']);
            break;
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>
