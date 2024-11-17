<?php

// src/Controllers/CustomerController.php
namespace App\Controllers;

use \PDO;
use App\Models\Customer;


class CustomerController extends BaseController {
    private $model;

    public function __construct(PDO $db) {
        parent::__construct($db);
        $this->model = new Customer($this->db);
    }

    public function index() {
        try {
            $customers = $this->model->getAll();
            $this->response = [
                'success' => true,
                'data' => $customers
            ];
            $this->sendResponse();
        } catch (\Exception $e) {
            $this->sendError($e->getMessage());
        }
    }

    public function create() {
        try {
            $required = ['name', 'number'];
            foreach ($required as $field) {
                if (!isset($this->request['params'][$field])) {
                    throw new \Exception("Field {$field} is required");
                }
            }

            $id = $this->model->create($this->request['params']);
            $customer = $this->model->findById($id);

            $this->response = [
                'success' => true,
                'data' => $customer
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
                throw new \Exception("Customer not found");
            }

            $customer = $this->model->findById($id);
            $this->response = [
                'success' => true,
                'data' => $customer
            ];
            $this->sendResponse();
        } catch (\Exception $e) {
            $this->sendError($e->getMessage());
        }
    }

    public function delete($id) {
        try {
            $success = $this->model->delete($id);
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
}