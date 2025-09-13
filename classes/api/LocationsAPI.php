<?php

namespace Tro365\Api;

use Exception;
use Tro365\Core\BaseAPI;
use Tro365\Services\LocationService;

/**
 * Locations API Class
 * Tro365 - Standardized locations API using provinces.open-api.vn
 */
class LocationsAPI extends BaseAPI
{
    private $locationService;

    public function __construct()
    {
        parent::__construct();
        $this->locationService = new LocationService();
    }

    /**
     * Handle API requests
     */
    public function handle()
    {
        $method = $_SERVER['REQUEST_METHOD'];
        $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

        // Parse the path to get the action
        $pathParts = explode('/', trim($path, '/'));

        // For direct access to router/api/locations.php, use query parameter
        if (strpos($path, 'router/api/locations.php') !== false) {
            $action = $_GET['action'] ?? '';
        } else {
            // For routed access like /api/locations/provinces, use path parsing
            $action = $pathParts[2] ?? '';

            // Remove query string from action
            if (strpos($action, '?') !== false) {
                $action = substr($action, 0, strpos($action, '?'));
            }

            // If no action from path, try query parameter
            if (empty($action)) {
                $action = $_GET['action'] ?? '';
            }
        }



        switch ($action) {
            case 'provinces':
                if ($method !== 'GET') {
                    $this->sendError('Method not allowed', 405);
                }
                $this->handleGetProvinces();
                break;

            case 'districts':
                if ($method !== 'GET') {
                    $this->sendError('Method not allowed', 405);
                }
                $this->handleGetDistricts();
                break;

            case 'wards':
                if ($method !== 'GET') {
                    $this->sendError('Method not allowed', 405);
                }
                $this->handleGetWards();
                break;

            case 'search':
                if ($method !== 'GET') {
                    $this->sendError('Method not allowed', 405);
                }
                $this->handleSearch();
                break;

            case 'reverse-geocode':
                if ($method !== 'GET') {
                    $this->sendError('Method not allowed', 405);
                }
                $this->handleReverseGeocode();
                break;

            default:
                $this->sendError('Invalid action', 404);
        }
    }

    /**
     * Handle get provinces
     */
    private function handleGetProvinces()
    {
        try {
            $provinces = $this->locationService->getProvinces();
            $this->sendSuccess($provinces, 'Provinces retrieved successfully');
        } catch (Exception $e) {
            $this->sendError('Failed to get provinces', 500, $e->getMessage());
        }
    }

    /**
     * Handle get districts
     */
    private function handleGetDistricts()
    {
        try {
            if (!isset($_GET['province_id'])) {
                $this->sendError('Missing province_id parameter', 400);
            }

            $provinceId = $_GET['province_id'];
            $districts = $this->locationService->getDistricts($provinceId);
            $this->sendSuccess($districts, 'Districts retrieved successfully');
        } catch (Exception $e) {
            $this->sendError('Failed to get districts', 500, $e->getMessage());
        }
    }

    /**
     * Handle get wards
     */
    private function handleGetWards()
    {
        try {
            if (!isset($_GET['district_id'])) {
                $this->sendError('Missing district_id parameter', 400);
            }

            $districtId = $_GET['district_id'];
            $wards = $this->locationService->getWards($districtId);
            $this->sendSuccess($wards, 'Wards retrieved successfully');
        } catch (Exception $e) {
            $this->sendError('Failed to get wards', 500, $e->getMessage());
        }
    }

    /**
     * Handle search
     */
    private function handleSearch()
    {
        try {
            $type = $_GET['type'] ?? '';
            $query = $_GET['q'] ?? '';

            if (empty($query)) {
                $this->sendError('Missing query parameter', 400);
            }

            $results = [];
            switch ($type) {
                case 'provinces':
                    $results = $this->locationService->searchProvinces($query);
                    break;
                case 'districts':
                    $provinceCode = $_GET['province_code'] ?? null;
                    $results = $this->locationService->searchDistricts($query, $provinceCode);
                    break;
                case 'wards':
                    $districtCode = $_GET['district_code'] ?? null;
                    $results = $this->locationService->searchWards($query, $districtCode);
                    break;
                default:
                    $this->sendError('Invalid search type', 400);
            }

            $this->sendSuccess($results, 'Search completed successfully');
        } catch (Exception $e) {
            $this->sendError('Search failed', 500, $e->getMessage());
        }
    }

    /**
     * Handle reverse geocoding
     */
    private function handleReverseGeocode()
    {
        try {
            $lat = $_GET['lat'] ?? '';
            $lng = $_GET['lng'] ?? '';

            if (empty($lat) || empty($lng)) {
                $this->sendError('Missing lat or lng parameters', 400);
            }

            if (!is_numeric($lat) || !is_numeric($lng)) {
                $this->sendError('Invalid lat or lng parameters', 400);
            }

            $result = $this->locationService->reverseGeocode($lat, $lng);
            $this->sendSuccess($result, 'Reverse geocoding completed successfully');
        } catch (Exception $e) {
            $this->sendError('Reverse geocoding failed', 500, $e->getMessage());
        }
    }
}
