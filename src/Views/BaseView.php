<?php
// src/Views/BaseView.php
namespace App\Views;

class BaseView {
    protected $template;
    protected $data = [];

    public function __construct($template = null) {
        $this->template = $template;
    }

    public function assign($key, $value) {
        $this->data[$key] = $value;
    }

    public function render() {
        extract($this->data);
        include __DIR__ . '/templates/layout.php';
    }

    protected function renderPartial($template) {
        extract($this->data);
        include __DIR__ . '/templates/' . $template . '.php';
    }
}