# Bitmesh.ai API SDK – PHP demo

Minimal PHP project to show Bitmesh.ai API usage (chat, image, video).

## Setup

```bash
composer install
```

## Run

Point your web server document root at `public/`, or use PHP built-in server:

```bash
php -S localhost:8080 -t public
```

Then open:

- http://localhost:8080/
- http://localhost:8080/chat
- http://localhost:8080/image
- http://localhost:8080/video

## Structure

- `public/index.php` – entry point, dispatches via router
- `src/Router.php` – simple router for `/`, `/chat`, `/image`, `/video`
- `src/BitmeshClient.php` – stub for the Bitmesh SDK (replace with real package when available)
