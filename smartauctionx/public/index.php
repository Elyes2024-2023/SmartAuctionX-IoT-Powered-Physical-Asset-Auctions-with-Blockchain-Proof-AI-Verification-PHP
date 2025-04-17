<?php
/**
 * SmartAuctionX API Entry Point
 * 
 * @package SmartAuctionX
 * @author ELYES
 * @copyright 2024-2025 ELYES. All rights reserved.
 * @license MIT
 */

require_once __DIR__ . '/../vendor/autoload.php';

use SmartAuctionX\Config\Config;
use SmartAuctionX\Models\Product;
use SmartAuctionX\Services\IoTService;
use SmartAuctionX\Services\BlockchainService;
use SmartAuctionX\Services\AIService;

// Load configuration
$config = Config::getInstance();

// Set headers
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Parse request
$method = $_SERVER['REQUEST_METHOD'];
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$uri = explode('/', trim($uri, '/'));
$endpoint = $uri[0] ?? '';

// Initialize services
$product = new Product();
$iotService = new IoTService();
$blockchainService = new BlockchainService();
$aiService = new AIService();

try {
    switch ($endpoint) {
        case 'products':
            handleProducts($method, $uri, $product, $iotService, $aiService);
            break;

        case 'auctions':
            handleAuctions($method, $uri, $product, $blockchainService);
            break;

        case 'bids':
            handleBids($method, $uri, $blockchainService);
            break;

        case 'sensors':
            handleSensors($method, $uri, $iotService);
            break;

        case 'verify':
            handleVerification($method, $uri, $aiService);
            break;

        default:
            throw new Exception('Endpoint not found', 404);
    }
} catch (Exception $e) {
    sendResponse([
        'error' => true,
        'message' => $e->getMessage()
    ], $e->getCode() ?: 500);
}

function handleProducts($method, $uri, $product, $iotService, $aiService) {
    $id = $uri[1] ?? null;

    switch ($method) {
        case 'GET':
            if ($id) {
                $data = $product->getProductWithDetails($id);
                if (!$data) {
                    throw new Exception('Product not found', 404);
                }
            } else {
                $data = $product->findAll();
            }
            break;

        case 'POST':
            $input = json_decode(file_get_contents('php://input'), true);
            if (!$input) {
                throw new Exception('Invalid input', 400);
            }

            // Verify product authenticity if images provided
            if (!empty($input['images'])) {
                $verification = $aiService->verifyAuthenticity(0, $input['images']);
                if (!$verification['is_authentic']) {
                    throw new Exception('Product authenticity verification failed', 400);
                }
            }

            $data = $product->create($input);
            break;

        case 'PUT':
            if (!$id) {
                throw new Exception('Product ID required', 400);
            }

            $input = json_decode(file_get_contents('php://input'), true);
            if (!$input) {
                throw new Exception('Invalid input', 400);
            }

            $success = $product->update($id, $input);
            if (!$success) {
                throw new Exception('Failed to update product', 400);
            }

            $data = $product->find($id);
            break;

        case 'DELETE':
            if (!$id) {
                throw new Exception('Product ID required', 400);
            }

            $success = $product->delete($id);
            if (!$success) {
                throw new Exception('Failed to delete product', 400);
            }

            $data = ['success' => true];
            break;

        default:
            throw new Exception('Method not allowed', 405);
    }

    sendResponse($data);
}

function handleAuctions($method, $uri, $product, $blockchainService) {
    $id = $uri[1] ?? null;

    switch ($method) {
        case 'GET':
            if ($id) {
                $data = $blockchainService->getAuctionDetails($id);
            } else {
                $data = $product->getActiveAuctions();
            }
            break;

        case 'POST':
            $input = json_decode(file_get_contents('php://input'), true);
            if (!$input) {
                throw new Exception('Invalid input', 400);
            }

            $txHash = $blockchainService->createAuction($input);
            $data = [
                'transaction_hash' => $txHash,
                'message' => 'Auction creation transaction submitted'
            ];
            break;

        default:
            throw new Exception('Method not allowed', 405);
    }

    sendResponse($data);
}

function handleBids($method, $uri, $blockchainService) {
    switch ($method) {
        case 'POST':
            $input = json_decode(file_get_contents('php://input'), true);
            if (!$input || !isset($input['auction_id']) || !isset($input['amount'])) {
                throw new Exception('Invalid input', 400);
            }

            $txHash = $blockchainService->placeBid($input['auction_id'], $input['amount']);
            $data = [
                'transaction_hash' => $txHash,
                'message' => 'Bid transaction submitted'
            ];
            break;

        default:
            throw new Exception('Method not allowed', 405);
    }

    sendResponse($data);
}

function handleSensors($method, $uri, $iotService) {
    $productId = $uri[1] ?? null;
    $sensorType = $uri[2] ?? null;

    switch ($method) {
        case 'GET':
            if (!$productId) {
                throw new Exception('Product ID required', 400);
            }

            if ($sensorType) {
                $data = $iotService->getSensorHistory($productId, $sensorType);
            } else {
                $data = $iotService->getActiveSensors($productId);
            }
            break;

        case 'POST':
            if (!$productId || !$sensorType) {
                throw new Exception('Product ID and sensor type required', 400);
            }

            $input = json_decode(file_get_contents('php://input'), true);
            if (!$input) {
                throw new Exception('Invalid input', 400);
            }

            $iotService->publishSensorData($productId, $sensorType, $input);
            $data = ['success' => true];
            break;

        default:
            throw new Exception('Method not allowed', 405);
    }

    sendResponse($data);
}

function handleVerification($method, $uri, $aiService) {
    switch ($method) {
        case 'POST':
            $input = json_decode(file_get_contents('php://input'), true);
            if (!$input || !isset($input['product_id']) || !isset($input['images'])) {
                throw new Exception('Invalid input', 400);
            }

            $data = $aiService->verifyAuthenticity($input['product_id'], $input['images']);
            break;

        default:
            throw new Exception('Method not allowed', 405);
    }

    sendResponse($data);
}

function sendResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    echo json_encode($data, JSON_PRETTY_PRINT);
    exit();
} 