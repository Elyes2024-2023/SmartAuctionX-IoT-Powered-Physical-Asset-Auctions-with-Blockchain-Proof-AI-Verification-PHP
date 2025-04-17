<?php
/**
 * SmartAuctionX IoT Simulator
 * 
 * This script simulates IoT devices and publishes sensor data to an MQTT broker.
 * 
 * @package SmartAuctionX
 * @author ELYES
 * @copyright 2024-2025 ELYES. All rights reserved.
 * @license MIT
 */

require_once __DIR__ . '/../vendor/autoload.php';

use PhpMqtt\Client\MqttClient;
use PhpMqtt\Client\ConnectionSettings;

class IoTSimulator
{
    private MqttClient $client;
    private array $products = [];
    private array $sensorTypes = ['temperature', 'humidity', 'gps', 'accelerometer'];
    private bool $running = true;

    public function __construct(string $host = 'localhost', int $port = 1883)
    {
        $this->client = new MqttClient($host, $port);
        $connectionSettings = new ConnectionSettings();
        $this->client->connect($connectionSettings, true);
        
        // Register signal handler for graceful shutdown
        pcntl_signal(SIGINT, [$this, 'shutdown']);
        pcntl_signal(SIGTERM, [$this, 'shutdown']);
    }

    public function addProduct(int $productId, array $sensors = null): void
    {
        $this->products[$productId] = [
            'sensors' => $sensors ?? $this->sensorTypes,
            'last_values' => []
        ];
    }

    public function start(): void
    {
        echo "Starting IoT simulator...\n";
        echo "Press Ctrl+C to stop\n\n";

        while ($this->running) {
            foreach ($this->products as $productId => $product) {
                foreach ($product['sensors'] as $sensorType) {
                    $value = $this->generateSensorData($sensorType, $product['last_values'][$sensorType] ?? null);
                    $this->products[$productId]['last_values'][$sensorType] = $value;
                    
                    $data = [
                        'product_id' => $productId,
                        'sensor_type' => $sensorType,
                        'value' => $value,
                        'timestamp' => time()
                    ];

                    $topic = "products/{$productId}/sensors/{$sensorType}";
                    $this->client->publish($topic, json_encode($data), 0);
                    
                    echo sprintf(
                        "Published to %s: %s\n",
                        $topic,
                        json_encode($data, JSON_PRETTY_PRINT)
                    );
                }
            }

            // Process signals
            pcntl_signal_dispatch();
            sleep(5); // Wait 5 seconds between updates
        }
    }

    private function generateSensorData(string $sensorType, $lastValue = null): mixed
    {
        switch ($sensorType) {
            case 'temperature':
                // Generate temperature between 15-35°C with small variations
                return $lastValue 
                    ? min(35, max(15, $lastValue + (rand(-10, 10) / 10)))
                    : rand(150, 350) / 10;

            case 'humidity':
                // Generate humidity between 30-70% with small variations
                return $lastValue
                    ? min(70, max(30, $lastValue + (rand(-5, 5))))
                    : rand(30, 70);

            case 'gps':
                // Generate GPS coordinates near a base location
                $baseLat = 40.7128; // New York
                $baseLon = -74.0060;
                
                return [
                    'latitude' => $baseLat + (rand(-1000, 1000) / 10000),
                    'longitude' => $baseLon + (rand(-1000, 1000) / 10000)
                ];

            case 'accelerometer':
                // Generate accelerometer data (x, y, z)
                return [
                    'x' => rand(-100, 100) / 100,
                    'y' => rand(-100, 100) / 100,
                    'z' => rand(-100, 100) / 100
                ];

            default:
                return rand(0, 100);
        }
    }

    public function shutdown(): void
    {
        echo "\nShutting down IoT simulator...\n";
        $this->running = false;
        $this->client->disconnect();
    }
}

// Example usage
if (php_sapi_name() === 'cli') {
    $simulator = new IoTSimulator();

    // Add some test products
    $simulator->addProduct(1, ['temperature', 'humidity']); // Product 1 with temp & humidity
    $simulator->addProduct(2, ['gps', 'accelerometer']); // Product 2 with GPS & accelerometer
    $simulator->addProduct(3); // Product 3 with all sensors

    // Start simulation
    $simulator->start();
} 