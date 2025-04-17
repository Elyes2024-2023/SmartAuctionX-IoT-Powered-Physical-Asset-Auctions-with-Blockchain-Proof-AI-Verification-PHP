# SmartAuctionX 🚀

IoT-Powered Physical Asset Auctions with Blockchain Proof + AI Verification

## Overview 🌟

SmartAuctionX is a revolutionary auction platform that combines IoT sensors, blockchain technology, and AI to create a transparent, secure, and intelligent marketplace for physical assets. Whether you're auctioning luxury cars, collectibles, art, or industrial equipment, SmartAuctionX ensures authenticity and real-time condition monitoring.

### Key Features 💎

1. **IoT Integration** 📡
   - Real-time condition monitoring
   - Automatic price adjustments based on sensor data
   - Support for multiple sensor types (temperature, humidity, GPS, etc.)

2. **Blockchain Security** 🔒
   - Smart contracts for secure transactions
   - NFT proof of ownership
   - Transparent auction history

3. **AI-Powered Verification** 🤖
   - Authenticity verification
   - Price estimation
   - Fraud detection
   - Market trend analysis

## Tech Stack 🛠️

- **Backend Core**: PHP 8.1+
- **Database**: MySQL + Redis
- **Blockchain**: Polygon Network (Ethereum compatible)
- **IoT Communication**: MQTT Protocol
- **AI Services**: Python Flask API
- **Frontend**: Vue.js/React (separate repository)

## Prerequisites 📋

- PHP 8.1 or higher
- Composer
- MySQL 8.0+
- Redis Server
- Node.js 16+ (for blockchain interaction)
- MQTT Broker (e.g., Mosquitto)
- Python 3.8+ (for AI services)

## Installation 🔧

1. **Clone the repository**
   ```bash
   git clone https://github.com/Elyes2024-2023/smartauctionx.git
   cd smartauctionx
   ```

2. **Install PHP dependencies**
   ```bash
   composer install
   ```

3. **Set up environment variables**
   ```bash
   cp .env.example .env
   # Edit .env with your configuration
   ```

4. **Create the database**
   ```bash
   mysql -u root -p
   CREATE DATABASE smartauctionx;
   exit;
   ```

5. **Run database migrations**
   ```bash
   php scripts/migrate.php
   ```

6. **Start the services**
   ```bash
   # Start MQTT broker
   service mosquitto start

   # Start Redis
   service redis-server start

   # Start the application
   php -S localhost:8000 -t public/
   ```

## Project Structure 📁

```
smartauctionx/
├── src/
│   ├── Config/
│   ├── Models/
│   ├── Services/
│   └── Controllers/
├── public/
├── contracts/
├── database/
├── tests/
└── config/
```

## Smart Contracts 📜

The project includes two main smart contracts:

1. **AuctionManager.sol**
   - Handles auction lifecycle
   - Manages bidding process
   - Escrow functionality

2. **AuctionNFT.sol**
   - ERC-721 compliant
   - Mints ownership certificates
   - Tracks product history

## IoT Integration 🌐

### Supported Sensors
- Temperature & Humidity (DHT22)
- GPS Location (NEO-6M)
- Accelerometer (MPU-6050)
- Custom sensors via MQTT protocol

### Data Flow
1. Sensors → MQTT Broker
2. MQTT Broker → PHP Backend
3. Backend → Database + Blockchain
4. Real-time Updates → Frontend

## AI Services 🧠

### Available Endpoints
- `/api/verify/authenticity`
- `/api/estimate/price`
- `/api/analyze/condition`
- `/api/detect/fraud`
- `/api/analyze/market`

## Testing 🧪

```bash
# Run unit tests
./vendor/bin/phpunit

# Run integration tests
./vendor/bin/phpunit --testsuite integration
```

## API Documentation 📚

API documentation is available at `/docs/api` after starting the server.

## Contributing 🤝

1. Fork the repository
2. Create your feature branch (`git checkout -b feature/AmazingFeature`)
3. Commit your changes (`git commit -m 'Add some AmazingFeature'`)
4. Push to the branch (`git push origin feature/AmazingFeature`)
5. Open a Pull Request

## License 📄

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## Acknowledgments 👏

- OpenZeppelin for smart contract templates
- MQTT.js for IoT communication
- Web3.php for blockchain integration

## Contact 📧

Elyes - [@Elyes2024-2023](https://github.com/Elyes2024-2023)

Project Link: [https://github.com/Elyes2024-2023/smartauctionx](https://github.com/Elyes2024-2023/smartauctionx)

---

© 2024-2025 ELYES. All rights reserved. 