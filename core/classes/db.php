<?php
declare(strict_types=1);

if (!defined('FastCore')) {
    exit('Oops!');
}

/**
 * PDO-powered DB wrapper compatible with the previous mysqli-based API.
 * - Keeps the same class name `db` and public methods for minimal disruption.
 * - Uses prepared statements with native server prepares (emulation off).
 * - Defaults to utf8mb4, throws no warnings to the browser, and records last errors.
 */
class db {
    /** @var ?PDO */
    protected $connection = null;

    /** @var ?PDOStatement */
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

    /** Optional quick connectivity check */
    public function isConnected(): bool {
        return $this->connection instanceof PDO;
    }

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
        $dsn = sprintf('mysql:host=%s;dbname=%s;charset=%s', $dbhost, $dbname, $charset);

        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,  // use exceptions, we'll catch
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,                   // native prepares
            PDO::ATTR_STRINGIFY_FETCHES  => false,
        ];

        try {
            $this->connection = new PDO($dsn, $dbuser, $dbpass, $options);
        } catch (Throwable $e) {
            $this->connection = null;
            $this->error('Failed to connect to MySQL (PDO) - ' . $e->getMessage());
            return;
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
     *
     * Returns $this to keep chaining with fetchAll()/fetchArray().
     */
    public function query(string $query, ...$params) {
        // Allow passing a single array of params
        if (count($params) === 1 && is_array($params[0])) {
            $params = $params[0];
        }

        $this->last_query = $query;
        $this->last_error = null;

        // Close previous statement if open
        if (!$this->query_closed && $this->query instanceof PDOStatement) {
            $this->query->closeCursor();
        }

        if (!$this->isConnected()) {
            $this->error('No DB connection available when executing query.');
            $this->query_closed = true;
            // Return self for API consistency
            return $this;
        }

        try {
            $stmt = $this->connection->prepare($query);
        } catch (Throwable $e) {
            $this->error('Failed to prepare SQL - ' . $e->getMessage());
            $this->query_closed = true;
            return $this;
        }

        try {
            // Numeric indices are fine: PDO binds by position.
            $ok = $stmt->execute($params);
            if (!$ok) {
                $this->error('Unable to process SQL (execute returned false)');
                $stmt->closeCursor();
                $this->query_closed = true;
                return $this;
            }
        } catch (Throwable $e) {
            $this->error('Unable to process SQL - ' . $e->getMessage());
            $stmt->closeCursor();
            $this->query_closed = true;
            return $this;
        }

        $this->query = $stmt;
        $this->query_closed = false;
        $this->query_count++;

        return $this;
    }

    /** Fetch all rows as array<assoc>. If a callback is passed, it will be invoked per row. */
    public function fetchAll($callback = null): array {
        if (!$this->query instanceof PDOStatement) {
            return [];
        }

        // Detect non-SELECT (no result set)
        $columnCount = $this->query->columnCount();
        if ($columnCount === 0) {
            $this->query->closeCursor();
            $this->query_closed = true;
            return [];
        }

        $result = [];
        try {
            if ($callback !== null && is_callable($callback)) {
                while (true) {
                    $row = $this->query->fetch(PDO::FETCH_ASSOC);
                    if ($row === false) break;
                    $value = $callback($row);
                    if ($value === 'break') {
                        break;
                    }
                    // Keep parity with original: if callback provided, do not collect unless it pushes to external array
                }
            } else {
                $result = $this->query->fetchAll(PDO::FETCH_ASSOC);
            }
        } catch (Throwable $e) {
            $this->error('Fetch all failed - ' . $e->getMessage());
        }

        $this->query->closeCursor();
        $this->query_closed = true;
        return $result;
    }

    /** Fetch first row as assoc array (or empty array if none). */
    public function fetchArray(): array {
        if (!$this->query instanceof PDOStatement) {
            return [];
        }

        $result = [];
        try {
            $row = $this->query->fetch(PDO::FETCH_ASSOC);
            if ($row !== false) {
                $result = $row;
            }
        } catch (Throwable $e) {
            $this->error('Fetch first row failed - ' . $e->getMessage());
        }

        $this->query->closeCursor();
        $this->query_closed = true;
        return $result;
    }

    /** Rows affected for last DML */
    public function affectedRows(): int {
        return ($this->query instanceof PDOStatement) ? (int)$this->query->rowCount() : 0;
    }

    /** Last insert id */
    public function lastInsert(): int {
        if (!$this->isConnected()) return 0;
        $id = $this->connection->lastInsertId();
        return (int) (is_numeric($id) ? $id : 0);
    }

    /** Begin transaction */
    public function begin(): void {
        if ($this->isConnected()) {
            $this->connection->beginTransaction();
        }
    }

    /** Commit transaction */
    public function commit(): void {
        if ($this->isConnected() && $this->connection->inTransaction()) {
            $this->connection->commit();
        }
    }

    /** Rollback transaction */
    public function rollback(): void {
        if ($this->isConnected() && $this->connection->inTransaction()) {
            $this->connection->rollBack();
        }
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
        $first = array_key_first($row);
        return $first !== null ? $row[$first] : null;
    }

    /** Centralized error handling */
    protected function error(string $error): void {
        $this->last_error = $error;

        if ($this->throw_exceptions) {
            throw new RuntimeException($error);
        }

        if ($this->show_errors) {
            // Keep original behavior
            exit($error);
        }
        // Otherwise: fail silently but record last_error; caller can inspect with getLastError()
    }

    public function __destruct() {
        if ($this->query instanceof PDOStatement) {
            $this->query->closeCursor();
        }
        $this->query = null;
        $this->connection = null; // let PDO close gracefully
    }
}
