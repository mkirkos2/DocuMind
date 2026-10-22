# DocuMind

DocuMind is a document-based AI assistant that allows users to upload documents and ask questions about their content.

## Features

- Upload and manage documents.
- Ask questions about uploaded documents using AI.
- Retrieve relevant information from document content.
- View answers based on the available document context.
- Web-based interface for interacting with documents.

## Tech Stack

- **Backend:** Laravel 13, PHP
- **Frontend:** Vue 3, TypeScript, Inertia.js
- **Styling:** Tailwind CSS
- **Database:** SQLite
- **AI:** Google Gemini API
- **Testing:** PHPUnit

## Requirements

- PHP and Composer
- Node.js and npm
- SQLite
- Google Gemini API key (free tier available)

## Installation

1. Clone the repository:

   ```bash
   git clone https://github.com/mkirkos2/DocuMind.git
   cd DocuMind
   ```

2. Install PHP dependencies:

   ```bash
   composer install
   ```

3. Create the environment file:

   ```bash
   cp .env.example .env
   ```

   On Windows PowerShell, use `Copy-Item .env.example .env` instead.

4. Generate the application key:

   ```bash
   php artisan key:generate
   ```

5. Create the local SQLite database if it does not exist, then run migrations:

   ```bash
   php artisan migrate
   ```

6. Install frontend dependencies and build the assets:

   ```bash
   npm install
   npm run build
   ```

7. Obtain a Google Gemini API key:
   - Visit [Google AI Studio](https://aistudio.google.com/) 
   - Create a free account or sign in
   - Generate an API key
   - Set `GEMINI_API_KEY` in your `.env` file
   - Optionally set `GEMINI_MODEL` to specify a different model (default: gemini-3-flash-preview)
   
   The application is configured to use models within Google's free tier by default. Be aware that free tier quotas and model availability can change, and you should monitor your usage to stay within limits.

8. Start the Laravel development server:

   ```bash
   php artisan serve
   ```

## Testing

Run the test suite with:

```bash
php artisan test
```

## Security

Do not commit your `.env` file, API keys, credentials, local database files, or uploaded private documents.

## License

No license has been specified yet.