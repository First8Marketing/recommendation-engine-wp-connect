# First8 Marketing - Recommendation Engine Plugin

> **Hyper-personalized content and product recommendations** — Connect your WordPress/WooCommerce site to the First8 Marketing Recommendation Engine for AI-powered personalization.

Transform your website into a hyper-personalized experience with **First8 Marketing Recommendation Engine Plugin** — the intelligent WordPress connector that delivers real-time, context-aware product recommendations and dynamic content personalization powered by advanced machine learning.

[![WordPress](https://img.shields.io/badge/WordPress-6.0%2B-21759B?logo=wordpress&logoColor=white)](https://wordpress.org/)
[![PHP](https://img.shields.io/badge/PHP-8.0%2B-777BB4?logo=php&logoColor=white)](https://php.net/)
[![WooCommerce](https://img.shields.io/badge/WooCommerce-7.0%2B-96588A?logo=woocommerce&logoColor=white)](https://woocommerce.com/)

---

## 🎯 Overview

This plugin is the **presentation layer** of the First8 Marketing Hyper-Personalized System, connecting your WordPress site to a proprietary machine learning recommendation engine that delivers:

- **Multi-dimensional Personalization** - Contextual, behavioral, temporal, sequential, and journey-aware recommendations
- **Real-time ML Predictions** - Collaborative filtering, content-based filtering, and sequential pattern mining
- **Dynamic Content Adaptation** - Personalize any content based on user preferences and behavior
- **WooCommerce Integration** - Product recommendations throughout the shopping journey
- **Email Personalization** - Add personalized recommendations to transactional emails

---

## 🏗️ System Architecture

```
WordPress/WooCommerce ←→ First8 Marketing Recommendation Engine Plugin ←→ Recommendation Engine API
                                                                                    ↓
                                                                          ML Models (Proprietary)
                                                                                    ↓
                                                                    Umami Analytics (Data Source)
```

**Data Flow:**
1. **User Interaction** - Visitor browses products, adds to cart, makes purchases
2. **Event Tracking** - First8 Marketing Track plugin sends events to Umami Analytics
3. **ETL Pipeline** - Recommendation engine processes analytics data in real-time
4. **ML Processing** - Multiple ML models generate personalized recommendations
5. **API Response** - Recommendation engine returns personalized product suggestions
6. **Display** - This plugin renders recommendations on WordPress/WooCommerce pages

---

## ✨ Key Features

### Personalized Recommendations
- **Product Recommendations** - Display personalized product suggestions based on user behavior
- **Context-Aware** - Recommendations adapt to page type, user journey, and session context
- **Multi-Strategy** - Combines collaborative filtering, content-based, and sequential patterns
- **Real-time Updates** - Recommendations update as user behavior changes

### WooCommerce Integration
- **Product Pages** - "You May Also Like" recommendations after product summary
- **Cart Page** - "Complete Your Purchase" suggestions after cart table
- **Checkout Page** - Last-minute recommendations before purchase
- **Email Personalization** - Add recommendations to order confirmation and marketing emails
- **Category Pages** - Personalized product sorting and suggestions

### Dynamic Content Personalization
- **Conditional Content** - Show/hide content based on user preferences
- **Personalized Messaging** - Adapt headlines, CTAs, and copy to user segments
- **A/B Testing Ready** - Test different personalization strategies
- **Logged-in User Focus** - Enhanced personalization for registered users

### Developer-Friendly
- **Shortcodes** - Easy-to-use shortcodes for displaying recommendations
- **PHP Functions** - Programmatic access to recommendation API
- **AJAX Support** - Load recommendations dynamically without page refresh
- **Hooks & Filters** - Customize behavior with WordPress actions and filters
- **REST API** - Access recommendations via WordPress REST API

---

## 📦 Installation

### Prerequisites

**Required:**
- WordPress 6.0+
- PHP 8.0+
- WooCommerce 7.0+ (for product recommendations)
- **Recommendation Engine API** - Running and accessible
- **First8 Marketing Track Plugin** - For event tracking (recommended)

**System Components:**
- Umami Analytics instance (with First8 Marketing enhancements)
- Recommendation Engine backend (proprietary ML system)
- PostgreSQL 17 database with Apache AGE and TimescaleDB

### Installation Steps

1. **Upload Plugin:**
   ```bash
   # Upload to WordPress plugins directory
   wp-content/plugins/first8marketing-recommendation-engine/
   ```

2. **Activate Plugin:**
   - WordPress Admin → Plugins → Activate "First8 Marketing - Recommendation Engine"

3. **Configure API Connection:**
   - Settings → Recommendation Engine
   - Enter Recommendation Engine API URL (e.g., `https://api.yourdomain.com`)
   - Enter API Key (provided by First8 Marketing)
   - Click "Save Changes"

4. **Test Connection:**
   - Click "Test Connection" button
   - Verify successful connection to API
   - Check that recommendations are being returned

5. **Configure Display Options:**
   - Set default recommendation count
   - Choose default layout (grid/list/carousel)
   - Configure WooCommerce integration points
   - Enable/disable email personalization

---

## ⚙️ Configuration

### API Settings

**Recommendation Engine API URL:**
```
https://api.yourdomain.com
```

**API Key:**
- Secure API key for authentication
- Stored encrypted in WordPress database
- Required for all API requests

**Connection Settings:**
- Request timeout (default: 5 seconds)
- Cache duration (default: 1 hour)
- Fallback behavior (show popular products if API fails)

### Display Settings

**Default Recommendation Count:**
- Product pages: 4 recommendations
- Cart page: 3 recommendations
- Email: 3 recommendations

**Layout Options:**
- Grid (default) - Responsive grid layout
- List - Vertical list layout
- Carousel - Horizontal scrolling carousel

**WooCommerce Integration Points:**
- ✅ After product summary (product pages)
- ✅ After cart table (cart page)
- ✅ Before checkout form (checkout page)
- ✅ Email footer (order emails)
- ⬜ Category pages (optional)
- ⬜ Homepage (optional)

---

## 🚀 Usage

### Shortcodes

**Display Recommendations:**
```php
[recengine_recommendations count="4" title="Recommended for You" layout="grid"]
```

**Parameters:**
- `count` - Number of recommendations (default: 4)
- `title` - Section title (default: "Recommended for You")
- `layout` - Display layout: grid, list, carousel (default: grid)
- `context` - Additional context for recommendations (optional)

**Personalized Content (logged-in users only):**
```php
[recengine_personalized]
  This content is only visible to logged-in users with personalization enabled.
[/recengine_personalized]
```

**Conditional Content:**
```php
[recengine_personalized segment="high_value"]
  Exclusive offer for our valued customers!
[/recengine_personalized]
```

### PHP Functions

**Get Recommendations:**
```php
$recommendations = RecEngine_Recommendations::get_instance()->get_recommendations(array(
    'count' => 10,
    'context' => array(
        'page_type' => 'product',
        'product_id' => get_the_ID(),
        'category' => 'electronics'
    )
));

// Returns array of product IDs with scores
foreach ($recommendations as $rec) {
    echo "Product ID: {$rec['product_id']}, Score: {$rec['score']}\n";
}
```

**Render Recommendations Widget:**
```php
echo RecEngine_Recommendations::get_instance()->render_recommendations(array(
    'title' => 'You May Also Like',
    'count' => 4,
    'layout' => 'grid',
    'context' => array('page_type' => 'product')
));
```

**Check API Connection:**
```php
$api_client = RecEngine_API_Client::get_instance();
$is_connected = $api_client->test_connection();

if ($is_connected) {
    echo "API connection successful!";
} else {
    echo "API connection failed.";
}
```

### WordPress Hooks

**Filter Recommendations:**
```php
add_filter('recengine_recommendations', function($recommendations, $context) {
    // Modify recommendations before display
    return $recommendations;
}, 10, 2);
```

**Customize Recommendation HTML:**
```php
add_filter('recengine_recommendation_html', function($html, $product_id) {
    // Customize individual recommendation HTML
    return $html;
}, 10, 2);
```

**Add Custom Context:**
```php
add_filter('recengine_api_context', function($context) {
    // Add custom context data to API requests
    $context['custom_field'] = 'custom_value';
    return $context;
});
```

---

## 🛒 WooCommerce Integration

### Automatic Integration Points

**Product Pages:**
- Location: After product summary
- Recommendations: "You May Also Like" based on current product
- Count: 4 products (configurable)
- Layout: Grid (configurable)

**Cart Page:**
- Location: After cart table
- Recommendations: "Complete Your Purchase" based on cart contents
- Count: 3 products (configurable)
- Layout: Grid (configurable)

**Checkout Page:**
- Location: Before checkout form
- Recommendations: Last-minute suggestions based on cart and user history
- Count: 3 products (configurable)
- Layout: Compact list

**Email Personalization:**
- Location: Email footer
- Recommendations: Personalized suggestions in order confirmation emails
- Count: 3 products (configurable)
- Condition: Only for logged-in users

### Manual Integration

**Add to Custom Template:**
```php
<?php
// In your WooCommerce template file
if (function_exists('recengine_display_recommendations')) {
    recengine_display_recommendations(array(
        'title' => 'Recommended for You',
        'count' => 6,
        'layout' => 'carousel'
    ));
}
?>
```

**Add to Specific Product Categories:**
```php
add_action('woocommerce_after_shop_loop', function() {
    if (is_product_category('electronics')) {
        echo do_shortcode('[recengine_recommendations count="8" title="Popular in Electronics"]');
    }
});
```

---

## 🧠 How It Works

### Machine Learning Models

The Recommendation Engine uses multiple ML strategies:

1. **Collaborative Filtering** - "Users who liked this also liked..."
   - ALS (Alternating Least Squares) algorithm
   - User-product interaction matrix factorization
   - Predicts preferences based on similar users

2. **Content-Based Filtering** - "Products similar to what you viewed..."
   - TF-IDF vectorization of product attributes
   - Cosine similarity for product matching
   - Category, tag, and attribute analysis

3. **Sequential Pattern Mining** - "Next likely purchase..."
   - PrefixSpan-like algorithm
   - Analyzes user behavior sequences
   - Predicts next action based on patterns

4. **Hybrid Ensemble** - Combines all strategies
   - Weighted combination of all models
   - Context-aware boosting
   - Real-time score adjustment

### Personalization Dimensions

**Contextual Awareness:**
- Page type (product, category, cart, checkout)
- Current product/category being viewed
- Time of day and day of week
- Device type (mobile, tablet, desktop)

**Behavioral Tracking:**
- Product views and interactions
- Add to cart behavior
- Purchase history
- Search queries and filters

**Temporal Patterns:**
- Session duration and engagement
- Time since last visit
- Purchase frequency
- Seasonal preferences

**Journey Mapping:**
- Entry point and referral source
- Navigation path through site
- Checkout abandonment patterns
- Email engagement

---

## 🔧 Advanced Configuration

### Caching

**Recommendation Cache:**
- Duration: 1 hour (configurable)
- Storage: WordPress transients
- Invalidation: On user action (add to cart, purchase)

**API Response Cache:**
- Duration: 5 minutes (configurable)
- Storage: WordPress object cache
- Invalidation: On cache expiration or manual flush

### Fallback Behavior

**If API is unavailable:**
1. Show cached recommendations (if available)
2. Show popular products (WooCommerce best sellers)
3. Show recently viewed products
4. Hide recommendation section (configurable)

### Performance Optimization

**AJAX Loading:**
- Load recommendations asynchronously
- Prevent blocking page render
- Improve perceived performance

**Lazy Loading:**
- Load recommendations on scroll
- Reduce initial page load time
- Better mobile performance

---

## 🔒 Security & Privacy

**API Authentication:**
- Secure API key authentication
- Encrypted storage of credentials
- HTTPS required for API communication

**Data Privacy:**
- No personal data sent to API without consent
- GDPR-compliant data handling
- User opt-out support
- Anonymous tracking option

**WordPress Security:**
- Nonce verification for all AJAX requests
- Capability checks for admin functions
- Sanitized input and escaped output
- SQL injection prevention

---

## 🐛 Troubleshooting

### Recommendations Not Showing

**Check API Connection:**
1. Settings → Recommendation Engine → Test Connection
2. Verify API URL is correct and accessible
3. Check API key is valid
4. Review error logs for API errors

**Check WooCommerce Integration:**
1. Verify WooCommerce is active and updated
2. Check integration points are enabled in settings
3. Clear WordPress cache
4. Test with default WooCommerce theme

**Check User Permissions:**
1. Personalized content requires logged-in users
2. Verify user has sufficient interaction history
3. Check if user has opted out of tracking

### API Connection Errors

**Timeout Errors:**
- Increase timeout in settings (default: 5 seconds)
- Check API server performance
- Verify network connectivity

**Authentication Errors:**
- Verify API key is correct
- Check API key hasn't expired
- Ensure API key has proper permissions

**404 Errors:**
- Verify API URL is correct
- Check API endpoint paths
- Ensure API is running and accessible

### Performance Issues

**Slow Page Load:**
- Enable AJAX loading for recommendations
- Reduce recommendation count
- Increase cache duration
- Use lazy loading

**High API Usage:**
- Increase cache duration
- Reduce recommendation refresh frequency
- Implement request throttling

---

## 📊 Analytics & Monitoring

### Recommendation Performance

**Track Metrics:**
- Recommendation impressions
- Click-through rate (CTR)
- Conversion rate from recommendations
- Revenue attributed to recommendations

**WordPress Integration:**
- Metrics sent to Umami Analytics
- Integration with First8 Marketing Track plugin
- Real-time dashboard in Umami

### A/B Testing

**Test Recommendation Strategies:**
- Different ML model weights
- Various recommendation counts
- Alternative layouts and placements
- Personalized vs. non-personalized content

---

## 🔄 Updates & Maintenance

### Plugin Updates

**Automatic Updates:**
- WordPress auto-update support
- Backward compatibility maintained
- Database migrations handled automatically

**Manual Updates:**
1. Backup WordPress site
2. Upload new plugin version
3. Activate updated plugin
4. Clear all caches
5. Test recommendation display

### API Compatibility

**Version Compatibility:**
- Plugin version: 1.0.0+
- API version: 1.0.0+
- WordPress: 6.0+
- WooCommerce: 7.0+
- PHP: 8.0+

---

## 📚 Related Components

### First8 Marketing Hyper-Personalization System

**Complete System Components:**

1. **Umami Analytics** - Enhanced analytics platform
   - PostgreSQL 17 + Apache AGE + TimescaleDB
   - Real-time event tracking
   - Graph database for relationship mapping

2. **First8 Marketing Track Plugin** - Event tracking connector
   - WordPress/WooCommerce event capture
   - Real-time data pipeline to Umami
   - Privacy-first analytics

3. **Recommendation Engine** - Proprietary ML backend (not public)
   - Collaborative filtering (ALS algorithm)
   - Content-based filtering (TF-IDF)
   - Sequential pattern mining
   - Hybrid ensemble model

4. **First8 Marketing Recommendation Engine Plugin** - This plugin
   - WordPress connector for personalized content
   - WooCommerce integration
   - Shortcodes and PHP API

---

## 💡 Best Practices

### Recommendation Display

**Product Pages:**
- Show 4-6 recommendations
- Use grid layout for visual appeal
- Place after product summary
- Title: "You May Also Like" or "Similar Products"

**Cart Page:**
- Show 3-4 recommendations
- Use compact layout
- Place after cart table
- Title: "Complete Your Purchase" or "Frequently Bought Together"

**Email:**
- Show 3 recommendations maximum
- Use simple HTML layout
- Include product images and prices
- Clear call-to-action buttons

### Performance

**Caching:**
- Cache recommendations for 1 hour
- Invalidate cache on user actions
- Use WordPress object cache if available

**AJAX Loading:**
- Load recommendations asynchronously
- Show loading indicator
- Graceful fallback if API fails

**Mobile Optimization:**
- Use responsive layouts
- Reduce recommendation count on mobile
- Lazy load below the fold

---

## 🆘 Support

**First8 Marketing Integration Support:**
- For plugin-specific issues, contact First8 Marketing
- For API issues, contact First8 Marketing technical support
- For WooCommerce integration, check WooCommerce documentation

**System Requirements:**
- Ensure all system components are properly installed
- Verify API connectivity and authentication
- Check WordPress and WooCommerce compatibility

---

## 📄 License

**License:** MIT License

**Copyright:** First8 Marketing

**Usage Rights:**
- Free to use for First8 Marketing clients
- Modification allowed for customization
- Redistribution requires permission

---

## 🙏 Credits

**Development:**
- **First8 Marketing** - Plugin development and system integration
- **Umami Software** - Analytics platform (upstream dependency)
- **ceviixx** - Original umami Connect plugin (inspiration for Track plugin)

**Technology Stack:**
- WordPress & WooCommerce
- PHP 8.0+
- FastAPI (Recommendation Engine backend)
- PostgreSQL 17 + Apache AGE + TimescaleDB
- Machine Learning (scikit-learn, numpy, scipy)

---

## 🚀 Getting Started

**Quick Start Guide:**

1. **Install Prerequisites:**
   - WordPress 6.0+
   - WooCommerce 7.0+
   - First8 Marketing Track plugin

2. **Install This Plugin:**
   - Upload and activate
   - Configure API connection
   - Test connection

3. **Configure Display:**
   - Set recommendation count
   - Choose layouts
   - Enable WooCommerce integration

4. **Test Recommendations:**
   - Visit product pages
   - Check cart page
   - Verify email personalization

5. **Monitor Performance:**
   - Track CTR and conversions
   - Optimize recommendation count
   - A/B test different strategies

---

**Ready to personalize your WordPress site?**

Install the First8 Marketing Recommendation Engine Plugin and start delivering hyper-personalized experiences to your visitors today!

---

*This plugin is part of the First8 Marketing Hyper-Personalization System. For complete system documentation, contact First8 Marketing.*
