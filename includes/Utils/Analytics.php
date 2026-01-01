<?php
namespace AIBG\Utils;

class Analytics {
    
    public function __construct() {
        // Hook to track post views if needed
        add_action('wp_head', [$this, 'track_post_view']);
    }
    
    /**
     * Get overall statistics
     */
    public function get_stats() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'aibg_posts';
        
        $stats = [
            'posts_generated' => $wpdb->get_var("SELECT COUNT(*) FROM $table_name"),
            'posts_published' => $wpdb->get_var("SELECT COUNT(*) FROM $table_name WHERE status = 'publish'"),
            'posts_draft' => $wpdb->get_var("SELECT COUNT(*) FROM $table_name WHERE status = 'draft'"),
            'posts_scheduled' => $wpdb->get_var("SELECT COUNT(*) FROM $table_name WHERE status = 'future'"),
            'posts_pending' => $wpdb->get_var("SELECT COUNT(*) FROM $table_name WHERE status = 'pending'"),
            'images_used' => $wpdb->get_var("SELECT COUNT(*) FROM $table_name WHERE featured_image_url IS NOT NULL AND featured_image_url != ''"),
            'total_words' => $this->get_total_word_count(),
            'avg_generation_time' => $this->get_average_generation_time()
        ];
        
        return $stats;
    }
    
    /**
     * Get analytics data for a specific period
     */
    public function get_analytics_data($days = 30) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'aibg_posts';
        
        // Posts by day
        $posts_by_day = $wpdb->get_results($wpdb->prepare("
            SELECT DATE(generation_date) as date, COUNT(*) as count
            FROM $table_name
            WHERE generation_date >= DATE_SUB(NOW(), INTERVAL %d DAY)
            GROUP BY DATE(generation_date)
            ORDER BY date ASC
        ", $days));
        
        // Posts by status
        $posts_by_status = $wpdb->get_results("
            SELECT status, COUNT(*) as count
            FROM $table_name
            GROUP BY status
        ");
        
        // Posts by hour of day (for scheduling optimization)
        $posts_by_hour = $wpdb->get_results("
            SELECT HOUR(generation_date) as hour, COUNT(*) as count
            FROM $table_name
            WHERE generation_date >= DATE_SUB(NOW(), INTERVAL 30 DAY)
            GROUP BY HOUR(generation_date)
            ORDER BY hour ASC
        ");
        
        // Top topics/categories
        $top_topics = $wpdb->get_results($wpdb->prepare("
            SELECT topic, COUNT(*) as count
            FROM $table_name
            WHERE generation_date >= DATE_SUB(NOW(), INTERVAL %d DAY)
            GROUP BY topic
            ORDER BY count DESC
            LIMIT 10
        ", $days));
        
        // Recent activity
        $recent_posts = $wpdb->get_results($wpdb->prepare("
            SELECT * FROM $table_name
            ORDER BY generation_date DESC
            LIMIT 10
        "));
        
        // Success rate (published vs total)
        $success_rate = $this->calculate_success_rate();
        
        // Monthly comparison
        $monthly_comparison = $this->get_monthly_comparison();
        
        return [
            'posts_by_day' => $posts_by_day,
            'posts_by_status' => $posts_by_status,
            'posts_by_hour' => $posts_by_hour,
            'top_topics' => $top_topics,
            'recent_posts' => $recent_posts,
            'success_rate' => $success_rate,
            'monthly_comparison' => $monthly_comparison
        ];
    }
    
    /**
     * Calculate success rate (published posts vs total)
     */
    private function calculate_success_rate() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'aibg_posts';
        
        $total = $wpdb->get_var("SELECT COUNT(*) FROM $table_name");
        $published = $wpdb->get_var("SELECT COUNT(*) FROM $table_name WHERE status = 'publish'");
        
        if ($total == 0) {
            return 0;
        }
        
        return round(($published / $total) * 100, 2);
    }
    
    /**
     * Get monthly comparison data
     */
    private function get_monthly_comparison() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'aibg_posts';
        
        $current_month = $wpdb->get_var("
            SELECT COUNT(*) FROM $table_name
            WHERE YEAR(generation_date) = YEAR(CURDATE())
            AND MONTH(generation_date) = MONTH(CURDATE())
        ");
        
        $last_month = $wpdb->get_var("
            SELECT COUNT(*) FROM $table_name
            WHERE YEAR(generation_date) = YEAR(DATE_SUB(CURDATE(), INTERVAL 1 MONTH))
            AND MONTH(generation_date) = MONTH(DATE_SUB(CURDATE(), INTERVAL 1 MONTH))
        ");
        
        $growth = 0;
        if ($last_month > 0) {
            $growth = round((($current_month - $last_month) / $last_month) * 100, 2);
        }
        
        return [
            'current_month' => $current_month,
            'last_month' => $last_month,
            'growth_percentage' => $growth
        ];
    }
    
    /**
     * Get total word count across all generated posts
     */
    private function get_total_word_count() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'aibg_posts';
        
        $post_ids = $wpdb->get_col("SELECT post_id FROM $table_name");
        $total_words = 0;
        
        foreach ($post_ids as $post_id) {
            $post = get_post($post_id);
            if ($post) {
                $total_words += str_word_count(strip_tags($post->post_content));
            }
        }
        
        return $total_words;
    }
    
    /**
     * Get average generation time (if tracked)
     */
    private function get_average_generation_time() {
        // This would need to be implemented with timing tracking in the Generator class
        // For now, return a placeholder
        return 0;
    }
    
    /**
     * Increment a specific statistic
     */
    public static function increment_stat($stat_name) {
        $current = get_option('aibg_stat_' . $stat_name, 0);
        update_option('aibg_stat_' . $stat_name, $current + 1);
    }
    
    /**
     * Track post view (optional feature)
     */
    public function track_post_view() {
        if (!is_single()) {
            return;
        }
        
        global $post, $wpdb;
        $table_name = $wpdb->prefix . 'aibg_posts';
        
        // Check if this is an AI-generated post
        $exists = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM $table_name WHERE post_id = %d",
            $post->ID
        ));
        
        if ($exists) {
            // Track view in post meta
            $views = get_post_meta($post->ID, '_aibg_views', true);
            $views = $views ? (int)$views + 1 : 1;
            update_post_meta($post->ID, '_aibg_views', $views);
        }
    }
    
    /**
     * Render the analytics page
     */
    public function render_analytics_page() {
        $stats = $this->get_stats();
        $analytics = $this->get_analytics_data(30);
        
        ?>
        <div class="wrap aibg-analytics">
            <h1><?php _e('Analytics & Reports', 'ai-blog-generator'); ?></h1>
            
            <!-- Overview Stats -->
            <div class="aibg-stats-grid">
                <div class="aibg-stat-card">
                    <div class="stat-icon dashicons dashicons-chart-line"></div>
                    <div class="stat-content">
                        <h3><?php echo number_format($stats['posts_generated']); ?></h3>
                        <p><?php _e('Total Posts Generated', 'ai-blog-generator'); ?></p>
                    </div>
                </div>
                
                <div class="aibg-stat-card">
                    <div class="stat-icon dashicons dashicons-yes-alt"></div>
                    <div class="stat-content">
                        <h3><?php echo number_format($stats['posts_published']); ?></h3>
                        <p><?php _e('Posts Published', 'ai-blog-generator'); ?></p>
                    </div>
                </div>
                
                <div class="aibg-stat-card">
                    <div class="stat-icon dashicons dashicons-images-alt2"></div>
                    <div class="stat-content">
                        <h3><?php echo number_format($stats['images_used']); ?></h3>
                        <p><?php _e('Images Used', 'ai-blog-generator'); ?></p>
                    </div>
                </div>
                
                <div class="aibg-stat-card">
                    <div class="stat-icon dashicons dashicons-text"></div>
                    <div class="stat-content">
                        <h3><?php echo number_format($stats['total_words']); ?></h3>
                        <p><?php _e('Total Words Written', 'ai-blog-generator'); ?></p>
                    </div>
                </div>
            </div>
            
            <!-- Success Rate & Monthly Growth -->
            <div class="aibg-analytics-grid" style="margin-top: 20px;">
                <div class="analytics-card">
                    <h3><?php _e('Success Rate', 'ai-blog-generator'); ?></h3>
                    <div class="success-rate-display">
                        <div class="rate-circle">
                            <svg viewBox="0 0 36 36" class="circular-chart">
                                <path class="circle-bg" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                                <path class="circle" stroke-dasharray="<?php echo $analytics['success_rate']; ?>, 100" 
                                    d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                                <text x="18" y="20.35" class="percentage"><?php echo $analytics['success_rate']; ?>%</text>
                            </svg>
                        </div>
                        <p><?php _e('of generated posts are published', 'ai-blog-generator'); ?></p>
                    </div>
                </div>
                
                <div class="analytics-card">
                    <h3><?php _e('Monthly Growth', 'ai-blog-generator'); ?></h3>
                    <div class="monthly-comparison">
                        <div class="comparison-row">
                            <span class="label"><?php _e('Current Month:', 'ai-blog-generator'); ?></span>
                            <span class="value"><?php echo number_format($analytics['monthly_comparison']['current_month']); ?> <?php _e('posts', 'ai-blog-generator'); ?></span>
                        </div>
                        <div class="comparison-row">
                            <span class="label"><?php _e('Last Month:', 'ai-blog-generator'); ?></span>
                            <span class="value"><?php echo number_format($analytics['monthly_comparison']['last_month']); ?> <?php _e('posts', 'ai-blog-generator'); ?></span>
                        </div>
                        <div class="comparison-row growth">
                            <span class="label"><?php _e('Growth:', 'ai-blog-generator'); ?></span>
                            <span class="value <?php echo $analytics['monthly_comparison']['growth_percentage'] >= 0 ? 'positive' : 'negative'; ?>">
                                <?php echo ($analytics['monthly_comparison']['growth_percentage'] >= 0 ? '+' : '') . $analytics['monthly_comparison']['growth_percentage']; ?>%
                            </span>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Charts -->
            <div class="aibg-analytics-grid" style="margin-top: 20px;">
                <div class="analytics-card">
                    <h3><?php _e('Generation Trends (Last 30 Days)', 'ai-blog-generator'); ?></h3>
                    <canvas id="aibg-chart-trends" height="300"></canvas>
                </div>
                
                <div class="analytics-card">
                    <h3><?php _e('Post Status Distribution', 'ai-blog-generator'); ?></h3>
                    <canvas id="aibg-chart-status" height="300"></canvas>
                </div>
                
                <div class="analytics-card">
                    <h3><?php _e('Generation by Hour', 'ai-blog-generator'); ?></h3>
                    <canvas id="aibg-chart-hours" height="300"></canvas>
                </div>
                
                <div class="analytics-card full-width">
                    <h3><?php _e('Top Topics', 'ai-blog-generator'); ?></h3>
                    <?php if (empty($analytics['top_topics'])): ?>
                        <p><?php _e('No topics generated yet.', 'ai-blog-generator'); ?></p>
                    <?php else: ?>
                        <table class="wp-list-table widefat striped">
                            <thead>
                                <tr>
                                    <th><?php _e('Topic', 'ai-blog-generator'); ?></th>
                                    <th><?php _e('Posts', 'ai-blog-generator'); ?></th>
                                    <th><?php _e('Percentage', 'ai-blog-generator'); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $total_topics = array_sum(array_column($analytics['top_topics'], 'count'));
                                foreach ($analytics['top_topics'] as $topic): 
                                    $percentage = $total_topics > 0 ? round(($topic->count / $total_topics) * 100, 1) : 0;
                                ?>
                                <tr>
                                    <td><strong><?php echo esc_html($topic->topic); ?></strong></td>
                                    <td><?php echo number_format($topic->count); ?></td>
                                    <td>
                                        <div class="progress-bar-wrapper">
                                            <div class="progress-bar-fill" style="width: <?php echo $percentage; ?>%;"></div>
                                            <span class="progress-label"><?php echo $percentage; ?>%</span>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
                
                <div class="analytics-card full-width">
                    <h3><?php _e('Recent Activity', 'ai-blog-generator'); ?></h3>
                    <?php if (empty($analytics['recent_posts'])): ?>
                        <p><?php _e('No recent activity.', 'ai-blog-generator'); ?></p>
                    <?php else: ?>
                        <table class="wp-list-table widefat striped">
                            <thead>
                                <tr>
                                    <th><?php _e('Topic', 'ai-blog-generator'); ?></th>
                                    <th><?php _e('Status', 'ai-blog-generator'); ?></th>
                                    <th><?php _e('Generated', 'ai-blog-generator'); ?></th>
                                    <th><?php _e('Actions', 'ai-blog-generator'); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($analytics['recent_posts'] as $post): ?>
                                <tr>
                                    <td><?php echo esc_html($post->topic); ?></td>
                                    <td>
                                        <span class="status-badge status-<?php echo esc_attr($post->status); ?>">
                                            <?php echo esc_html(ucfirst($post->status)); ?>
                                        </span>
                                    </td>
                                    <td><?php echo human_time_diff(strtotime($post->generation_date), current_time('timestamp')) . ' ago'; ?></td>
                                    <td>
                                        <a href="<?php echo get_edit_post_link($post->post_id); ?>" class="button button-small">
                                            <?php _e('Edit', 'ai-blog-generator'); ?>
                                        </a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Export Options -->
            <div class="analytics-card" style="margin-top: 20px;">
                <h3><?php _e('Export Data', 'ai-blog-generator'); ?></h3>
                <p><?php _e('Download your analytics data for external analysis.', 'ai-blog-generator'); ?></p>
                <button class="button button-secondary" id="aibg-export-csv">
                    <span class="dashicons dashicons-download"></span>
                    <?php _e('Export as CSV', 'ai-blog-generator'); ?>
                </button>
                <button class="button button-secondary" id="aibg-export-json">
                    <span class="dashicons dashicons-media-code"></span>
                    <?php _e('Export as JSON', 'ai-blog-generator'); ?>
                </button>
            </div>
        </div>
        
        <style>
        .success-rate-display {
            text-align: center;
            padding: 20px;
        }
        
        .rate-circle {
            width: 200px;
            height: 200px;
            margin: 0 auto 20px;
        }
        
        .circular-chart {
            display: block;
            margin: 0 auto;
            max-width: 100%;
            max-height: 250px;
        }
        
        .circle-bg {
            fill: none;
            stroke: #f0f0f1;
            stroke-width: 3.8;
        }
        
        .circle {
            fill: none;
            stroke: #46b450;
            stroke-width: 2.8;
            stroke-linecap: round;
            animation: progress 1s ease-out forwards;
        }
        
        @keyframes progress {
            0% { stroke-dasharray: 0 100; }
        }
        
        .circular-chart .percentage {
            fill: #333;
            font-size: 0.5em;
            font-weight: bold;
            text-anchor: middle;
        }
        
        .monthly-comparison {
            padding: 20px;
        }
        
        .comparison-row {
            display: flex;
            justify-content: space-between;
            padding: 12px 0;
            border-bottom: 1px solid #f0f0f1;
        }
        
        .comparison-row:last-child {
            border-bottom: none;
        }
        
        .comparison-row.growth {
            font-weight: bold;
            font-size: 1.1em;
        }
        
        .comparison-row .value.positive {
            color: #46b450;
        }
        
        .comparison-row .value.negative {
            color: #dc3232;
        }
        
        .progress-bar-wrapper {
            position: relative;
            width: 100%;
            height: 24px;
            background: #f0f0f1;
            border-radius: 12px;
            overflow: hidden;
        }
        
        .progress-bar-fill {
            height: 100%;
            background: linear-gradient(90deg, #0073aa, #00a0d2);
            transition: width 0.5s ease;
        }
        
        .progress-label {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            font-weight: bold;
            font-size: 12px;
            color: #333;
        }
        </style>
        
        <script>
        var trendsData = <?php echo json_encode($analytics['posts_by_day']); ?>;
        var statusData = <?php echo json_encode($analytics['posts_by_status']); ?>;
        var hoursData = <?php echo json_encode($analytics['posts_by_hour']); ?>;
        
        jQuery(document).ready(function($) {
            // Export functionality
            $('#aibg-export-csv').on('click', function() {
                window.location.href = ajaxurl + '?action=aibg_export_analytics&format=csv&_wpnonce=' + aibgAdmin.nonce;
            });
            
            $('#aibg-export-json').on('click', function() {
                window.location.href = ajaxurl + '?action=aibg_export_analytics&format=json&_wpnonce=' + aibgAdmin.nonce;
            });
            
            // Initialize charts
            if (typeof Chart !== 'undefined') {
                // Trends Chart
                new Chart($('#aibg-chart-trends')[0].getContext('2d'), {
                    type: 'line',
                    data: {
                        labels: trendsData.map(d => d.date),
                        datasets: [{
                            label: 'Posts Generated',
                            data: trendsData.map(d => d.count),
                            borderColor: '#0073aa',
                            backgroundColor: 'rgba(0, 115, 170, 0.1)',
                            tension: 0.4,
                            fill: true
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: { display: true }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                ticks: { precision: 0 }
                            }
                        }
                    }
                });
                
                // Status Chart
                new Chart($('#aibg-chart-status')[0].getContext('2d'), {
                    type: 'doughnut',
                    data: {
                        labels: statusData.map(d => d.status.charAt(0).toUpperCase() + d.status.slice(1)),
                        datasets: [{
                            data: statusData.map(d => d.count),
                            backgroundColor: [
                                '#46b450',
                                '#ffb900',
                                '#dc3232',
                                '#00a0d2',
                                '#826eb4'
                            ]
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: { position: 'bottom' }
                        }
                    }
                });
                
                // Hours Chart
                new Chart($('#aibg-chart-hours')[0].getContext('2d'), {
                    type: 'bar',
                    data: {
                        labels: hoursData.map(d => d.hour + ':00'),
                        datasets: [{
                            label: 'Posts by Hour',
                            data: hoursData.map(d => d.count),
                            backgroundColor: 'rgba(0, 115, 170, 0.7)',
                            borderColor: '#0073aa',
                            borderWidth: 1
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: { display: false }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                ticks: { precision: 0 }
                            }
                        }
                    }
                });
            }
        });
        </script>
        <?php
    }
    
    /**
     * REST API endpoint for analytics
     */
    public function get_analytics_endpoint($request) {
        $days = $request->get_param('days') ?: 30;
        $data = $this->get_analytics_data($days);
        $stats = $this->get_stats();
        
        return new \WP_REST_Response([
            'stats' => $stats,
            'analytics' => $data
        ], 200);
    }
    
    /**
     * Export analytics data
     */
    public function export_analytics() {
        check_ajax_referer('wp_rest', '_wpnonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('Insufficient permissions', 'ai-blog-generator'));
        }
        
        $format = isset($_GET['format']) ? sanitize_text_field($_GET['format']) : 'csv';
        $data = $this->get_analytics_data(365); // Full year
        
        if ($format === 'csv') {
            $this->export_csv($data);
        } else {
            $this->export_json($data);
        }
        
        exit;
    }
    
    /**
     * Export as CSV
     */
    private function export_csv($data) {
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="aibg-analytics-' . date('Y-m-d') . '.csv"');
        
        $output = fopen('php://output', 'w');
        
        // Headers
        fputcsv($output, ['Date', 'Topic', 'Status', 'Published']);
        
        // Data
        foreach ($data['recent_posts'] as $post) {
            fputcsv($output, [
                $post->generation_date,
                $post->topic,
                $post->status,
                $post->publish_date ?: 'N/A'
            ]);
        }
        
        fclose($output);
    }
    
    /**
     * Export as JSON
     */
    private function export_json($data) {
        header('Content-Type: application/json');
        header('Content-Disposition: attachment; filename="aibg-analytics-' . date('Y-m-d') . '.json"');
        
        echo json_encode($data, JSON_PRETTY_PRINT);
    }
}