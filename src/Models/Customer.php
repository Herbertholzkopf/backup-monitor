<?php

// src/Models/Customer.php
namespace App\Models;

class Customer extends BaseModel {
    protected $table = 'customers';
    protected $fillable = ['name', 'number', 'note'];
    
    public function getBackupJobs($customerId) {
        $stmt = $this->conn->prepare("
            SELECT bj.*, bt.name as backup_type_name 
            FROM backup_jobs bj
            LEFT JOIN backup_types bt ON bj.backup_type_id = bt.id
            WHERE bj.customer_id = :customer_id
            ORDER BY bj.name ASC
        ");
        
        $stmt->execute(['customer_id' => $customerId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function getDashboardData($customerId) {
        $stmt = $this->conn->prepare("
            SELECT 
                bj.id as job_id,
                bj.name as job_name,
                bt.name as backup_type,
                br.status,
                br.date,
                br.time,
                br.note,
                COUNT(br2.id) as runs_count
            FROM backup_jobs bj
            LEFT JOIN backup_types bt ON bj.backup_type_id = bt.id
            LEFT JOIN backup_results br ON bj.id = br.backup_job_id
            LEFT JOIN backup_results br2 ON br.date = br2.date 
                AND br.backup_job_id = br2.backup_job_id
            WHERE bj.customer_id = :customer_id
                AND br.date >= DATE_SUB(CURRENT_DATE, INTERVAL 24 DAY)
            GROUP BY bj.id, br.date
            ORDER BY br.date DESC
        ");
        
        $stmt->execute(['customer_id' => $customerId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
