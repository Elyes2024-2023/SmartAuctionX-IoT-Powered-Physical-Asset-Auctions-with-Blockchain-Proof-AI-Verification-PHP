<?php

namespace SmartAuctionX\Config;

use Dotenv\Dotenv;

class Config
{
    private static ?Config $instance = null;
    private array $config = [];

    private function __construct()
    {
        $dotenv = Dotenv::createImmutable(__DIR__ . '/../../');
        $dotenv->load();

        $this->loadConfig();
    }

    public static function getInstance(): Config
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function loadConfig(): void
    {
        $this->config = [
            'app' => [
                'name' => $_ENV['APP_NAME'] ?? 'SmartAuctionX',
                'env' => $_ENV['APP_ENV'] ?? 'development',
                'debug' => $_ENV['APP_DEBUG'] ?? true,
                'url' => $_ENV['APP_URL'] ?? 'http://localhost:8000',
            ],
            'database' => [
                'connection' => $_ENV['DB_CONNECTION'] ?? 'mysql',
                'host' => $_ENV['DB_HOST'] ?? '127.0.0.1',
                'port' => $_ENV['DB_PORT'] ?? '3306',
                'database' => $_ENV['DB_DATABASE'] ?? 'smartauctionx',
                'username' => $_ENV['DB_USERNAME'] ?? 'root',
                'password' => $_ENV['DB_PASSWORD'] ?? '',
            ],
            'redis' => [
                'host' => $_ENV['REDIS_HOST'] ?? '127.0.0.1',
                'password' => $_ENV['REDIS_PASSWORD'] ?? null,
                'port' => $_ENV['REDIS_PORT'] ?? 6379,
            ],
            'blockchain' => [
                'ethereum_node_url' => $_ENV['ETHEREUM_NODE_URL'] ?? '',
                'polygon_node_url' => $_ENV['POLYGON_NODE_URL'] ?? '',
                'contract_address' => $_ENV['CONTRACT_ADDRESS'] ?? '',
            ],
            'iot' => [
                'mqtt_broker_host' => $_ENV['MQTT_BROKER_HOST'] ?? 'localhost',
                'mqtt_broker_port' => $_ENV['MQTT_BROKER_PORT'] ?? 1883,
                'mqtt_username' => $_ENV['MQTT_USERNAME'] ?? '',
                'mqtt_password' => $_ENV['MQTT_PASSWORD'] ?? '',
            ],
            'ai' => [
                'service_url' => $_ENV['AI_SERVICE_URL'] ?? 'http://localhost:5000',
                'api_key' => $_ENV['AI_SERVICE_API_KEY'] ?? '',
            ],
            'jwt' => [
                'secret' => $_ENV['JWT_SECRET'] ?? 'your-secret-key',
                'expiration' => $_ENV['JWT_EXPIRATION'] ?? 3600,
            ],
            'storage' => [
                'ipfs_gateway' => $_ENV['IPFS_GATEWAY'] ?? 'https://ipfs.io',
                's3_bucket' => $_ENV['S3_BUCKET'] ?? '',
                's3_region' => $_ENV['S3_REGION'] ?? '',
                's3_access_key' => $_ENV['S3_ACCESS_KEY'] ?? '',
                's3_secret_key' => $_ENV['S3_SECRET_KEY'] ?? '',
            ],
        ];
    }

    public function get(string $key, $default = null)
    {
        $keys = explode('.', $key);
        $value = $this->config;

        foreach ($keys as $k) {
            if (!isset($value[$k])) {
                return $default;
            }
            $value = $value[$k];
        }

        return $value;
    }

    private function __clone() {}
    private function __wakeup() {}
} 