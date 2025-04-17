// SPDX-License-Identifier: MIT
/**
 * @title AuctionNFT
 * @dev NFT contract for representing ownership of physical assets
 * @author ELYES
 * @copyright 2024-2025 ELYES. All rights reserved.
 */
pragma solidity ^0.8.0;

import "@openzeppelin/contracts/token/ERC721/ERC721.sol";
import "@openzeppelin/contracts/token/ERC721/extensions/ERC721URIStorage.sol";
import "@openzeppelin/contracts/token/ERC721/extensions/ERC721Enumerable.sol";
import "@openzeppelin/contracts/access/Ownable.sol";
import "@openzeppelin/contracts/utils/Counters.sol";

/**
 * @title AuctionNFT
 * @dev NFT contract for representing ownership of physical assets
 */
contract AuctionNFT is ERC721, ERC721URIStorage, ERC721Enumerable, Ownable {
    using Counters for Counters.Counter;

    // State variables
    Counters.Counter private _tokenIds;
    address public auctionManager;
    
    // Mapping from productId to tokenId
    mapping(uint256 => uint256) private _productTokens;
    
    // Mapping from tokenId to product details
    mapping(uint256 => ProductDetails) private _tokenDetails;

    // Struct to store product details
    struct ProductDetails {
        uint256 productId;
        string category;
        uint256 mintTime;
        bytes32 conditionHash;
        string[] sensorTypes;
    }

    // Events
    event ProductMinted(uint256 indexed tokenId, uint256 indexed productId, address owner);
    event ConditionUpdated(uint256 indexed tokenId, bytes32 conditionHash);
    event SensorsAdded(uint256 indexed tokenId, string[] sensorTypes);

    // Constructor
    constructor() ERC721("SmartAuctionX NFT", "SANFT") {}

    // Modifiers
    modifier onlyAuctionManager() {
        require(msg.sender == auctionManager, "Caller is not the auction manager");
        _;
    }

    // Setup function
    function setAuctionManager(address _auctionManager) external onlyOwner {
        require(_auctionManager != address(0), "Invalid auction manager address");
        auctionManager = _auctionManager;
    }

    // Main functions
    function mint(
        address to,
        uint256 productId,
        string memory tokenURI,
        string memory category,
        string[] memory sensorTypes
    ) external onlyAuctionManager returns (uint256) {
        require(_productTokens[productId] == 0, "Product already has a token");

        _tokenIds.increment();
        uint256 newTokenId = _tokenIds.current();
        
        _safeMint(to, newTokenId);
        _setTokenURI(newTokenId, tokenURI);
        
        _productTokens[productId] = newTokenId;
        _tokenDetails[newTokenId] = ProductDetails({
            productId: productId,
            category: category,
            mintTime: block.timestamp,
            conditionHash: bytes32(0),
            sensorTypes: sensorTypes
        });

        emit ProductMinted(newTokenId, productId, to);
        return newTokenId;
    }

    function updateCondition(uint256 tokenId, bytes32 conditionHash) external onlyAuctionManager {
        require(_exists(tokenId), "Token does not exist");
        _tokenDetails[tokenId].conditionHash = conditionHash;
        emit ConditionUpdated(tokenId, conditionHash);
    }

    function addSensors(uint256 tokenId, string[] memory newSensorTypes) external onlyAuctionManager {
        require(_exists(tokenId), "Token does not exist");
        
        ProductDetails storage details = _tokenDetails[tokenId];
        uint256 currentLength = details.sensorTypes.length;
        
        for (uint256 i = 0; i < newSensorTypes.length; i++) {
            details.sensorTypes.push(newSensorTypes[i]);
        }
        
        emit SensorsAdded(tokenId, newSensorTypes);
    }

    // View functions
    function getTokenIdByProduct(uint256 productId) external view returns (uint256) {
        require(_productTokens[productId] != 0, "Product has no token");
        return _productTokens[productId];
    }

    function getProductDetails(uint256 tokenId) external view returns (
        uint256 productId,
        string memory category,
        uint256 mintTime,
        bytes32 conditionHash,
        string[] memory sensorTypes
    ) {
        require(_exists(tokenId), "Token does not exist");
        ProductDetails storage details = _tokenDetails[tokenId];
        return (
            details.productId,
            details.category,
            details.mintTime,
            details.conditionHash,
            details.sensorTypes
        );
    }

    // Required overrides
    function _beforeTokenTransfer(
        address from,
        address to,
        uint256 tokenId,
        uint256 batchSize
    ) internal override(ERC721, ERC721Enumerable) {
        super._beforeTokenTransfer(from, to, tokenId, batchSize);
    }

    function _burn(uint256 tokenId) internal override(ERC721, ERC721URIStorage) {
        super._burn(tokenId);
    }

    function tokenURI(uint256 tokenId) public view override(ERC721, ERC721URIStorage) returns (string memory) {
        return super.tokenURI(tokenId);
    }

    function supportsInterface(bytes4 interfaceId) public view override(ERC721, ERC721Enumerable) returns (bool) {
        return super.supportsInterface(interfaceId);
    }
} 