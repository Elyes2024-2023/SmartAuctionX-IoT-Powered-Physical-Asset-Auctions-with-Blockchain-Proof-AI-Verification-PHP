/**
 * SmartAuctionX Smart Contract Deployment Script
 * 
 * This script deploys the AuctionNFT and AuctionManager contracts to the specified network.
 * 
 * @package SmartAuctionX
 * @author ELYES
 * @copyright 2024-2025 ELYES. All rights reserved.
 * @license MIT
 */

const hre = require("hardhat");

async function main() {
    console.log("Deploying SmartAuctionX contracts...");

    // Deploy NFT contract first
    const AuctionNFT = await hre.ethers.getContractFactory("AuctionNFT");
    const nft = await AuctionNFT.deploy();
    await nft.deployed();
    console.log("AuctionNFT deployed to:", nft.address);

    // Deploy Auction Manager
    const AuctionManager = await hre.ethers.getContractFactory("AuctionManager");
    const manager = await AuctionManager.deploy(nft.address);
    await manager.deployed();
    console.log("AuctionManager deployed to:", manager.address);

    // Set up permissions
    const setAuctionManagerTx = await nft.setAuctionManager(manager.address);
    await setAuctionManagerTx.wait();
    console.log("AuctionManager set as NFT minter");

    // Verify contracts on Etherscan
    if (hre.network.name !== "hardhat" && hre.network.name !== "localhost") {
        console.log("Verifying contracts on Etherscan...");
        
        await hre.run("verify:verify", {
            address: nft.address,
            constructorArguments: []
        });

        await hre.run("verify:verify", {
            address: manager.address,
            constructorArguments: [nft.address]
        });
    }

    // Save deployment info
    const fs = require("fs");
    const deployments = {
        network: hre.network.name,
        nft: nft.address,
        manager: manager.address,
        timestamp: new Date().toISOString()
    };

    fs.writeFileSync(
        "deployments.json",
        JSON.stringify(deployments, null, 2)
    );

    console.log("Deployment complete! Details saved to deployments.json");
}

main()
    .then(() => process.exit(0))
    .catch((error) => {
        console.error(error);
        process.exit(1);
    }); 