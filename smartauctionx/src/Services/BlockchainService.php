<?php

namespace SmartAuctionX\Services;

use SmartAuctionX\Config\Config;
use Web3\Web3;
use Web3\Contract;
use Web3\Providers\HttpProvider;
use Web3\RequestManagers\HttpRequestManager;

class BlockchainService
{
    private Config $config;
    private Web3 $web3;
    private Contract $auctionContract;
    private Contract $nftContract;

    public function __construct()
    {
        $this->config = Config::getInstance();
        $this->initializeWeb3();
        $this->initializeContracts();
    }

    private function initializeWeb3(): void
    {
        $provider = new HttpProvider(
            new HttpRequestManager(
                $this->config->get('blockchain.polygon_node_url'),
                10 // timeout in seconds
            )
        );
        $this->web3 = new Web3($provider);
    }

    private function initializeContracts(): void
    {
        // Load contract ABIs
        $auctionAbi = json_decode(file_get_contents(__DIR__ . '/../../contracts/AuctionManager.json'), true)['abi'];
        $nftAbi = json_decode(file_get_contents(__DIR__ . '/../../contracts/AuctionNFT.json'), true)['abi'];

        // Initialize contracts
        $this->auctionContract = new Contract(
            $this->web3->provider,
            $auctionAbi,
            $this->config->get('blockchain.contract_address')
        );

        $this->nftContract = new Contract(
            $this->web3->provider,
            $nftAbi,
            $this->config->get('blockchain.nft_contract_address')
        );
    }

    public function createAuction(array $auctionData): string
    {
        $encodedData = $this->auctionContract->getData('createAuction', [
            $auctionData['productId'],
            $auctionData['startPrice'],
            $auctionData['startTime'],
            $auctionData['endTime']
        ]);

        return $this->sendTransaction([
            'to' => $this->config->get('blockchain.contract_address'),
            'data' => $encodedData,
            'gas' => '200000'
        ]);
    }

    public function placeBid(int $auctionId, float $amount): string
    {
        $encodedData = $this->auctionContract->getData('placeBid', [$auctionId]);

        return $this->sendTransaction([
            'to' => $this->config->get('blockchain.contract_address'),
            'data' => $encodedData,
            'value' => $this->web3->toWei($amount, 'ether'),
            'gas' => '150000'
        ]);
    }

    public function mintNFT(int $productId, string $tokenURI): string
    {
        $encodedData = $this->nftContract->getData('mint', [
            $productId,
            $tokenURI
        ]);

        return $this->sendTransaction([
            'to' => $this->config->get('blockchain.nft_contract_address'),
            'data' => $encodedData,
            'gas' => '250000'
        ]);
    }

    public function getAuctionDetails(int $auctionId): array
    {
        return $this->auctionContract->call('getAuction', [$auctionId]);
    }

    public function verifyOwnership(int $productId, string $ownerAddress): bool
    {
        $tokenId = $this->nftContract->call('getTokenIdByProduct', [$productId]);
        $owner = $this->nftContract->call('ownerOf', [$tokenId]);
        
        return strtolower($owner) === strtolower($ownerAddress);
    }

    private function sendTransaction(array $transaction): string
    {
        $transaction['from'] = $this->config->get('blockchain.wallet_address');
        $transaction['nonce'] = $this->web3->eth->getTransactionCount(
            $transaction['from'],
            'latest'
        );

        // Sign and send transaction
        $signedTransaction = $this->web3->eth->accounts->signTransaction(
            $transaction,
            $this->config->get('blockchain.private_key')
        );

        return $this->web3->eth->sendRawTransaction($signedTransaction);
    }

    public function getTransactionReceipt(string $txHash): ?array
    {
        return $this->web3->eth->getTransactionReceipt($txHash);
    }

    public function waitForConfirmation(string $txHash, int $maxAttempts = 30): bool
    {
        $attempts = 0;
        while ($attempts < $maxAttempts) {
            $receipt = $this->getTransactionReceipt($txHash);
            if ($receipt && $receipt->status === '0x1') {
                return true;
            }
            sleep(2); // Wait 2 seconds between checks
            $attempts++;
        }
        return false;
    }

    public function getGasPrice(): string
    {
        return $this->web3->eth->gasPrice();
    }

    public function estimateGas(array $transaction): string
    {
        return $this->web3->eth->estimateGas($transaction);
    }
} 