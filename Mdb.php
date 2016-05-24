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

    private $getOptions = array();
    private $where;


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

            return TRUE;

        } catch(Exception $e) {
            $this->err[] = "At line ".$e->getLine()." an error occured. " . $e->getMessage(). ". (Insert error)";
            $this->errorLog();
        }
    }

    public function insert_id()
    {
        return $this->insertId;
    }


    public function update($collection, $data, $filter, $options = array('multi' => true, 'upsert' => false))
    {
        try {

            if(!is_array($data)){
                throw new Exception('Invalid data.');
            }

            $bulk = new MongoDB\Driver\BulkWrite;
            $bulk->update($filter,array('$set'=>$data),$options);

            $result = $this->conn->executeBulkWrite($this->dbname.'.'.$collection,$bulk);

            return $result->getModifiedCount();

        } catch(Exception $e) {
            $this->err[] = "At line ".$e->getLine()." an error occured. " . $e->getMessage(). ". (Update error)";
            $this->errorLog();
        }
    }


    public function delete($collection,$filter=array(),$deleteAll=false)
    {
        try {
            $bulk = new MongoDB\Driver\BulkWrite;
            $bulk->delete($filter,['limit'=>$deleteAll]);

            $result = $this->conn->executeBulkWrite($this->dbname.'.'.$collection,$bulk);

            return $result->getDeletedCount();

        } catch(Exception $e) {
            $this->err[] = "At line ".$e->getLine()." an error occured. " . $e->getMessage(). ". (Delete error)";
            $this->errorLog();
        }
    }

    /**
    * Insert_batch || added by Bryup
    * @Return integer number of inserted rows(documents)
    */
    public function insert_batch($collection, $data)
    {
      try {
          if(!is_array($data)){
              throw new Exception('Invalid data.');
          }

          $bulk = new MongoDB\Driver\BulkWrite(['ordered' => true]);

          foreach ($data as $documents) {
            $bulk->insert($documents);
          }

          $result = $this->conn->executeBulkWrite($this->dbname.'.'.$collection,$bulk);

          return $result->getInsertedCount();

      } catch(Exception $e) {
          $this->err[] = "At line ".$e->getLine()." an error occured. " . $e->getMessage(). ". (Insert error)";
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
            if(!is_array($filter)){
              throw new Exception("Second parameter is not an array");
            }

            if(!is_array($options)){
              throw new Exception("Third parameter is not an array");
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
    * @param string $collection
    * @param integer $start
    * @param integer $limit
    * @return $this
    */
    public function get($collection,$start=null,$limit=null)
    {
        try{
            if(!is_array($this->where) || count($this->where) == 0){
              $filter =  array();
            }else{
              $filter = $this->where;
            }

            if(!is_array($this->getOptions)){
                $this->getOptions = array();
            }

            $query = new MongoDB\Driver\Query($filter, $this->getOptions);
            $this->cursor = $this->conn->executeQuery($this->dbname.'.'.$collection, $query)->toArray();
            return $this;
        }catch (Exception $e)
        {
            $this->err[] = "At line ".$e->getLine()." an error occured. " . $e->getMessage(). ". (Query error)";
            $this->errorLog();
        }
    }

    /**
    * @param array $where
    * @return $this
    */
    public function where($where=array())
    {
      $this->where = $where;
      return $this;
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
    * @param integer $offset
    * @return $this
    */
    public function limit($limit,$offset=0)
    {
        $this->getOptions['limit'] = $limit;
        if($offset > 0){
          $this->getOptions['skip'] = $offset;
        }
        return $this;
    }

    /**
     * @param integer $skip
     * @return $this
     */
    public function skip($skip)
    {
        $this->getOptions['skip'] = $skip;
        return $this;
    }

    /**
     * @param array $sort
     * @return $this
     */
    public function order_by($sort)
    {
        $this->getOptions['sort'] = $sort;
        return $this;
    }

    /**
     * @param array $col
     * @return $this
     */
    public function select($col)
    {
      if(is_array($col)){
        $this->getOptions['projection'] = $col;
      }
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
