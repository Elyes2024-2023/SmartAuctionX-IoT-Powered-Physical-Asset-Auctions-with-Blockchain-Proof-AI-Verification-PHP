<?php

namespace SmartAuctionX\Models;

class Product extends Model
{
    protected string $table = 'products';

    public function getActiveAuctions(): array
    {
        return $this->findAll(
            ['status' => 'active'],
            ['auction_start_time' => 'DESC']
        );
    }

    public function getUpcomingAuctions(): array
    {
        return $this->findAll(
            ['status' => 'pending'],
            ['auction_start_time' => 'ASC']
        );
    }

    public function getCompletedAuctions(): array
    {
        return $this->findAll(
            ['status' => 'sold'],
            ['auction_end_time' => 'DESC']
        );
    }

    public function getProductsBySeller(int $sellerId): array
    {
        return $this->findAll(
            ['seller_id' => $sellerId],
            ['created_at' => 'DESC']
        );
    }

    public function updatePrice(int $id, float $newPrice): bool
    {
        return $this->update($id, ['current_price' => $newPrice]);
    }

    public function updateStatus(int $id, string $status): bool
    {
        return $this->update($id, ['status' => $status]);
    }

    public function getProductsWithSensors(): array
    {
        $sql = "SELECT p.*, GROUP_CONCAT(i.sensor_type) as sensor_types 
                FROM {$this->table} p 
                LEFT JOIN iot_sensors i ON p.id = i.product_id 
                GROUP BY p.id";
        
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function getProductsWithAIVerification(): array
    {
        $sql = "SELECT p.*, a.verification_type, a.confidence_score, a.result 
                FROM {$this->table} p 
                LEFT JOIN ai_verifications a ON p.id = a.product_id 
                ORDER BY a.created_at DESC";
        
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function searchProducts(string $query, array $filters = []): array
    {
        $sql = "SELECT * FROM {$this->table} WHERE (name LIKE :query OR description LIKE :query)";
        $params = ['query' => "%$query%"];

        if (!empty($filters)) {
            foreach ($filters as $key => $value) {
                $sql .= " AND $key = :$key";
                $params[$key] = $value;
            }
        }

        $sql .= " ORDER BY created_at DESC";
        
        $stmt = $this->db->query($sql, $params);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function getProductWithDetails(int $id): ?array
    {
        $sql = "SELECT p.*, 
                GROUP_CONCAT(DISTINCT i.sensor_type) as sensor_types,
                GROUP_CONCAT(DISTINCT a.verification_type) as verification_types,
                GROUP_CONCAT(DISTINCT b.amount) as bid_amounts
                FROM {$this->table} p
                LEFT JOIN iot_sensors i ON p.id = i.product_id
                LEFT JOIN ai_verifications a ON p.id = a.product_id
                LEFT JOIN bids b ON p.id = b.product_id
                WHERE p.id = :id
                GROUP BY p.id";
        
        $stmt = $this->db->query($sql, ['id' => $id]);
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);
        
        return $result ?: null;
    }
} 