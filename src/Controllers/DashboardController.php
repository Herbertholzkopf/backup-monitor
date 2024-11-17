<?php

// src/Controllers/DashboardController.php
namespace App\Controllers;

use \PDO;
use App\Models\Customer;
use App\Models\BackupResult;


class DashboardController extends BaseController {
    public function index() {
        try {
            $customerModel = new Customer($this->db);
            $resultModel = new BackupResult($this->db);

            // Gesamtstatistiken abrufen
            $stats = $resultModel->getDashboardStats();

            // Alle Kunden mit ihren Backup-Jobs abrufen
            $customers = $customerModel->getAll();
            $dashboardData = [];

            foreach ($customers as $customer) {
                $jobData = $customerModel->getDashboardData($customer['id']);
                $dashboardData[] = [
                    'customer' => $customer,
                    'jobs' => $jobData
                ];
            }

            $this->response = [
                'success' => true,
                'stats' => $stats,
                'data' => $dashboardData
            ];

            $this->sendResponse();
        } catch (\Exception $e) {
            $this->sendError($e->getMessage());
        }
    }
}