// SPDX-License-Identifier: MIT
/**
 * @title AuctionManager
 * @dev Manages auctions for physical assets with IoT integration
 * @author ELYES
 * @copyright 2024-2025 ELYES. All rights reserved.
 */
pragma solidity ^0.8.0;

import "@openzeppelin/contracts/access/Ownable.sol";
import "@openzeppelin/contracts/security/ReentrancyGuard.sol";
import "@openzeppelin/contracts/token/ERC721/IERC721.sol";

/**
 * @title AuctionManager
 * @dev Manages auctions for physical assets with IoT integration
 */
contract AuctionManager is Ownable, ReentrancyGuard {
    // Structs
    struct Auction {
        uint256 productId;
        address seller;
        uint256 startPrice;
        uint256 currentPrice;
        uint256 startTime;
        uint256 endTime;
        address highestBidder;
        uint256 highestBid;
        bool ended;
        bool claimed;
        bytes32 conditionHash; // Hash of IoT sensor data
    }

    // State variables
    mapping(uint256 => Auction) public auctions;
    mapping(uint256 => mapping(address => uint256)) public bids;
    IERC721 public nftContract;
    uint256 public auctionCount;
    uint256 public platformFee = 250; // 2.5% fee (basis points)

    // Events
    event AuctionCreated(uint256 indexed auctionId, uint256 productId, address seller, uint256 startPrice);
    event BidPlaced(uint256 indexed auctionId, address bidder, uint256 amount);
    event AuctionEnded(uint256 indexed auctionId, address winner, uint256 amount);
    event ConditionUpdated(uint256 indexed auctionId, bytes32 conditionHash);
    event PriceAdjusted(uint256 indexed auctionId, uint256 newPrice);
    event AuctionClaimed(uint256 indexed auctionId, address winner);

    // Constructor
    constructor(address _nftContract) {
        nftContract = IERC721(_nftContract);
    }

    // Modifiers
    modifier auctionExists(uint256 auctionId) {
        require(auctions[auctionId].seller != address(0), "Auction does not exist");
        _;
    }

    modifier auctionActive(uint256 auctionId) {
        require(
            block.timestamp >= auctions[auctionId].startTime &&
            block.timestamp <= auctions[auctionId].endTime &&
            !auctions[auctionId].ended,
            "Auction not active"
        );
        _;
    }

    // Main functions
    function createAuction(
        uint256 productId,
        uint256 startPrice,
        uint256 startTime,
        uint256 duration
    ) external returns (uint256) {
        require(startPrice > 0, "Start price must be greater than 0");
        require(startTime >= block.timestamp, "Start time must be in the future");
        require(duration > 0, "Duration must be greater than 0");

        uint256 auctionId = auctionCount++;
        
        auctions[auctionId] = Auction({
            productId: productId,
            seller: msg.sender,
            startPrice: startPrice,
            currentPrice: startPrice,
            startTime: startTime,
            endTime: startTime + duration,
            highestBidder: address(0),
            highestBid: 0,
            ended: false,
            claimed: false,
            conditionHash: bytes32(0)
        });

        emit AuctionCreated(auctionId, productId, msg.sender, startPrice);
        return auctionId;
    }

    function placeBid(uint256 auctionId) external payable 
        auctionExists(auctionId) 
        auctionActive(auctionId)
        nonReentrant 
    {
        Auction storage auction = auctions[auctionId];
        require(msg.value >= auction.currentPrice, "Bid too low");
        require(msg.sender != auction.seller, "Seller cannot bid");

        // Refund previous highest bidder
        if (auction.highestBidder != address(0)) {
            bids[auctionId][auction.highestBidder] += auction.highestBid;
        }

        auction.highestBidder = msg.sender;
        auction.highestBid = msg.value;
        bids[auctionId][msg.sender] = msg.value;

        emit BidPlaced(auctionId, msg.sender, msg.value);
    }

    function endAuction(uint256 auctionId) external 
        auctionExists(auctionId)
        nonReentrant 
    {
        Auction storage auction = auctions[auctionId];
        require(
            block.timestamp > auction.endTime || msg.sender == owner(),
            "Auction still active"
        );
        require(!auction.ended, "Auction already ended");

        auction.ended = true;

        if (auction.highestBidder != address(0)) {
            uint256 fee = (auction.highestBid * platformFee) / 10000;
            uint256 sellerAmount = auction.highestBid - fee;
            
            // Transfer funds to seller
            payable(auction.seller).transfer(sellerAmount);
            // Transfer fee to platform
            payable(owner()).transfer(fee);
        }

        emit AuctionEnded(auctionId, auction.highestBidder, auction.highestBid);
    }

    function updateCondition(uint256 auctionId, bytes32 conditionHash) external 
        onlyOwner 
        auctionExists(auctionId) 
        auctionActive(auctionId) 
    {
        auctions[auctionId].conditionHash = conditionHash;
        emit ConditionUpdated(auctionId, conditionHash);
    }

    function adjustPrice(uint256 auctionId, uint256 newPrice) external 
        onlyOwner 
        auctionExists(auctionId) 
        auctionActive(auctionId) 
    {
        require(newPrice > 0, "Price must be greater than 0");
        require(
            newPrice <= auctions[auctionId].currentPrice,
            "Can only adjust price downward"
        );

        auctions[auctionId].currentPrice = newPrice;
        emit PriceAdjusted(auctionId, newPrice);
    }

    function claimAuction(uint256 auctionId) external 
        auctionExists(auctionId)
        nonReentrant 
    {
        Auction storage auction = auctions[auctionId];
        require(auction.ended, "Auction not ended");
        require(!auction.claimed, "Auction already claimed");
        require(
            msg.sender == auction.highestBidder,
            "Only winner can claim"
        );

        auction.claimed = true;
        // Transfer NFT to winner
        nftContract.transferFrom(auction.seller, msg.sender, auction.productId);

        emit AuctionClaimed(auctionId, msg.sender);
    }

    function withdrawBid(uint256 auctionId) external nonReentrant {
        uint256 amount = bids[auctionId][msg.sender];
        require(amount > 0, "No bids to withdraw");
        
        // Ensure bidder is not the highest bidder in an active auction
        Auction storage auction = auctions[auctionId];
        require(
            auction.ended || 
            msg.sender != auction.highestBidder,
            "Highest bidder cannot withdraw"
        );

        bids[auctionId][msg.sender] = 0;
        payable(msg.sender).transfer(amount);
    }

    // View functions
    function getAuction(uint256 auctionId) external view returns (
        uint256 productId,
        address seller,
        uint256 currentPrice,
        uint256 startTime,
        uint256 endTime,
        address highestBidder,
        uint256 highestBid,
        bool ended,
        bool claimed,
        bytes32 conditionHash
    ) {
        Auction storage auction = auctions[auctionId];
        return (
            auction.productId,
            auction.seller,
            auction.currentPrice,
            auction.startTime,
            auction.endTime,
            auction.highestBidder,
            auction.highestBid,
            auction.ended,
            auction.claimed,
            auction.conditionHash
        );
    }

    function setPlatformFee(uint256 newFee) external onlyOwner {
        require(newFee <= 1000, "Fee cannot exceed 10%");
        platformFee = newFee;
    }

    // Emergency functions
    function emergencyEnd(uint256 auctionId) external onlyOwner {
        Auction storage auction = auctions[auctionId];
        require(!auction.ended, "Auction already ended");
        
        auction.ended = true;
        emit AuctionEnded(auctionId, auction.highestBidder, auction.highestBid);
    }

    receive() external payable {}
    fallback() external payable {}
} 