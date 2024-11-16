<?php

// src/Models/BackupResult.php
namespace App\Models;

class BackupResult extends BaseModel {
    protected $table = 'backup_results';
    protected $fillable = [
        'backup_job_id',
        'mail_id',
        'status',
        'date',
        'time',
        'note'
    ];
    
    public function getDashboardStats() {
        $stmt = $this->conn->prepare("
            SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN status = 'success' THEN 1 ELSE 0 END) as success,
                SUM(CASE WHEN status = 'warning' THEN 1 ELSE 0 END) as warnings,
                SUM(CASE WHEN status = 'error' THEN 1 ELSE 0 END) as errors
            FROM {$this->table}
            WHERE date >= DATE_SUB(CURRENT_DATE, INTERVAL 24 DAY)
        ");
        
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    public function getJobStats($jobId) {
        $stmt = $this->conn->prepare("
            SELECT 
                DATE_FORMAT(date, '%Y-%m') as month,
                COUNT(*) as total,
                SUM(CASE WHEN status = 'success' THEN 1 ELSE 0 END) as success,
                SUM(CASE WHEN status = 'warning' THEN 1 ELSE 0 END) as warnings,
                SUM(CASE WHEN status = 'error' THEN 1 ELSE 0 END) as errors
            FROM {$this->table}
            WHERE backup_job_id = :job_id
            GROUP BY DATE_FORMAT(date, '%Y-%m')
            ORDER BY month DESC
            LIMIT 12
        ");
        
        $stmt->execute(['job_id' => $jobId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function updateNote($resultId, $note) {
        return $this->update($resultId, ['note' => $note]);
    }
}