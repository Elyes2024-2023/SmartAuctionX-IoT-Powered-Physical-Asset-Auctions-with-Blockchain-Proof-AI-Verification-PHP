<?php
// Database connection configuration
$servername = "localhost";
$username = "your_username";
$password = "your_password";
$dbname = "your_database_name";

try {
    // Create a new PDO instance
    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);

    // Set PDO error mode to exception
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Function to start an auction
    function startAuction($sellerId, $name, $description, $startingPrice, $startingDatetime, $priceInterval, $priceReductionInterval, $minimumBid) {
        global $conn;

        // Insert the auction into the products table
        $query = "INSERT INTO products (seller_id, name, description, starting_price, current_price, starting_datetime, price_interval, price_reduction_interval, minimum_bid) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($query);
        $currentPrice = $startingPrice;
        $stmt->execute([$sellerId, $name, $description, $startingPrice, $currentPrice, $startingDatetime, $priceInterval, $priceReductionInterval, $minimumBid]);
        $auctionId = $conn->lastInsertId();

        return $auctionId;
    }

    // Function to place a bid
    function placeBid($auctionId, $bidderId, $bidAmount) {
        global $conn;

        // Update the current price of the auction
        $query = "UPDATE products SET current_price = ? WHERE id = ?";
        $stmt = $conn->prepare($query);
        $stmt->execute([$bidAmount, $auctionId]);

        // Insert the bid into the bids table
        $query = "INSERT INTO bids (product_id, bidder_id, bid_amount, bid_datetime) VALUES (?, ?, ?, NOW())";
        $stmt = $conn->prepare($query);
        $stmt->execute([$auctionId, $bidderId, $bidAmount]);
    }

    // Example usage: Start an auction
    $auctionId = startAuction(1, "Antique Watch", "Description of the antique watch", 1000000.00, "2023-05-18 12:00:00", 10000.00, 1, 900000.00);
    echo "Auction started with ID: " . $auctionId . "<br>";