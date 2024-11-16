<?php

// src/Controllers/BackupResultController.php
namespace App\Controllers;

use App\Models\BackupResult;

class BackupResultController extends BaseController {
    private $model;

    public function __construct(PDO $db) {
        parent::__construct($db);
        $this->model = new BackupResult($this->db);
    }

    public function updateNote() {
        try {
            if (!isset($this->request['params']['id']) || !isset($this->request['params']['note'])) {
                throw new \Exception("Result ID and note are required");
            }

            $success = $this->model->updateNote(
                $this->request['params']['id'],
                $this->request['params']['note']
            );

            if (!$success) {
                throw new \Exception("Backup result not found");
            }

            $this->response = [
                'success' => true,
                'message' => 'Note updated successfully'
            ];
            $this->sendResponse();
        } catch (\Exception $e) {
            $this->sendError($e->getMessage());
        }
    }
}