=== First8 Marketing - Recommendation Engine ===
Contributors: iskandarsulaili
Tags: woocommerce, recommendations, personalization, machine learning, elementor
Requires at least: 6.0
Tested up to: 6.8
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Hyper-personalized recommendation engine for dynamic content and product recommendations

== Description ==

First8 Marketing Recommendation Engine connects your WordPress site to a machine learning recommendation system that provides personalized content and product suggestions based on user behavior and preferences.

**What This Plugin Provides:**

- **Product Recommendations**: Display personalized product suggestions using the `[recengine_recommendations]` shortcode with configurable layouts
- **Content Personalization**: Show different content to logged-in users with the `[recengine_personalized]` shortcode
- **Context-Aware Suggestions**: Recommendations adapt based on page type (single, archive, home) and device (mobile/desktop)
- **Conditional Content**: Display content based on specific conditions using trigger-based shortcodes
- **Popup Management**: Create and manage personalized popups with the `[recengine_popup]` shortcode
- **Audience Segmentation**: Group users into audiences for targeted content delivery
- **WooCommerce Integration**: Automatic product recommendation integration for e-commerce stores
- **Elementor Support**: Widget integration for drag-and-drop personalization

**How It Works:**
The plugin connects to an external recommendation engine API that processes user behavior data to generate personalized suggestions. It provides multiple integration methods including shortcodes, Gutenberg blocks, and Elementor widgets for flexible implementation.

**Use Cases:**

- Display "Recommended for You" product sections on WooCommerce stores
- Show personalized content blocks to registered users
- Create conditional popups based on user behavior
- Segment content for different audience groups
- Integrate recommendations into existing page layouts

== Key Features ==

### Product Recommendations

- **Product Suggestions**: Machine learning algorithms analyze customer behavior for product recommendations
- **"Customers Also Bought"**: Collaborative filtering based on similar customer purchases
- **"You May Also Like"**: Content-based recommendations using product attributes and preferences
- **"Frequently Bought Together"**: Cross-sell recommendations
- **Personalized Upsells**: Dynamic upsell suggestions based on cart contents and browsing history

### Context-Aware Personalization

- **Real-Time Adaptation**: Recommendations update based on current session behavior
- **Journey-Based Recommendations**: Different suggestions for first-time visitors vs. returning customers
- **Page-Specific Personalization**: Tailored recommendations for product pages, cart, checkout, and homepage
- **Time-Sensitive Offers**: Seasonal and time-based recommendation strategies

### WooCommerce Integration

- **Product Pages** - "You May Also Like" recommendations after product summary
- **Cart Page** - "Complete Your Purchase" suggestions after cart table
- **Checkout Page** - Last-minute recommendations before purchase
- **Email Personalization** - Add recommendations to order confirmation and marketing emails
- **Category Pages** - Personalized product sorting and suggestions

== Installation ==

### Prerequisites

**Required:**

- WordPress 6.0+
- PHP 8.0+
- WooCommerce 7.0+ (for product recommendations)
- **Recommendation Engine API** - Running and accessible
- **First8 Marketing Track Plugin** (https://github.com/First8Marketing/first8marketing-umami) - For event tracking (recommended); this enhanced version of Umami includes WordPress-specific integrations and e-commerce tracking features not available in the original umami-software/umami

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

== Frequently Asked Questions ==

= What is the minimum WordPress version required? =
WordPress 6.0 or higher is required.

= Does this work with WooCommerce? =
Yes, this plugin has full WooCommerce integration for product recommendations.

= Do I need a separate API? =
Yes, you need a running First8 Marketing Recommendation Engine API instance.

== Changelog ==

= 1.0.0 =

- Initial release

== Upgrade Notice ==

= 1.0.0 =
Initial release of the plugin.
