# QueryMind - AI-Powered Database Explorer for WordPress

Ask questions about your WordPress data in plain English. Get instant answers powered by AI.

<img width="1910" height="1439" alt="query-mind" src="https://github.com/user-attachments/assets/8e3ee251-3d79-4ab1-aabf-d2a8d8c8b4a5" />


![WordPress](https://img.shields.io/badge/WordPress-6.0%2B-blue.svg)
![PHP](https://img.shields.io/badge/PHP-8.0%2B-purple.svg)
![License](https://img.shields.io/badge/License-GPLv2-green.svg)

## Overview

QueryMind is an AI-powered WordPress plugin that enables users to query their database using natural language. Instead of writing SQL or navigating complex admin panels, simply ask questions like:

- *"How many orders did we get last month?"*
- *"Who are my top 10 customers by revenue?"*
- *"Show me all users who haven't logged in for 30 days"*
- *"What's the average order value this quarter?"*

## Features

- **Natural Language Queries** - Ask questions in plain English
- **AI-Powered** - Uses OpenAI GPT-4 or Anthropic Claude for intelligent SQL generation
- **Safe & Secure** - Read-only queries with comprehensive SQL validation
- **WooCommerce Support** - Deep integration with orders, products, and customers
- **Visual Results** - View results in tables with CSV export
- **Query History** - Track all your past queries
- **Saved Queries** - Save frequently used queries for quick access
- **Role-Based Access** - Configure which user roles can use QueryMind

## Supported Integrations

| Integration | Status |
|-------------|--------|
| WordPress Core | Full Support |
| WooCommerce | Full Support |
| LearnDash | Full Support |
| MemberPress | Full Support |
| Easy Digital Downloads | Full Support |
| Gravity Forms | Full Support |

## Requirements

- PHP 8.0 or higher
- WordPress 6.0 or higher
- MySQL 5.7+ or MariaDB 10.3+
- OpenAI API key or Anthropic API key

## Installation

1. Download the plugin zip file or clone this repository
2. Upload to `/wp-content/plugins/querymind`
3. Activate the plugin through the WordPress admin
4. Go to **QueryMind > Settings** and enter your API key
5. Start querying your data!

```bash
# Clone the repository
git clone https://github.com/mejba13/querymind.git

# Navigate to WordPress plugins directory
cd /path/to/wordpress/wp-content/plugins/

# Move the plugin
mv querymind ./
```

## Configuration

### API Keys

QueryMind supports multiple AI providers:

| Provider | Models | Get API Key |
|----------|--------|-------------|
| OpenAI | GPT-4o, GPT-4o-mini, GPT-4-turbo | [platform.openai.com](https://platform.openai.com/api-keys) |
| Anthropic | Claude 3.5 Sonnet, Claude 3 Opus | [console.anthropic.com](https://console.anthropic.com/) |

### Settings

| Setting | Default | Description |
|---------|---------|-------------|
| Max Rows | 1000 | Maximum rows returned per query |
| Query Timeout | 30s | Maximum query execution time |
| Daily Limit | 20 | Queries per user per day (0 = unlimited) |
| Cache Duration | 1 hour | Schema cache duration |

## Usage

### Basic Queries

```
"How many posts do we have?"
"Show me the 10 most recent users"
"What's the total number of comments this month?"
```

### WooCommerce Queries

```
"What was our revenue this month?"
"Show me top 10 customers by total spend"
"How many orders are pending?"
"What is our average order value?"
"Which products sold the most last week?"
```

### Advanced Queries

```
"Show me users who registered but never made a purchase"
"What's the revenue breakdown by country?"
"List products that haven't sold in 30 days"
```

## Security

QueryMind takes security seriously:

- **Read-Only Queries** - Only SELECT statements are allowed
- **SQL Validation** - All queries are validated before execution
- **Blocked Keywords** - DELETE, UPDATE, INSERT, DROP, ALTER, etc. are blocked
- **Row Limits** - Automatic LIMIT clause enforcement
- **Execution Timeout** - Queries are terminated if they exceed the timeout
- **Role-Based Access** - Only authorized users can run queries
- **No Data Transmission** - Your actual data never leaves your server

## File Structure

```
querymind/
├── querymind.php              # Main plugin file
├── uninstall.php              # Clean uninstall
├── readme.txt                 # WordPress.org readme
├── README.md                  # GitHub readme
├── includes/
│   ├── admin/                 # Admin interface
│   ├── api/                   # REST API endpoints
│   ├── ai/                    # AI provider integrations
│   ├── core/                  # Core functionality
│   ├── integrations/          # Plugin integrations
│   └── utils/                 # Utilities
├── assets/
│   ├── css/                   # Stylesheets
│   └── js/                    # JavaScript
└── languages/                 # Translations
```

## REST API Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/wp-json/querymind/v1/query` | Execute natural language query |
| GET | `/wp-json/querymind/v1/schema` | Get database schema |
| GET | `/wp-json/querymind/v1/suggestions` | Get query suggestions |
| GET | `/wp-json/querymind/v1/history` | Get query history |
| POST | `/wp-json/querymind/v1/saved` | Save a query |
| GET | `/wp-json/querymind/v1/saved` | List saved queries |
| DELETE | `/wp-json/querymind/v1/saved/{id}` | Delete saved query |

## Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

1. Fork the repository
2. Create your feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add some amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

## License

This project is licensed under the GPLv2 or later - see the [LICENSE](LICENSE) file for details.

## Author

**Engr Mejba Ahmed**
- Website: [mejba.me](https://www.mejba.me/)
- GitHub: [@mejba13](https://github.com/mejba13)

## Support

- [GitHub Issues](https://github.com/mejba13/querymind/issues)
- [Documentation](https://github.com/mejba13/querymind/wiki)

## Changelog

### 1.0.0
- Initial release
- Natural language to SQL conversion
- OpenAI and Anthropic support
- WooCommerce, LearnDash, MemberPress integrations
- Query history and saved queries
- CSV export
- Comprehensive SQL validation and security
