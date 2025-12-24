=== QueryMind ===
Contributors: developer-developer
Tags: database, ai, natural language, sql, woocommerce, analytics
Requires at least: 6.0
Tested up to: 6.9
Requires PHP: 8.0
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Ask questions about your WordPress data in plain English. AI-powered database explorer with visual reports.

== Description ==

QueryMind is an AI-powered WordPress plugin that enables you to query your database using natural language. Instead of writing SQL or navigating complex admin panels, simply ask questions like:

* "How many orders did we get last month?"
* "Who are my top 10 customers by revenue?"
* "Show me all users who haven't logged in for 30 days"
* "What's the average order value this quarter?"

= Key Features =

* **Natural Language Queries**: Ask questions in plain English
* **AI-Powered**: Uses OpenAI GPT-4 or Anthropic Claude for intelligent SQL generation
* **Safe & Secure**: Read-only queries with comprehensive SQL validation
* **WooCommerce Support**: Deep integration with WooCommerce orders, products, and customers
* **Visual Results**: View results in tables with CSV export
* **Query History**: Track all your past queries
* **Saved Queries**: Save frequently used queries for quick access
* **Configurable Limits**: Set daily query limits and row limits to manage API costs

= Supported Integrations =

* WordPress Core (posts, users, comments)
* WooCommerce (orders, products, customers)
* LearnDash (courses, progress, quizzes)
* MemberPress (memberships, transactions, subscriptions)
* Easy Digital Downloads
* Gravity Forms

= Requirements =

* PHP 8.0 or higher
* WordPress 6.0 or higher
* OpenAI API key or Anthropic API key (you bring your own API key)

= Privacy & Security =

QueryMind takes security seriously:

* All queries are validated to ensure they are read-only (SELECT only)
* Dangerous SQL keywords and functions are blocked
* Row limits are enforced to prevent server overload
* Only your question and database schema structure are sent to the AI provider
* Your actual database content is never transmitted externally
* API keys are stored securely in your WordPress database

= Third-Party Services =

This plugin connects to external AI services to convert natural language questions into SQL queries:

**OpenAI API** (if configured)
* Service URL: https://api.openai.com
* Privacy Policy: https://openai.com/privacy/
* Terms of Service: https://openai.com/terms/

**Anthropic API** (if configured)
* Service URL: https://api.anthropic.com
* Privacy Policy: https://www.anthropic.com/privacy
* Terms of Service: https://www.anthropic.com/terms

Data sent to these services includes your question and your database schema structure (table names, column names). Your actual database content is never sent.

== Installation ==

1. Upload the plugin files to the `/wp-content/plugins/querymind` directory, or install the plugin through the WordPress plugins screen directly.
2. Activate the plugin through the 'Plugins' screen in WordPress.
3. Go to QueryMind > Settings and enter your OpenAI or Anthropic API key.
4. Navigate to QueryMind to start querying your data!

== Frequently Asked Questions ==

= Do I need technical knowledge to use QueryMind? =

No! QueryMind is designed for non-technical users. Simply ask questions in plain English, and the AI will generate the appropriate SQL queries.

= Is my data safe? =

Yes. QueryMind only performs read-only (SELECT) queries. It cannot modify, delete, or alter your data in any way. Additionally, your actual data is never sent to external AI services - only your question and database schema are transmitted.

= Which AI providers are supported? =

QueryMind supports OpenAI (GPT-4o, GPT-4o Mini, GPT-4 Turbo, GPT-3.5 Turbo) and Anthropic (Claude 3.5 Sonnet, Claude 3 Opus, Claude 3 Haiku).

= Does it work with WooCommerce? =

Yes! QueryMind has deep integration with WooCommerce and understands orders, products, customers, and all related data. You can ask questions like "What was our revenue this month?" or "Show me top selling products."

= Can I export the results? =

Yes, you can export query results to CSV format with one click.

= Can I limit the number of queries? =

Yes, you can configure a daily query limit per user in the settings. This helps manage API costs. Set it to 0 for unlimited queries.

== Screenshots ==

1. Main query interface - Ask questions in natural language
2. Results display with data table view
3. Settings page with AI provider configuration
4. Query history with status tracking
5. Saved queries for quick access

== Changelog ==

= 1.0.0 =
* Initial release
* Natural language to SQL conversion using AI
* OpenAI and Anthropic API support
* WooCommerce integration for e-commerce queries
* LearnDash and MemberPress integration support
* Query history tracking
* Save and manage favorite queries
* CSV export functionality
* Comprehensive SQL validation and security
* Role-based access control
* Configurable query limits and timeouts

== Upgrade Notice ==

= 1.0.0 =
Initial release of QueryMind - AI-powered database explorer for WordPress.
