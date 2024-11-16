<?php

// src/Controllers/BaseController.php
namespace App\Controllers;

use PDO;

abstract class BaseController {
    protected $db;
    protected $request;
    protected $response = [];

    public function __construct(PDO $db) {
        $this->db = $db;
        $this->request = $this->parseRequest();
    }

    protected function parseRequest() {
        $request = [];
        $request['method'] = $_SERVER['REQUEST_METHOD'];
        $request['params'] = [];

        switch ($request['method']) {
            case 'GET':
                $request['params'] = $_GET;
                break;
            case 'POST':
                $request['params'] = $_POST;
                if (empty($_POST)) {
                    $json = file_get_contents('php://input');
                    $data = json_decode($json, true);
                    if ($data) {
                        $request['params'] = $data;
                    }
                }
                break;
        }

        return $request;
    }

    protected function sendResponse($statusCode = 200) {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo json_encode($this->response);
        exit;
    }

    protected function sendError($message, $statusCode = 400) {
        $this->response = [
            'success' => false,
            'error' => $message
        ];
        $this->sendResponse($statusCode);
    }
}