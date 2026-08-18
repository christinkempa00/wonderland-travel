<?php
/**
 * ================================================================
 * TRAVEL MANAGEMENT SYSTEM - Base Model Class
 * ================================================================
 */

// Prevent direct access
if (!defined('BASE_PATH')) {
    exit('No direct script access allowed');
}

/**
 * Base Model Class
 * Provides common database operations for all models
 */
abstract class Model {
    
    /**
     * Table name - must be defined in child classes
     */
    protected static string $table = '';
    
    /**
     * Primary key column
     */
    protected static string $primaryKey = 'id';
    
    /**
     * Columns that can be mass assigned
     */
    protected static array $fillable = [];
    
    /**
     * Columns that should be hidden from arrays
     */
    protected static array $hidden = [];
    
    /**
     * Default order by
     */
    protected static string $orderBy = 'id';
    protected static string $orderDir = 'DESC';
    
    /**
     * Enable soft deletes
     */
    protected static bool $softDeletes = false;
    
    /**
     * Timestamps columns
     */
    protected static bool $timestamps = true;
    
    /**
     * Model data
     */
    protected array $data = [];
    protected array $original = [];
    protected bool $exists = false;
    
    /**
     * Constructor
     */
    public function __construct(array $data = []) {
        $this->fill($data);
    }
    
    /**
     * Get table name
     */
    public static function getTable(): string {
        return static::$table;
    }
    
    /**
     * Get database instance
     */
    protected static function db(): Database {
        return Database::getInstance();
    }
    
    /**
     * Fill model with data
     */
    public function fill(array $data): self {
        foreach ($data as $key => $value) {
            $this->data[$key] = $value;
        }
        return $this;
    }
    
    /**
     * Get attribute
     */
    public function __get(string $key) {
        return $this->data[$key] ?? null;
    }
    
    /**
     * Set attribute
     */
    public function __set(string $key, $value): void {
        $this->data[$key] = $value;
    }
    
    /**
     * Check if attribute exists
     */
    public function __isset(string $key): bool {
        return isset($this->data[$key]);
    }
    
    /**
     * Get all data as array
     */
    public function toArray(): array {
        $data = $this->data;
        
        // Remove hidden fields
        foreach (static::$hidden as $hidden) {
            unset($data[$hidden]);
        }
        
        return $data;
    }
    
    /**
     * Get raw data
     */
    public function getData(): array {
        return $this->data;
    }
    
    /**
     * Find by ID
     */
    public static function find(int $id): ?static {
        $sql = "SELECT * FROM " . static::$table . " WHERE " . static::$primaryKey . " = ?";
        
        if (static::$softDeletes) {
            $sql .= " AND deleted_at IS NULL";
        }
        
        $row = static::db()->fetchOne($sql, [$id]);
        
        if (!$row) {
            return null;
        }
        
        $model = new static($row);
        $model->exists = true;
        $model->original = $row;
        
        return $model;
    }
    
    /**
     * Find by ID or throw exception
     */
    public static function findOrFail(int $id): static {
        $model = static::find($id);
        
        if (!$model) {
            throw new Exception(static::class . " with ID {$id} not found");
        }
        
        return $model;
    }
    
    /**
     * Find by column
     */
    public static function findBy(string $column, $value): ?static {
        $sql = "SELECT * FROM " . static::$table . " WHERE {$column} = ?";
        
        if (static::$softDeletes) {
            $sql .= " AND deleted_at IS NULL";
        }
        
        $sql .= " LIMIT 1";
        
        $row = static::db()->fetchOne($sql, [$value]);
        
        if (!$row) {
            return null;
        }
        
        $model = new static($row);
        $model->exists = true;
        $model->original = $row;
        
        return $model;
    }
    
    /**
     * Get all records
     */
    public static function all(array $conditions = []): array {
        $sql = "SELECT * FROM " . static::$table;
        $params = [];
        
        $where = [];
        
        if (static::$softDeletes) {
            $where[] = "deleted_at IS NULL";
        }
        
        foreach ($conditions as $column => $value) {
            if (is_array($value)) {
                $placeholders = implode(',', array_fill(0, count($value), '?'));
                $where[] = "{$column} IN ({$placeholders})";
                $params = array_merge($params, $value);
            } else {
                $where[] = "{$column} = ?";
                $params[] = $value;
            }
        }
        
        if (!empty($where)) {
            $sql .= " WHERE " . implode(' AND ', $where);
        }
        
        $sql .= " ORDER BY " . static::$orderBy . " " . static::$orderDir;
        
        $rows = static::db()->fetchAll($sql, $params);
        
        return array_map(function($row) {
            $model = new static($row);
            $model->exists = true;
            $model->original = $row;
            return $model;
        }, $rows);
    }
    
    /**
     * Get records with pagination
     */
    public static function paginate(int $page = 1, int $perPage = 10, array $conditions = [], string $search = '', array $searchColumns = []): array {
        $offset = ($page - 1) * $perPage;
        $params = [];
        $where = [];
        
        if (static::$softDeletes) {
            $where[] = "deleted_at IS NULL";
        }
        
        foreach ($conditions as $column => $value) {
            if ($value === null) {
                $where[] = "{$column} IS NULL";
            } elseif (is_array($value)) {
                $placeholders = implode(',', array_fill(0, count($value), '?'));
                $where[] = "{$column} IN ({$placeholders})";
                $params = array_merge($params, $value);
            } else {
                $where[] = "{$column} = ?";
                $params[] = $value;
            }
        }
        
        // Search
        if ($search && !empty($searchColumns)) {
            $searchWhere = [];
            foreach ($searchColumns as $col) {
                $searchWhere[] = "{$col} LIKE ?";
                $params[] = "%{$search}%";
            }
            $where[] = "(" . implode(' OR ', $searchWhere) . ")";
        }
        
        $whereClause = !empty($where) ? " WHERE " . implode(' AND ', $where) : "";
        
        // Count total
        $countSql = "SELECT COUNT(*) FROM " . static::$table . $whereClause;
        $total = (int) static::db()->fetchColumn($countSql, $params);
        
        // Get data
        $sql = "SELECT * FROM " . static::$table . $whereClause;
        $sql .= " ORDER BY " . static::$orderBy . " " . static::$orderDir;
        $sql .= " LIMIT {$perPage} OFFSET {$offset}";
        
        $rows = static::db()->fetchAll($sql, $params);
        
        // Return as arrays (not objects) for simpler view access
        $data = $rows;
        
        return [
            'data' => $data,
            'total' => $total,
            'per_page' => $perPage,
            'current_page' => $page,
            'last_page' => ceil($total / $perPage) ?: 1,
            'from' => $total > 0 ? $offset + 1 : 0,
            'to' => min($offset + $perPage, $total)
        ];
    }
    
    /**
     * Create new record
     */
    public static function create(array $data): static {
        // Filter fillable
        if (!empty(static::$fillable)) {
            $data = array_intersect_key($data, array_flip(static::$fillable));
        }
        
        // Add timestamps
        if (static::$timestamps) {
            $data['created_at'] = date('Y-m-d H:i:s');
        }
        
        $id = static::db()->insert(static::$table, $data);
        
        return static::find($id);
    }
    
    /**
     * Update record
     */
    public function update(array $data): bool {
        // Filter fillable
        if (!empty(static::$fillable)) {
            $data = array_intersect_key($data, array_flip(static::$fillable));
        }
        
        // Add timestamps
        if (static::$timestamps) {
            $data['updated_at'] = date('Y-m-d H:i:s');
        }
        
        $id = $this->data[static::$primaryKey];
        
        $affected = static::db()->update(
            static::$table,
            $data,
            static::$primaryKey . " = ?",
            [$id]
        );
        
        if ($affected) {
            $this->fill($data);
        }
        
        return $affected > 0;
    }
    
    /**
     * Save model (create or update)
     */
    public function save(): bool {
        if ($this->exists) {
            return $this->update($this->data);
        }
        
        $model = static::create($this->data);
        $this->data = $model->getData();
        $this->exists = true;
        
        return true;
    }
    
    /**
     * Delete record
     */
    public function delete(): bool {
        $id = $this->data[static::$primaryKey];
        
        if (static::$softDeletes) {
            return $this->update(['deleted_at' => date('Y-m-d H:i:s')]);
        }
        
        $affected = static::db()->delete(
            static::$table,
            static::$primaryKey . " = ?",
            [$id]
        );
        
        return $affected > 0;
    }
    
    /**
     * Force delete (bypass soft delete)
     */
    public function forceDelete(): bool {
        $id = $this->data[static::$primaryKey];
        
        $affected = static::db()->delete(
            static::$table,
            static::$primaryKey . " = ?",
            [$id]
        );
        
        return $affected > 0;
    }
    
    /**
     * Restore soft deleted record
     */
    public function restore(): bool {
        if (!static::$softDeletes) {
            return false;
        }
        
        return $this->update(['deleted_at' => null]);
    }
    
    /**
     * Count records
     */
    public static function count(array $conditions = []): int {
        $sql = "SELECT COUNT(*) FROM " . static::$table;
        $params = [];
        $where = [];
        
        if (static::$softDeletes) {
            $where[] = "deleted_at IS NULL";
        }
        
        foreach ($conditions as $column => $value) {
            $where[] = "{$column} = ?";
            $params[] = $value;
        }
        
        if (!empty($where)) {
            $sql .= " WHERE " . implode(' AND ', $where);
        }
        
        return (int) static::db()->fetchColumn($sql, $params);
    }
    
    /**
     * Check if record exists
     */
    public static function exists(array $conditions): bool {
        return static::count($conditions) > 0;
    }
    
    /**
     * Get first record matching conditions
     */
    public static function first(array $conditions = []): ?static {
        $sql = "SELECT * FROM " . static::$table;
        $params = [];
        $where = [];
        
        if (static::$softDeletes) {
            $where[] = "deleted_at IS NULL";
        }
        
        foreach ($conditions as $column => $value) {
            $where[] = "{$column} = ?";
            $params[] = $value;
        }
        
        if (!empty($where)) {
            $sql .= " WHERE " . implode(' AND ', $where);
        }
        
        $sql .= " ORDER BY " . static::$orderBy . " " . static::$orderDir;
        $sql .= " LIMIT 1";
        
        $row = static::db()->fetchOne($sql, $params);
        
        if (!$row) {
            return null;
        }
        
        $model = new static($row);
        $model->exists = true;
        $model->original = $row;
        
        return $model;
    }
    
    /**
     * Raw query
     */
    public static function query(string $sql, array $params = []): array {
        return static::db()->fetchAll($sql, $params);
    }
    
    /**
     * Get by company
     */
    public static function byCompany(int $companyId, array $conditions = []): array {
        $conditions['company_id'] = $companyId;
        return static::all($conditions);
    }
    
    /**
     * Sum column
     */
    public static function sum(string $column, array $conditions = []): float {
        $sql = "SELECT COALESCE(SUM({$column}), 0) FROM " . static::$table;
        $params = [];
        $where = [];
        
        if (static::$softDeletes) {
            $where[] = "deleted_at IS NULL";
        }
        
        foreach ($conditions as $col => $value) {
            $where[] = "{$col} = ?";
            $params[] = $value;
        }
        
        if (!empty($where)) {
            $sql .= " WHERE " . implode(' AND ', $where);
        }
        
        return (float) static::db()->fetchColumn($sql, $params);
    }
    
    /**
     * Pluck single column
     */
    public static function pluck(string $column, ?string $keyColumn = null, array $conditions = []): array {
        $select = $keyColumn ? "{$keyColumn}, {$column}" : $column;
        $sql = "SELECT {$select} FROM " . static::$table;
        $params = [];
        $where = [];
        
        if (static::$softDeletes) {
            $where[] = "deleted_at IS NULL";
        }
        
        foreach ($conditions as $col => $value) {
            $where[] = "{$col} = ?";
            $params[] = $value;
        }
        
        if (!empty($where)) {
            $sql .= " WHERE " . implode(' AND ', $where);
        }
        
        $sql .= " ORDER BY " . static::$orderBy . " " . static::$orderDir;
        
        $rows = static::db()->fetchAll($sql, $params);
        
        if ($keyColumn) {
            $result = [];
            foreach ($rows as $row) {
                $result[$row[$keyColumn]] = $row[$column];
            }
            return $result;
        }
        
        return array_column($rows, $column);
    }
}
