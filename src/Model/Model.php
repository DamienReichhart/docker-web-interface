<?php /** @noinspection PhpUnused */

/** @noinspection SqlNoDataSourceInspection */

namespace App\Model;

use Exception;
use PDO;
use PDOException;
use RuntimeException;

abstract class Model
{
    private static PDO $connection;

    // Child classes should define these properties
    protected static string $table;
    public static array $columns = [];
    public static array $foreignKeys = [];

    protected array $attributes = [];
    protected static array $primaryKey = ['id'];

    /** @noinspection GlobalVariableUsageInspection */
    /**
     * @throws Exception
     */
    private static function initializeConnection(): void
    {
        if (!isset(self::$connection)) {
            try {
                $dsn = $_ENV['DB_CONNECTION'] . ':' . $_ENV['DB_HOST'] . ';dbname=' . $_ENV['DB_DATABASE'] . ';charset=utf8';
                $username = $_ENV['DB_USERNAME'];
                $password = $_ENV['DB_PASSWORD'];

                self::$connection = new PDO($dsn, $username, $password);
                self::$connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            } catch (PDOException $e) {
                throw new RuntimeException('Database connection error: ' . $e->getMessage());
            }
        }
    }

    /**
     * @throws Exception
     */
    public function __construct(array $primaryKeyValues = [])
    {
        self::initializeConnection();
        if (!empty($primaryKeyValues)) {
            foreach ($primaryKeyValues as $key => $value) {
                $this->attributes[$key] = $value;
            }
            $this->load();
        }
    }

    /**
     * @throws Exception
     */
    public function __get(string $name): mixed
    {
        if (in_array($name, static::$columns, true)) {
            return $this->attributes[$name] ?? null;
        }

        throw new RuntimeException("Property '$name' does not exist on " . static::$table);
    }

    /**
     * @throws Exception
     */
    public function __set(string $name, mixed $value): void
    {
        if (in_array($name, static::$columns, true)) {
            $this->attributes[$name] = $value;
        } else {
            throw new RuntimeException("Property '$name' does not exist on " . static::$table);
        }
    }

    /**
     * @throws Exception
     */
    public function __isset(string $name): bool
    {
        if (in_array($name, static::$columns, true)) {
            return isset($this->attributes[$name]);
        }

        throw new RuntimeException("Property '$name' does not exist on " . static::$table);
    }

    /**
     * @throws Exception
     */
    private function load(): void
    {
        if (empty(static::$primaryKey)) {
            throw new RuntimeException("Cannot load a record without a primary key defined.");
        }

        $keyValues = $this->getPrimaryKeyAttributes();
        $where = $this->buildWhereClause($keyValues);

        $sql = "SELECT * FROM " . static::$table . " WHERE " . $where['clause'];
        $stmt = self::$connection->prepare($sql);

        foreach ($where['params'] as $param => $value) {
            $stmt->bindValue($param, $value);
        }

        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($result) {
            $this->attributes = array_merge($this->attributes, $result);
        } else {
            throw new RuntimeException("Record not found in " . static::$table);
        }
    }

    private function getPrimaryKeyAttributes(): array
    {
        $primaryKeyAttributes = [];
        foreach (static::$primaryKey as $keyColumn) {
            if (!isset($this->attributes[$keyColumn])) {
                throw new RuntimeException("Primary key attribute '$keyColumn' is not set.");
            }
            $primaryKeyAttributes[$keyColumn] = $this->attributes[$keyColumn];
        }
        return $primaryKeyAttributes;
    }

    private function buildWhereClause(array $keyValues): array
    {
        $whereClauses = [];
        $params = [];

        foreach ($keyValues as $keyColumn => $value) {
            $whereClauses[] = "$keyColumn = :$keyColumn";
            $params[":$keyColumn"] = $value;
        }

        $whereClause = implode(' AND ', $whereClauses);

        return ['clause' => $whereClause, 'params' => $params];
    }

    final public function save(): void
    {
        if ($this->hasPrimaryKeyValues()) {
            $this->update();
        } else {
            $this->insert();
        }
    }

    private function hasPrimaryKeyValues(): bool
    {
        foreach (static::$primaryKey as $keyColumn) {
            if (!isset($this->attributes[$keyColumn])) {
                return false;
            }
        }
        return true;
    }

    final public function insert(): void
    {
        $columns = array_keys($this->attributes);
        $placeholders = array_map(static fn($col) => ":$col", $columns);

        $sql = "INSERT INTO " . static::$table . " (" . implode(", ", $columns) . ") VALUES (" . implode(", ", $placeholders) . ")";
        $stmt = self::$connection->prepare($sql);

        foreach ($this->attributes as $key => $value) {
            $stmt->bindValue(":$key", $value);
        }

        $stmt->execute();

        // Handle auto-increment primary key
        if (in_array('id', static::$primaryKey, true) && !isset($this->attributes['id'])) {
            $this->attributes['id'] = self::$connection->lastInsertId();
        }
    }

    final public function update(): void
    {
        $columns = array_keys($this->attributes);
        $setString = implode(", ", array_map(static fn($col) => "$col = :$col", $columns));

        $keyValues = $this->getPrimaryKeyAttributes();
        $where = $this->buildWhereClause($keyValues);

        $sql = "UPDATE " . static::$table . " SET $setString WHERE " . $where['clause'];
        $stmt = self::$connection->prepare($sql);

        foreach ($this->attributes as $key => $value) {
            $stmt->bindValue(":$key", $value);
        }

        $stmt->execute();
    }

    /**
     * @throws Exception
     */
    final public function delete(): void
    {
        if (empty(static::$primaryKey)) {
            throw new RuntimeException("Cannot delete a record without a primary key defined.");
        }

        $keyValues = $this->getPrimaryKeyAttributes();
        $where = $this->buildWhereClause($keyValues);

        $sql = "DELETE FROM " . static::$table . " WHERE " . $where['clause'];
        $stmt = self::$connection->prepare($sql);

        foreach ($where['params'] as $param => $value) {
            $stmt->bindValue($param, $value);
        }

        $stmt->execute();

        $this->attributes = [];
    }

    /**
     * @throws Exception
     */
    final public static function all(): array
    {
        self::initializeConnection();

        $sql = "SELECT * FROM " . static::$table;
        $stmt = self::$connection->query($sql);
        $dbOutput = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $result = [];
        foreach ($dbOutput as $row) {
            $result[] = static::fromArray($row);
        }

        return $result;
    }

    /**
     * @throws Exception
     */
    final public static function getByProperty(string $property, mixed $value): array
    {
        self::initializeConnection();

        if (!in_array($property, static::$columns, true)) {
            throw new RuntimeException("Property '$property' does not exist on " . static::$table);
        }

        $sql = "SELECT * FROM " . static::$table . " WHERE $property = :value";
        $stmt = self::$connection->prepare($sql);
        $stmt->bindValue(':value', $value);
        $stmt->execute();
        $dbOutput = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $result = [];
        foreach ($dbOutput as $row) {
            $result[] = static::fromArray($row);
        }

        return $result;
    }

    final public function getForeignKey(string $property): mixed
    {
        if (!array_key_exists($property, static::$foreignKeys)) {
            throw new RuntimeException("Property '$property' is not a foreign key on " . static::$table);
        }

        $foreignKeyClass = static::$foreignKeys[$property];
        return new $foreignKeyClass($this->attributes[$property]);
    }

    final public static function fromArray(array $data): static
    {
        $model = new static();
        foreach ($data as $key => $value) {
            $model->$key = $value;
        }
        return $model;
    }

    /**
     * @throws Exception
     */
    final public static function getWhere(array $filters): array
    {
        self::initializeConnection();
        $sql = "SELECT * FROM " . static::$table . " WHERE ";
        $where = [];
        foreach ($filters as $key => $value) {
            $where[] = "$key = :$key";
        }
        $sql .= implode(" AND ", $where);
        $stmt = self::$connection->prepare($sql);
        foreach ($filters as $key => $value) {
            $stmt->bindValue(":$key", $value);
        }
        $stmt->execute();
        $dbOutput = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $result = [];
        foreach ($dbOutput as $row) {
            $result[] = static::fromArray($row);
        }

        return $result;
    }

    final public function getDisplayName(): string
    {
        if (isset($this->attributes['name']) || isset($this->attributes['nameServers'])) {
            return $this->attributes['name'] ?? $this->attributes['nameServers'];
        }

        $columns = array_diff(static::$columns, ['id']);
        foreach ($columns as $col) {
            if (isset($this->attributes[$col])) {
                return $this->attributes[$col];
            }
        }
        return '';
    }
}
