=== Script-and-Tool-AI-Content-Writer-WordPress-Plugin ===
Contributors: webpenter
Donate link: https://webpenter.com/
Tags: ai, blog, automation, openai, content
Requires at least: 5.8
Tested up to: 7.0
Requires PHP: 7.4
Stable tag: 1.0.7
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Automatically generate and publish SEO-optimized blog posts using AI with customizable scheduling. Pro version includes custom prompt templates.

== Description ==

Script-and-Tool-AI-Content-Writer-WordPress-Plugin is a powerful WordPress plugin that leverages artificial intelligence to automatically create high-quality, SEO-optimized blog posts for your website. Perfect for content marketers, bloggers, and website owners who want to maintain a consistent posting schedule.

= Key Features (Free Version) =

* **Unlimited Posts with Groq** - Generate as many posts as you want using free Groq AI
* **Automated Content Generation** - Create full blog posts automatically using AI
* **Daily Scheduling** - Automatic daily post generation with WordPress Cron
* **Customizable Content** - Configure topics and keywords for diverse content
* **SEO Optimization** - Built-in default prompt template designed for search engine friendly content
* **Smart Publishing** - Publish immediately or save as drafts for review
* **Intelligent Categories** - Auto-assigns to best matching category or creates new ones
* **Manual Generation** - Create posts on-demand with a single click
* **Error Logging** - Track generation issues and troubleshoot easily
* **Statistics Dashboard** - Monitor total posts generated and scheduling status
* **Free Groq AI** - Uses Groq (completely free, no credit card required)

= Premium Features (Pro Version) =

Upgrade to Pro for advanced capabilities:

* **Multiple AI Providers** - Use OpenAI GPT-4, Claude, or any custom AI endpoint
* **Custom Post Lengths** - Set any word count from 300 to 5000 words with interactive slider
* **Flexible Scheduling** - Post every 2, 3, 6 hours or custom intervals
* **Multiple Categories & Tags** - Auto-assign up to 3 relevant categories and generate tags
* **12+ Prompt Templates** - How-To guides, listicles, case studies, comparisons, and more
* **Custom Prompt Template Editor** - Fully customize AI prompts to match your content style and requirements
* **SEO Optimization** - Auto-generate meta descriptions (Yoast, Rank Math, AIOSEO compatible)
* **Bulk Generation** - Create multiple posts at once from keyword list

[Learn more about Pro features →](https://webpenter.com/script-and-tool-ai-content-writer-wordpress-plugin/)

= How It Works =

1. Get your free Groq API key from console.groq.com
2. Enter your API key in the plugin settings
3. Add your desired topics and keywords (comma-separated)
4. Configure post frequency and publishing preferences
5. Let WordPress Cron automatically generate content on schedule
6. Review drafts or publish automatically - your choice!

= Perfect For =

* Content marketers maintaining multiple blogs
* Niche websites needing consistent content
* Affiliate marketers building authority sites
* Businesses wanting to improve SEO presence
* Bloggers who want to scale content production

= External Services Used =

**IMPORTANT:** This plugin uses external services to generate content and images.

= 1. AI Content Generation =

This plugin requires an API key from an AI service provider to generate blog posts.

**Why External Service is Required:**
AI content generation requires large language models (LLMs) that run on powerful servers with specialized hardware. These models cannot run locally on a WordPress server due to:
- Massive computational requirements (billions of parameters)
- Specialized GPU infrastructure needed for inference
- Continuous model updates and improvements
- Resource-intensive processing that would overwhelm typical hosting environments

The plugin sends your prompts to these external AI services where the actual content generation processing occurs on their servers.

**Free Version:** Unlimited posts with Groq AI exclusively

**Service Used:** Groq (https://console.groq.com)
- Completely free, no credit card required
- Fast AI model (Llama 3.3-70b-versatile)
- No cost per request
- Get your API key: https://console.groq.com/

**Default API Endpoint:** https://api.groq.com/openai/v1/chat/completions

**Terms of Use & Privacy Policy:**
- Groq Privacy Policy: https://groq.com/privacy-policy/
- Groq Terms of Use: https://groq.com/terms-of-use/

**Note:** The Pro version supports multiple AI providers (OpenAI, Claude, custom endpoints). When using Pro with other AI providers, refer to those providers' Terms of Use and Privacy Policy pages directly.

**Data Transmitted:**
- Your prompt text (including keywords)
- Post generation requests
- You control your own API key
- No personal data is collected by this plugin

**Pro Version AI Providers:**

The Pro version supports multiple AI providers. When using Pro, data is transmitted to your selected provider:

**OpenAI (GPT-4, GPT-3.5-turbo)**
- Service: OpenAI (https://openai.com)
- API Endpoint: https://api.openai.com/v1/chat/completions
- Data transmitted: Your prompt text (including keywords) and API key
- Privacy Policy: https://openai.com/policies/privacy-policy
- Terms of Use: https://openai.com/policies/terms-of-use

**Anthropic (Claude)**
- Service: Anthropic (https://anthropic.com)
- API Endpoint: https://api.anthropic.com/v1/messages
- Data transmitted: Your prompt text (including keywords) and API key
- Privacy Policy: https://www.anthropic.com/privacy
- Terms of Use: https://www.anthropic.com/legal/consumer-terms

**Azure OpenAI, LocalAI, Custom Endpoints**
- Pro users can configure custom OpenAI-compatible API endpoints
- When using custom endpoints, refer to those providers' Terms of Use and Privacy Policy pages directly

= 2. Featured Images (Unsplash) =

The plugin automatically fetches relevant featured images from Unsplash's free stock photo API.

**Why External Service is Required:**
Unsplash provides access to millions of high-quality, royalty-free stock photos that are properly licensed for commercial use. This service cannot be replicated locally because:
- Requires access to a curated library of millions of professional photos
- Ensures proper licensing and attribution compliance
- Provides search functionality across a vast image database
- Maintains photographer attribution and licensing information

The plugin queries Unsplash's API to retrieve images based on your post keywords, ensuring you get properly licensed images with correct attribution.

**Service:** Unsplash (https://unsplash.com)
**API Endpoint:** https://api.unsplash.com/photos/random
**Privacy Policy:** https://unsplash.com/privacy
**Terms of Service:** https://unsplash.com/terms
**License:** All photos are free to use under the Unsplash License

**Requirements:**
- Free Unsplash API key (get it at https://unsplash.com/developers)
- Create a free developer account
- Register your application (demo/development tier is free)

**Data Transmitted:**
- Search keyword only (based on your post topic)
- No personal data is sent

**Attribution:**
- Photos are automatically attributed to photographers
- Attribution is added to image captions (Unsplash requirement)
- All photos are properly licensed for commercial use

**Pro Version:** Pro users get multiple categories and auto-generated tags in addition to featured images.

= Requirements =

* WordPress 5.8 or higher
* PHP 7.4 or higher
* Free Groq API key (https://console.groq.com)
* Free Unsplash API key (https://unsplash.com/developers)
* Active WordPress Cron (standard on most hosts)

= Documentation =

Full documentation is available in the plugin's README.md file, including:
* Detailed installation instructions
* API configuration examples (OpenAI, Azure, LocalAI)
* 9 ready-to-use prompt templates
* Troubleshooting guide
* Security best practices

== Installation ==

= Automatic Installation =

1. Log in to your WordPress admin panel
2. Navigate to Plugins → Add New
3. Search for "Script-and-Tool-AI-Content-Writer-WordPress-Plugin"
4. Click "Install Now" and then "Activate"

= Manual Installation =

1. Download the plugin ZIP file
2. Log in to your WordPress admin panel
3. Navigate to Plugins → Add New → Upload Plugin
4. Choose the ZIP file and click "Install Now"
5. Click "Activate Plugin"

= Configuration =

1. Go to Settings → Script-and-Tool-AI-Content-Writer-WordPress-Plugin
2. Enter your API key (Groq API key for free version)
4. Configure your content preferences:
   * Add keywords/topics (comma-separated)
   * Choose post length
   * Select category
5. Set publishing preferences:
   * Choose frequency (daily recommended)
   * Select publish mode (draft or immediate)
6. Click "Save Settings"
7. Test with "Generate Post Now" button

Note: Custom prompt template editing is available in the Pro version. Free version uses a pre-configured SEO-optimized template.

== Frequently Asked Questions ==

= Is this plugin really free? =

Yes! The free version is fully functional and includes unlimited posts with Groq AI. You just need to add your own free API key from Groq (no credit card required). Premium features are available for users who need OpenAI, Claude, custom AI endpoints, bulk generation, and advanced features.

= Do I need an API key? =

Yes, you'll need a free API key from Groq (https://console.groq.com) which is completely free and requires no credit card. The Pro version allows you to use OpenAI, Claude, or any custom AI endpoint.

= Why is the free version limited to Groq? =

Groq offers completely free, unlimited AI API access with no credit card required, making it perfect for free users. Pro users can choose from any AI provider including OpenAI GPT-4, Claude, or custom endpoints.

= How much does it cost? =

The plugin itself is free. However, you'll need to pay for API usage based on your AI provider's pricing. OpenAI's GPT-3.5-turbo typically costs $0.004-$0.008 per blog post depending on length.

= Can I review posts before they're published? =

Absolutely! Set the publishing mode to "Save as Draft" and review each post before publishing manually.

= What happens if the API fails? =

The plugin includes comprehensive error handling. Failed generations are logged in the "Recent Errors" section of the settings page, and you'll receive details about what went wrong.

= Can I use my own AI service? =

The free version uses Groq AI exclusively. Pro users can use any OpenAI-compatible API including OpenAI, Claude, Azure OpenAI, LocalAI, or custom endpoints. Configure your custom endpoint in Pro settings.

= Will this work with other AI providers? =

Yes, as long as they support the OpenAI chat completion format. Examples include Azure OpenAI, LocalAI, and various other providers.

= Can I customize the writing style? =

The free version uses a pre-configured SEO-optimized prompt template. Pro users can fully customize the prompt template using the Prompt Template Editor, and Pro includes 12+ ready-to-use templates for different content types (How-To guides, listicles, case studies, and more).

= Does this require WP-Cron? =

Yes, the plugin uses WordPress Cron for scheduling. This is enabled by default on most WordPress installations.

= How do I stop automatic generation? =

Simply deactivate the plugin, or change the frequency settings and save.

= Is the generated content unique? =

Yes, AI generates unique content each time. However, you should always review content for accuracy and quality before publishing.

= Can I use this for multiple categories? =

The free version supports one category. The Pro version includes multiple category support and automatic tag generation.

= What if I run out of keywords? =

The plugin analyzes your recent posts and selects the keyword that is most different from them, ensuring content variety. With even 5-10 keywords, you can generate diverse content indefinitely as the plugin automatically picks topics you haven't covered recently.

= Does this support custom post types? =

Currently, the plugin only supports standard WordPress posts. Custom post type support may be added in future versions.

= Is multilingual content supported? =

You can generate content in any language supported by your AI provider by writing prompts in that language.

== Changelog ==

= 1.0.7 - 2026-05-21 =
* Fixed: Manual "Generate Post Now" now increments the total posts generated counter.
* Fixed: Redirect after manual generation returns to the correct plugin settings page.
* Added: Loading state (spinner and disabled button) while a post is being generated manually.
* Added: Logs submenu page for viewing and clearing generation error history.
* Improved: External Services notice moved below the Save Settings button.
* Improved: SEO Optimization section layout when using Pro (How It Works and important notices shown first).
* Tested up to WordPress 7.0.

= 1.0.6 - 2026-05-10 =
* Fixed: With AI Blog Automator Pro active, saving settings no longer overwrote the stored AI API endpoint with Groq when the provider form did not submit a separate endpoint field.

= 1.0.5 - 2026-05-09 =
* Added: New compatibility hook to the generator.

= 1.0.4 - 2026-05-04 =
* Fixed: Generating a post without an Unsplash API key no longer logs a featured-image error; the image step is skipped quietly.

= 1.0.3 - 2026-05-03 =
* Changed: Unsplash API key is no longer a required HTML field on the settings form, so you can save settings without it.
* Improved: Clearer wording that the key is optional for saving but still needed for featured images to work.

= 1.0.2 - 2026-02-17 =
* Added: SEO Optimization settings section for Pro plugin
* Added: Hook for Pro plugin to display internal linking settings
* Improved: Better integration with Pro plugin advanced features

= 1.0.1 - 2026-02-17 =
* Fixed: Added missing action hook for Pro plugin AI provider selector
* Fixed: Pro AI provider selector now displays correctly above API key field
* Fixed: Removed conflicting Pro conditional that prevented Pro UI from rendering
* Fixed: Pro features now fully visible when license is activated

= 1.0.0 - 2025-11-26 =
* Initial release
* AI-powered post generation with Groq API (free, unlimited)
* Automatic daily scheduling with WordPress Cron
* Draft and publish modes
* Pre-configured SEO-optimized prompt template (custom editing in Pro)
* Error logging and tracking
* Statistics dashboard
* Manual post generation
* Intelligent category assignment
* Automatic featured images from Unsplash
* Smart keyword selection based on recent posts
* WordPress Cron integration
* Security: Nonce verification, capability checks, input sanitization
* Comprehensive documentation

== Upgrade Notice ==

= 1.0.7 =
Fixes manual post generation counter and redirect; adds a generating indicator on Generate Post Now; dismissible recent errors; cleaner settings page layout. Tested with WordPress 7.0.

= 1.0.6 =
Pro users: update so your chosen AI provider endpoint is preserved when you save settings (fixes wrong host / invalid API key style errors after save).

= 1.0.4 =
Posts generate cleanly without an Unsplash key; featured images are only fetched when a key is saved.

= 1.0.3 =
You can save plugin settings without an Unsplash key; add a key when you want automatic featured images.

= 1.0.0 =
Initial release of AI Blog Automator. Automatically generate SEO-optimized blog posts using AI!

== Privacy Policy ==

This plugin does not collect, store, or transmit any user data from your WordPress installation, except what is necessary for its core functionality:

**What data is transmitted:**
- Your configured prompt template (including keywords)
- Basic API request parameters

**Where data is sent:**
- To your configured AI API endpoint (by default: Groq API)
- No data is sent to the plugin developers
- No tracking or analytics are performed by this plugin

**Data storage:**
- Plugin settings are stored in your WordPress database
- Generated posts are stored as standard WordPress posts
- No data is transmitted to third parties except your chosen AI provider

**Your responsibilities:**
- You control what data is sent via prompt templates
- You are responsible for compliance with your AI provider's terms
- Review generated content before publishing
- Ensure compliance with privacy laws in your jurisdiction

**Third-party services:**
- Groq Privacy Policy: https://groq.com/privacy-policy/
- Groq Terms of Use: https://groq.com/terms-of-use/
- Unsplash Privacy Policy: https://unsplash.com/privacy
- Unsplash Terms of Service: https://unsplash.com/terms
- Note: Pro version supports additional AI providers (OpenAI, Claude, etc.) - when using Pro with other providers, refer to those providers' Terms of Use and Privacy Policy pages directly

== Support ==

For support, please visit:
* Plugin documentation (included in download)
* WordPress.org support forums
* GitHub repository (if applicable)

== Credits ==

Developed with WordPress coding standards and best practices. Uses OpenAI API or compatible services for content generation.

== Additional Notes ==

**API Costs:** Be aware of API usage costs from your AI provider. Monitor your usage dashboard and set billing alerts.

**Content Quality:** AI-generated content should be reviewed for accuracy, quality, and brand alignment before publishing.

**SEO Considerations:** While the plugin is optimized for SEO-friendly content, you should still review and optimize posts with your preferred SEO plugin.

**Legal Compliance:** Ensure your use of AI-generated content complies with relevant laws and regulations in your jurisdiction.

**Backup Regularly:** As with any automation tool, maintain regular backups of your WordPress site.


