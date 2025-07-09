<?php
declare(strict_types=1);
namespace FaustVik\Router\interfaces\Router;

use FaustVik\Router\interfaces\Router\Components\ConfigInterface;
use FaustVik\Router\Route\RoutesCollection;

/**
 * Интерфейс роутера
 *
 * Определяет основные методы для работы с роутером:
 * - Запуск обработки запросов
 * - Управление коллекцией маршрутов
 * - Работа с URI
 * - Настройка конфигурации
 *
 * @package FaustVik\Router\interfaces\Router
 */
interface RouterInterface
{
    /**
     * Запускает обработку HTTP запроса
     *
     * Основной метод роутера, который:
     * - Парсит URI запроса
     * - Находит подходящий маршрут
     * - Валидирует параметры
     * - Выполняет middleware
     * - Запускает контроллер
     * - Отправляет ответ
     *
     * @return void
     */
    public function run(): void;

    /**
     * Устанавливает коллекцию маршрутов
     *
     * @param RoutesCollection $collection Коллекция маршрутов для обработки
     * @return self Возвращает текущий экземпляр для цепочки вызовов
     */
    public function setCollection(RoutesCollection $collection): self;

    /**
     * Устанавливает URI для обработки
     *
     * Используется для тестирования или когда нужно обработать
     * конкретный URI не из $_SERVER['REQUEST_URI']
     *
     * @param string $uri URI для обработки
     * @return self Возвращает текущий экземпляр для цепочки вызовов
     */
    public function setUri(string $uri): self;

    /**
     * Получает URI для обработки
     *
     * Если URI не был установлен явно, возвращает из $_SERVER['REQUEST_URI']
     *
     * @return string Текущий URI для обработки
     */
    public function getUri(): string;

    /**
     * Устанавливает конфигурацию роутера
     *
     * @param ConfigInterface $config Конфигурация для установки
     * @return void
     */
    public function setConfig(ConfigInterface $config): void;

    /**
     * Получает текущую конфигурацию роутера
     *
     * @return ConfigInterface Текущая конфигурация роутера
     */
    public function getConfig(): ConfigInterface;
}
