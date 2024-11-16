<?php

// src/Models/Mail.php
namespace App\Models;

class Mail extends BaseModel {
    protected $table = 'mails';
    protected $fillable = ['sender_email', 'date', 'subject', 'content', 'processed'];
    
    public function getUnprocessed() {
        $stmt = $this->conn->prepare("
            SELECT * FROM {$this->table} 
            WHERE processed = 0 
            ORDER BY date ASC
        ");
        
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function markAsProcessed($mailId) {
        return $this->update($mailId, ['processed' => 1]);
    }
}