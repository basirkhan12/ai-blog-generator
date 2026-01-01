<?php
namespace AIBG\Utils;

class SEO {
    public function __construct() {
        add_action('wp_head', [$this, 'output_meta_tags']);
    }
    
    public function output_meta_tags() {
        if (!is_single()) {
            return;
        }
        
        global $post;
        
        $meta_description = get_post_meta($post->ID, '_aibg_meta_description', true);
        $focus_keyword = get_post_meta($post->ID, '_aibg_focus_keyword', true);
        
        if ($meta_description) {
            echo '<meta name="description" content="' . esc_attr($meta_description) . '">' . "\n";
        }
        
        if ($focus_keyword) {
            echo '<meta name="keywords" content="' . esc_attr($focus_keyword) . '">' . "\n";
        }
        
        // Open Graph tags
        echo '<meta property="og:title" content="' . esc_attr(get_the_title()) . '">' . "\n";
        if ($meta_description) {
            echo '<meta property="og:description" content="' . esc_attr($meta_description) . '">' . "\n";
        }
        
        $thumbnail = get_the_post_thumbnail_url($post->ID, 'large');
        if ($thumbnail) {
            echo '<meta property="og:image" content="' . esc_url($thumbnail) . '">' . "\n";
        }
        
        // Twitter Card tags
        echo '<meta name="twitter:card" content="summary_large_image">' . "\n";
        echo '<meta name="twitter:title" content="' . esc_attr(get_the_title()) . '">' . "\n";
        if ($meta_description) {
            echo '<meta name="twitter:description" content="' . esc_attr($meta_description) . '">' . "\n";
        }
        if ($thumbnail) {
            echo '<meta name="twitter:image" content="' . esc_url($thumbnail) . '">' . "\n";
        }
    }
    
    public function analyze_content($content, $focus_keyword = '') {
        $analysis = [
            'word_count' => str_word_count(strip_tags($content)),
            'paragraph_count' => substr_count($content, '<p>'),
            'heading_count' => substr_count($content, '<h2>') + substr_count($content, '<h3>'),
            'image_count' => substr_count($content, '<img'),
            'link_count' => substr_count($content, '<a'),
            'keyword_density' => 0,
            'readability_score' => 0
        ];
        
        if ($focus_keyword && $analysis['word_count'] > 0) {
            $keyword_count = substr_count(strtolower($content), strtolower($focus_keyword));
            $analysis['keyword_density'] = ($keyword_count / $analysis['word_count']) * 100;
        }
        
        // Simple readability score (Flesch Reading Ease approximation)
        $sentences = preg_split('/[.!?]+/', strip_tags($content), -1, PREG_SPLIT_NO_EMPTY);
        $sentence_count = count($sentences);
        
        if ($sentence_count > 0 && $analysis['word_count'] > 0) {
            $avg_words_per_sentence = $analysis['word_count'] / $sentence_count;
            $analysis['readability_score'] = 206.835 - (1.015 * $avg_words_per_sentence);
        }
        
        return $analysis;
    }
    
    public function generate_slug($title) {
        return sanitize_title($title);
    }
    
    public function optimize_internal_links($content, $post_id = 0) {
        // Get related posts
        $related_posts = get_posts([
            'post__not_in' => [$post_id],
            'numberposts' => 5,
            'post_status' => 'publish'
        ]);
        
        // Logic to insert internal links would go here
        // This is a simplified version
        return $content;
    }
}