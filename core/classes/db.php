<?php
declare(strict_types=1);

if (!defined('FastCore')) {
    exit('Oops!');
}

class db {
    /** @var mysqli */
    protected $connection;

    /** @var ?mysqli_stmt */
    protected $query = null;

    /** @var bool */
    protected $show_errors = false;

    /** @var bool */
    protected $throw_exceptions = false;

    /** @var bool */
    protected $query_closed = true;

    /** @var int */
    public $query_count = 0;

    /** @var ?string */
    protected $last_error = null;

    /** @var ?string */
    protected $last_query = null;

    /**
     * Keep constructor signature for compatibility.
     * You can still pass a different charset (defaults to utf8mb4).
     */
    public function __construct(
        string $dbhost = 'localhost',
        string $dbuser = 'root',
        string $dbpass = '',
        string $dbname = '',
        string $charset = 'utf8mb4'
    ) {
        // Connect
        $this->connection = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
        if ($this->connection->connect_error) {
            $this->error('Failed to connect to MySQL - ' . $this->connection->connect_error);
            return;
        }

        // Set connection charset
        if (!$this->connection->set_charset($charset)) {
            $this->error('Failed to set charset to ' . $charset . ' - ' . $this->connection->error);
        }

        // Optional: return native int/float types when appropriate (bind_result still defines types explicitly)
        if (defined('MYSQLI_OPT_INT_AND_FLOAT_NATIVE')) {
            @$this->connection->options(MYSQLI_OPT_INT_AND_FLOAT_NATIVE, 1);
        }
    }

    /** Toggle dev error echoing (false by default) */
    public function setShowErrors(bool $show): void {
        $this->show_errors = $show;
    }

    /** Throw exceptions on errors instead of silent failure/exit (false by default) */
    public function setThrowExceptions(bool $throw): void {
        $this->throw_exceptions = $throw;
    }

    /** Get last error string (if any) */
    public function getLastError(): ?string {
        return $this->last_error;
    }

    /** Get last executed SQL (without parameters) */
    public function getLastQuery(): ?string {
        return $this->last_query;
    }

    /**
     * Prepare & execute a statement.
     * Usage:
     *   $db->query('SELECT * FROM users WHERE id=?', $id);
     *   $db->query('INSERT INTO t(a,b) VALUES(?,?)', $a, $b);
     *   $db->query('UPDATE t SET x=? WHERE y=?', ...[$x, $y]); // array also OK
     */
    public function query(string $query, ...$params) {
        // Allow passing a single array of params
        if (count($params) === 1 && is_array($params[0])) {
            $params = $params[0];
        }

        // Close previous statement if open
        if (!$this->query_closed && $this->query instanceof mysqli_stmt) {
            @$this->query->close();
        }

        $this->last_query = $query;
        $this->last_error = null;

        $stmt = $this->connection->prepare($query);
        if (!$stmt) {
            $this->error('Unable to prepare MySQL statement - ' . $this->connection->error);
            return $this;
        }

        // Bind params if provided
        if (!empty($params)) {
            $types = '';
            foreach ($params as $param) {
                $types .= $this->_gettype($param);
            }
            // Note: bind_param requires references in older PHP, but argument unpacking works here.
            if (!$stmt->bind_param($types, ...$params)) {
                $this->error('Failed to bind parameters - ' . $stmt->error);
                @$stmt->close();
                return $this;
            }
        }

        // Execute
        if (!$stmt->execute()) {
            $this->error('Unable to process MySQL query - ' . $stmt->error);
            @$stmt->close();
            return $this;
        }

        $this->query = $stmt;
        $this->query_closed = false;
        $this->query_count++;

        return $this;
    }

    /** Fetch all rows as array<assoc>. If a callback is provided, it will be invoked per row (original behavior preserved). */
    public function fetchAll($callback = null): array {
        if (!$this->query instanceof mysqli_stmt) {
            return [];
        }

        // No result set (e.g., INSERT/UPDATE/DELETE)
        if ($this->query->field_count === 0) {
            $this->query->close();
            $this->query_closed = true;
            return [];
        }

        $params = [];
        $row = [];
        $meta = $this->query->result_metadata();
        if ($meta) {
            while ($field = $meta->fetch_field()) {
                $params[] = &$row[$field->name];
            }
            $meta->free();
        }

        if (!empty($params)) {
            @call_user_func_array([$this->query, 'bind_result'], $params);
        }

        $result = [];
        while ($this->query->fetch()) {
            $r = [];
            foreach ($row as $key => $val) {
                $r[$key] = $val;
            }
            if ($callback !== null && is_callable($callback)) {
                $value = $callback($r);
                if ($value === 'break') {
                    break;
                }
            } else {
                $result[] = $r;
            }
        }

        $this->query->close();
        $this->query_closed = true;
        return $result;
    }

    /** Fetch first row as assoc array (or empty array if none). */
    public function fetchArray(): array {
        if (!$this->query instanceof mysqli_stmt) {
            return [];
        }

        // No result set (e.g., INSERT/UPDATE/DELETE)
        if ($this->query->field_count === 0) {
            $this->query->close();
            $this->query_closed = true;
            return [];
        }

        $params = [];
        $row = [];
        $meta = $this->query->result_metadata();
        if ($meta) {
            while ($field = $meta->fetch_field()) {
                $params[] = &$row[$field->name];
            }
            $meta->free();
        }

        if (!empty($params)) {
            @call_user_func_array([$this->query, 'bind_result'], $params);
        }

        $result = [];
        if ($this->query->fetch()) {
            foreach ($row as $key => $val) {
                $result[$key] = $val;
            }
        }

        $this->query->close();
        $this->query_closed = true;
        return $result;
    }

    /** Close the underlying connection */
    public function close() {
        if ($this->query instanceof mysqli_stmt && !$this->query_closed) {
            @$this->query->close();
        }
        return $this->connection->close();
    }

    /** Number of rows in the current SELECT result (or affected rows for non-SELECT) */
    public function numRows(): int {
        if (!$this->query instanceof mysqli_stmt) {
            return 0;
        }
        if ($this->query->field_count === 0) {
            // Not a SELECT; return affected rows
            return $this->query->affected_rows;
        }
        $this->query->store_result();
        return $this->query->num_rows;
    }

    /** Affected rows for the last DML */
    public function affectedRows(): int {
        return ($this->query instanceof mysqli_stmt) ? $this->query->affected_rows : 0;
    }

    /** Last insert id */
    public function lastInsert(): int {
        return (int)$this->connection->insert_id;
    }

    /** Begin transaction */
    public function begin(): void {
        $this->connection->begin_transaction();
    }

    /** Commit transaction */
    public function commit(): void {
        $this->connection->commit();
    }

    /** Rollback transaction */
    public function rollback(): void {
        $this->connection->rollback();
    }

    /** Simple helpers */
    public function select(string $sql, ...$params): array {
        return $this->query($sql, ...$params)->fetchAll();
    }

    public function selectRow(string $sql, ...$params): array {
        return $this->query($sql, ...$params)->fetchArray();
    }

    /** Return the first column of the first row, or null */
    public function selectValue(string $sql, ...$params) {
        $row = $this->query($sql, ...$params)->fetchArray();
        if (!$row) return null;
        // Return first value
        foreach ($row as $v) { return $v; }
        return null;
    }

    /** Escape a raw string (rarely needed if you always use prepared statements) */
    public function escape(string $value): string {
        return $this->connection->real_escape_string($value);
    }

    /** Escape a value for LIKE clause, adding wildcards around it by default */
    public function escapeLike(string $value, bool $wrap = true): string {
        $v = $this->connection->real_escape_string($value);
        $v = strtr($v, ['%' => '\%', '_' => '\_']);
        return $wrap ? "%{$v}%" : $v;
    }

    /** Centralized error handling (log, echo in dev, or throw) */
    public function error(string $error): void {
        $this->last_error = $error;
        error_log('[DB] ' . $error);

        if ($this->throw_exceptions) {
            throw new RuntimeException($error);
        }

        if ($this->show_errors) {
            // Keep original behavior
            exit($error);
        }
        // Otherwise: fail silently but record last_error; caller can inspect with getLastError()
    }

    /** Infer mysqli bind type from PHP var */
    private function _gettype($var): string {
        if (is_int($var) || is_bool($var)) return 'i';
        if (is_float($var)) return 'd';
        if (is_null($var)) return 's'; // NULLs are fine bound as 's'
        return 's'; // default to string for everything else
    }
}
