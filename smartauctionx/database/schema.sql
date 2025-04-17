-- Users table
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(255) NOT NULL UNIQUE,
    email VARCHAR(255) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    wallet_address VARCHAR(42),
    role ENUM('admin', 'seller', 'bidder') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Products table
CREATE TABLE products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    seller_id INT NOT NULL,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    category VARCHAR(100) NOT NULL,
    initial_price DECIMAL(20, 8) NOT NULL,
    current_price DECIMAL(20, 8) NOT NULL,
    status ENUM('pending', 'active', 'sold', 'cancelled') NOT NULL DEFAULT 'pending',
    auction_start_time TIMESTAMP,
    auction_end_time TIMESTAMP,
    nft_token_id VARCHAR(255),
    ipfs_hash VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (seller_id) REFERENCES users(id)
);

-- IoT Sensors table
CREATE TABLE iot_sensors (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    sensor_type VARCHAR(50) NOT NULL,
    sensor_id VARCHAR(255) NOT NULL,
    last_value DECIMAL(10, 2),
    last_updated TIMESTAMP,
    status ENUM('active', 'inactive', 'error') NOT NULL DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id)
);

-- Sensor Readings table
CREATE TABLE sensor_readings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sensor_id INT NOT NULL,
    value DECIMAL(10, 2) NOT NULL,
    timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (sensor_id) REFERENCES iot_sensors(id)
);

-- Bids table
CREATE TABLE bids (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    bidder_id INT NOT NULL,
    amount DECIMAL(20, 8) NOT NULL,
    transaction_hash VARCHAR(66),
    status ENUM('pending', 'confirmed', 'rejected') NOT NULL DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id),
    FOREIGN KEY (bidder_id) REFERENCES users(id)
);

-- AI Verifications table
CREATE TABLE ai_verifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    verification_type ENUM('authenticity', 'condition', 'price') NOT NULL,
    confidence_score DECIMAL(5, 2) NOT NULL,
    result TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id)
);

-- Blockchain Transactions table
CREATE TABLE blockchain_transactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    transaction_hash VARCHAR(66) NOT NULL UNIQUE,
    product_id INT,
    bid_id INT,
    transaction_type ENUM('bid', 'nft_mint', 'payment') NOT NULL,
    status ENUM('pending', 'confirmed', 'failed') NOT NULL DEFAULT 'pending',
    block_number INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id),
    FOREIGN KEY (bid_id) REFERENCES bids(id)
); 