# SQLiteDB PHP

![SQLiteDB logo](./assets/sqlite-logo.png)  

[![Latest Stable Version](https://poser.pugx.org/andrey-tech/sqlitedb-php/v)](//packagist.org/packages/andrey-tech/sqlitedb-php)
[![PHP Version Require](http://poser.pugx.org/andrey-tech/sqlitedb-php/require/php)](//packagist.org/packages/andrey-tech/sqlitedb-php)
[![License](https://poser.pugx.org/andrey-tech/sqlitedb-php/license)](//packagist.org/packages/andrey-tech/sqlitedb-php)

Простая библиотека для работы с СУБД SQLite 3 для несложных проектов на PHP7+.

## Содержание

<!-- MarkdownTOC levels="1,2,3,4,5,6" autoanchor="true" autolink="true" -->

- [Установка](#%D0%A3%D1%81%D1%82%D0%B0%D0%BD%D0%BE%D0%B2%D0%BA%D0%B0)
- [Класс `SQLiteDB`](#%D0%9A%D0%BB%D0%B0%D1%81%D1%81-sqlitedb)
- [Примеры](#%D0%9F%D1%80%D0%B8%D0%BC%D0%B5%D1%80%D1%8B)
- [Автор](#%D0%90%D0%B2%D1%82%D0%BE%D1%80)
- [Лицензия](#%D0%9B%D0%B8%D1%86%D0%B5%D0%BD%D0%B7%D0%B8%D1%8F)

<!-- /MarkdownTOC -->

<a id="%D0%A3%D1%81%D1%82%D0%B0%D0%BD%D0%BE%D0%B2%D0%BA%D0%B0"></a>
## Установка

```
$ composer require andrey-tech/sqlitedb-php:"^3.0"
```

<a id="%D0%9A%D0%BB%D0%B0%D1%81%D1%81-sqlitedb"></a>
## Класс `SQLiteDB`

Финальный класс `\AndreyTech\SQLiteDB\SQLiteDB` предназначен для работы с СУБД SQLite 3.  
При возникновении ошибок в классах пространства имен `\AndreyTech\SQLiteDB` выбрасывается исключение класса `\AndreyTech\SQLiteDB\SQLiteDBException`.  

Класс `\AndreyTech\SQLiteDB\SQLiteDB` содержит следующие общие публичные методы:

- `__construct(array $config = [], array $options = []): SQLiteDB` Конструктор класса.
    + `$config` - конфигурация соединения с СУБД;
    + `$options` - опции подключения для драйвера PDO.
- `connect(): void` Выполняет подключение к серверу СУБД. В обычных условиях не требуется, так как подключение к серверу СУБД выполняется автоматически при первом запросе.
- `disconnect(): void` Выполняет отключение от сервера СУБД. В обычных условиях не требуется, так как отключение от сервера СУБД выполняется автоматически при уничтожении объекта класса.
- `getDSN(): string` Возвращает строку [DSN](https://en.wikipedia.org/wiki/Data_source_name) подключения к серверу СУБД. 
- `getConfig(): array` Возвращает конфигурацию соединения с СУБД.
- `getOptions(): array` Возвращает опции подключения для драйвера PDO.
- `getPDO(): ?PDO` Возвращает объект класса `\PDO`, если соединение с СУБД установлено.
- `isConnected(): bool` Возвращает флаг соединения с СУБД: `true` - соединение установлено, `false` - не установлено.
- `getDebugMode(): bool` Возвращает флаг состояния отладочного режима.
- `setDebugMode(bool $debugMode): void` Включает или отключает отладочный режим работы с выводом информации в `STDOUT`.
- `doStatement(string $statement, array $values = [], array $prepareOptions = []): \PDOStatement`  
    Подготавливает запрос, кэширует подготовленный запрос и запускает подготовленный запрос на выполнение.  
    Возвращает объект класса `\PDOStatement`.
    + `$statement` - SQL оператор;
    + `$values` - массив значений для SQL оператора;
    + `$prepareOptions` - опции драйвера СУБД для подготовки запроса.
- `beginTransaction(): void` Инициализирует транзакцию.
- `commitTransaction(): void` Фиксирует транзакцию.
- `rollbackTransaction(): void` Откатывает транзакцию.
- `fetchAll(\PDOStatement $stmt): \Generator` Позволяет выбирать все записи с помощью генератора.  
    + `$stmt` - объект класса `\PDOStatement`.
- `getLastInsertId(string $idName = null): string` Возвращает значение `id` последней вставленной записи.
    + `$idName` - имя столбца `id`.
- `createInStatement(array $in = []): string` Создает и возвращает строку для выражения `IN (?, ?, ?,...)`.
    + `$in` - массив значений внутри выражения `IN (?, ?, ?,...)`.

Существуют следующие параметры конфигурации и опции подключения с установленными значениями по умолчанию:

```php
$config = [
    'database' => './db.sqlite' // Имя файла СУБД SQLite
    'username' => null, // Имя пользователя
    'password' => null, // Пароль пользователя 
];

$options = [
     PDO::ATTR_TIMEOUT            => 60,
     PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
     PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
];    
```

<a id="%D0%9F%D1%80%D0%B8%D0%BC%D0%B5%D1%80%D1%8B"></a>
## Примеры

```php
use AndreyTech\SQLiteDB\SQLiteDB;
use AndreyTech\SQLiteDB\SQLiteDBException;

try {

    // Устанавливаем имя файла СУБД SQLiteDB
    $config = [
        'database' => 'my_database.sqlite',
    ];

    $db = new SQLiteDB($config);
    
    // Включаем отладочный режим с выводом информации в STDOUT
    $db->setDebugMode(true);

    // Отправляем запрос без параметров
    $stmt = $db->doStatement('
        SELECT COUNT(*) AS count
        FROM contacts
    ');
    
    // Выбираем все записи
    print_r($stmt->fetchAll());
    
    // Отправляем с использованием именованных параметров
    $stmt = $db->doStatement('
        SELECT * 
        FROM contacts
        WHERE status = :status
        LIMIT 10
    ', [ 'status' => 1 ]);
    
    // Выбираем все записи
    print_r($stmt->fetchAll());

    // Отправляем запрос с использованием НЕ именованных параметров
    $stmt = $db->doStatement('
        SELECT * 
        FROM contacts
        WHERE status = ?
    ', [ 1 ]);

    // Выбираем все записи с помощью генератора
    $generator = $db->fetchAll($stmt);
    foreach ($generator as $row) {
        print_r($row);
    }

} catch (SQLiteDBException $exception) {
    printf('SQLiteDB exception (%u): %s', $exception->getCode(), $exception->getMessage());
}
```

Пример вывода отладочной информации в STDOUT:

```
***** CONNECT "sqlite:my_database.sqlite"
***** [1]  SELECT COUNT(*) AS count FROM contacts 
***** [2]  SELECT * FROM contacts WHERE status = 1 LIMIT 10 
***** [3]  SELECT * FROM contacts WHERE status = 1 
***** DISCONNECT "sqlite:my_database.sqlite"
```

<a id="%D0%90%D0%B2%D1%82%D0%BE%D1%80"></a>
## Автор

© 2019-2023 andrey-tech

<a id="%D0%9B%D0%B8%D1%86%D0%B5%D0%BD%D0%B7%D0%B8%D1%8F"></a>
## Лицензия

Данная библиотека распространяется на условиях лицензии [MIT](./LICENSE).
