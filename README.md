# AI Image Generator API (Pollinations)

A simple PHP REST API for generating AI images using the Pollinations image generation service.

This API uses the Pollinations AI image generation service:  
https://pollinations.ai

This project demonstrates how to build a lightweight image generation API with optional:

- file storage
- database logging
- billing system
- authentication

---

## Example

### Request

POST `/api/generation.php`

```json
{
  "prompt": "a futuristic city at sunset",
  "aspect_ratio": "16:9",
  "model": "flux"
}
```

### Response

```json
{
  "ok": true,
  "image_id": 12,
  "image_uuid": "b6b8a4f2-9e6e-4b1e-a1fa-8f1c2d6f1d72",
  "image_url": "/uploads/ai/2026/03/ai-b6b8a4f2.webp",
  "meta": {
    "model": "flux",
    "width": 1280,
    "height": 720,
    "aspect_ratio": "16:9",
    "seed": 18372651
  }
}
```

---

## Example Result

Create a folder:

docs/

Place an example generated image inside:

docs/example-image.jpg

Then display it in README:

![Example image](docs/example-image.jpg)

---

## Endpoint

POST `/api/generation.php`

### Request JSON

- `prompt` (string, required)
- `model` (string, optional) — validated against config list
- `aspect_ratio` (string, optional) — validated against config list
- `seed` (int|string, optional)
- `is_private` ("yes" | "no", optional)

### Response JSON

- `ok` (bool)
- `image_id` (int) — 0 if SAVE_DB=no
- `image_uuid` (string)
- `image_url` (string) — empty if SAVE_FILES=no
- `meta` (object)

---

## Features

- AI image generation using Pollinations
- simple REST API endpoint
- configurable models
- configurable aspect ratios
- automatic seed generation
- optional file storage (WebP + original JPG)
- optional database logging
- optional billing system
- privacy mode support
- authentication via Bearer token

---

## Quick Start

Run the local PHP server:

```bash
php -S 127.0.0.1:8080 -t public
```

Send a request:

```bash
curl -X POST http://127.0.0.1:8080/api/generation.php \
-H "Content-Type: application/json" \
-d '{"prompt":"a cyberpunk cat","aspect_ratio":"1:1"}'
```

---

## Authentication

If `REQUIRE_AUTH=yes` in `.env`, send header:

Authorization: Bearer <API_AUTH_TOKEN>

---

## Setup (local)

1) Create `.env` from `.env.example`

2) Configure environment variables

3) Run the server:

```bash
php -S 127.0.0.1:8080 -t public
```

4) Call the API endpoint

---

## Database setup (optional)

Database is required only if you enable these features:

- SAVE_DB=yes
- BILLING_ENABLED=yes

### 1. Create a database

### 2. Import schema

```bash
mysql -u USER -p DATABASE_NAME < sql/schema.sql
```

### 3. Configure `.env`

```
DB_DSN="mysql:host=127.0.0.1;dbname=DATABASE_NAME;charset=utf8mb4"
DB_USER=your_db_user
DB_PASS=your_db_password
```

If SAVE_DB=no, the API works without a database.

---

## Notes

If SAVE_FILES=yes, images are saved to:

/uploads/ai/YYYY/MM/

Images are stored as:

- WebP (80% quality)
- Original JPG (100%)

If SAVE_DB=yes, records are inserted into table `ai_images`.

No secrets are committed to GitHub.

---

## Project Structure

```
ai-image-generator-api
  README.md
  .env.example
  .gitignore

  sql/
    schema.sql

  config/
    config.php

  app/
    core/

  public/
    api/generation.php
    index.php

  uploads/
  storage/

  docs/
    example-image.jpg
```

---

## Recommended GitHub Topics

ai  
image-generation  
php  
rest-api  
ai-images  
pollinations  
ai-api  

---

## License

MIT License

---

⭐ If you find this project useful, please consider giving it a star.