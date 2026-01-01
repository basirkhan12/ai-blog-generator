<?php
namespace AIBG\Admin;

class Settings {
    public function __construct() {
        add_action('admin_init', [$this, 'register_settings']);
    }
    
    public function register_settings() {
        // API Settings
        register_setting('aibg_settings', 'aibg_deepseek_api_key', ['sanitize_callback' => 'sanitize_text_field']);
        register_setting('aibg_settings', 'aibg_unsplash_api_key', ['sanitize_callback' => 'sanitize_text_field']);
        
        // Generation Settings
        register_setting('aibg_settings', 'aibg_auto_publish', ['default' => 'draft']);
        register_setting('aibg_settings', 'aibg_post_frequency', ['default' => 'daily']);
        register_setting('aibg_settings', 'aibg_post_length', ['default' => 'medium']);
        register_setting('aibg_settings', 'aibg_internal_links', ['default' => '3']);
        register_setting('aibg_settings', 'aibg_external_links', ['default' => '2']);
        
        // SEO Settings
        register_setting('aibg_settings', 'aibg_seo_enabled', ['default' => '1']);
        register_setting('aibg_settings', 'aibg_focus_keywords', ['default' => '1']);
        
        // Image Settings
        register_setting('aibg_settings', 'aibg_image_orientation', ['default' => 'landscape']);
        register_setting('aibg_settings', 'aibg_image_resolution', ['default' => 'regular']);
        
        // Category Settings
        register_setting('aibg_settings', 'aibg_default_categories', ['sanitize_callback' => 'sanitize_text_field']);
    }
    
    public static function render_settings_page() {
        if (isset($_POST['aibg_save_settings']) && check_admin_referer('aibg_settings_nonce')) {
            // Handle form submission
            $fields = [
                'aibg_deepseek_api_key', 'aibg_unsplash_api_key', 'aibg_auto_publish',
                'aibg_post_frequency', 'aibg_post_length', 'aibg_internal_links',
                'aibg_external_links', 'aibg_seo_enabled', 'aibg_image_orientation',
                'aibg_image_resolution', 'aibg_default_categories'
            ];
            
            foreach ($fields as $field) {
                if (isset($_POST[$field])) {
                    update_option($field, sanitize_text_field($_POST[$field]));
                }
            }
            
            echo '<div class="notice notice-success"><p>' . __('Settings saved successfully!', 'ai-blog-generator') . '</p></div>';
        }
        
        ?>
        <div class="wrap aibg-settings">
            <h1><?php _e('AI Blog Generator Settings', 'ai-blog-generator'); ?></h1>
            
            <form method="post" action="">
                <?php wp_nonce_field('aibg_settings_nonce'); ?>
                
                <h2 class="nav-tab-wrapper">
                    <a href="#api-settings" class="nav-tab nav-tab-active"><?php _e('API Settings', 'ai-blog-generator'); ?></a>
                    <a href="#generation-settings" class="nav-tab"><?php _e('Generation', 'ai-blog-generator'); ?></a>
                    <a href="#seo-settings" class="nav-tab"><?php _e('SEO', 'ai-blog-generator'); ?></a>
                    <a href="#image-settings" class="nav-tab"><?php _e('Images', 'ai-blog-generator'); ?></a>
                </h2>
                
                <div id="api-settings" class="tab-content active">
                    <h2><?php _e('API Configuration', 'ai-blog-generator'); ?></h2>
                    <table class="form-table">
                        <tr>
                            <th><label for="aibg_deepseek_api_key"><?php _e('Deepseek API Key', 'ai-blog-generator'); ?></label></th>
                            <td>
                                <input type="password" id="aibg_deepseek_api_key" name="aibg_deepseek_api_key" 
                                    value="<?php echo esc_attr(get_option('aibg_deepseek_api_key')); ?>" class="regular-text">
                                <p class="description">
                                    <?php _e('Get your API key from', 'ai-blog-generator'); ?>
                                    <a href="https://platform.deepseek.com" target="_blank">platform.deepseek.com</a>
                                </p>
                            </td>
                        </tr>
                        <tr>
                            <th><label for="aibg_unsplash_api_key"><?php _e('Unsplash API Key', 'ai-blog-generator'); ?></label></th>
                            <td>
                                <input type="password" id="aibg_unsplash_api_key" name="aibg_unsplash_api_key" 
                                    value="<?php echo esc_attr(get_option('aibg_unsplash_api_key')); ?>" class="regular-text">
                                <p class="description">
                                    <?php _e('Get your API key from', 'ai-blog-generator'); ?>
                                    <a href="https://unsplash.com/developers" target="_blank">unsplash.com/developers</a>
                                </p>
                            </td>
                        </tr>
                    </table>
                </div>
                
                <div id="generation-settings" class="tab-content">
                    <h2><?php _e('Content Generation Settings', 'ai-blog-generator'); ?></h2>
                    <table class="form-table">
                        <tr>
                            <th><label for="aibg_auto_publish"><?php _e('Publishing Mode', 'ai-blog-generator'); ?></label></th>
                            <td>
                                <select id="aibg_auto_publish" name="aibg_auto_publish">
                                    <option value="draft" <?php selected(get_option('aibg_auto_publish'), 'draft'); ?>><?php _e('Save as Draft', 'ai-blog-generator'); ?></option>
                                    <option value="publish" <?php selected(get_option('aibg_auto_publish'), 'publish'); ?>><?php _e('Auto Publish', 'ai-blog-generator'); ?></option>
                                    <option value="pending" <?php selected(get_option('aibg_auto_publish'), 'pending'); ?>><?php _e('Pending Review', 'ai-blog-generator'); ?></option>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <th><label for="aibg_post_frequency"><?php _e('Generation Frequency', 'ai-blog-generator'); ?></label></th>
                            <td>
                                <select id="aibg_post_frequency" name="aibg_post_frequency">
                                    <option value="hourly" <?php selected(get_option('aibg_post_frequency'), 'hourly'); ?>><?php _e('Hourly', 'ai-blog-generator'); ?></option>
                                    <option value="twicedaily" <?php selected(get_option('aibg_post_frequency'), 'twicedaily'); ?>><?php _e('Twice Daily', 'ai-blog-generator'); ?></option>
                                    <option value="daily" <?php selected(get_option('aibg_post_frequency'), 'daily'); ?>><?php _e('Daily', 'ai-blog-generator'); ?></option>
                                    <option value="weekly" <?php selected(get_option('aibg_post_frequency'), 'weekly'); ?>><?php _e('Weekly', 'ai-blog-generator'); ?></option>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <th><label for="aibg_post_length"><?php _e('Post Length', 'ai-blog-generator'); ?></label></th>
                            <td>
                                <select id="aibg_post_length" name="aibg_post_length">
                                    <option value="short" <?php selected(get_option('aibg_post_length'), 'short'); ?>><?php _e('Short (500-800 words)', 'ai-blog-generator'); ?></option>
                                    <option value="medium" <?php selected(get_option('aibg_post_length'), 'medium'); ?>><?php _e('Medium (1000-1500 words)', 'ai-blog-generator'); ?></option>
                                    <option value="long" <?php selected(get_option('aibg_post_length'), 'long'); ?>><?php _e('Long (2000+ words)', 'ai-blog-generator'); ?></option>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <th><label for="aibg_internal_links"><?php _e('Internal Links', 'ai-blog-generator'); ?></label></th>
                            <td>
                                <input type="number" id="aibg_internal_links" name="aibg_internal_links" 
                                    value="<?php echo esc_attr(get_option('aibg_internal_links', '3')); ?>" 
                                    min="0" max="10" class="small-text">
                                <p class="description"><?php _e('Number of internal links to include per post', 'ai-blog-generator'); ?></p>
                            </td>
                        </tr>
                        <tr>
                            <th><label for="aibg_external_links"><?php _e('External Links', 'ai-blog-generator'); ?></label></th>
                            <td>
                                <input type="number" id="aibg_external_links" name="aibg_external_links" 
                                    value="<?php echo esc_attr(get_option('aibg_external_links', '2')); ?>" 
                                    min="0" max="10" class="small-text">
                                <p class="description"><?php _e('Number of external links to authoritative sources', 'ai-blog-generator'); ?></p>
                            </td>
                        </tr>
                        <tr>
                            <th><label for="aibg_default_categories"><?php _e('Default Categories', 'ai-blog-generator'); ?></label></th>
                            <td>
                                <input type="text" id="aibg_default_categories" name="aibg_default_categories" 
                                    value="<?php echo esc_attr(get_option('aibg_default_categories')); ?>" class="regular-text">
                                <p class="description"><?php _e('Comma-separated list of categories for AI to focus on', 'ai-blog-generator'); ?></p>
                            </td>
                        </tr>
                    </table>
                </div>
                
                <div id="seo-settings" class="tab-content">
                    <h2><?php _e('SEO Settings', 'ai-blog-generator'); ?></h2>
                    <table class="form-table">
                        <tr>
                            <th><label for="aibg_seo_enabled"><?php _e('Enable SEO Optimization', 'ai-blog-generator'); ?></label></th>
                            <td>
                                <label>
                                    <input type="checkbox" id="aibg_seo_enabled" name="aibg_seo_enabled" value="1" 
                                        <?php checked(get_option('aibg_seo_enabled', '1'), '1'); ?>>
                                    <?php _e('Generate meta descriptions and focus keywords', 'ai-blog-generator'); ?>
                                </label>
                            </td>
                        </tr>
                    </table>
                </div>
                
                <div id="image-settings" class="tab-content">
                    <h2><?php _e('Image Settings', 'ai-blog-generator'); ?></h2>
                    <table class="form-table">
                        <tr>
                            <th><label for="aibg_image_orientation"><?php _e('Image Orientation', 'ai-blog-generator'); ?></label></th>
                            <td>
                                <select id="aibg_image_orientation" name="aibg_image_orientation">
                                    <option value="landscape" <?php selected(get_option('aibg_image_orientation'), 'landscape'); ?>><?php _e('Landscape', 'ai-blog-generator'); ?></option>
                                    <option value="portrait" <?php selected(get_option('aibg_image_orientation'), 'portrait'); ?>><?php _e('Portrait', 'ai-blog-generator'); ?></option>
                                    <option value="squarish" <?php selected(get_option('aibg_image_orientation'), 'squarish'); ?>><?php _e('Square', 'ai-blog-generator'); ?></option>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <th><label for="aibg_image_resolution"><?php _e('Image Resolution', 'ai-blog-generator'); ?></label></th>
                            <td>
                                <select id="aibg_image_resolution" name="aibg_image_resolution">
                                    <option value="thumb" <?php selected(get_option('aibg_image_resolution'), 'thumb'); ?>><?php _e('Thumbnail', 'ai-blog-generator'); ?></option>
                                    <option value="small" <?php selected(get_option('aibg_image_resolution'), 'small'); ?>><?php _e('Small', 'ai-blog-generator'); ?></option>
                                    <option value="regular" <?php selected(get_option('aibg_image_resolution'), 'regular'); ?>><?php _e('Regular', 'ai-blog-generator'); ?></option>
                                    <option value="full" <?php selected(get_option('aibg_image_resolution'), 'full'); ?>><?php _e('Full', 'ai-blog-generator'); ?></option>
                                </select>
                            </td>
                        </tr>
                    </table>
                </div>
                
                <p class="submit">
                    <button type="submit" name="aibg_save_settings" class="button button-primary">
                        <?php _e('Save Settings', 'ai-blog-generator'); ?>
                    </button>
                </p>
            </form>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            $('.nav-tab').on('click', function(e) {
                e.preventDefault();
                $('.nav-tab').removeClass('nav-tab-active');
                $(this).addClass('nav-tab-active');
                $('.tab-content').removeClass('active');
                $($(this).attr('href')).addClass('active');
            });
        });
        </script>
        <?php
    }
}