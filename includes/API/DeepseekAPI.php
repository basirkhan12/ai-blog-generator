<?php
namespace AIBG\API;

class DeepseekAPI {
    private $api_key;
    private $api_url = 'https://api.deepseek.com/v1/chat/completions';
    
    public function __construct() {
        $this->api_key = get_option('aibg_deepseek_api_key', '');
    }
    
    public function generate($prompt, $max_tokens = 1500) {
        if (empty($this->api_key)) {
            throw new \Exception('Deepseek API key is not configured');
        }
        
        $data = [
            'model' => 'deepseek-chat',
            'messages' => [
                [
                    'role' => 'system',
                    'content' => 'You are an expert content writer who creates engaging, SEO-optimized blog posts.'
                ],
                [
                    'role' => 'user',
                    'content' => $prompt
                ]
            ],
            'max_tokens' => $max_tokens,
            'temperature' => 0.7
        ];
        
        $response = wp_remote_post($this->api_url, [
            'headers' => [
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $this->api_key
            ],
            'body' => json_encode($data),
            'timeout' => 600
        ]);
        
        if (is_wp_error($response)) {
            throw new \Exception('API request failed: ' . $response->get_error_message());
        }
        
        $body = json_decode(wp_remote_retrieve_body($response), true);
        
        if (isset($body['error'])) {
            throw new \Exception('API error: ' . $body['error']['message']);
        }
        
        if (!isset($body['choices'][0]['message']['content'])) {
            throw new \Exception('Invalid API response format');
        }

        $content = $this->clean_json_response($body['choices'][0]['message']['content']);
        
        return $content;
    }
    
    public function test_connection() {
        try {
            $this->generate('Say "Hello"', 10);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }
    
    public function clean_json_response($content) {
        // Remove ```json and ``` markers
        $content = preg_replace('/^```json\s*/', '', $content);
        $content = preg_replace('/\s*```$/', '', $content);
        
        // Remove any other markdown code block indicators
        $content = preg_replace('/^```\s*/', '', $content);
        
        // Trim whitespace
        return trim($content);
    }
    
}