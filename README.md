# AI Blog Generator Pro - WordPress Plugin

A comprehensive WordPress plugin that automates blog content creation using
AI-powered content generation and featured image integration.

## Features

### ✅ Automated Blog Post Creation

- Integration with Deepseek API for AI-powered content generation
- Automatic topic suggestion based on site content
- SEO-optimized titles, content, meta descriptions, and keywords
- Automatic internal and external linking
- Customizable post length (short, medium, long)

### 🖼️ Intelligent Featured Image Generation

- Integration with Unsplash API for high-quality images
- Keyword-based image search
- Customizable orientation (landscape, portrait, square)
- Multiple resolution options
- Automatic image download and attachment

### 🎛️ Comprehensive Admin Dashboard

- Clean, intuitive interface
- Real-time statistics and analytics
- Post tracking with metadata
- Manual approval workflow
- Bulk generation capabilities

### ⚙️ Flexible Automation Settings

- Choose between draft, publish, or pending review
- Scheduled content generation (hourly, daily, weekly)
- Customizable SEO optimization rules
- Internal/external linking configuration

### 📊 Reporting & Analytics

- Track generated, published, and scheduled posts
- Visual charts and graphs
- Performance metrics
- Category distribution analysis

## Installation

### Prerequisites

- WordPress 5.8 or higher
- PHP 7.4 or higher
- MySQL 5.7 or higher

### Step 1: Upload Plugin Files

1. Download the plugin files
2. Upload the entire `ai-blog-generator` folder to `/wp-content/plugins/`
3. Or install via WordPress admin: Plugins → Add New → Upload Plugin

### Step 2: Directory Structure

Ensure your plugin has this structure:

```
ai-blog-generator/
├── ai-blog-generator.php (main plugin file)
├── includes/
│   ├── Admin/
│   │   ├── Dashboard.php
│   │   ├── Settings.php
│   │   └── PostManager.php
│   ├── API/
│   │   ├── DeepseekAPI.php
│   │   └── UnsplashAPI.php
│   ├── Core/
│   │   ├── Generator.php
│   │   └── Scheduler.php
│   └── Utils/
│       ├── SEO.php
│       └── Analytics.php
├── assets/
│   ├── css/
│   │   └── admin.css
│   └── js/
│       └── admin.js
└── README.md
```

### Step 3: Activate Plugin

1. Go to WordPress Admin → Plugins
2. Find "AI Blog Generator Pro"
3. Click "Activate"

### Step 4: Configure API Keys

1. Navigate to **AI Blog Gen → Settings**
2. Enter your API keys:

#### Get Deepseek API Key:

- Visit [platform.deepseek.com](https://platform.deepseek.com)
- Sign up or log in
- Navigate to API Keys section
- Create a new API key
- Copy and paste into plugin settings

#### Get Unsplash API Key:

- Visit [unsplash.com/developers](https://unsplash.com/developers)
- Create a new application
- Copy the Access Key
- Paste into plugin settings

3. Click "Save Settings"

## Configuration

### Generation Settings

Navigate to **AI Blog Gen → Settings → Generation** to configure:

- **Publishing Mode**: Draft, Auto Publish, or Pending Review
- **Generation Frequency**: Hourly, Twice Daily, Daily, or Weekly
- **Post Length**: Short (500-800), Medium (1000-1500), Long (2000+)
- **Internal Links**: Number of internal links per post (0-10)
- **External Links**: Number of external links per post (0-10)
- **Default Categories**: Comma-separated list for AI guidance

### SEO Settings

Configure SEO optimization:

- Enable/disable SEO meta generation
- Automatic focus keyword detection
- Meta description generation
- Open Graph and Twitter Card tags

### Image Settings

Customize image selection:

- **Orientation**: Landscape, Portrait, or Square
- **Resolution**: Thumbnail, Small, Regular, or Full
- Keyword-based search

## Usage

### Manual Post Generation

1. Go to **AI Blog Gen → Generate New**
2. (Optional) Enter a specific topic
3. Choose number of posts for bulk generation (1-10)
4. Click "Generate Post"
5. Wait for generation to complete
6. Edit or publish the generated post

### Automatic Generation

The plugin automatically generates posts based on your frequency settings:

1. Ensure API keys are configured
2. Set generation frequency in Settings
3. Plugin will generate posts automatically via WordPress Cron

### Managing Generated Posts

1. Go to **AI Blog Gen → Generated Posts**
2. View all posts with metadata
3. Actions available:
   - **Edit**: Modify post content
   - **View**: Preview published post
   - **Publish**: Publish draft posts
   - **Delete**: Remove post and tracking data

### Viewing Analytics

1. Navigate to **AI Blog Gen → Analytics**
2. View statistics:
   - Posts generated over time
   - Status distribution
   - Top categories
   - Image usage

## REST API Endpoints

The plugin provides REST API endpoints for custom integrations:

### Generate Post

```
POST /wp-json/aibg/v1/generate
Content-Type: application/json
X-WP-Nonce: {nonce}

{
  "topic": "Your Topic Here"
}
```

### Get Posts

```
GET /wp-json/aibg/v1/posts?per_page=20&page=1
```

### Get Analytics

```
GET /wp-json/aibg/v1/analytics?days=30
```

## Customization

### Hooks & Filters

#### Filters

```php
// Modify generated content before saving
add_filter('aibg_before_save_content', function($content, $post_data) {
    // Your modifications
    return $content;
}, 10, 2);

// Customize topic generation
add_filter('aibg_topic_prompt', function($prompt) {
    return $prompt . ' Focus on technology topics.';
});

// Modify SEO settings
add_filter('aibg_seo_meta', function($meta, $post_id) {
    // Customize meta tags
    return $meta;
}, 10, 2);
```

#### Actions

```php
// Run code after post generation
add_action('aibg_post_generated', function($post_id, $result) {
    // Your custom code
}, 10, 2);

// Before scheduled generation
add_action('aibg_before_scheduled_generation', function() {
    // Pre-generation tasks
});
```

## Troubleshooting

### Posts Not Generating

1. **Check API Keys**: Ensure both Deepseek and Unsplash API keys are valid
2. **Test Connections**: Dashboard shows API connection status
3. **Check Error Logs**: View WordPress debug.log for errors
4. **Verify Permissions**: Ensure user has `edit_posts` capability

### Images Not Loading

1. **API Key**: Verify Unsplash API key is correct
2. **Rate Limits**: Unsplash has 50 requests/hour free tier
3. **File Permissions**: Check wp-content/uploads is writable
4. **Memory Limit**: Increase PHP memory_limit if needed

### Scheduled Posts Not Working

1. **WordPress Cron**: Ensure WP-Cron is functioning
2. **Server Timezone**: Check server timezone settings
3. **Debug Cron**: Use WP Crontrol plugin to debug
4. **Frequency Setting**: Verify in Settings → Generation

### Performance Issues

1. **Bulk Generation**: Limit to 5 posts at a time
2. **Post Length**: Use shorter posts for faster generation
3. **Cache**: Clear WordPress cache after settings changes
4. **Server Resources**: Ensure adequate PHP memory/execution time

## Security Best Practices

1. **API Keys**: Never commit API keys to version control
2. **Permissions**: Plugin respects WordPress capability system
3. **Nonces**: All forms use WordPress nonces
4. **Sanitization**: All input is sanitized and validated
5. **SQL Injection**: Uses prepared statements
6. **XSS Protection**: All output is escaped

## Performance Optimization

### Caching

```php
// Enable object caching
define('WP_CACHE', true);
```

### Rate Limiting

- Plugin includes 2-second delay between bulk generations
- Respects API rate limits automatically

### Database Optimization

```sql
-- Add indexes for better performance
CREATE INDEX idx_generation_date ON wp_aibg_posts(generation_date);
CREATE INDEX idx_status ON wp_aibg_posts(status);
```

## Uninstallation

### Clean Removal

1. Deactivate plugin
2. Delete plugin files
3. (Optional) Remove database table:

```sql
DROP TABLE IF EXISTS wp_aibg_posts;
```

4. (Optional) Remove options:

```php
DELETE FROM wp_options WHERE option_name LIKE 'aibg_%';
```

## Development

### Requirements

- Node.js (for asset compilation)
- Composer (for PHP dependencies)
- WordPress coding standards

### Setup Development Environment

```bash
# Clone repository
git clone https://github.com/yourusername/ai-blog-generator.git

# Install dependencies
composer install
npm install

# Watch assets
npm run watch
```

### Code Standards

Follow WordPress coding standards:

```bash
# Check PHP
./vendor/bin/phpcs --standard=WordPress includes/

# Check JavaScript
npm run lint:js
```

## Support & Contributing

### Get Help

- Documentation: [plugin-site.com/docs](https://plugin-site.com/docs)
- Support Forum: WordPress.org support forums
- Email: support@yoursite.com

### Contributing

1. Fork the repository
2. Create feature branch
3. Make changes with tests
4. Submit pull request

## Changelog

### Version 1.0.0 (2024-01-01)

- Initial release
- Deepseek API integration
- Unsplash API integration
- Admin dashboard
- Analytics system
- Scheduling system
- SEO optimization
- REST API endpoints

## License

GPL v2 or later

## Credits

- Developed by [Your Name]
- Uses Deepseek API for content generation
- Uses Unsplash API for images
- Built with WordPress best practices

## Upgrade Notes

### From Future Versions

- Always backup database before upgrading
- Deactivate plugin before updating files
- Reactivate after update complete
- Clear all caches

## FAQ

**Q: How many posts can I generate per month?** A: Depends on your Deepseek API
plan. Free tier typically allows 100 requests/day.

**Q: Can I use my own images instead of Unsplash?** A: Yes, you can modify the
`UnsplashAPI.php` to use any image source.

**Q: Does it work with Gutenberg?** A: Yes, generated content is compatible with
both Classic and Block editors.

**Q: Can I schedule posts for future dates?** A: Yes, use WordPress native
scheduling after generation.

**Q: Is it translation-ready?** A: Yes, all strings are translatable using the
'ai-blog-generator' text domain.

**Q: Can I customize the AI prompts?** A: Yes, use the `aibg_topic_prompt` and
other filters to customize prompts.

## System Requirements

- **WordPress**: 5.8+
- **PHP**: 7.4+ (8.0+ recommended)
- **MySQL**: 5.7+ (8.0+ recommended)
- **Memory**: 128MB minimum (256MB recommended)
- **Disk Space**: 10MB for plugin files

## Browser Support

Admin interface supports:

- Chrome (latest)
- Firefox (latest)
- Safari (latest)
- Edge (latest)

---

**Made with ❤️ for the WordPress community**
