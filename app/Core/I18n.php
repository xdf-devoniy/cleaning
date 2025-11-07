<?php
namespace App\Core;

class I18n
{
    private static ?self $instance = null;
    private string $locale;
    private array $messages = [];

    private function __construct()
    {
        $config = require __DIR__ . '/../Config/config.php';
        $this->locale = $config['locale'];
        $this->messages = [
            'uz' => ['welcome' => 'CleanTrack CRM ga xush kelibsiz'],
            'ru' => ['welcome' => 'Добро пожаловать в CleanTrack CRM'],
            'en' => ['welcome' => 'Welcome to CleanTrack CRM'],
        ];
    }

    public static function instance(): self
    {
        return self::$instance ??= new self();
    }

    public function setLocale(string $locale): void
    {
        $this->locale = $locale;
    }

    public function trans(string $key): string
    {
        return $this->messages[$this->locale][$key] ?? $key;
    }
}
