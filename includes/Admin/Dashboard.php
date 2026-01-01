<?php
namespace AIBG\Admin;

class Dashboard {
    public function __construct() {
        add_action('admin_menu', [$this, 'add_menu_pages']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_assets']);
    }
    
    public function add_menu_pages() {
        add_menu_page(
            __('AI Blog Generator', 'ai-blog-generator'),
            __('AI Blog Gen', 'ai-blog-generator'),
            'manage_options',
            'ai-blog-generator',
            [$this, 'render_dashboard'],
            'dashicons-edit-large',
            30
        );
        
        add_submenu_page(
            'ai-blog-generator',
            __('Dashboard', 'ai-blog-generator'),
            __('Dashboard', 'ai-blog-generator'),
            'manage_options',
            'ai-blog-generator',
            [$this, 'render_dashboard']
        );
        
        add_submenu_page(
            'ai-blog-generator',
            __('Generated Posts', 'ai-blog-generator'),
            __('Generated Posts', 'ai-blog-generator'),
            'edit_posts',
            'aibg-posts',
            [$this, 'render_posts_page']
        );
        
        add_submenu_page(
            'ai-blog-generator',
            __('Generate New', 'ai-blog-generator'),
            __('Generate New', 'ai-blog-generator'),
            'edit_posts',
            'aibg-generate',
            [$this, 'render_generate_page']
        );
        
        add_submenu_page(
            'ai-blog-generator',
            __('Analytics', 'ai-blog-generator'),
            __('Analytics', 'ai-blog-generator'),
            'manage_options',
            'aibg-analytics',
            [$this, 'render_analytics_page']
        );
        
        add_submenu_page(
            'ai-blog-generator',
            __('Settings', 'ai-blog-generator'),
            __('Settings', 'ai-blog-generator'),
            'manage_options',
            'aibg-settings',
            [\AIBG\Admin\Settings::class, 'render_settings_page']
        );
    }
    
    public function enqueue_assets($hook) {
        if (strpos($hook, 'ai-blog-generator') === false && strpos($hook, 'aibg-') === false) {
            return;
        }
        
        wp_enqueue_style('aibg-admin-css', AIBG_PLUGIN_URL . 'assets/css/admin.css', [], AIBG_VERSION);
        wp_enqueue_script('aibg-admin-js', AIBG_PLUGIN_URL . 'assets/js/admin.js', ['jquery'], AIBG_VERSION, true);
        
        wp_localize_script('aibg-admin-js', 'aibgAdmin', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'restUrl' => rest_url('aibg/v1'),
            'nonce' => wp_create_nonce('wp_rest'),
            'i18n' => [
                'generating' => __('Generating post...', 'ai-blog-generator'),
                'success' => __('Post generated successfully!', 'ai-blog-generator'),
                'error' => __('Error generating post', 'ai-blog-generator'),
                'confirm_delete' => __('Are you sure you want to delete this post?', 'ai-blog-generator')
            ]
        ]);
    }
    
    public function render_dashboard() {
        $analytics = new \AIBG\Utils\Analytics();
        $stats = $analytics->get_stats();
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'aibg_posts';
        $recent_posts = $wpdb->get_results("SELECT * FROM $table_name ORDER BY generation_date DESC LIMIT 5");
        
        ?>
        <div class="wrap aibg-dashboard">
            <h1><?php _e('AI Blog Generator Dashboard', 'ai-blog-generator'); ?></h1>
            
            <div class="aibg-stats-grid">
                <div class="aibg-stat-card">
                    <div class="stat-icon dashicons dashicons-edit"></div>
                    <div class="stat-content">
                        <h3><?php echo number_format($stats['posts_generated']); ?></h3>
                        <p><?php _e('Posts Generated', 'ai-blog-generator'); ?></p>
                    </div>
                </div>
                
                <div class="aibg-stat-card">
                    <div class="stat-icon dashicons dashicons-yes"></div>
                    <div class="stat-content">
                        <h3><?php echo number_format($stats['posts_published']); ?></h3>
                        <p><?php _e('Posts Published', 'ai-blog-generator'); ?></p>
                    </div>
                </div>
                
                <div class="aibg-stat-card">
                    <div class="stat-icon dashicons dashicons-calendar"></div>
                    <div class="stat-content">
                        <h3><?php echo number_format($stats['posts_scheduled']); ?></h3>
                        <p><?php _e('Posts Scheduled', 'ai-blog-generator'); ?></p>
                    </div>
                </div>
                
                <div class="aibg-stat-card">
                    <div class="stat-icon dashicons dashicons-images-alt2"></div>
                    <div class="stat-content">
                        <h3><?php echo number_format($stats['images_used']); ?></h3>
                        <p><?php _e('Images Used', 'ai-blog-generator'); ?></p>
                    </div>
                </div>
            </div>
            
            <div class="aibg-quick-actions">
                <h2><?php _e('Quick Actions', 'ai-blog-generator'); ?></h2>
                <div class="action-buttons">
                    <a href="<?php echo admin_url('admin.php?page=aibg-generate'); ?>" class="button button-primary button-hero">
                        <span class="dashicons dashicons-plus-alt"></span>
                        <?php _e('Generate New Post', 'ai-blog-generator'); ?>
                    </a>
                    <a href="<?php echo admin_url('admin.php?page=aibg-posts'); ?>" class="button button-secondary button-hero">
                        <span class="dashicons dashicons-list-view"></span>
                        <?php _e('View All Posts', 'ai-blog-generator'); ?>
                    </a>
                    <a href="<?php echo admin_url('admin.php?page=aibg-settings'); ?>" class="button button-secondary button-hero">
                        <span class="dashicons dashicons-admin-settings"></span>
                        <?php _e('Settings', 'ai-blog-generator'); ?>
                    </a>
                </div>
            </div>
            
            <div class="aibg-recent-posts">
                <h2><?php _e('Recently Generated Posts', 'ai-blog-generator'); ?></h2>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th><?php _e('Topic', 'ai-blog-generator'); ?></th>
                            <th><?php _e('Status', 'ai-blog-generator'); ?></th>
                            <th><?php _e('Generated', 'ai-blog-generator'); ?></th>
                            <th><?php _e('Actions', 'ai-blog-generator'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($recent_posts)): ?>
                        <tr>
                            <td colspan="4"><?php _e('No posts generated yet.', 'ai-blog-generator'); ?></td>
                        </tr>
                        <?php else: ?>
                        <?php foreach ($recent_posts as $post): ?>
                        <tr>
                            <td><strong><?php echo esc_html($post->topic); ?></strong></td>
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
                                <a href="<?php echo get_permalink($post->post_id); ?>" class="button button-small" target="_blank">
                                    <?php _e('View', 'ai-blog-generator'); ?>
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
            <div class="aibg-system-status">
                <h2><?php _e('System Status', 'ai-blog-generator'); ?></h2>
                <table class="form-table">
                    <tr>
                        <th><?php _e('Deepseek API', 'ai-blog-generator'); ?></th>
                        <td>
                            <?php 
                            $deepseek_status = (new \AIBG\API\DeepseekAPI())->test_connection();
                            echo $deepseek_status 
                                ? '<span class="status-ok">✓ ' . __('Connected', 'ai-blog-generator') . '</span>' 
                                : '<span class="status-error">✗ ' . __('Not Connected', 'ai-blog-generator') . '</span>';
                            ?>
                        </td>
                    </tr>
                    <tr>
                        <th><?php _e('Unsplash API', 'ai-blog-generator'); ?></th>
                        <td>
                            <?php 
                            $unsplash_status = (new \AIBG\API\UnsplashAPI())->test_connection();
                            echo $unsplash_status 
                                ? '<span class="status-ok">✓ ' . __('Connected', 'ai-blog-generator') . '</span>' 
                                : '<span class="status-error">✗ ' . __('Not Connected', 'ai-blog-generator') . '</span>';
                            ?>
                        </td>
                    </tr>
                    <tr>
                        <th><?php _e('Scheduled Tasks', 'ai-blog-generator'); ?></th>
                        <td>
                            <?php 
                            $next_scheduled = wp_next_scheduled('aibg_generate_post');
                            if ($next_scheduled) {
                                echo '<span class="status-ok">✓ ' . sprintf(
                                    __('Next run: %s', 'ai-blog-generator'),
                                    human_time_diff($next_scheduled, current_time('timestamp')) . ' from now'
                                ) . '</span>';
                            } else {
                                echo '<span class="status-warning">⚠ ' . __('Not scheduled', 'ai-blog-generator') . '</span>';
                            }
                            ?>
                        </td>
                    </tr>
                </table>
            </div>
        </div>
        <?php
    }
    
    public function render_posts_page() {
        $post_manager = new \AIBG\Admin\PostManager();
        $post_manager->render_posts_list();
    }
    
    public function render_generate_page() {
        ?>
        <div class="wrap aibg-generate">
            <h1><?php _e('Generate New Post', 'ai-blog-generator'); ?></h1>
            
            <div class="aibg-generate-form">
                <form id="aibg-generate-form">
                    <table class="form-table">
                        <tr>
                            <th><label for="aibg-topic"><?php _e('Topic (Optional)', 'ai-blog-generator'); ?></label></th>
                            <td>
                                <input type="text" id="aibg-topic" name="topic" class="regular-text" 
                                    placeholder="<?php _e('Leave empty for AI to suggest a topic', 'ai-blog-generator'); ?>">
                                <p class="description"><?php _e('Specify a topic or let AI suggest one based on your site content.', 'ai-blog-generator'); ?></p>
                            </td>
                        </tr>
                        <tr>
                            <th><label for="aibg-bulk-count"><?php _e('Bulk Generate', 'ai-blog-generator'); ?></label></th>
                            <td>
                                <input type="number" id="aibg-bulk-count" name="bulk_count" min="1" max="10" value="1" class="small-text">
                                <p class="description"><?php _e('Generate multiple posts at once (1-10)', 'ai-blog-generator'); ?></p>
                            </td>
                        </tr>
                    </table>
                    
                    <p class="submit">
                        <button type="submit" class="button button-primary button-hero" id="aibg-generate-btn">
                            <span class="dashicons dashicons-update"></span>
                            <?php _e('Generate Post', 'ai-blog-generator'); ?>
                        </button>
                    </p>
                </form>
                
                <div id="aibg-generation-progress" style="display:none;">
                    <div class="aibg-progress-bar">
                        <div class="progress-fill"></div>
                    </div>
                    <p class="progress-text"><?php _e('Generating...', 'ai-blog-generator'); ?></p>
                </div>
                
                <div id="aibg-generation-result" style="display:none;"></div>
            </div>
        </div>
        <?php
    }
    
    public function render_analytics_page() {
        $analytics = new \AIBG\Utils\Analytics();
        $analytics->render_analytics_page();
    }
}