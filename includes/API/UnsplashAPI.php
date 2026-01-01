<?php
namespace AIBG\API;

class UnsplashAPI {
    private $api_key;
    private $api_url = 'https://api.unsplash.com';
    
    public function __construct() {
        $this->api_key = get_option('aibg_unsplash_api_key', '');
    }
    
    public function fetch_image($keyword, $custom_params = []) {
        if (empty($this->api_key)) {
            throw new \Exception('Unsplash API key is not configured');
        }
        
        $orientation = get_option('aibg_image_orientation', 'landscape');
        $resolution = get_option('aibg_image_resolution', 'regular');
        
        $params = array_merge([
            'query' => sanitize_text_field($keyword),
            'orientation' => $orientation,
            'per_page' => 1
        ], $custom_params);
        
        $url = add_query_arg($params, $this->api_url . '/search/photos');
        
        $response = wp_remote_get($url, [
            'headers' => [
                'Authorization' => 'Client-ID ' . $this->api_key
            ],
            'timeout' => 30
        ]);
        
        if (is_wp_error($response)) {
            throw new \Exception('Unsplash API request failed: ' . $response->get_error_message());
        }
        
        $body = json_decode(wp_remote_retrieve_body($response), true);
        
        if (isset($body['errors'])) {
            throw new \Exception('Unsplash API error: ' . implode(', ', $body['errors']));
        }
        
        if (empty($body['results'])) {
            // Fallback to generic search
            return $this->fetch_image('blog', ['query' => 'blog']);
        }
        
        $image = $body['results'][0];
        
        // Trigger download endpoint (required by Unsplash API)
        if (isset($image['links']['download_location'])) {
            wp_remote_get($image['links']['download_location'], [
                'headers' => [
                    'Authorization' => 'Client-ID ' . $this->api_key
                ]
            ]);
        }
        
        // Return appropriate resolution
        $resolutions = [
            'raw' => $image['urls']['raw'],
            'full' => $image['urls']['full'],
            'regular' => $image['urls']['regular'],
            'small' => $image['urls']['small'],
            'thumb' => $image['urls']['thumb']
        ];
        
        return $resolutions[$resolution] ?? $resolutions['regular'];
    }
    
    public function test_connection() {
        try {
            $this->fetch_image('test');
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }
    
    public function search_images($keyword, $count = 10) {
        if (empty($this->api_key)) {
            throw new \Exception('Unsplash API key is not configured');
        }
        
        $params = [
            'query' => sanitize_text_field($keyword),
            'per_page' => min($count, 30)
        ];
        
        $url = add_query_arg($params, $this->api_url . '/search/photos');
        
        $response = wp_remote_get($url, [
            'headers' => [
                'Authorization' => 'Client-ID ' . $this->api_key
            ],
            'timeout' => 30
        ]);
        
        if (is_wp_error($response)) {
            return [];
        }
        
        $body = json_decode(wp_remote_retrieve_body($response), true);
        
        if (empty($body['results'])) {
            return [];
        }
        
        return array_map(function($img) {
            return [
                'id' => $img['id'],
                'url' => $img['urls']['regular'],
                'thumb' => $img['urls']['thumb'],
                'description' => $img['description'] ?? '',
                'photographer' => $img['user']['name'] ?? '',
                'download_location' => $img['links']['download_location']
            ];
        }, $body['results']);
    }
}