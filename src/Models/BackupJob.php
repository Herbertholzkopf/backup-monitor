<?php

// src/Models/BackupJob.php
namespace App\Models;

use \PDO;
use \PDOException;


class BackupJob extends BaseModel {
    protected $table = 'backup_jobs';
    protected $fillable = [
        'name', 
        'customer_id', 
        'email', 
        'note', 
        'backup_type_id',
        'search_term1',
        'search_term2',
        'search_term3'
    ];
    
    public function getLastResults($jobId, $limit = 24) {
        $stmt = $this->conn->prepare("
            SELECT * FROM backup_results 
            WHERE backup_job_id = :job_id 
            ORDER BY date DESC, time DESC 
            LIMIT :limit
        ");
        
        $stmt->bindValue(':job_id', $jobId, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function findBySearchTerms($sender, $subject, $content) {
        $stmt = $this->conn->prepare("
            SELECT * FROM {$this->table}
            WHERE (search_term1 IS NOT NULL AND (
                    :sender LIKE CONCAT('%', search_term1, '%') OR
                    :subject LIKE CONCAT('%', search_term1, '%') OR
                    :content LIKE CONCAT('%', search_term1, '%')
                ))
                OR (search_term2 IS NOT NULL AND (
                    :sender LIKE CONCAT('%', search_term2, '%') OR
                    :subject LIKE CONCAT('%', search_term2, '%') OR
                    :content LIKE CONCAT('%', search_term2, '%')
                ))
                OR (search_term3 IS NOT NULL AND (
                    :sender LIKE CONCAT('%', search_term3, '%') OR
                    :subject LIKE CONCAT('%', search_term3, '%') OR
                    :content LIKE CONCAT('%', search_term3, '%')
                ))
        ");
        
        $stmt->execute([
            'sender' => $sender,
            'subject' => $subject,
            'content' => $content
        ]);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
