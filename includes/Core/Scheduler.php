<?php
namespace AIBG\Core;

class Scheduler {
    public function __construct() {
        add_action('aibg_generate_post', [$this, 'scheduled_generation']);
        add_filter('cron_schedules', [$this, 'add_custom_intervals']);
    }
    
    public function add_custom_intervals($schedules) {
        $schedules['weekly'] = [
            'interval' => 604800,
            'display' => __('Once Weekly', 'ai-blog-generator')
        ];
        
        return $schedules;
    }
    
    public function scheduled_generation() {
        $generator = new Generator();
        $result = $generator->generate_post();
        
        // Log result
        if ($result['success']) {
            error_log('AIBG: Scheduled post generated successfully. Post ID: ' . $result['post_id']);
        } else {
            error_log('AIBG: Scheduled generation failed: ' . $result['error']);
        }
        
        return $result;
    }
    
    public static function reschedule() {
        $frequency = get_option('aibg_post_frequency', 'daily');
        
        // Clear existing schedule
        $timestamp = wp_next_scheduled('aibg_generate_post');
        if ($timestamp) {
            wp_unschedule_event($timestamp, 'aibg_generate_post');
        }
        
        // Schedule new event
        if (!wp_next_scheduled('aibg_generate_post')) {
            wp_schedule_event(time(), $frequency, 'aibg_generate_post');
        }
    }
}