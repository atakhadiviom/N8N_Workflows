# N8N Workflows Explorer

A PHP application that scrapes GitHub for N8N workflows and generates AI-powered descriptions and titles using OpenAI's API. Features a modern grid view with search functionality.

## Features

- 🔍 **GitHub Scraping**: Automatically discovers N8N workflows from GitHub repositories
- 🤖 **AI Enhancement**: Uses OpenAI API to generate better titles and descriptions
- 🎨 **Modern UI**: Beautiful grid layout with Bootstrap 5
- 🔎 **Search Functionality**: Real-time search through workflows
- ⚡ **Caching**: Efficient caching system to avoid API rate limits
- 📱 **Responsive**: Works on desktop and mobile devices

## Requirements

- PHP 7.4 or higher
- OpenAI API key (optional but recommended)
- Internet connection for GitHub API access

## Setup Instructions

### 1. Clone or Download

Download the files to your local directory.

### 2. Configure OpenAI API (Optional)

1. Get your OpenAI API key from [OpenAI Platform](https://platform.openai.com/api-keys)
2. Open `config.php`
3. Replace `'your-openai-api-key-here'` with your actual API key:

```php
define('OPENAI_API_KEY', 'sk-your-actual-api-key-here');
```

### 3. Configure GitHub Token (Optional)

For higher API rate limits, you can add a GitHub personal access token:

1. Get a token from [GitHub Settings](https://github.com/settings/tokens)
2. In `config.php`, set:

```php
define('GITHUB_TOKEN', 'your-github-token-here');
```

### 4. Start the Application

#### Option A: Using PHP Built-in Server

```bash
php -S localhost:8000
```

Then open http://localhost:8000 in your browser.

#### Option B: Using XAMPP/WAMP/MAMP

1. Copy files to your web server directory (htdocs/www)
2. Access via http://localhost/N8N_Workflows/

## Usage

1. **Scrape Workflows**: Click "Scrape New Workflows" to fetch the latest N8N workflows from GitHub
2. **Search**: Use the search bar to filter workflows by title, description, or author
3. **View Details**: Click "View" on any workflow card to open the GitHub repository

## How It Works

1. **GitHub API Search**: Searches GitHub for repositories containing N8N workflows using various keywords
2. **Data Processing**: Extracts repository information including name, description, author, and stats
3. **AI Enhancement**: Sends workflow data to OpenAI API to generate improved titles and descriptions
4. **Caching**: Stores results in `workflows_cache.json` to avoid repeated API calls
5. **Display**: Shows workflows in a responsive grid with search functionality

## File Structure

```
├── index.php          # Main application file with UI
├── config.php         # Configuration settings
├── functions.php      # Core functionality (scraping, AI, search)
├── README.md          # This file
└── workflows_cache.json # Auto-generated cache file
```

## API Rate Limits

- **GitHub API**: 60 requests/hour without token, 5000/hour with token
- **OpenAI API**: Depends on your plan (pay-per-use)
- The application includes rate limiting and caching to minimize API usage

## Troubleshooting

### Common Issues

1. **"Failed to scrape workflows"**
   - Check internet connection
   - Verify GitHub API is accessible
   - Check PHP error logs

2. **No AI descriptions generated**
   - Verify OpenAI API key is correct
   - Check OpenAI account has credits
   - Review error logs

3. **Empty results**
   - GitHub API might be rate-limited
   - Try again after some time
   - Consider adding GitHub token

### Error Logs

Check PHP error logs for detailed error information:
- On most systems: `/var/log/php_errors.log`
- Or check your web server's error log

## Customization

### Search Queries

Modify the search queries in `functions.php` to find different types of workflows:

```php
$searchQueries = [
    'n8n workflow',
    'n8n automation',
    'n8n json',
    'filename:.n8n'
];
```

### UI Styling

Customize the appearance by modifying the CSS in `index.php` or adding external stylesheets.

### AI Prompts

Adjust the OpenAI prompts in `generateAIDescription()` function to get different types of descriptions.

## Security Notes

- Never commit your API keys to version control
- Use environment variables for production deployments
- Consider implementing user authentication for production use
- Validate and sanitize all user inputs

## License

This project is open source. Feel free to modify and distribute as needed.

## Contributing

Contributions are welcome! Please feel free to submit issues and enhancement requests.