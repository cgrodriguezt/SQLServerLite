<?php

namespace Rmunate\SqlServerLite\Traits;

use PDO;
use Rmunate\SqlServerLite\Exceptions\SQLServerException;
use Rmunate\SqlServerLite\Utilities\Utilities;
use Rmunate\SqlServerLite\Validator\StatementsValidator;

trait Execute
{
    /**
     * verify the query send and execute it
     * @return method from the query send
     */
    private function execGeneral()
    {
        match ($this->operation) {
            'select'                          => $this->execSelect(),
            'update'                          => $this->execUpdate(),
            'insert'                          => $this->execInsert(),
            'insert_get_id'                   => $this->execInsertGetId(),
            'delete'                          => $this->execDelete(),
            'execute_procedure'               => $this->execProcedure(),
            'execute_transactional_procedure' => $this->execTransactionalProcedure(),
        };
    }

    /**
     * execute select query
     * @return array The result set as an array of associative arrays.
     */
    private function execSelect()
    {
        try {
            $PDO = $this->connection->prepare($this->statement);

            StatementsValidator::isValidParams($this->params);

            if (!empty($this->params)) {
                foreach ($this->params as $key => $value) {
                    if (strpos($this->statement, $key) !== false) {
                        $PDO->bindParam($key, $this->params[$key]);
                    }
                }
            }

            $PDO->execute();
            $rows = $PDO->fetchAll(PDO::FETCH_ASSOC);

            $this->response = $rows;

            return $this;
        } catch (\Exception $e) {
            throw SQLServerException::create('Error executing SQL Select Query: '.$e->getMessage());
        }
    }

    /**
     * execute update query
     * @return bool Returns true if the query is executed successfully, false otherwise.
     */
    private function execUpdate()
    {
        try {
            $this->inactivateCheckConstraint();

            $PDO = $this->connection->prepare($this->statement);

            StatementsValidator::isValidParams($this->params);

            if (!empty($this->params)) {
                foreach ($this->params as $key => $value) {
                    if (strpos($this->statement, $key) !== false) {
                        $PDO->bindParam($key, $this->params[$key]);
                    }
                }
            }

            $response = $PDO->execute();

            $this->response = $response && $PDO->rowCount() > 0;

            $this->activateCheckConstraint();
        } catch (\Exception $e) {
            throw SQLServerException::create('Error executing SQL Update Query: '.$e->getMessage());
        }
    }

    /**
     * Execute an INSERT query.
     * @return bool Returns true if the INSERT query was successful, false otherwise.
     */
    private function execInsert()
    {
        try {
            $this->inactivateCheckConstraint();

            if (!empty($this->params)) {
                $PDO = $this->connection->prepare($this->statement);

                if (Utilities::hasSubArrays($this->params)) {
                    foreach ($this->params as $key => $param) {
                        foreach ($param as $key => $value) {
                            if (strpos($this->statement, $key) !== false) {
                                $PDO->bindParam($key, $param[$key]);
                            }
                        }

                        $response = $PDO->execute();
                    }
                } else {
                    foreach ($this->params as $key => $value) {
                        if (strpos($this->statement, $key) !== false) {
                            $PDO->bindParam($key, $this->params[$key]);
                        }
                    }

                    $response = $PDO->execute();
                }

                $this->response = $response && $PDO->rowCount() > 0;
            } else {
                $response = $this->connection->exec($this->statement);

                $this->response = $response > 0;
            }

            $this->activateCheckConstraint();
        } catch (\Exception $e) {
            throw SQLServerException::create('Error executing SQL Insert Query: '.$e->getMessage());
        }
    }

    /**
     * Execute an INSERT query.
     * @return mixed The last inserted ID.
     */
    private function execInsertGetId()
    {
        try {
            $this->inactivateCheckConstraint();

            if (!empty($this->params)) {
                $PDO = $this->connection->prepare($this->statement);

                if (Utilities::hasSubArrays($this->params)) {
                    $ids = [];

                    foreach ($this->params as $key => $param) {
                        foreach ($param as $key => $value) {
                            if (strpos($this->statement, $key) !== false) {
                                $PDO->bindParam($key, $param[$key]);
                            }
                        }

                        $response = $PDO->execute();

                        array_push($ids, ($response && $PDO->rowCount() > 0) ? $this->connection->lastInsertId() : null);
                    }

                    $this->response = $ids;
                } else {
                    foreach ($this->params as $key => $value) {
                        if (strpos($this->statement, $key) !== false) {
                            $PDO->bindParam($key, $this->params[$key]);
                        }
                    }

                    $response = $PDO->execute();

                    $this->response = ($response && $PDO->rowCount() > 0) ? $this->connection->lastInsertId() : null;
                }
            } else {
                $response = $this->connection->exec($this->statement);

                $this->response = $response > 0;
            }

            $this->activateCheckConstraint();
        } catch (\Exception $e) {
            throw SQLServerException::create('Error executing SQL Insert Query: '.$e->getMessage());
        }
    }

    /**
     * Execute a DELETE query.
     * @return bool Returns true if the DELETE query was successful, false otherwise.
     */
    private function execDelete()
    {
        try {
            $this->inactivateCheckConstraint();

            if (!empty($this->params)) {
                $PDO = $this->connection->prepare($this->statement);

                foreach ($this->params as $key => $value) {
                    if (strpos($this->statement, $key) !== false) {
                        $PDO->bindParam($key, $this->params[$key]);
                    }
                }

                $PDO->execute();

                $this->response = $PDO->rowCount() > 0;
            } else {
                $PDO = $this->connection->exec($this->statement);

                $this->response = $PDO !== false;
            }

            $this->activateCheckConstraint();
        } catch (\Exception $e) {
            throw SQLServerException::create('Error executing SQL Delete Query: '.$e->getMessage());
        }
    }

    /**
     * execute store procedure
     * @return array The result set as an array of associative arrays.
     */
    private function execProcedure()
    {
        try {
            $PDO = $this->connection->prepare($this->statement);

            StatementsValidator::isValidParams($this->params);

            if (!empty($this->params)) {
                foreach ($this->params as $key => $value) {
                    if (strpos($this->statement, $key) !== false) {
                        $PDO->bindParam($key, $this->params[$key]);
                    }
                }
            }

            $PDO->execute();
            $rows = $PDO->fetchAll(PDO::FETCH_ASSOC);

            $this->response = $rows;

            return $this;
        } catch (\Exception $e) {
            throw SQLServerException::create('Error executing SQL Store Procedure Query: '.$e->getMessage());
        }
    }

    /**
     * execute store procedure
     * @return bool Returns true if the store procedure was successful, false otherwise.
     */
    private function execTransactionalProcedure()
    {
        try {
            $PDO = $this->connection->prepare($this->statement);

            StatementsValidator::isValidParams($this->params);

            if (!empty($this->params)) {
                foreach ($this->params as $key => $value) {
                    if (strpos($this->statement, $key) !== false) {
                        $PDO->bindParam($key, $this->params[$key]);
                    }
                }
            }

            $result = $PDO->execute();
            $PDO->closeCursor();

            $this->response = $result;
        } catch (\Exception $e) {
            throw SQLServerException::create('Error executing SQL Transactional Store Procedure Query: '.$e->getMessage());
        }
    }

    /**
     * Disable constraints for all tables or specified tables.
     * @return $this The current instance of the object.
     */
    private function inactivateCheckConstraint()
    {
        if ($this->constraints) {
            $nameTable = Utilities::getNameTable($this->statement);
            $stmt = "ALTER TABLE {$nameTable} NOCHECK CONSTRAINT ALL;";
            $this->connection->exec($stmt);
        }
    }

    /**
     * Disable constraints for all tables or specified tables.
     * @return $this The current instance of the object.
     */
    private function activateCheckConstraint()
    {
        if ($this->constraints) {
            $nameTable = Utilities::getNameTable($this->statement);
            $stmt = "ALTER TABLE {$nameTable} CHECK CONSTRAINT ALL;";
            $this->connection->exec($stmt);
        }
    }
}