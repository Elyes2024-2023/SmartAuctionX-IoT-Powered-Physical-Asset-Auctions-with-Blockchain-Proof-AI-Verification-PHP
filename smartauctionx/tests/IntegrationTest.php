<?php

namespace SmartAuctionX\Tests;

use PHPUnit\Framework\TestCase;
use SmartAuctionX\Models\User;
use SmartAuctionX\Models\Product;
use SmartAuctionX\Models\Bid;
use SmartAuctionX\Services\AIService;
use SmartAuctionX\Services\BlockchainService;
use SmartAuctionX\Services\IoTService;

class IntegrationTest extends TestCase
{
    private User $userModel;
    private Product $productModel;
    private Bid $bidModel;
    private AIService $aiService;
    private BlockchainService $blockchainService;
    private IoTService $iotService;

    protected function setUp(): void
    {
        $this->userModel = new User();
        $this->productModel = new Product();
        $this->bidModel = new Bid();
        $this->aiService = new AIService();
        $this->blockchainService = new BlockchainService();
        $this->iotService = new IoTService();
    }

    /**
     * Test Case 1: Complete User Registration and Authentication Flow
     */
    public function testUserRegistrationAndAuthentication(): void
    {
        // Create new user
        $userData = [
            'username' => 'testuser',
            'email' => 'test@example.com',
            'password' => 'securepassword123',
            'wallet_address' => '0x1234567890abcdef',
            'role' => 'bidder'
        ];

        $this->assertTrue($this->userModel->create($userData));

        // Test authentication
        $authResult = $this->userModel->authenticate('test@example.com', 'securepassword123');
        $this->assertNotNull($authResult);
        $this->assertArrayHasKey('token', $authResult);
        $this->assertArrayHasKey('user', $authResult);
    }

    /**
     * Test Case 2: Product Listing with AI Verification
     */
    public function testProductListingWithAIVerification(): void
    {
        // Create product
        $productData = [
            'seller_id' => 1,
            'name' => 'Test Product',
            'description' => 'Test Description',
            'category' => 'electronics',
            'initial_price' => 1000.00,
            'current_price' => 1000.00,
            'status' => 'pending'
        ];

        $productId = $this->productModel->create($productData);
        $this->assertNotNull($productId);

        // Verify authenticity using AI
        $verificationResult = $this->aiService->verifyAuthenticity($productId, ['image1.jpg']);
        $this->assertArrayHasKey('confidence_score', $verificationResult);
        $this->assertGreaterThan(0.7, $verificationResult['confidence_score']);
    }

    /**
     * Test Case 3: IoT Sensor Integration and Price Adjustment
     */
    public function testIoTSensorIntegration(): void
    {
        // Add sensor to product
        $sensorData = [
            'product_id' => 1,
            'sensor_type' => 'temperature',
            'sensor_id' => 'TEMP001',
            'status' => 'active'
        ];

        $this->iotService->connect();
        $this->iotService->subscribeToSensor('1', 'temperature');

        // Simulate sensor data
        $sensorReading = [
            'value' => 35.5,
            'timestamp' => time()
        ];

        $this->iotService->publishSensorData('1', 'temperature', $sensorReading);
        
        // Verify price adjustment
        $product = $this->productModel->find(1);
        $this->assertLessThan(1000.00, $product['current_price']);
    }

    /**
     * Test Case 4: Bidding Process with Blockchain Integration
     */
    public function testBiddingProcess(): void
    {
        // Place bid
        $bidData = [
            'product_id' => 1,
            'bidder_id' => 1,
            'amount' => 1100.00,
            'transaction_hash' => '0x123...'
        ];

        $this->assertTrue($this->bidModel->create($bidData));

        // Verify blockchain transaction
        $txHash = $this->blockchainService->placeBid(1, 1100.00);
        $this->assertNotNull($txHash);
        $this->assertTrue($this->blockchainService->waitForConfirmation($txHash));
    }

    /**
     * Test Case 5: AI Price Estimation and Market Analysis
     */
    public function testAIPriceEstimation(): void
    {
        $productData = [
            'name' => 'Luxury Watch',
            'category' => 'watches',
            'condition' => 'excellent',
            'year' => 2020
        ];

        $estimationResult = $this->aiService->estimatePrice(1, $productData);
        $this->assertArrayHasKey('estimated_price', $estimationResult);
        $this->assertArrayHasKey('confidence_score', $estimationResult);

        $marketAnalysis = $this->aiService->analyzeMarketTrends('watches', []);
        $this->assertArrayHasKey('trend', $marketAnalysis);
        $this->assertArrayHasKey('recommendation', $marketAnalysis);
    }

    /**
     * Test Case 6: NFT Minting and Ownership Verification
     */
    public function testNFTMinting(): void
    {
        $tokenURI = 'ipfs://QmTest123';
        $txHash = $this->blockchainService->mintNFT(1, $tokenURI);
        
        $this->assertNotNull($txHash);
        $this->assertTrue($this->blockchainService->waitForConfirmation($txHash));
        
        $isOwner = $this->blockchainService->verifyOwnership(1, '0x1234567890abcdef');
        $this->assertTrue($isOwner);
    }

    /**
     * Test Case 7: Sensor Data Validation and Fraud Detection
     */
    public function testSensorDataValidation(): void
    {
        $sensorData = [
            'temperature' => 25.5,
            'humidity' => 60,
            'location' => ['lat' => 40.7128, 'lng' => -74.0060]
        ];

        $validationResult = $this->aiService->validateSensorData($sensorData, 'electronics');
        $this->assertArrayHasKey('is_valid', $validationResult);
        $this->assertTrue($validationResult['is_valid']);

        $fraudDetection = $this->aiService->detectFraud(1, $sensorData);
        $this->assertArrayHasKey('fraud_probability', $fraudDetection);
        $this->assertLessThan(0.1, $fraudDetection['fraud_probability']);
    }

    /**
     * Test Case 8: Auction End Process and Payment Settlement
     */
    public function testAuctionEndProcess(): void
    {
        // End auction
        $auctionDetails = $this->blockchainService->getAuctionDetails(1);
        $this->assertArrayHasKey('status', $auctionDetails);
        $this->assertEquals('completed', $auctionDetails['status']);

        // Verify payment settlement
        $bid = $this->bidModel->findById(1);
        $this->assertEquals('confirmed', $bid['status']);
    }

    /**
     * Test Case 9: Real-time Price Updates Based on Market Conditions
     */
    public function testRealTimePriceUpdates(): void
    {
        $initialPrice = 1000.00;
        $product = $this->productModel->find(1);
        $this->assertEquals($initialPrice, $product['initial_price']);

        // Simulate market condition changes
        $marketData = [
            'demand' => 'high',
            'competition' => 'low',
            'seasonal_factor' => 1.2
        ];

        $priceAdjustment = $this->aiService->analyzeMarketTrends('electronics', $marketData);
        $this->assertArrayHasKey('price_adjustment', $priceAdjustment);
    }

    /**
     * Test Case 10: System Health and Performance Monitoring
     */
    public function testSystemHealthMonitoring(): void
    {
        // Check AI service health
        $aiConfidence = $this->aiService->getModelConfidence('price_estimation');
        $this->assertGreaterThan(0.8, $aiConfidence);

        // Check blockchain connection
        $gasPrice = $this->blockchainService->getGasPrice();
        $this->assertNotNull($gasPrice);

        // Check IoT service
        $this->iotService->connect();
        $this->assertTrue($this->iotService->mqttClient->isConnected());
        $this->iotService->disconnect();
    }
} 