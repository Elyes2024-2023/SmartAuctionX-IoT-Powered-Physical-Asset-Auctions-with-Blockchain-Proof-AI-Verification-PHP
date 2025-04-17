<?php

/**
 * User Model
 * 
 * @package SmartAuctionX
 * @author ELYES
 * @copyright 2024-2025 ELYES
 * @license MIT
 */

namespace SmartAuctionX\Models;

use PDO;
use SmartAuctionX\Config\Config;
use Firebase\JWT\JWT;

class User
{
    private PDO $db;
    private int $id;
    private string $username;
    private string $email;
    private string $passwordHash;
    private string $walletAddress;
    private string $role;
    private \DateTime $createdAt;
    private \DateTime $updatedAt;

    public function __construct()
    {
        $config = new Config();
        $this->db = $config->getDatabaseConnection();
    }

    public function create(array $data): bool
    {
        $sql = "INSERT INTO users (username, email, password_hash, wallet_address, role) 
                VALUES (:username, :email, :password_hash, :wallet_address, :role)";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            'username' => $data['username'],
            'email' => $data['email'],
            'password_hash' => password_hash($data['password'], PASSWORD_BCRYPT),
            'wallet_address' => $data['wallet_address'],
            'role' => $data['role'] ?? 'user'
        ]);
    }

    public function authenticate(string $email, string $password): ?array
    {
        $sql = "SELECT * FROM users WHERE email = :email";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['email' => $email]);
        
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$user || !password_verify($password, $user['password_hash'])) {
            return null;
        }

        return [
            'user' => $user,
            'token' => $this->generateJWT($user)
        ];
    }

    public function findById(int $id): ?array
    {
        $sql = "SELECT * FROM users WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id' => $id]);
        
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function update(int $id, array $data): bool
    {
        $updates = [];
        $params = ['id' => $id];

        foreach ($data as $key => $value) {
            if (in_array($key, ['username', 'email', 'wallet_address', 'role'])) {
                $updates[] = "{$key} = :{$key}";
                $params[$key] = $value;
            }
        }

        if (isset($data['password'])) {
            $updates[] = "password_hash = :password_hash";
            $params['password_hash'] = password_hash($data['password'], PASSWORD_BCRYPT);
        }

        if (empty($updates)) {
            return false;
        }

        $sql = "UPDATE users SET " . implode(', ', $updates) . " WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
    }

    public function delete(int $id): bool
    {
        $sql = "DELETE FROM users WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute(['id' => $id]);
    }

    private function generateJWT(array $user): string
    {
        $config = new Config();
        $payload = [
            'sub' => $user['id'],
            'username' => $user['username'],
            'email' => $user['email'],
            'role' => $user['role'],
            'iat' => time(),
            'exp' => time() + $config->get('jwt.expiration', 3600)
        ];

        return JWT::encode($payload, $config->get('jwt.secret'), 'HS256');
    }
} 