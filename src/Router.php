<?php

declare(strict_types=1);

namespace BitmeshExample;

use BitmeshAI\BitmeshClient;

final class Router
{
    private const ROUTES = [
        '/' => 'home',
        '/chat' => 'chat',
        '/chat-vision' => 'chatVision',
        '/image' => 'image',
        '/video' => 'video',
        '/video-status' => 'videoStatus',
    ];

    public function match(string $path): ?string
    {
        $path = '/' . trim(parse_url($path, PHP_URL_PATH) ?? '', '/');
        if ($path === '') {
            $path = '/';
        }

        return self::ROUTES[$path] ?? null;
    }

    public function dispatch(string $path): void
    {
        $action = $this->match($path);

        if ($action === null) {
            http_response_code(404);
            echo $this->renderLayout('Not found', '<p>Page not found. Try <a href="/chat">/chat</a>, <a href="/chat-vision">/chat-vision</a>, <a href="/image">/image</a>, <a href="/video">/video</a>, <a href="/video-status">/video-status</a>.</p>');
            return;
        }

        $this->{$action}();
    }

    private function home(): void
    {
        echo $this->renderLayout('Home', '<h1>Bitmesh Demo</h1><p>Choose: <a href="/chat">Chat</a>, <a href="/chat-vision">Chat Vision</a>, <a href="/image">Image</a>, <a href="/video">Video</a>.</p>');
    }

    private function chat(): void
    {
        echo $this->renderLayout('Chat', $this->renderChat());
    }

    private function chatVision(): void
    {
        echo $this->renderLayout('Chat Vision', $this->renderChatVision());
    }

    private function image(): void
    {
        echo $this->renderLayout('Image', $this->renderImage());
    }

    private function video(): void
    {
        echo $this->renderLayout('Video', $this->renderVideo());
    }

    private function videoStatus(): void
    {
        echo $this->renderLayout('Video status', $this->renderVideoStatus());
    }

    private function renderLayout(string $title, string $body): string
    {
        return <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bitmesh Demo – {$title}</title>
    <style>
        body { font-family: system-ui, sans-serif; max-width: 640px; margin: 2rem auto; padding: 0 1rem; }
        nav { margin-bottom: 1.5rem; }
        nav a { margin-right: 1rem; }
        h1 { font-size: 1.25rem; }
        pre { background: #f4f4f4; padding: 1rem; overflow-x: auto; font-size: 0.875rem; }
        .error { color: #c00; }
        code { background: #eee; padding: 0.2em 0.4em; font-size: 0.9em; }
    </style>
</head>
<body>
    <nav>
        <a href="/chat">Chat</a>
        <a href="/chat-vision">Chat Vision</a>
        <a href="/image">Image</a>
        <a href="/video">Video</a>
    </nav>
    {$body}
</body>
</html>
HTML;
    }

    private function renderChat(): string
    {
        $snippet = <<<'PHP'
<?php

require 'vendor/autoload.php';

use BitmeshAI\BitmeshClient;

$consumerKey = getenv('BITMESH_CONSUMER_KEY') ?: 'YOUR_CONSUMER_KEY';
$consumerSecret = getenv('BITMESH_CONSUMER_SECRET') ?: 'YOUR_CONSUMER_SECRET';
$apiBaseUrl = getenv('BITMESH_API_BASE_URL') ?: 'https://aiproxyapi-production.up.railway.app';

$client = new BitmeshClient($consumerKey, $consumerSecret, $apiBaseUrl);

// Chat with system and user messages
$response = $client->chat([
    ['role' => 'system', 'content' => 'You are a helpful assistant.'],
    ['role' => 'user', 'content' => 'What are some fun things to do with AI?']
], 'google/gemma-3n-e4b-it', ['max_tokens' => 1000]);

// Response has 'choices' and optionally 'usage'
$content = $response['choices'][0]['message']['content'] ?? json_encode($response);
echo $content;
PHP;
        $html = "<h1>Chat</h1><p>Basic text-only PHP example using the Bitmesh SDK.</p>";
        $html .= "<h2>Example code</h2><pre>" . htmlspecialchars($snippet) . "</pre>";

        $consumerKey = (string) ($_ENV['BITMESH_CONSUMER_KEY'] ?? getenv('BITMESH_CONSUMER_KEY') ?: '');
        $consumerSecret = (string) ($_ENV['BITMESH_CONSUMER_SECRET'] ?? getenv('BITMESH_CONSUMER_SECRET') ?: '');
        $apiBaseUrl = (string) ($_ENV['BITMESH_API_BASE_URL'] ?? getenv('BITMESH_API_BASE_URL') ?: 'https://aiproxyapi-production.up.railway.app');

        if ($consumerKey !== '' && $consumerSecret !== '') {
            try {
                $client = new BitmeshClient($consumerKey, $consumerSecret, $apiBaseUrl);
                $response = $client->chat([
                    ['role' => 'system', 'content' => 'You are a helpful assistant.'],
                    ['role' => 'user', 'content' => 'Say "Hello from Bitmesh" in one short sentence.']
                ], 'google/gemma-3n-e4b-it', ['max_tokens' => 1000]);
                $html .= "<h2>API response</h2><pre>" . htmlspecialchars(json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)) . "</pre>";
                $content = $response['choices'][0]['message']['content'] ?? null;
                if ($content !== null) {
                    $html .= "<p><strong>Assistant reply:</strong> " . htmlspecialchars($content) . "</p>";
                }
            } catch (\Throwable $e) {
                $html .= "<h2>API response</h2><p class=\"error\">Error: " . htmlspecialchars($e->getMessage()) . "</p>";
            }
        } else {
            $html .= "<h2>API response</h2><p>Set <code>BITMESH_CONSUMER_KEY</code> and <code>BITMESH_CONSUMER_SECRET</code> in your environment to see a live response above.</p>";
        }

        return $html;
    }

    private function renderChatVision(): string
    {
        $snippet = <<<'PHP'
<?php

require 'vendor/autoload.php';

use BitmeshAI\BitmeshClient;

$consumerKey = getenv('BITMESH_CONSUMER_KEY') ?: 'YOUR_CONSUMER_KEY';
$consumerSecret = getenv('BITMESH_CONSUMER_SECRET') ?: 'YOUR_CONSUMER_SECRET';
$apiBaseUrl = getenv('BITMESH_API_BASE_URL') ?: 'https://aiproxyapi-production.up.railway.app';

$client = new BitmeshClient($consumerKey, $consumerSecret, $apiBaseUrl);

// Chat with structured multimodal content
$response = $client->chat([
    ['role' => 'system', 'content' => 'You are a helpful assistant.'],
    ['role' => 'user', 'content' => [
        ['type' => 'text', 'text' => 'solve this riddle'],
        ['type' => 'image_url', 'image_url' => ['url' => 'https://cdn1.byjus.com/wp-content/uploads/2020/10/maths-puzzles-example-2-solution.png']]
    ]]
], 'google/gemma-3n-e4b-it', ['max_tokens' => 1000]);

// Response has 'choices' and optionally 'usage'
$content = $response['choices'][0]['message']['content'] ?? json_encode($response);
echo $content;
PHP;
        $html = "<h1>Chat Vision</h1><p>Multimodal PHP example using structured message content.</p>";
        $html .= "<h2>Example code</h2><pre>" . htmlspecialchars($snippet) . "</pre>";

        $consumerKey = (string) ($_ENV['BITMESH_CONSUMER_KEY'] ?? getenv('BITMESH_CONSUMER_KEY') ?: '');
        $consumerSecret = (string) ($_ENV['BITMESH_CONSUMER_SECRET'] ?? getenv('BITMESH_CONSUMER_SECRET') ?: '');
        $apiBaseUrl = (string) ($_ENV['BITMESH_API_BASE_URL'] ?? getenv('BITMESH_API_BASE_URL') ?: 'https://aiproxyapi-production.up.railway.app');

        if ($consumerKey !== '' && $consumerSecret !== '') {
            try {
                $client = new BitmeshClient($consumerKey, $consumerSecret, $apiBaseUrl);
                $response = $client->chat([
                    ['role' => 'system', 'content' => 'You are a helpful assistant.'],
                    ['role' => 'user', 'content' => [
                        ['type' => 'text', 'text' => 'solve this riddle'],
                        ['type' => 'image_url', 'image_url' => ['url' => 'https://cdn1.byjus.com/wp-content/uploads/2020/10/maths-puzzles-example-2-solution.png']]
                    ]]
                ], 'google/gemma-3n-e4b-it', ['max_tokens' => 1000]);
                $html .= "<h2>API response</h2><pre>" . htmlspecialchars(json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)) . "</pre>";
                $content = $response['choices'][0]['message']['content'] ?? null;
                if ($content !== null) {
                    $html .= "<p><strong>Assistant reply:</strong> " . htmlspecialchars($content) . "</p>";
                }
            } catch (\Throwable $e) {
                $html .= "<h2>API response</h2><p class=\"error\">Error: " . htmlspecialchars($e->getMessage()) . "</p>";
            }
        } else {
            $html .= "<h2>API response</h2><p>Set <code>BITMESH_CONSUMER_KEY</code> and <code>BITMESH_CONSUMER_SECRET</code> in your environment to see a live response above.</p>";
        }

        return $html;
    }

    private function renderImage(): string
    {
        $snippet = <<<'PHP'
<?php

require 'vendor/autoload.php';

use BitmeshAI\BitmeshClient;

$consumerKey = getenv('BITMESH_CONSUMER_KEY') ?: 'YOUR_CONSUMER_KEY';
$consumerSecret = getenv('BITMESH_CONSUMER_SECRET') ?: 'YOUR_CONSUMER_SECRET';
$apiBaseUrl = getenv('BITMESH_API_BASE_URL') ?: 'https://aiproxyapi-production.up.railway.app';

$client = new BitmeshClient($consumerKey, $consumerSecret, $apiBaseUrl);

// Generate image: prompt, model name, optional options (width, height, steps, seed, n)
$response = $client->image(
    'A serene mountain landscape at sunset',
    'rundiffusion/juggernaut-lightning-flux',
    ['width' => 1024, 'height' => 1024]
);

// Image URL(s) are in data[].url
$url = $response['data'][0]['url'] ?? null;
if ($url) {
    echo '<img src="' . htmlspecialchars($url) . '" alt="Generated" />';
}
PHP;
        $html = "<h1>Image</h1><p>Basic PHP example using the Bitmesh SDK with <code>rundiffusion/juggernaut-lightning-flux</code>.</p>";
        $html .= "<h2>Example code</h2><pre>" . htmlspecialchars($snippet) . "</pre>";

        $consumerKey = (string) ($_ENV['BITMESH_CONSUMER_KEY'] ?? getenv('BITMESH_CONSUMER_KEY') ?: '');
        $consumerSecret = (string) ($_ENV['BITMESH_CONSUMER_SECRET'] ?? getenv('BITMESH_CONSUMER_SECRET') ?: '');
        $apiBaseUrl = (string) ($_ENV['BITMESH_API_BASE_URL'] ?? getenv('BITMESH_API_BASE_URL') ?: 'https://aiproxyapi-production.up.railway.app');

        if ($consumerKey !== '' && $consumerSecret !== '') {
            try {
                $client = new BitmeshClient($consumerKey, $consumerSecret, $apiBaseUrl);
                $response = $client->image(
                    'A cute robot reading a book, digital art',
                    'rundiffusion/juggernaut-lightning-flux',
                    ['width' => 1024, 'height' => 1024]
                );
                $html .= "<h2>API response</h2><pre>" . htmlspecialchars(json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)) . "</pre>";
                $data = $response['data'] ?? [];
                if (is_array($data) && $data !== []) {
                    $html .= "<h2>Generated image</h2>";
                    foreach ($data as $item) {
                        $url = $item['url'] ?? null;
                        if ($url !== null && $url !== '') {
                            $html .= '<p><img src="' . htmlspecialchars($url) . '" alt="Generated" style="max-width:100%;height:auto;border:1px solid #ddd;" /></p>';
                        }
                    }
                }
            } catch (\Throwable $e) {
                $html .= "<h2>API response</h2><p class=\"error\">Error: " . htmlspecialchars($e->getMessage()) . "</p>";
            }
        } else {
            $html .= "<h2>API response</h2><p>Set <code>BITMESH_CONSUMER_KEY</code> and <code>BITMESH_CONSUMER_SECRET</code> in your environment to see a live response above.</p>";
        }

        return $html;
    }

    private function renderVideo(): string
    {
        $snippet = <<<'PHP'
<?php

require 'vendor/autoload.php';

use BitmeshAI\BitmeshClient;

$consumerKey = getenv('BITMESH_CONSUMER_KEY') ?: 'YOUR_CONSUMER_KEY';
$consumerSecret = getenv('BITMESH_CONSUMER_SECRET') ?: 'YOUR_CONSUMER_SECRET';
$apiBaseUrl = getenv('BITMESH_API_BASE_URL') ?: 'https://aiproxyapi-production.up.railway.app';

$client = new BitmeshClient($consumerKey, $consumerSecret, $apiBaseUrl);

// Start video generation (returns in_progress; use id to poll status)
$response = $client->video(
    'A cat walking in the rain, cinematic',
    'ByteDance/Seedance-1.0-lite'
);

// Response has id and status (e.g. in_progress)
$id = $response['id'] ?? null;
$status = $response['status'] ?? null;
if ($id) {
    // Poll GET /video/{id} for status and output URL
    $statusResponse = $client->videoStatus($id);
}
PHP;
        $html = "<h1>Video</h1><p>Start a video generation request. Uses model <code>ByteDance/Seedance-1.0-lite</code>. Response returns <code>in_progress</code>; use the link below to check progress.</p>";
        $html .= "<h2>Example code</h2><pre>" . htmlspecialchars($snippet) . "</pre>";

        $consumerKey = (string) ($_ENV['BITMESH_CONSUMER_KEY'] ?? getenv('BITMESH_CONSUMER_KEY') ?: '');
        $consumerSecret = (string) ($_ENV['BITMESH_CONSUMER_SECRET'] ?? getenv('BITMESH_CONSUMER_SECRET') ?: '');
        $apiBaseUrl = (string) ($_ENV['BITMESH_API_BASE_URL'] ?? getenv('BITMESH_API_BASE_URL') ?: 'https://aiproxyapi-production.up.railway.app');

        if ($consumerKey !== '' && $consumerSecret !== '') {
            try {
                $client = new BitmeshClient($consumerKey, $consumerSecret, $apiBaseUrl);
                $response = $client->video(
                    'A cat walking in the rain, cinematic, 4k',
                    'ByteDance/Seedance-1.0-lite'
                );
                $html .= "<h2>API response</h2><pre>" . htmlspecialchars(json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)) . "</pre>";
                $id = $response['id'] ?? null;
                $status = $response['status'] ?? null;
                if ($id !== null && $id !== '') {
                    $statusEsc = htmlspecialchars($status ?? '');
                    $html .= "<p><strong>Status:</strong> " . $statusEsc . "</p>";
                    $html .= '<p><a href="/video-status?id=' . htmlspecialchars(rawurlencode($id)) . '">Check video progress</a></p>';
                }
            } catch (\Throwable $e) {
                $html .= "<h2>API response</h2><p class=\"error\">Error: " . htmlspecialchars($e->getMessage()) . "</p>";
            }
        } else {
            $html .= "<h2>API response</h2><p>Set <code>BITMESH_CONSUMER_KEY</code> and <code>BITMESH_CONSUMER_SECRET</code> in your environment to start a video and see a link to check progress.</p>";
        }

        return $html;
    }

    private function renderVideoStatus(): string
    {
        $snippet = <<<'PHP'
// After video() returns an id, poll status with GET /video/{id}
$statusResponse = $client->videoStatus($id);
// e.g. status, outputs, video_url, cost
PHP;
        $html = "<h1>Video status</h1><p>Check progress of a video job using <code>GET /video/{id}</code>.</p>";
        $html .= "<h2>Example code</h2><pre>" . htmlspecialchars($snippet) . "</pre>";

        $id = isset($_GET['id']) ? trim((string) $_GET['id']) : '';
        if ($id === '') {
            $html .= "<h2>Check status</h2><p>Add a video ID to the URL, e.g. <a href=\"/video-status?id=example-id\">/video-status?id=example-id</a>, or start a video on the <a href=\"/video\">Video</a> page and use the &quot;Check video progress&quot; link.</p>";
            return $html;
        }

        $consumerKey = (string) ($_ENV['BITMESH_CONSUMER_KEY'] ?? getenv('BITMESH_CONSUMER_KEY') ?: '');
        $consumerSecret = (string) ($_ENV['BITMESH_CONSUMER_SECRET'] ?? getenv('BITMESH_CONSUMER_SECRET') ?: '');
        $apiBaseUrl = (string) ($_ENV['BITMESH_API_BASE_URL'] ?? getenv('BITMESH_API_BASE_URL') ?: 'https://aiproxyapi-production.up.railway.app');

        if ($consumerKey !== '' && $consumerSecret !== '') {
            try {
                $client = new BitmeshClient($consumerKey, $consumerSecret, $apiBaseUrl);
                $response = $client->videoStatus($id);
                $html .= "<h2>GET /video/" . htmlspecialchars($id) . "</h2><pre>" . htmlspecialchars(json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)) . "</pre>";
                $status = $response['status'] ?? null;
                if ($status !== null) {
                    $html .= "<p><strong>Status:</strong> " . htmlspecialchars((string) $status) . "</p>";
                }
                $videoUrl = $response['video_url'] ?? null;
                if ($videoUrl === null && isset($response['outputs']['video_url'])) {
                    $videoUrl = $response['outputs']['video_url'];
                }
                if ($videoUrl === null && isset($response['outputs'][0]['url'])) {
                    $videoUrl = $response['outputs'][0]['url'];
                }
                if ($videoUrl !== null && $videoUrl !== '') {
                    $html .= '<p><a href="' . htmlspecialchars($videoUrl) . '" target="_blank" rel="noopener noreferrer">Play video</a> (opens in new tab)</p>';
                }
                $html .= '<p><a href="/video">Back to Video</a></p>';
            } catch (\Throwable $e) {
                $html .= "<h2>API response</h2><p class=\"error\">Error: " . htmlspecialchars($e->getMessage()) . "</p>";
                $html .= '<p><a href="/video-status">Try another ID</a> · <a href="/video">Video</a></p>';
            }
        } else {
            $html .= "<h2>API response</h2><p>Set <code>BITMESH_CONSUMER_KEY</code> and <code>BITMESH_CONSUMER_SECRET</code> in your environment to check video status.</p>";
        }

        return $html;
    }
}
