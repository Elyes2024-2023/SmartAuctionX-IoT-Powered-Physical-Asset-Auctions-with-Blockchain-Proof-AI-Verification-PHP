<?php

namespace SmartAuctionX\Services;

use SmartAuctionX\Config\Config;
use SmartAuctionX\Models\Product;
use PhpMqtt\Client\MqttClient;
use PhpMqtt\Client\ConnectionSettings;

class IoTService
{
    private Config $config;
    private MqttClient $mqttClient;
    private Product $productModel;

    public function __construct()
    {
        $this->config = Config::getInstance();
        $this->productModel = new Product();
        $this->initializeMqttClient();
    }

    private function initializeMqttClient(): void
    {
        $connectionSettings = new ConnectionSettings();
        
        if ($this->config->get('iot.mqtt_username')) {
            $connectionSettings->setUsername($this->config->get('iot.mqtt_username'));
            $connectionSettings->setPassword($this->config->get('iot.mqtt_password'));
        }

        $this->mqttClient = new MqttClient(
            $this->config->get('iot.mqtt_broker_host'),
            $this->config->get('iot.mqtt_broker_port'),
            'SmartAuctionX-' . uniqid()
        );
    }

    public function connect(): void
    {
        $this->mqttClient->connect();
    }

    public function disconnect(): void
    {
        $this->mqttClient->disconnect();
    }

    public function subscribeToSensor(string $productId, string $sensorType): void
    {
        $topic = "products/{$productId}/sensors/{$sensorType}";
        $this->mqttClient->subscribe($topic, function ($topic, $message) use ($productId, $sensorType) {
            $this->handleSensorData($productId, $sensorType, $message);
        }, 0);
    }

    private function handleSensorData(string $productId, string $sensorType, string $message): void
    {
        $data = json_decode($message, true);
        if (!$data) {
            return;
        }

        // Update sensor reading in database
        $this->updateSensorReading($productId, $sensorType, $data);

        // Check if we need to update product price based on sensor data
        $this->checkAndUpdatePrice($productId, $sensorType, $data);
    }

    private function updateSensorReading(string $productId, string $sensorType, array $data): void
    {
        $sql = "INSERT INTO sensor_readings (sensor_id, value) 
                SELECT id, :value 
                FROM iot_sensors 
                WHERE product_id = :product_id AND sensor_type = :sensor_type";
        
        $this->productModel->db->query($sql, [
            'product_id' => $productId,
            'sensor_type' => $sensorType,
            'value' => $data['value']
        ]);
    }

    private function checkAndUpdatePrice(string $productId, string $sensorType, array $data): void
    {
        // Example: If temperature sensor reads above threshold, reduce price
        if ($sensorType === 'temperature' && $data['value'] > 30) {
            $product = $this->productModel->find($productId);
            if ($product && $product['status'] === 'active') {
                $newPrice = $product['current_price'] * 0.95; // Reduce by 5%
                $this->productModel->updatePrice($productId, $newPrice);
            }
        }
    }

    public function publishSensorData(string $productId, string $sensorType, array $data): void
    {
        $topic = "products/{$productId}/sensors/{$sensorType}";
        $this->mqttClient->publish($topic, json_encode($data), 0);
    }

    public function getSensorHistory(string $productId, string $sensorType, int $limit = 100): array
    {
        $sql = "SELECT sr.* 
                FROM sensor_readings sr
                JOIN iot_sensors i ON sr.sensor_id = i.id
                WHERE i.product_id = :product_id 
                AND i.sensor_type = :sensor_type
                ORDER BY sr.timestamp DESC
                LIMIT :limit";
        
        $stmt = $this->productModel->db->query($sql, [
            'product_id' => $productId,
            'sensor_type' => $sensorType,
            'limit' => $limit
        ]);
        
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function getActiveSensors(string $productId): array
    {
        $sql = "SELECT * FROM iot_sensors 
                WHERE product_id = :product_id 
                AND status = 'active'";
        
        $stmt = $this->productModel->db->query($sql, ['product_id' => $productId]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }
} 