<?php

// src/Controllers/BackupJobController.php
namespace App\Controllers;

use \PDO;
use App\Models\BackupJob;


class BackupJobController extends BaseController {
    private $model;

    public function __construct(PDO $db) {
        parent::__construct($db);
        $this->model = new BackupJob($this->db);
    }

    public function create() {
        try {
            $required = ['name', 'customer_id', 'backup_type_id'];
            foreach ($required as $field) {
                if (!isset($this->request['params'][$field])) {
                    throw new \Exception("Field {$field} is required");
                }
            }

            $id = $this->model->create($this->request['params']);
            $job = $this->model->findById($id);

            $this->response = [
                'success' => true,
                'data' => $job
            ];
            $this->sendResponse(201);
        } catch (\Exception $e) {
            $this->sendError($e->getMessage());
        }
    }

    public function update($id) {
        try {
            if (empty($this->request['params'])) {
                throw new \Exception("No data provided for update");
            }

            $success = $this->model->update($id, $this->request['params']);
            if (!$success) {
                throw new \Exception("Backup job not found");
            }

            $job = $this->model->findById($id);
            $this->response = [
                'success' => true,
                'data' => $job
            ];
            $this->sendResponse();
        } catch (\Exception $e) {
            $this->sendError($e->getMessage());
        }
    }

    public function getResults($jobId) {
        try {
            $results = $this->model->getLastResults($jobId);
            $this->response = [
                'success' => true,
                'data' => $results
            ];
            $this->sendResponse();
        } catch (\Exception $e) {
            $this->sendError($e->getMessage());
        }
    }
}