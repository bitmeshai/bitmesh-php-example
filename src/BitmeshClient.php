<?php

declare(strict_types=1);

namespace BitmeshExample;

/**
 * Stub for Bitmesh.ai API SDK.
 * Replace with the official SDK when available (e.g. composer require bitmesh/sdk).
 */
class BitmeshClient
{
    public function __construct(private string $apiKey = '')
    {
    }

    public function chat(array $params): array
    {
        // TODO: call real Bitmesh chat API
        return ['usage' => [], 'choices' => []];
    }

    public function image(array $params): array
    {
        // TODO: call real Bitmesh image API
        return ['data' => []];
    }

    public function video(array $params): array
    {
        // TODO: call real Bitmesh video API
        return ['data' => []];
    }
}
