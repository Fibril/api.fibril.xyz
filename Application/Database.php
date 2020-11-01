<?php

abstract class Database
{
    private $connection;

    // public function __construct() // TODO: Try and make this a protected constructor.
    // {

    // }

    /**
     * Performs a query on the database.
     *
     * @param string $sql
     * @param array $data
     * @param bool $assoc Whether to return an associative array.
     * @return bool|array If the $fetchData parameter is true, then it fetches and returns 
     * the data as an associative array. Otherwise true on success, false on failure.
     */
    protected function query($sql, array $data, bool $fetchData = false)
    {
        if ($this->connection === null)
            $this->connect();

        $statement = $this->connection->prepare($sql);

        for ($i = 1; $i <= count($data); $i++)
        {
            // https://www.php.net/manual/en/pdostatement.bindparam.php
            // https://www.php.net/manual/en/pdo.constants.php

            $dataType = PDO::PARAM_STR;

            if (is_int($data[$i - 1]))
                $dataType = PDO::PARAM_INT;

            $statement->bindParam($i, $data[$i - 1], $dataType);
        }

        // $statement->debugDumpParams();

        $wasSuccessful = $statement->execute();

        if ($wasSuccessful && $fetchData)
            return $statement->fetchAll(PDO::FETCH_ASSOC);

        return $wasSuccessful;
    }

    /**
     * Prepares a query and gets a statement object in return.
     *
     * @param string $query The SQL query that'll be executed against the database.
     * @return PDOStatement The prepared statement.
     */
    protected function prepare($sql)
    {
        if ($this->connection === null)
            $this->connect();

        return $this->connection->prepare($sql);
    }

    /**
     * Opens the database connection.
     *
     * @throws Exception If the database connection could not be established.
     * @return void
     */
    private function connect()
    {
        $host = Config::get('database', 'host');
        $dbname = Config::get('database', 'dbname');
        $username = Config::get('database', 'username');
        $password = Config::get('database', 'password');

        try
        {
            $this->connection = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", "$username", "$password");
        }
        catch (PDOException $exception)
        {
            die('Failed to connect to the database ' . $exception->getMessage());
        }

        $this->connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION); // PDO::ERRMODE_SILENT
        $this->connection->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_OBJ);
    }

    /**
     * Closes the database connection.
     *
     * @return void
     */
    private function disconnect()
    {
        unset($this->connection);
    }
}