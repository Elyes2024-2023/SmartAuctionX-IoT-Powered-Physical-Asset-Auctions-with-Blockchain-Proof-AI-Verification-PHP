/**
 * SmartAuctionX AuctionManager Test Suite
 * 
 * This file contains tests for the AuctionManager smart contract.
 * 
 * @package SmartAuctionX
 * @author ELYES
 * @copyright 2024-2025 ELYES. All rights reserved.
 * @license MIT
 */

const { expect } = require("chai");
const { ethers } = require("hardhat");
const { time } = require("@nomicfoundation/hardhat-network-helpers");

describe("AuctionManager", function () {
    let AuctionNFT;
    let AuctionManager;
    let nft;
    let manager;
    let owner;
    let seller;
    let bidder1;
    let bidder2;

    beforeEach(async function () {
        [owner, seller, bidder1, bidder2] = await ethers.getSigners();

        AuctionNFT = await ethers.getContractFactory("AuctionNFT");
        AuctionManager = await ethers.getContractFactory("AuctionManager");

        nft = await AuctionNFT.deploy();
        manager = await AuctionManager.deploy(await nft.getAddress());

        await nft.setAuctionManager(await manager.getAddress());
    });

    describe("Auction Creation", function () {
        it("Should create a new auction", async function () {
            const productId = 1;
            const startPrice = ethers.parseEther("1");
            const duration = 3600; // 1 hour

            await expect(manager.createAuction(
                productId,
                startPrice,
                duration
            ))
                .to.emit(manager, "AuctionCreated")
                .withArgs(1, productId, startPrice, duration);

            const auction = await manager.getAuction(1);
            expect(auction.productId).to.equal(productId);
            expect(auction.startPrice).to.equal(startPrice);
            expect(auction.duration).to.equal(duration);
            expect(auction.seller).to.equal(owner.address);
            expect(auction.isActive).to.be.true;
        });

        it("Should not create auction with invalid parameters", async function () {
            await expect(
                manager.createAuction(0, ethers.parseEther("1"), 3600)
            ).to.be.revertedWith("Invalid product ID");

            await expect(
                manager.createAuction(1, 0, 3600)
            ).to.be.revertedWith("Invalid start price");

            await expect(
                manager.createAuction(1, ethers.parseEther("1"), 0)
            ).to.be.revertedWith("Invalid duration");
        });
    });

    describe("Bidding", function () {
        beforeEach(async function () {
            await manager.createAuction(1, ethers.parseEther("1"), 3600);
        });

        it("Should accept valid bid", async function () {
            const bidAmount = ethers.parseEther("1.5");
            await expect(manager.connect(bidder1).placeBid(1, { value: bidAmount }))
                .to.emit(manager, "BidPlaced")
                .withArgs(1, bidder1.address, bidAmount);

            const auction = await manager.getAuction(1);
            expect(auction.currentBid).to.equal(bidAmount);
            expect(auction.highestBidder).to.equal(bidder1.address);
        });

        it("Should not accept bid lower than current bid", async function () {
            await manager.connect(bidder1).placeBid(1, { value: ethers.parseEther("1.5") });
            await expect(
                manager.connect(bidder2).placeBid(1, { value: ethers.parseEther("1.2") })
            ).to.be.revertedWith("Bid too low");
        });

        it("Should refund previous highest bidder", async function () {
            const initialBalance = await ethers.provider.getBalance(bidder1.address);
            
            await manager.connect(bidder1).placeBid(1, { value: ethers.parseEther("1.5") });
            await manager.connect(bidder2).placeBid(1, { value: ethers.parseEther("2") });
            
            const finalBalance = await ethers.provider.getBalance(bidder1.address);
            expect(finalBalance).to.be.gt(initialBalance);
        });
    });

    describe("Auction End", function () {
        beforeEach(async function () {
            await manager.createAuction(1, ethers.parseEther("1"), 3600);
            await manager.connect(bidder1).placeBid(1, { value: ethers.parseEther("1.5") });
        });

        it("Should end auction after duration", async function () {
            await time.increase(3601);
            await expect(manager.endAuction(1))
                .to.emit(manager, "AuctionEnded")
                .withArgs(1, bidder1.address, ethers.parseEther("1.5"));

            const auction = await manager.getAuction(1);
            expect(auction.isActive).to.be.false;
        });

        it("Should not end auction before duration", async function () {
            await expect(
                manager.endAuction(1)
            ).to.be.revertedWith("Auction still active");
        });

        it("Should transfer funds correctly", async function () {
            const sellerInitialBalance = await ethers.provider.getBalance(seller.address);
            await time.increase(3601);
            await manager.endAuction(1);
            const sellerFinalBalance = await ethers.provider.getBalance(seller.address);
            
            expect(sellerFinalBalance).to.be.gt(sellerInitialBalance);
        });
    });

    describe("IoT Integration", function () {
        beforeEach(async function () {
            await manager.createAuction(1, ethers.parseEther("1"), 3600);
        });

        it("Should update condition hash", async function () {
            const conditionHash = ethers.id("new condition");
            await expect(manager.updateCondition(1, conditionHash))
                .to.emit(manager, "ConditionUpdated")
                .withArgs(1, conditionHash);
        });

        it("Should adjust price based on condition", async function () {
            const conditionHash = ethers.id("worse condition");
            await manager.updateCondition(1, conditionHash);
            
            const auction = await manager.getAuction(1);
            expect(auction.startPrice).to.be.lt(ethers.parseEther("1"));
        });
    });
}); 