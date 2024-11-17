<?php

// src/Models/BackupType.php
namespace App\Models;

use \PDO;
use \PDOException;


class BackupType extends BaseModel {
    protected $table = 'backup_types';
    protected $fillable = ['name'];
    
    public function getJobCount($typeId) {
        $stmt = $this->conn->prepare("
            SELECT COUNT(*) as count 
            FROM backup_jobs 
            WHERE backup_type_id = :type_id
        ");
        
        $stmt->execute(['type_id' => $typeId]);
        return $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    }
}