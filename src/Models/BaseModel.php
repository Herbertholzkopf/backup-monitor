<?php

// src/Models/BaseModel.php
namespace App\Models;

use \PDO;
use \PDOException;


abstract class BaseModel {
    protected $conn;
    protected $table;
    protected $fillable = [];
    
    public function __construct(PDO $db) {
        $this->conn = $db;
    }
    
    public function findById($id) {
        $stmt = $this->conn->prepare("SELECT * FROM {$this->table} WHERE id = :id");
        $stmt->execute(['id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    public function getAll($orderBy = 'id', $direction = 'ASC') {
        $stmt = $this->conn->prepare("SELECT * FROM {$this->table} ORDER BY {$orderBy} {$direction}");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function create(array $data) {
        $filtered = array_intersect_key($data, array_flip($this->fillable));
        $columns = implode(', ', array_keys($filtered));
        $values = ':' . implode(', :', array_keys($filtered));
        
        $stmt = $this->conn->prepare(
            "INSERT INTO {$this->table} ({$columns}) VALUES ({$values})"
        );
        
        try {
            $stmt->execute($filtered);
            return $this->conn->lastInsertId();
        } catch (PDOException $e) {
            throw new \Exception("Create error: " . $e->getMessage());
        }
    }
    
    public function update($id, array $data) {
        $filtered = array_intersect_key($data, array_flip($this->fillable));
        $fields = array_map(function($key) {
            return "{$key} = :{$key}";
        }, array_keys($filtered));
        
        $sql = "UPDATE {$this->table} SET " . implode(', ', $fields) . " WHERE id = :id";
        $filtered['id'] = $id;
        
        try {
            $stmt = $this->conn->prepare($sql);
            return $stmt->execute($filtered);
        } catch (PDOException $e) {
            throw new \Exception("Update error: " . $e->getMessage());
        }
    }
    
    public function delete($id) {
        try {
            $stmt = $this->conn->prepare("DELETE FROM {$this->table} WHERE id = :id");
            return $stmt->execute(['id' => $id]);
        } catch (PDOException $e) {
            throw new \Exception("Delete error: " . $e->getMessage());
        }
    }
    
    public function beginTransaction() {
        return $this->conn->beginTransaction();
    }
    
    public function commit() {
        return $this->conn->commit();
    }
    
    public function rollback() {
        return $this->conn->rollBack();
    }
}