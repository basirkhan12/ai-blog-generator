<?php
namespace AIBG\Core;

class Generator {
    private $deepseek_api;
    private $unsplash_api;
    private $seo_helper;
    
    public function __construct() {
        $this->deepseek_api = new \AIBG\API\DeepseekAPI();
        $this->unsplash_api = new \AIBG\API\UnsplashAPI();
        $this->seo_helper = new \AIBG\Utils\SEO();
    }
    
    public function generate_post($topic = null, $manual = false) {
        try {
            // Generate or use provided topic
            if (!$topic) {
                $topic = $this->generate_topic();
            }
            
            // Generate post content
            $post_data = $this->generate_content($topic);
            
            // Generate featured image
            $featured_image_id = $this->get_featured_image($topic);
            
            // Create post
            $post_id = $this->create_wordpress_post($post_data, $featured_image_id);
            
            // Save tracking data
            $this->save_tracking_data($post_id, $topic, $post_data);
            
            // Update analytics
            \AIBG\Utils\Analytics::increment_stat('posts_generated');
            
            return [
                'success' => true,
                'post_id' => $post_id,
                'topic' => $topic,
                "post_Data" => $post_data,
                'status' => get_post_status($post_id)
            ];
            
        } catch (\Exception $e) {
            error_log('AIBG Error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    private function generate_topic() {
        // Get existing categories and tags for context
        $categories = get_categories(['hide_empty' => false]);
        $tags = get_tags(['hide_empty' => false]);
        
        $context = "Generate a blog topic based on these categories: ";
        $context .= implode(', ', array_column($categories, 'name'));
        
        $prompt = "Generate a single, engaging blog post topic that would be relevant and SEO-friendly. 
        Consider current trends and user interests. Return only the topic, no additional text.
        Context: " . $context;
        
        $response = $this->deepseek_api->generate($prompt, 100);
        return trim($response);
    }
    
    private function generate_content($topic) {
        $post_length = get_option('aibg_post_length', 'medium');
        $max_tokens = [
            'short' => 800,
            'medium' => 1500,
            'long' => 2500
        ][$post_length];
        
        $internal_links = (int) get_option('aibg_internal_links', 3);
        $external_links = (int) get_option('aibg_external_links', 2);
        
        // Get recent posts for internal linking
        $recent_posts = get_posts([
            'numberposts' => 10,
            'post_status' => 'publish'
        ]);
        
        $internal_links_context = '';
        if (!empty($recent_posts) && $internal_links > 0) {
            $internal_links_context = "\n\nInclude $internal_links internal links to these posts where relevant:\n";
            foreach ($recent_posts as $post) {
                $internal_links_context .= "- " . $post->post_title . " (" . get_permalink($post->ID) . ")\n";
            }
        }
        
        $prompt = "Write a comprehensive, SEO-optimized blog post about: $topic

Requirements:
1. Create an engaging title (H1)
2. Write a compelling introduction
3. Use proper heading structure (H2, H3)
4. Include 5-7 relevant tags (comma-separated)
5. Suggest 2-3 categories
6. Include a meta description (150-160 characters)
7. " . ($external_links > 0 ? "Include $external_links external links to authoritative sources" : "No external links needed") . "
8. Make it conversational yet professional
9. Ensure proper keyword density
10. End with a strong conclusion
$internal_links_context

Format the response as JSON with this structure:
{
    \"title\": \"Post title\",
    \"content\": \"Full HTML content with proper formatting\",
    \"excerpt\": \"Brief excerpt\",
    \"meta_description\": \"SEO meta description\",
    \"tags\": [\"tag1\", \"tag2\"],
    \"categories\": [\"category1\", \"category2\"],
    \"focus_keyword\": \"main keyword\"
}";
        
        $response = $this->deepseek_api->generate($prompt, $max_tokens);

       
        
        // Parse JSON response
        $data = json_decode($response, true);
        if (!$data) {
            // Fallback if JSON parsing fails
            return [
                'title' =>$data["title"] ?? $topic,
                'content' => $data['content'],
                'excerpt' => wp_trim_words($data["excerpt"], 55),
                'meta_description' => wp_trim_words($data['excerpt'], 25),
                'tags' => [$topic],
                'categories' =>$data["categories"] ?? ['Uncategorized'],
                'focus_keyword' => $data["focus_keyword"] ?? $topic,
                "data" => $response
            ];
        }
        
        return $data;
    }
    
    private function get_featured_image($topic) {
        $image_url = $this->unsplash_api->fetch_image($topic);
        
        if (!$image_url) {
            return 0;
        }
        
        // Download and attach image to media library
        require_once(ABSPATH . 'wp-admin/includes/media.php');
        require_once(ABSPATH . 'wp-admin/includes/file.php');
        require_once(ABSPATH . 'wp-admin/includes/image.php');
        
        $tmp = download_url($image_url);
        
        if (is_wp_error($tmp)) {
            return 0;
        }
        
        $file_array = [
            'name' => sanitize_file_name($topic) . '.jpg',
            'tmp_name' => $tmp
        ];
        
        $id = media_handle_sideload($file_array, 0);
        
        if (is_wp_error($id)) {
            @unlink($file_array['tmp_name']);
            return 0;
        }
        
        return $id;
    }
    
    private function create_wordpress_post($post_data, $featured_image_id) {
    $auto_publish = get_option('aibg_auto_publish', 'draft');
    
    // Check if content is JSON encoded
    $content = $post_data['content'] ?? '';
    
    // Try to decode if it looks like JSON
    $decoded_content = json_decode($content, true);
    
    if (json_last_error() === JSON_ERROR_NONE && isset($decoded_content['content'])) {
        // If it's a JSON object with a 'content' field, use that
        $post_content = $decoded_content['content'];
    } elseif (json_last_error() === JSON_ERROR_NONE && is_string($decoded_content)) {
        // If it's a JSON string, use the decoded string
        $post_content = $decoded_content;
    } else {
        // Otherwise use the original content
        $post_content = $content;
    }
    
    // If content contains HTML tags, use it directly
    // Otherwise, try to parse it as Markdown
    if (strip_tags($post_content) === $post_content) {
        // No HTML tags found, parse as Markdown
        $Parsedown = new \Parsedown();
        $post_content = $Parsedown->text($post_content);
    }
    
    $post_arr = [
        'post_title' => sanitize_text_field($post_data['title']),
        'post_content' => wp_kses_post($post_content),
        'post_excerpt' => sanitize_text_field($post_data['excerpt'] ?? ''),
        'post_status' => $auto_publish === 'publish' ? 'publish' : 'draft',
        'post_type' => 'post',
        'post_author' => get_current_user_id() ?: 1
    ];
    
    $post_id = wp_insert_post($post_arr);
    
    if (is_wp_error($post_id)) {
        throw new \Exception('Failed to create post: ' . $post_id->get_error_message());
    }
    
    // Set featured image
    if ($featured_image_id) {
        set_post_thumbnail($post_id, $featured_image_id);
    }
    
    // Set categories
    if (!empty($post_data['categories'])) {
        $category_ids = [];
        foreach ($post_data['categories'] as $cat_name) {
            $cat = get_term_by('name', $cat_name, 'category');
            if (!$cat) {
                $cat = wp_insert_term($cat_name, 'category');
                if (!is_wp_error($cat)) {
                    $category_ids[] = $cat['term_id'];
                }
            } else {
                $category_ids[] = $cat->term_id;
            }
        }
        wp_set_post_categories($post_id, $category_ids);
    }
    
    // Set tags
    if (!empty($post_data['tags'])) {
        wp_set_post_tags($post_id, $post_data['tags']);
    }
    
    // Set SEO meta
    if (get_option('aibg_seo_enabled', '1') === '1') {
        update_post_meta($post_id, '_aibg_meta_description', $post_data['meta_description'] ?? '');
        update_post_meta($post_id, '_aibg_focus_keyword', $post_data['focus_keyword'] ?? '');
    }
    
    return $post_id;
}
    private function save_tracking_data($post_id, $topic, $post_data) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'aibg_posts';
        
        $wpdb->insert(
            $table_name,
            [
                'post_id' => $post_id,
                'topic' => $topic,
                'status' => get_post_status($post_id),
                'featured_image_url' => get_the_post_thumbnail_url($post_id),
                'generation_date' => current_time('mysql'),
                'publish_date' => get_post_status($post_id) === 'publish' ? current_time('mysql') : null,
                'metadata' => json_encode([
                    'tags' => $post_data['tags'] ?? [],
                    'categories' => $post_data['categories'] ?? [],
                    'focus_keyword' => $post_data['focus_keyword'] ?? ''
                ])
            ],
            ['%d', '%s', '%s', '%s', '%s', '%s', '%s']
        );
    }
    
    public function generate_post_endpoint($request) {
        $topic = $request->get_param('topic');
        $result = $this->generate_post($topic, true);
        
        return new \WP_REST_Response($result, $result['success'] ? 200 : 500);
    }
    
    public function bulk_generate($count = 5, $topics = []) {
        $results = [];
        
        for ($i = 0; $i < $count; $i++) {
            $topic = $topics[$i] ?? null;
            $result = $this->generate_post($topic);
            $results[] = $result;
            
            // Sleep to avoid API rate limits
            sleep(2);
        }
        
        return $results;
    }
}