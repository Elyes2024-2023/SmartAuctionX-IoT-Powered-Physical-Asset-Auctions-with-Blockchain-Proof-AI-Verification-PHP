<?php

namespace SmartAuctionX\Services;

use SmartAuctionX\Config\Config;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

class AIService
{
    private Config $config;
    private Client $httpClient;

    public function __construct()
    {
        $this->config = Config::getInstance();
        $this->httpClient = new Client([
            'base_uri' => $this->config->get('ai.service_url'),
            'headers' => [
                'Authorization' => 'Bearer ' . $this->config->get('ai.api_key'),
                'Content-Type' => 'application/json',
                'Accept' => 'application/json'
            ]
        ]);
    }

    public function verifyAuthenticity(int $productId, array $images): array
    {
        try {
            $response = $this->httpClient->post('/api/verify/authenticity', [
                'json' => [
                    'product_id' => $productId,
                    'images' => $images
                ]
            ]);

            return json_decode($response->getBody()->getContents(), true);
        } catch (GuzzleException $e) {
            throw new \RuntimeException('Failed to verify authenticity: ' . $e->getMessage());
        }
    }

    public function estimatePrice(int $productId, array $productData, array $images = []): array
    {
        try {
            $response = $this->httpClient->post('/api/estimate/price', [
                'json' => [
                    'product_id' => $productId,
                    'product_data' => $productData,
                    'images' => $images
                ]
            ]);

            return json_decode($response->getBody()->getContents(), true);
        } catch (GuzzleException $e) {
            throw new \RuntimeException('Failed to estimate price: ' . $e->getMessage());
        }
    }

    public function analyzeCondition(int $productId, array $sensorData, array $images = []): array
    {
        try {
            $response = $this->httpClient->post('/api/analyze/condition', [
                'json' => [
                    'product_id' => $productId,
                    'sensor_data' => $sensorData,
                    'images' => $images
                ]
            ]);

            return json_decode($response->getBody()->getContents(), true);
        } catch (GuzzleException $e) {
            throw new \RuntimeException('Failed to analyze condition: ' . $e->getMessage());
        }
    }

    public function detectFraud(int $productId, array $data): array
    {
        try {
            $response = $this->httpClient->post('/api/detect/fraud', [
                'json' => [
                    'product_id' => $productId,
                    'data' => $data
                ]
            ]);

            return json_decode($response->getBody()->getContents(), true);
        } catch (GuzzleException $e) {
            throw new \RuntimeException('Failed to detect fraud: ' . $e->getMessage());
        }
    }

    public function generateProductDescription(array $productData, array $images = []): string
    {
        try {
            $response = $this->httpClient->post('/api/generate/description', [
                'json' => [
                    'product_data' => $productData,
                    'images' => $images
                ]
            ]);

            $result = json_decode($response->getBody()->getContents(), true);
            return $result['description'];
        } catch (GuzzleException $e) {
            throw new \RuntimeException('Failed to generate description: ' . $e->getMessage());
        }
    }

    public function analyzeMarketTrends(string $category, array $historicalData): array
    {
        try {
            $response = $this->httpClient->post('/api/analyze/market', [
                'json' => [
                    'category' => $category,
                    'historical_data' => $historicalData
                ]
            ]);

            return json_decode($response->getBody()->getContents(), true);
        } catch (GuzzleException $e) {
            throw new \RuntimeException('Failed to analyze market trends: ' . $e->getMessage());
        }
    }

    public function validateSensorData(array $sensorData, string $productType): array
    {
        try {
            $response = $this->httpClient->post('/api/validate/sensor-data', [
                'json' => [
                    'sensor_data' => $sensorData,
                    'product_type' => $productType
                ]
            ]);

            return json_decode($response->getBody()->getContents(), true);
        } catch (GuzzleException $e) {
            throw new \RuntimeException('Failed to validate sensor data: ' . $e->getMessage());
        }
    }

    public function getModelConfidence(string $modelType): float
    {
        try {
            $response = $this->httpClient->get("/api/model/confidence/$modelType");
            $result = json_decode($response->getBody()->getContents(), true);
            return $result['confidence_score'];
        } catch (GuzzleException $e) {
            throw new \RuntimeException('Failed to get model confidence: ' . $e->getMessage());
        }
    }
} 