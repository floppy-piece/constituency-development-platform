# Constituency Development Platform

**AI-Powered Civic Engagement & Issue Routing System for Members of Parliament (MPs) and Citizens**
Built with **Laravel 13**, **Gemma 4 (Google AI Studio)**, and **Telegram Bot Webhooks**.

## 📌 Overview

The **Constituency Development Platform** bridges the gap between citizens and their elected representatives. Currently citizens send requests in many diffferent ways and some may not even reach their target. **Constituency Development Platform** provides a centralized request sending mode with multimedi capabilities. Citizens submit real-world community issues (infrastructure, water supply, security, public services) via **Telegram** using text, voice notes, or image uploads.

Powered by **Google’s Gemma 4 LLM**, the system automatically transcribes, categorizes, extracts structured JSON data, assigns urgency scores, and detects duplicate issues before routing them directly to the MP’s administrative dashboard.



## 🔥 Key Features

* **📱 Multi-Modal Telegram Bot Integration**
  * Accepts raw **Text**, **Voice Messages** (`.ogg`/`.mp3`), and **Photo Uploads**.
  * Auto-transcribes voice notes and extracts key entities using Gemma 4.

* **🤖 Gemma 4 Civic AI Engine**
  * **Automated Categorization**: Tag issues (e.g., *Roads*, *Sanitation*, *Healthcare*, *Education*).
  * **Urgency Rating**: Scores requests from `Low` to `Critical` to prioritize emergencies.
  * **Deduplication Engine**: Flags duplicate complaints within the same geographic ward.
  * **Language Translation**: Translates local vernacular/dialects into clean official reports.

* **⏱️ Anti-Spam & Rate Limiting**
  * Enforces a strict cooldown (e.g., 1 submission per 2 hours per Telegram user) to prevent bot spam and server overload.

* **📊 MP Dashboard (Laravel Blade + Tailwind)**
  * Real-time status tracking (`Pending`, `In Progress`, `Resolved`, `Rejected`).
  * Citizen identity lookup, category filtering, and location-based sorting.
  * One-click official response dispatch back to the citizen's Telegram.



## 🏗️ System Architecture




                [ Telegram User ]
                        │
                        ▼ 
                (Webhook POST)
                    [ Laravel Application ] ──► (Rate Limiter Check)
                        │
                        ├──► [ Audio/Image Storage ]
                        │
                        ├──► [ Gemma 4 API (Google AI Studio) ]
                        │         │
                        │         └─► Extract Structured JSON (Category, Urgency, Summary)
                        │
                        ▼
                [ SQLite / MySQL Database ]
                        │
                        ▼
                [ MP Administrative Dashboard (Blade + Tailwind) ]



## 🛠️ Tech Stack
```
 ___________________________________________________________________________________________
| Component             | Technology                                                        |
|_______________________|___________________________________________________________________|
|                       |                                                                   |
| **Backend Framework** | Laravel 11 (PHP 8.2+)                                             |
| **Frontend UI**       | Laravel Blade, Tailwind CSS, Alpine.js                            |
| **AI / LLM Engine**   | Gemma 4 (`gemma-4-9b-it` / `gemma-4-27b-it` via Google GenAI SDK) |
| **Messaging Channel** | Telegram Bot API (Webhook Mode)                                   |
| **Database**          | SQLite (Development) / PostgreSQL / MySQL (Production)            |
|_______________________|___________________________________________________________________|
```


## 🚀 Getting Started

### Prerequisites

* PHP 8.2 or higher
* Composer
* Node.js & npm
* A Telegram Bot Token (from [@BotFather](https://t.me/BotFather))
* A Google Gemini / Gemma 4 API Key (from [Google AI Studio](https://aistudio.google.com/))
* Ngrok (for local Telegram webhook testing)

### 📥 Installation

1. **Clone the Repository**
```bash
   
   git clone https://github.com/floppy-piece/constituency-development-platform.git
   cd constituency-development-platform
```
    

2. **Install PHP & Node Dependencies**
```bash
    composer install
    npm install && npm run build
```


3. **Environment Configuration**
Copy the `.env.example` file to `.env`:
```bash
    
    cp .env.example .env

```


**🔑 Authentication Setup (JWT)**

This project uses php-open-source-saver/jwt-auth (or tymon/jwt-auth) to handle stateless API authentication for Member Parliament (MP) users.
 Install the JWT Package
```Bash
    
    composer require php-open-source-saver/jwt-auth

```

 Publish Configuration File
```Bash

    php artisan vendor:publish --provider="PHPOpenSourceSaver\JWTAuth\Providers\LaravelServiceProvider"

```

Generate JWT Secret Key

Generate the JWT_SECRET key in your .env file:  

```Bash

    php artisan jwt:secret 

```

Update the following variables inside `.env` if they are not present:
```env
   
    APP_NAME="Constituency Development Platform"                                                        
    APP_URL=http://localhost:8000                                                                       
    
    # Gemma 4 / Google AI Studio Key
    GEMMA_API_KEY=your_google_ai_studio_or_vertex_api_key
    GEMMA_ENDPOINT=https://generativelanguage.googleapis.com/v1beta/models/gemma-4-12b:generateContent
    GEMMA_MODEL=gemma-4-12b
    
    # Telegram Bot Configuration
    TELEGRAM_BOT_TOKEN=123456789:ABCdefGhIJKlmNoPQRsTUVwxyZ
    TELEGRAM_WEBHOOK_URL=https://your_domain.ngrok-free.app/api/telegram/webhook
```
**Other required APIs are available in the .env.example**


4. **Generate Application Key & Run Migrations**
```bash

    php artisan key:generate
    php artisan migrate --seed

```


5. **Register Telegram Webhook**
Run the artisan command or execute a CURL request:
```bash

    curl -X POST "https://api.telegram.org/bot<telegram_bot_token>/setWebhook" \ 
     -H "Content-Type: application/json" \
     -d '{
           "url": "https://<your_domain.ngrok-free.app>/api/telegram/webhook",
           "secret_token": "<your_secret_key>"
         }'
```


6. **Start the Local Server**
```bash

    php artisan serve

```


## 🤖 Gemma 4 Integration Details

The application uses a custom service (`App\Services\Gemma4Service`) to send unstructured user inputs (text/voice/images) to **Gemma 4**. The prompt enforces strict JSON response formatting:

json
    {
        "category": "Infrastructure",
        "urgency": "High",
        "summary": "Pothole causing severe traffic near Main Street bridge.",
        "location_mentioned": "Main Street Bridge, Ward 3",
        "language_detected": "Swahili",
        "translated_text": "There is a deep hole on the bridge causing accidents."
    }



## 🧪 Testing the Webhook Locally

1. Start `ngrok` to expose your local web server:
```bash

    ngrok http 8000

```

2. Update `TELEGRAM_WEBHOOK_URL` in `.env` with your HTTPS ngrok URL.
3. Send a message or voice note to your Telegram Bot.
4. Check the logs in real time:
```bash
    
    tail -f storage/logs/laravel.log
    
```

## 👥 Authors & Acknowledgments

* **Developer**: Built for the Gemma 4 Hackathon / Civic Innovation Challenge.Pwani GDG
* Special thanks to **Kaggle && Google DeepMind** for the Gemma 4 model models.

