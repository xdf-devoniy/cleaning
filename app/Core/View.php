<?php
namespace App\Core;

class View
{
    public function __construct(private string $template, private array $data = [])
    {
    }

    public function render(): string
    {
        $viewPath = __DIR__ . '/../Views/' . $this->template . '.php';
        if (!file_exists($viewPath)) {
            return 'View not found: ' . htmlspecialchars($this->template, ENT_QUOTES);
        }

        extract($this->data);
        ob_start();
        include $viewPath;
        return (string)ob_get_clean();
    }
}
