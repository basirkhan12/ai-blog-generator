<?php
namespace AIBG\Admin;

class PostManager {
    public function __construct() {
        add_action('admin_post_aibg_delete_post', [$this, 'delete_post']);
        add_action('admin_post_aibg_publish_post', [$this, 'publish_post']);
    }
    
    public function render_posts_list() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'aibg_posts';
        
        $paged = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
        $per_page = 20;
        $offset = ($paged - 1) * $per_page;
        
        $total = $wpdb->get_var("SELECT COUNT(*) FROM $table_name");
        $posts = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table_name ORDER BY generation_date DESC LIMIT %d OFFSET %d",
            $per_page,
            $offset
        ));
        
        $total_pages = ceil($total / $per_page);
        
        ?>
        <div class="wrap aibg-posts-list">
            <h1 class="wp-heading-inline"><?php _e('Generated Posts', 'ai-blog-generator'); ?></h1>
            <a href="<?php echo admin_url('admin.php?page=aibg-generate'); ?>" class="page-title-action">
                <?php _e('Generate New', 'ai-blog-generator'); ?>
            </a>
            
            <form method="get">
                <input type="hidden" name="page" value="aibg-posts">
                <?php
                $list_table = new PostsListTable();
                $list_table->prepare_items();
                $list_table->display();
                ?>
            </form>
            
            <?php if ($total_pages > 1): ?>
            <div class="tablenav">
                <div class="tablenav-pages">
                    <?php
                    echo paginate_links([
                        'base' => add_query_arg('paged', '%#%'),
                        'format' => '',
                        'prev_text' => '&laquo;',
                        'next_text' => '&raquo;',
                        'total' => $total_pages,
                        'current' => $paged
                    ]);
                    ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
        <?php
    }
    
    public function delete_post() {
        check_admin_referer('aibg_delete_post');
        
        if (!current_user_can('delete_posts')) {
            wp_die(__('You do not have permission to delete posts.', 'ai-blog-generator'));
        }
        
        $post_id = isset($_GET['post_id']) ? intval($_GET['post_id']) : 0;
        
        if ($post_id) {
            global $wpdb;
            $table_name = $wpdb->prefix . 'aibg_posts';
            
            // Delete WordPress post
            wp_delete_post($post_id, true);
            
            // Delete tracking record
            $wpdb->delete($table_name, ['post_id' => $post_id], ['%d']);
        }
        
        wp_redirect(admin_url('admin.php?page=aibg-posts&deleted=1'));
        exit;
    }
    
    public function publish_post() {
        check_admin_referer('aibg_publish_post');
        
        if (!current_user_can('publish_posts')) {
            wp_die(__('You do not have permission to publish posts.', 'ai-blog-generator'));
        }
        
        $post_id = isset($_GET['post_id']) ? intval($_GET['post_id']) : 0;
        
        if ($post_id) {
            wp_publish_post($post_id);
            
            global $wpdb;
            $table_name = $wpdb->prefix . 'aibg_posts';
            $wpdb->update(
                $table_name,
                ['status' => 'publish', 'publish_date' => current_time('mysql')],
                ['post_id' => $post_id],
                ['%s', '%s'],
                ['%d']
            );
        }
        
        wp_redirect(admin_url('admin.php?page=aibg-posts&published=1'));
        exit;
    }
    
    public function get_posts_endpoint($request) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'aibg_posts';
        
        $per_page = $request->get_param('per_page') ?: 20;
        $page = $request->get_param('page') ?: 1;
        $offset = ($page - 1) * $per_page;
        
        $posts = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table_name ORDER BY generation_date DESC LIMIT %d OFFSET %d",
            $per_page,
            $offset
        ));
        
        $total = $wpdb->get_var("SELECT COUNT(*) FROM $table_name");
        
        return new \WP_REST_Response([
            'posts' => $posts,
            'total' => $total,
            'pages' => ceil($total / $per_page)
        ], 200);
    }
}

// Custom List Table
if (!class_exists('WP_List_Table')) {
    require_once(ABSPATH . 'wp-admin/includes/class-wp-list-table.php');
}

class PostsListTable extends \WP_List_Table {
    public function prepare_items() {
        $columns = $this->get_columns();
        $hidden = [];
        $sortable = [];
        
        $this->_column_headers = [$columns, $hidden, $sortable];
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'aibg_posts';
        $this->items = $wpdb->get_results("SELECT * FROM $table_name ORDER BY generation_date DESC LIMIT 20");
    }
    
    public function get_columns() {
        return [
            'topic' => __('Topic', 'ai-blog-generator'),
            'status' => __('Status', 'ai-blog-generator'),
            'featured_image' => __('Image', 'ai-blog-generator'),
            'generated' => __('Generated', 'ai-blog-generator'),
            'actions' => __('Actions', 'ai-blog-generator')
        ];
    }
    
    public function column_default($item, $column_name) {
        switch ($column_name) {
            case 'topic':
                return '<strong>' . esc_html($item->topic) . '</strong>';
            case 'status':
                return '<span class="status-badge status-' . esc_attr($item->status) . '">' . 
                    esc_html(ucfirst($item->status)) . '</span>';
            case 'featured_image':
                if ($item->featured_image_url) {
                    return '<img src="' . esc_url($item->featured_image_url) . '" style="max-width:60px;height:auto;">';
                }
                return '—';
            case 'generated':
                return human_time_diff(strtotime($item->generation_date), current_time('timestamp')) . ' ago';
            case 'actions':
                $actions = [];
                $actions[] = '<a href="' . get_edit_post_link($item->post_id) . '">' . __('Edit', 'ai-blog-generator') . '</a>';
                $actions[] = '<a href="' . get_permalink($item->post_id) . '" target="_blank">' . __('View', 'ai-blog-generator') . '</a>';
                
                if ($item->status !== 'publish') {
                    $publish_url = wp_nonce_url(
                        admin_url('admin-post.php?action=aibg_publish_post&post_id=' . $item->post_id),
                        'aibg_publish_post'
                    );
                    $actions[] = '<a href="' . $publish_url . '">' . __('Publish', 'ai-blog-generator') . '</a>';
                }
                
                $delete_url = wp_nonce_url(
                    admin_url('admin-post.php?action=aibg_delete_post&post_id=' . $item->post_id),
                    'aibg_delete_post'
                );
                $actions[] = '<a href="' . $delete_url . '" class="delete" onclick="return confirm(\'' . 
                    esc_js(__('Are you sure?', 'ai-blog-generator')) . '\');">' . __('Delete', 'ai-blog-generator') . '</a>';
                
                return implode(' | ', $actions);
            default:
                return print_r($item, true);
        }
    }
}