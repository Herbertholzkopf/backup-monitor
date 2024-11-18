<?php

// src/Controllers/SettingsController.php
namespace App\Controllers;

use \PDO;
use App\Models\Customer;
use App\Models\BackupJob;
use App\Models\BackupType;

class SettingsController extends BaseController {
    private $customerModel;
    private $backupJobModel;
    private $backupTypeModel;

    public function __construct(PDO $db) {
        parent::__construct($db);
        $this->customerModel = new Customer($this->db);
        $this->backupJobModel = new BackupJob($this->db);
        $this->backupTypeModel = new BackupType($this->db);
    }

    // GET /api/settings/customers
    public function getCustomers() {
        try {
            $customers = $this->customerModel->getAll('name', 'ASC');
            $this->response = [
                'success' => true,
                'data' => $customers
            ];
            $this->sendResponse();
        } catch (\Exception $e) {
            $this->sendError($e->getMessage());
        }
    }

    // GET /api/settings/backup-jobs
    public function getBackupJobs() {
        try {
            $stmt = $this->db->prepare("
                SELECT 
                    bj.*,
                    c.name as customer_name,
                    bt.name as backup_type_name
                FROM backup_jobs bj
                LEFT JOIN customers c ON bj.customer_id = c.id
                LEFT JOIN backup_types bt ON bj.backup_type_id = bt.id
                ORDER BY c.name ASC, bj.name ASC
            ");
            $stmt->execute();
            $jobs = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $this->response = [
                'success' => true,
                'data' => $jobs
            ];
            $this->sendResponse();
        } catch (\Exception $e) {
            $this->sendError($e->getMessage());
        }
    }

    // GET /api/settings/backup-types
    public function getBackupTypes() {
        try {
            $types = $this->backupTypeModel->getAll('name', 'ASC');
            $this->response = [
                'success' => true,
                'data' => $types
            ];
            $this->sendResponse();
        } catch (\Exception $e) {
            $this->sendError($e->getMessage());
        }
    }

    // POST /api/settings/customers
    public function createCustomer() {
        try {
            if (!isset($this->request['params']['name']) || !isset($this->request['params']['number'])) {
                throw new \Exception("Name and number are required");
            }

            $id = $this->customerModel->create($this->request['params']);
            $customer = $this->customerModel->findById($id);

            $this->response = [
                'success' => true,
                'data' => $customer
            ];
            $this->sendResponse(201);
        } catch (\Exception $e) {
            $this->sendError($e->getMessage());
        }
    }

    // POST /api/settings/backup-jobs
    public function createBackupJob() {
        try {
            $required = ['name', 'customer_id', 'backup_type_id', 'email'];
            foreach ($required as $field) {
                if (!isset($this->request['params'][$field])) {
                    throw new \Exception("Field {$field} is required");
                }
            }

            // Optional search terms
            $searchTerms = [
                'search_term1' => $this->request['params']['search_term1'] ?? null,
                'search_term2' => $this->request['params']['search_term2'] ?? null,
                'search_term3' => $this->request['params']['search_term3'] ?? null
            ];

            $data = array_merge($this->request['params'], $searchTerms);
            $id = $this->backupJobModel->create($data);
            $job = $this->backupJobModel->findById($id);

            $this->response = [
                'success' => true,
                'data' => $job
            ];
            $this->sendResponse(201);
        } catch (\Exception $e) {
            $this->sendError($e->getMessage());
        }
    }

    // PUT /api/settings/customers/{id}
    public function updateCustomer($id) {
        try {
            if (empty($this->request['params'])) {
                throw new \Exception("No data provided for update");
            }

            $success = $this->customerModel->update($id, $this->request['params']);
            if (!$success) {
                throw new \Exception("Customer not found");
            }

            $customer = $this->customerModel->findById($id);
            $this->response = [
                'success' => true,
                'data' => $customer
            ];
            $this->sendResponse();
        } catch (\Exception $e) {
            $this->sendError($e->getMessage());
        }
    }

    // PUT /api/settings/backup-jobs/{id}
    public function updateBackupJob($id) {
        try {
            if (empty($this->request['params'])) {
                throw new \Exception("No data provided for update");
            }

            $success = $this->backupJobModel->update($id, $this->request['params']);
            if (!$success) {
                throw new \Exception("Backup job not found");
            }

            $job = $this->backupJobModel->findById($id);
            $this->response = [
                'success' => true,
                'data' => $job
            ];
            $this->sendResponse();
        } catch (\Exception $e) {
            $this->sendError($e->getMessage());
        }
    }

    // DELETE /api/settings/customers/{id}
    public function deleteCustomer($id) {
        try {
            $success = $this->customerModel->delete($id);
            if (!$success) {
                throw new \Exception("Customer not found");
            }

            $this->response = [
                'success' => true,
                'message' => 'Customer deleted successfully'
            ];
            $this->sendResponse();
        } catch (\Exception $e) {
            $this->sendError($e->getMessage());
        }
    }

    // DELETE /api/settings/backup-jobs/{id}
    public function deleteBackupJob($id) {
        try {
            $success = $this->backupJobModel->delete($id);
            if (!$success) {
                throw new \Exception("Backup job not found");
            }

            $this->response = [
                'success' => true,
                'message' => 'Backup job deleted successfully'
            ];
            $this->sendResponse();
        } catch (\Exception $e) {
            $this->sendError($e->getMessage());
        }
    }

    public function getMailSettings() {
        try {
            $config = require __DIR__ . '/../../config/mail.php';
            
            // Passwort aus Sicherheitsgründen nicht zurückgeben
            unset($config['password']);
            
            $this->response = [
                'success' => true,
                'data' => $config
            ];
            $this->sendResponse();
        } catch (\Exception $e) {
            $this->sendError($e->getMessage());
        }
    }
    
    public function updateMailSettings() {
        try {
            if (empty($this->request['params'])) {
                throw new \Exception("No mail settings provided");
            }
    
            $required = ['server', 'port', 'username', 'password', 'protocol', 'encryption'];
            foreach ($required as $field) {
                if (!isset($this->request['params'][$field])) {
                    throw new \Exception("Field {$field} is required");
                }
            }
    
            // Validiere die Einstellungen
            $server = $this->request['params']['server'];
            $port = (int)$this->request['params']['port'];
            $username = $this->request['params']['username'];
            $password = $this->request['params']['password'];
            
            // Optional: Teste die Verbindung
            $test = @imap_open(
                "{{$server}:{$port}/imap/ssl}INBOX",
                $username,
                $password
            );
            
            if (!$test) {
                throw new \Exception("Could not connect to mail server: " . imap_last_error());
            }
            imap_close($test);
    
            // Speichere die Einstellungen
            $config = "<?php\nreturn " . var_export($this->request['params'], true) . ";";
            file_put_contents(__DIR__ . '/../../config/mail.php', $config);
    
            $this->response = [
                'success' => true,
                'message' => 'Mail settings updated successfully'
            ];
            $this->sendResponse();
        } catch (\Exception $e) {
            $this->sendError($e->getMessage());
        }
    }
}