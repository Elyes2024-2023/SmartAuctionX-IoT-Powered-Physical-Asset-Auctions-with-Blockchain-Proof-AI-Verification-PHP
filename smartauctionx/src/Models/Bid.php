<?php

/**
 * Bid Model
 * 
 * @package SmartAuctionX
 * @author ELYES
 * @copyright 2024-2025 ELYES
 * @license MIT
 */

namespace SmartAuctionX\Models;

use PDO;
use SmartAuctionX\Config\Config;
use Web3\Web3;
use Web3\Contract;

class Bid
{
    private PDO $db;
    private int $id;
    private int $auctionId;
    private int $userId;
    private float $amount;
    private string $transactionHash;
    private string $status;
    private \DateTime $createdAt;
    private \DateTime $updatedAt;

    public function __construct()
    {
        $config = new Config();
        $this->db = $config->getDatabaseConnection();
    }

    public function create(array $data): bool
    {
        try {
            $this->db->beginTransaction();

            // Insert bid record
            $sql = "INSERT INTO bids (auction_id, user_id, amount, transaction_hash, status) 
                    VALUES (:auction_id, :user_id, :amount, :transaction_hash, :status)";
            
            $stmt = $this->db->prepare($sql);
            $success = $stmt->execute([
                'auction_id' => $data['auction_id'],
                'user_id' => $data['user_id'],
                'amount' => $data['amount'],
                'transaction_hash' => $data['transaction_hash'],
                'status' => $data['status'] ?? 'pending'
            ]);

            if (!$success) {
                throw new \Exception("Failed to create bid record");
            }

            // Update auction's current highest bid
            $sql = "UPDATE auctions SET current_bid = :amount, highest_bidder_id = :user_id 
                    WHERE id = :auction_id AND current_bid < :amount";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                'amount' => $data['amount'],
                'user_id' => $data['user_id'],
                'auction_id' => $data['auction_id']
            ]);

            $this->db->commit();
            return true;
        } catch (\Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function findById(int $id): ?array
    {
        $sql = "SELECT b.*, u.username, u.wallet_address, a.title as auction_title 
                FROM bids b 
                JOIN users u ON b.user_id = u.id 
                JOIN auctions a ON b.auction_id = a.id 
                WHERE b.id = :id";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id' => $id]);
        
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function getAuctionBids(int $auctionId): array
    {
        $sql = "SELECT b.*, u.username, u.wallet_address 
                FROM bids b 
                JOIN users u ON b.user_id = u.id 
                WHERE b.auction_id = :auction_id 
                ORDER BY b.amount DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['auction_id' => $auctionId]);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function updateStatus(int $id, string $status, ?string $transactionHash = null): bool
    {
        $sql = "UPDATE bids SET status = :status";
        $params = ['id' => $id, 'status' => $status];

        if ($transactionHash) {
            $sql .= ", transaction_hash = :transaction_hash";
            $params['transaction_hash'] = $transactionHash;
        }

        $sql .= " WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
    }

    public function verifyBlockchainTransaction(string $transactionHash): bool
    {
        $config = new Config();
        $web3 = new Web3($config->get('blockchain.rpc_url'));
        
        try {
            $transaction = $web3->eth->getTransactionByHash($transactionHash);
            return $transaction && $transaction->blockNumber !== null;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function getUserBids(int $userId): array
    {
        $sql = "SELECT b.*, a.title as auction_title, a.end_time 
                FROM bids b 
                JOIN auctions a ON b.auction_id = a.id 
                WHERE b.user_id = :user_id 
                ORDER BY b.created_at DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['user_id' => $userId]);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
} 