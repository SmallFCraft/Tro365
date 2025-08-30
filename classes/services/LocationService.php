<?php

namespace Tro365\Services;

use Exception;

/**
 * Location Service - Sử dụng API provinces.open-api.vn
 * Tro365 - Website thuê trọ
 */
class LocationService
{
    private $baseUrl;
    private $timeout;
    private $cache;
    private $cacheLifetime;
    
    public function __construct()
    {
        $this->baseUrl = 'https://provinces.open-api.vn/api/v1';
        $this->timeout = 10; // 10 seconds timeout
        $this->cache = [];
        $this->cacheLifetime = 3600; // 1 hour cache
    }
    
    /**
     * Make HTTP request to API
     */
    private function makeRequest($endpoint, $params = [])
    {
        $cacheKey = 'loc:http:' . md5($endpoint . serialize($params));

        // Global cache first (PSR-16)
        $ttl = 3600;
        $cached = cache_get($cacheKey, null, $ttl);
        if ($cached !== null) return $cached;

        // Build URL
        $url = $this->baseUrl . $endpoint;
        $options = [ 'headers' => [ 'Accept' => 'application/json', 'Content-Type' => 'application/json' ], 'timeout' => $this->timeout ];
        if (!empty($params)) { $options['query'] = $params; }

        // Guzzle client with optional cache middleware (only if PSR-16 cache is available)
        $stack = \GuzzleHttp\HandlerStack::create();
        $psr16 = cache_client();
        if ($psr16 instanceof \Psr\SimpleCache\CacheInterface) {
            $cacheStorage = new \Kevinrob\GuzzleCache\Storage\Psr16CacheStorage($psr16);
            $stack->push(new \Kevinrob\GuzzleCache\CacheMiddleware(
                new \Kevinrob\GuzzleCache\Strategy\PrivateCacheStrategy($cacheStorage)
            ));
        }
        $client = new \GuzzleHttp\Client(['handler' => $stack, 'timeout' => $this->timeout]);

        try {
            $response = $client->request('GET', $url, $options);
            if ($response->getStatusCode() !== 200) {
                throw new Exception('API địa điểm trả về lỗi: HTTP ' . $response->getStatusCode());
            }
            $data = json_decode((string)$response->getBody(), true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new Exception('Lỗi parse JSON từ API địa điểm');
            }
            cache_set($cacheKey, $data, $ttl);
            return $data;
        } catch (\Throwable $e) {
            throw new Exception('Lỗi kết nối API địa điểm: ' . $e->getMessage());
        }
    }
    
    /**
     * Get all provinces
     */
    public function getProvinces()
    {
        try {
            $data = $this->makeRequest('/p/');
            
            // Transform to match our format
            $provinces = [];
            foreach ($data as $province) {
                $provinces[] = [
                    'ID' => $province['code'],
                    'TenTT' => $province['name'],
                    'MaTT' => $province['code']
                ];
            }
            
            return $provinces;
            
        } catch (Exception $e) {
            writeLog("LocationService::getProvinces error: " . $e->getMessage());
            
            // Return fallback data if API fails
            return $this->getFallbackProvinces();
        }
    }
    
    /**
     * Get districts by province code
     */
    public function getDistricts($provinceCode)
    {
        try {
            $data = $this->makeRequest("/p/$provinceCode", ['depth' => 2]);
            
            // Transform to match our format
            $districts = [];
            if (isset($data['districts'])) {
                foreach ($data['districts'] as $district) {
                    $districts[] = [
                        'ID' => $district['code'],
                        'TenQH' => $district['name'],
                        'MaQH' => $district['code'],
                        'TinhThanhID' => $provinceCode
                    ];
                }
            }
            
            return $districts;
            
        } catch (Exception $e) {
            writeLog("LocationService::getDistricts error: " . $e->getMessage());
            
            // Return empty array if API fails
            return [];
        }
    }
    
    /**
     * Get wards by district code
     */
    public function getWards($districtCode)
    {
        try {
            $data = $this->makeRequest("/d/$districtCode", ['depth' => 2]);
            
            // Transform to match our format
            $wards = [];
            if (isset($data['wards'])) {
                foreach ($data['wards'] as $ward) {
                    $wards[] = [
                        'ID' => $ward['code'],
                        'TenXP' => $ward['name'],
                        'MaXP' => $ward['code'],
                        'QuanHuyenID' => $districtCode
                    ];
                }
            }
            
            return $wards;
            
        } catch (Exception $e) {
            writeLog("LocationService::getWards error: " . $e->getMessage());
            
            // Return empty array if API fails
            return [];
        }
    }
    
    /**
     * Search provinces
     */
    public function searchProvinces($query)
    {
        try {
            $data = $this->makeRequest('/p/search/', ['q' => $query]);
            
            // Transform to match our format
            $provinces = [];
            foreach ($data as $province) {
                $provinces[] = [
                    'ID' => $province['code'],
                    'TenTT' => $province['name'],
                    'MaTT' => $province['code']
                ];
            }
            
            return $provinces;
            
        } catch (Exception $e) {
            writeLog("LocationService::searchProvinces error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Search districts
     */
    public function searchDistricts($query, $provinceCode = null)
    {
        try {
            $params = ['q' => $query];
            if ($provinceCode) {
                $params['p'] = $provinceCode;
            }
            
            $data = $this->makeRequest('/d/search/', $params);
            
            // Transform to match our format
            $districts = [];
            foreach ($data as $district) {
                $districts[] = [
                    'ID' => $district['code'],
                    'TenQH' => $district['name'],
                    'MaQH' => $district['code']
                ];
            }
            
            return $districts;
            
        } catch (Exception $e) {
            writeLog("LocationService::searchDistricts error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Search wards
     */
    public function searchWards($query, $districtCode = null)
    {
        try {
            $params = ['q' => $query];
            if ($districtCode) {
                $params['d'] = $districtCode;
            }
            
            $data = $this->makeRequest('/w/search/', $params);
            
            // Transform to match our format
            $wards = [];
            foreach ($data as $ward) {
                $wards[] = [
                    'ID' => $ward['code'],
                    'TenXP' => $ward['name'],
                    'MaXP' => $ward['code']
                ];
            }
            
            return $wards;
            
        } catch (Exception $e) {
            writeLog("LocationService::searchWards error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get province name by code
     */
    public function getProvinceName($code)
    {
        static $cache = [];
        if (isset($cache[$code])) return $cache[$code];
        try {
            $data = $this->makeRequest("/p/$code");
            return $cache[$code] = ($data['name'] ?? '');
        } catch (Exception $e) {
            return $cache[$code] = '';
        }
    }

    /**
     * Reverse geocode coordinates to location
     * Uses a simple distance-based approach to find nearest province
     */
    public function reverseGeocode($lat, $lng)
    {
        try {
            // Get all provinces
            $provinces = $this->getProvinces();

            // Vietnam province coordinates (approximate centers)
            $provinceCoords = [
                '01' => ['lat' => 21.0285, 'lng' => 105.8542, 'name' => 'Thành phố Hà Nội'], // Hanoi
                '79' => ['lat' => 10.8231, 'lng' => 106.6297, 'name' => 'Thành phố Hồ Chí Minh'], // Ho Chi Minh
                '48' => ['lat' => 16.0544, 'lng' => 108.2022, 'name' => 'Thành phố Đà Nẵng'], // Da Nang
                '31' => ['lat' => 20.8449, 'lng' => 106.6881, 'name' => 'Thành phố Hải Phòng'], // Hai Phong
                '92' => ['lat' => 10.0452, 'lng' => 105.7469, 'name' => 'Thành phố Cần Thơ'], // Can Tho
                '02' => ['lat' => 22.8026, 'lng' => 105.0861, 'name' => 'Tỉnh Hà Giang'],
                '04' => ['lat' => 22.6663, 'lng' => 106.2581, 'name' => 'Tỉnh Cao Bằng'],
                '06' => ['lat' => 22.1474, 'lng' => 105.8348, 'name' => 'Tỉnh Bắc Kạn'],
                '08' => ['lat' => 21.8240, 'lng' => 105.2280, 'name' => 'Tỉnh Tuyên Quang'],
                '10' => ['lat' => 22.4856, 'lng' => 103.9707, 'name' => 'Tỉnh Lào Cai'],
                '11' => ['lat' => 21.3891, 'lng' => 103.0198, 'name' => 'Tỉnh Điện Biên'],
                '12' => ['lat' => 22.3380, 'lng' => 103.4608, 'name' => 'Tỉnh Lai Châu'],
                '14' => ['lat' => 21.3256, 'lng' => 103.9188, 'name' => 'Tỉnh Sơn La'],
                '15' => ['lat' => 21.7168, 'lng' => 104.8986, 'name' => 'Tỉnh Yên Bái'],
                '17' => ['lat' => 20.8173, 'lng' => 105.3380, 'name' => 'Tỉnh Hoà Bình'],
                '19' => ['lat' => 21.5944, 'lng' => 105.8480, 'name' => 'Tỉnh Thái Nguyên'],
                '20' => ['lat' => 21.8564, 'lng' => 106.7610, 'name' => 'Tỉnh Lạng Sơn'],
                '22' => ['lat' => 20.9593, 'lng' => 107.0426, 'name' => 'Tỉnh Quảng Ninh'],
                '24' => ['lat' => 21.2819, 'lng' => 106.1946, 'name' => 'Tỉnh Bắc Giang'],
                '25' => ['lat' => 21.4208, 'lng' => 105.2045, 'name' => 'Tỉnh Phú Thọ'],
                '26' => ['lat' => 21.3609, 'lng' => 105.6057, 'name' => 'Tỉnh Vĩnh Phúc'],
                '27' => ['lat' => 21.1861, 'lng' => 106.0763, 'name' => 'Tỉnh Bắc Ninh'],
                '30' => ['lat' => 20.9373, 'lng' => 106.3256, 'name' => 'Tỉnh Hải Dương'],
                '33' => ['lat' => 20.6540, 'lng' => 106.0522, 'name' => 'Tỉnh Hưng Yên'],
                '34' => ['lat' => 20.4501, 'lng' => 106.3404, 'name' => 'Tỉnh Thái Bình'],
                '35' => ['lat' => 20.5835, 'lng' => 105.9230, 'name' => 'Tỉnh Hà Nam'],
                '36' => ['lat' => 20.4388, 'lng' => 106.1621, 'name' => 'Tỉnh Nam Định'],
                '37' => ['lat' => 20.2506, 'lng' => 105.9745, 'name' => 'Tỉnh Ninh Bình'],
                '38' => ['lat' => 19.8077, 'lng' => 105.7851, 'name' => 'Tỉnh Thanh Hóa'],
                '40' => ['lat' => 18.6740, 'lng' => 105.6905, 'name' => 'Tỉnh Nghệ An'],
                '42' => ['lat' => 18.3560, 'lng' => 105.9058, 'name' => 'Tỉnh Hà Tĩnh'],
                '44' => ['lat' => 17.4677, 'lng' => 106.6234, 'name' => 'Tỉnh Quảng Bình'],
                '45' => ['lat' => 16.7403, 'lng' => 107.1851, 'name' => 'Tỉnh Quảng Trị'],
                '46' => ['lat' => 16.4637, 'lng' => 107.5909, 'name' => 'Thành phố Huế'],
                '49' => ['lat' => 15.5394, 'lng' => 108.0191, 'name' => 'Tỉnh Quảng Nam'],
                '51' => ['lat' => 15.1214, 'lng' => 108.8044, 'name' => 'Tỉnh Quảng Ngãi'],
                '52' => ['lat' => 13.7765, 'lng' => 109.2216, 'name' => 'Tỉnh Bình Định'],
                '54' => ['lat' => 13.0882, 'lng' => 109.0929, 'name' => 'Tỉnh Phú Yên'],
                '56' => ['lat' => 12.2388, 'lng' => 109.1967, 'name' => 'Tỉnh Khánh Hòa'],
                '58' => ['lat' => 11.5753, 'lng' => 108.9971, 'name' => 'Tỉnh Ninh Thuận'],
                '60' => ['lat' => 11.0904, 'lng' => 108.0721, 'name' => 'Tỉnh Bình Thuận'],
                '62' => ['lat' => 14.3497, 'lng' => 107.9650, 'name' => 'Tỉnh Kon Tum'],
                '64' => ['lat' => 13.9316, 'lng' => 108.0002, 'name' => 'Tỉnh Gia Lai'],
                '66' => ['lat' => 12.7100, 'lng' => 108.2378, 'name' => 'Tỉnh Đắk Lắk'],
                '67' => ['lat' => 12.2646, 'lng' => 107.6098, 'name' => 'Tỉnh Đắk Nông'],
                '68' => ['lat' => 11.9404, 'lng' => 108.4583, 'name' => 'Tỉnh Lâm Đồng'],
                '70' => ['lat' => 11.6738, 'lng' => 106.6320, 'name' => 'Tỉnh Bình Phước'],
                '72' => ['lat' => 11.3254, 'lng' => 106.1110, 'name' => 'Tỉnh Tây Ninh'],
                '74' => ['lat' => 11.3254, 'lng' => 106.7772, 'name' => 'Tỉnh Bình Dương'],
                '75' => ['lat' => 10.9571, 'lng' => 107.1676, 'name' => 'Tỉnh Đồng Nai'],
                '77' => ['lat' => 10.4113, 'lng' => 107.1362, 'name' => 'Tỉnh Bà Rịa - Vũng Tàu'],
                '80' => ['lat' => 10.6769, 'lng' => 106.6151, 'name' => 'Tỉnh Long An'],
                '82' => ['lat' => 10.3559, 'lng' => 106.3621, 'name' => 'Tỉnh Tiền Giang'],
                '83' => ['lat' => 10.2431, 'lng' => 106.3757, 'name' => 'Tỉnh Bến Tre'],
                '84' => ['lat' => 9.9344, 'lng' => 106.3453, 'name' => 'Tỉnh Trà Vinh'],
                '86' => ['lat' => 10.2397, 'lng' => 105.9571, 'name' => 'Tỉnh Vĩnh Long'],
                '87' => ['lat' => 10.6637, 'lng' => 105.6516, 'name' => 'Tỉnh Đồng Tháp'],
                '89' => ['lat' => 10.5216, 'lng' => 105.1259, 'name' => 'Tỉnh An Giang'],
                '91' => ['lat' => 10.0125, 'lng' => 105.0808, 'name' => 'Tỉnh Kiên Giang'],
                '93' => ['lat' => 9.5328, 'lng' => 105.6420, 'name' => 'Tỉnh Hậu Giang'],
                '94' => ['lat' => 9.6003, 'lng' => 105.9739, 'name' => 'Tỉnh Sóc Trăng'],
                '95' => ['lat' => 9.2940, 'lng' => 105.7215, 'name' => 'Tỉnh Bạc Liêu'],
                '96' => ['lat' => 9.1767, 'lng' => 105.1524, 'name' => 'Tỉnh Cà Mau']
            ];

            // Find nearest province
            $nearestProvince = null;
            $minDistance = PHP_FLOAT_MAX;

            foreach ($provinceCoords as $code => $coords) {
                $distance = $this->calculateDistance($lat, $lng, $coords['lat'], $coords['lng']);
                if ($distance < $minDistance) {
                    $minDistance = $distance;
                    $nearestProvince = [
                        'ID' => $code,
                        'TenTT' => $coords['name'],
                        'MaTT' => $code
                    ];
                }
            }

            if ($nearestProvince) {
                // Try to get districts for the province
                $districts = $this->getDistricts($nearestProvince['ID']);
                $nearestDistrict = null;

                if (!empty($districts)) {
                    // For simplicity, just return the first district
                    // In a real implementation, you'd calculate distance to district centers
                    $nearestDistrict = $districts[0];
                }

                return [
                    'success' => true,
                    'province' => $nearestProvince,
                    'district' => $nearestDistrict,
                    'ward' => null, // Could be implemented similarly
                    'distance' => round($minDistance, 2)
                ];
            }

            return [
                'success' => false,
                'error' => 'No location found for coordinates'
            ];

        } catch (Exception $e) {
            writeLog("LocationService::reverseGeocode error: " . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Reverse geocoding failed'
            ];
        }
    }

    /**
     * Calculate distance between two coordinates using Haversine formula
     */
    private function calculateDistance($lat1, $lng1, $lat2, $lng2)
    {
        $earthRadius = 6371; // Earth radius in kilometers

        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);

        $a = sin($dLat/2) * sin($dLat/2) +
             cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
             sin($dLng/2) * sin($dLng/2);

        $c = 2 * atan2(sqrt($a), sqrt(1-$a));
        $distance = $earthRadius * $c;

        return $distance;
    }
    
    /**
     * Get district name by code
     */
    public function getDistrictName($code)
    {
        static $cache = [];
        if (isset($cache[$code])) return $cache[$code];
        try {
            $data = $this->makeRequest("/d/$code");
            return $cache[$code] = ($data['name'] ?? '');
        } catch (Exception $e) {
            return $cache[$code] = '';
        }
    }
    
    /**
     * Get ward name by code
     */
    public function getWardName($code)
    {
        static $cache = [];
        if (isset($cache[$code])) return $cache[$code];
        try {
            $data = $this->makeRequest("/w/$code");
            return $cache[$code] = ($data['name'] ?? '');
        } catch (Exception $e) {
            return $cache[$code] = '';
        }
    }

    /**
     * Get province by ID/code (alias for getProvinceName)
     */
    public function getProvinceById($code)
    {
        try {
            $data = $this->makeRequest("/p/$code");
            return $data ?: ['name' => ''];
        } catch (Exception $e) {
            return ['name' => ''];
        }
    }

    /**
     * Get district by ID/code (alias for getDistrictName)
     */
    public function getDistrictById($code)
    {
        try {
            $data = $this->makeRequest("/d/$code");
            return $data ?: ['name' => ''];
        } catch (Exception $e) {
            return ['name' => ''];
        }
    }

    /**
     * Get ward by ID/code (alias for getWardName)
     */
    public function getWardById($code)
    {
        try {
            $data = $this->makeRequest("/w/$code");
            return $data ?: ['name' => ''];
        } catch (Exception $e) {
            return ['name' => ''];
        }
    }
    
    /**
     * Fallback provinces data if API fails
     */
    private function getFallbackProvinces()
    {
        return [
            ['ID' => 1, 'TenTT' => 'Hà Nội', 'MaTT' => '1'],
            ['ID' => 79, 'TenTT' => 'Hồ Chí Minh', 'MaTT' => '79'],
            ['ID' => 48, 'TenTT' => 'Đà Nẵng', 'MaTT' => '48'],
            ['ID' => 31, 'TenTT' => 'Hải Phòng', 'MaTT' => '31'],
            ['ID' => 92, 'TenTT' => 'Cần Thơ', 'MaTT' => '92']
        ];
    }
    
    /**
     * Clear cache
     */
    public function clearCache()
    {
        $this->cache = [];
    }
}
