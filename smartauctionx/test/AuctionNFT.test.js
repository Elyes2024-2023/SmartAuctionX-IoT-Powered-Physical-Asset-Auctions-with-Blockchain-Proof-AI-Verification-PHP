/**
 * SmartAuctionX AuctionNFT Test Suite
 * 
 * This file contains tests for the AuctionNFT smart contract.
 * 
 * @package SmartAuctionX
 * @author ELYES
 * @copyright 2024-2025 ELYES. All rights reserved.
 * @license MIT
 */

const { expect } = require("chai");
const { ethers } = require("hardhat");

describe("AuctionNFT", function () {
    let AuctionNFT;
    let nft;
    let owner, auctionManager, user;
    let productId, tokenURI, category;

    beforeEach(async function () {
        [owner, auctionManager, user] = await ethers.getSigners();

        AuctionNFT = await ethers.getContractFactory("AuctionNFT");
        nft = await AuctionNFT.deploy();
        await nft.deployed();

        // Set auction manager
        await nft.setAuctionManager(auctionManager.address);

        // Test parameters
        productId = 1;
        tokenURI = "ipfs://QmTest";
        category = "Collectibles";
    });

    describe("Deployment", function () {
        it("Should set the right owner", async function () {
            expect(await nft.owner()).to.equal(owner.address);
        });

        it("Should set the auction manager", async function () {
            expect(await nft.auctionManager()).to.equal(auctionManager.address);
        });
    });

    describe("Minting", function () {
        it("Should mint a new token", async function () {
            const sensorTypes = ["temperature", "humidity"];
            
            await expect(
                nft.connect(auctionManager).mint(
                    user.address,
                    productId,
                    tokenURI,
                    category,
                    sensorTypes
                )
            ).to.emit(nft, "ProductMinted");

            const tokenId = await nft.getTokenIdByProduct(productId);
            expect(await nft.ownerOf(tokenId)).to.equal(user.address);
        });

        it("Should fail if not called by auction manager", async function () {
            const sensorTypes = ["temperature", "humidity"];
            
            await expect(
                nft.connect(user).mint(
                    user.address,
                    productId,
                    tokenURI,
                    category,
                    sensorTypes
                )
            ).to.be.revertedWith("Caller is not the auction manager");
        });

        it("Should store product details correctly", async function () {
            const sensorTypes = ["temperature", "humidity"];
            
            await nft.connect(auctionManager).mint(
                user.address,
                productId,
                tokenURI,
                category,
                sensorTypes
            );

            const tokenId = await nft.getTokenIdByProduct(productId);
            const details = await nft.getProductDetails(tokenId);

            expect(details.productId).to.equal(productId);
            expect(details.category).to.equal(category);
            expect(details.sensorTypes).to.deep.equal(sensorTypes);
        });
    });

    describe("Token URI", function () {
        it("Should return the correct token URI", async function () {
            const sensorTypes = ["temperature"];
            
            await nft.connect(auctionManager).mint(
                user.address,
                productId,
                tokenURI,
                category,
                sensorTypes
            );

            const tokenId = await nft.getTokenIdByProduct(productId);
            expect(await nft.tokenURI(tokenId)).to.equal(tokenURI);
        });
    });

    describe("Condition Updates", function () {
        let tokenId;

        beforeEach(async function () {
            const sensorTypes = ["temperature", "humidity"];
            await nft.connect(auctionManager).mint(
                user.address,
                productId,
                tokenURI,
                category,
                sensorTypes
            );
            tokenId = await nft.getTokenIdByProduct(productId);
        });

        it("Should update condition hash", async function () {
            const conditionHash = ethers.utils.keccak256(
                ethers.utils.toUtf8Bytes("temperature:25,humidity:50")
            );
            
            await expect(
                nft.connect(auctionManager).updateCondition(tokenId, conditionHash)
            ).to.emit(nft, "ConditionUpdated")
             .withArgs(tokenId, conditionHash);

            const details = await nft.getProductDetails(tokenId);
            expect(details.conditionHash).to.equal(conditionHash);
        });

        it("Should fail to update condition if not auction manager", async function () {
            const conditionHash = ethers.utils.keccak256(
                ethers.utils.toUtf8Bytes("temperature:25,humidity:50")
            );
            
            await expect(
                nft.connect(user).updateCondition(tokenId, conditionHash)
            ).to.be.revertedWith("Caller is not the auction manager");
        });
    });

    describe("Sensor Management", function () {
        let tokenId;

        beforeEach(async function () {
            const sensorTypes = ["temperature"];
            await nft.connect(auctionManager).mint(
                user.address,
                productId,
                tokenURI,
                category,
                sensorTypes
            );
            tokenId = await nft.getTokenIdByProduct(productId);
        });

        it("Should add new sensors", async function () {
            const newSensors = ["humidity", "gps"];
            
            await expect(
                nft.connect(auctionManager).addSensors(tokenId, newSensors)
            ).to.emit(nft, "SensorsAdded")
             .withArgs(tokenId, newSensors);

            const details = await nft.getProductDetails(tokenId);
            expect(details.sensorTypes).to.include.members(["temperature", "humidity", "gps"]);
        });

        it("Should fail to add sensors if not auction manager", async function () {
            const newSensors = ["humidity"];
            
            await expect(
                nft.connect(user).addSensors(tokenId, newSensors)
            ).to.be.revertedWith("Caller is not the auction manager");
        });
    });

    describe("Token Enumeration", function () {
        it("Should track total supply correctly", async function () {
            const sensorTypes = ["temperature"];
            
            expect(await nft.totalSupply()).to.equal(0);

            await nft.connect(auctionManager).mint(
                user.address,
                productId,
                tokenURI,
                category,
                sensorTypes
            );

            expect(await nft.totalSupply()).to.equal(1);
        });

        it("Should enumerate tokens correctly", async function () {
            const sensorTypes = ["temperature"];
            
            await nft.connect(auctionManager).mint(
                user.address,
                productId,
                tokenURI,
                category,
                sensorTypes
            );

            const tokenId = await nft.tokenByIndex(0);
            expect(tokenId).to.equal(1);
        });
    });
}); 