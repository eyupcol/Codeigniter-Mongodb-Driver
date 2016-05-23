<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Mongodb Codeigniter Driver v1.0
 * Versions: CI 3.X , Mongodb 3.2+ (also can be used with CI 2.X) , PHP 5 and PHP 7
 * Author: el-ma
 */

class Mdb implements MDatabase
{
    private $host = 'localhost';
    private $port = '27017';
    private $dbname = '';
    private $user = '';
    private $password = '';

    private $connString = null;

    private $conn = null;
    private $err = array();
    private $insertId;
    private $cursor;
    
    private $queryOptions = array();


    /**
     * @param array $settings
     *
     */
    public function __construct($settings=null){
        if(isset($settings) && is_array($settings)){
            foreach($settings as $k=>$v){
                $this->{$k} = $v;
            }
        }
        $this->connect();
    }

    public function connect()
    {
        try {
            if ($this->connString === null) {
                $this->conn = new MongoDB\Driver\Manager("mongodb://" . $this->user . ":" . $this->password . "@" . $this->host . ":" . $this->port . "/" . $this->dbname);
            } else {
                $this->conn = new MongoDB\Driver\Manager($this->connString);
            }
            return $this;
        } catch (Exception $e) {
            $this->err[] = "At line ".$e->getLine()." an error occured. " . $e->getMessage(). ". (Connection error)";
            $this->errorLog();
        }
    }

    public function selectDb($db)
    {
        $this->dbname = $db;
        return $this;
    }

    public function insert($collection,$data)
    {
        try {
            if(!is_array($data)){
                throw new Exception('Invalid data.');
            }

            $bulk = new MongoDB\Driver\BulkWrite(['ordered' => true]);
            $id = $bulk->insert($data);

            $this->conn->executeBulkWrite($this->dbname.'.'.$collection,$bulk);

            if(isset($data['_id'])){
                $this->insertId = $data['_id'];
            }else{
                $this->insertId = $id;
            }

            return $this;

        } catch(Exception $e) {
            $this->err[] = "At line ".$e->getLine()." an error occured. " . $e->getMessage(). ". (Insert error)";
            $this->errorLog();
        }
    }

    public function insert_id()
    {
        return $this->insertId;
    }


    public function update($collection, $data, $filter, $options = array('multi' => false, 'upsert' => false))
    {
        try {

            if(!is_array($data)){
                throw new Exception('Invalid data.');
            }

            $bulk = new MongoDB\Driver\BulkWrite(['ordered' => true]);
            $bulk->update($filter,array('$set'=>$data),$options);

            $this->conn->executeBulkWrite($this->dbname.'.'.$collection,$bulk);

            return $this;

        } catch(Exception $e) {
            $this->err[] = "At line ".$e->getLine()." an error occured. " . $e->getMessage(). ". (Update error)";
            $this->errorLog();
        }
    }	:	MF TM TS DİL YGS



    public function delete($collection,$filter,$deleteAll=false)
    {
        try {
            $bulk = new MongoDB\Driver\BulkWrite;
            $bulk->delete($filter,['limit'=>$deleteAll]);

            $this->conn->executeBulkWrite($this->dbname.'.'.$collection,$bulk);

            return $this;

        } catch(Exception $e) {
            $this->err[] = "At line ".$e->getLine()." an error occured. " . $e->getMessage(). ". (Delete error)";
            $this->errorLog();
        }
    }

    /**
     * @param $collection
     * @param array $filter
     * @param array $options
     * @return $this
     */
    public function query($collection,$filter=array(),$options=array())
    {
        try{
            if(!is_array($options) || count($options) == 0){
                if(!is_array($this->queryOptions)){
                    $this->queryOptions = array();
                }
                $options = $this->queryOptions;
            }

            $query = new MongoDB\Driver\Query($filter, $options);
            $this->cursor = $this->conn->executeQuery($this->dbname.'.'.$collection, $query)->toArray();
            return $this;
        }catch (Exception $e)
        {
            $this->err[] = "At line ".$e->getLine()." an error occured. " . $e->getMessage(). ". (Query error)";
            $this->errorLog();
        }
    }

    /**
     * @return mixed
     */
    public function result()
    {
        return $this->cursor;
    }

    /**
     * @return int
     */
    public function num_rows()
    {
        return count($this->cursor);
    }

    /**
     * @param integer $limit
     * @return $this
     */
    public function limit($limit)
    {
        $this->queryOptions['limit'] = $limit;
        return $this;
    }

    /**
     * @param integer $skip
     * @return $this
     */
    public function skip($skip)
    {
        $this->queryOptions['skip'] = $skip;
        return $this;
    }

    /**
     * @param array $sort
     * @return $this
     */
    public function sort($sort)
    {
        $this->queryOptions['sort'] = $sort;
        return $this;
    }


    public function errorLog()
    {
        $msg = date('d-m-Y H:i:s') ."\n";
        foreach ($this->err as $error) {
            $msg .= " - ".$error."\n";
        }

        show_error($msg, 500, $heading = 'An Error Was Encountered');
    }

}



interface MDatabase
{
    public function connect();
    public function insert($collection,$data);
    public function update($collection,$data,$filter,$options);
    public function delete($collection,$filter,$deleteAll);
    public function num_rows();
    public function query($collection,$filter,$options);

}
