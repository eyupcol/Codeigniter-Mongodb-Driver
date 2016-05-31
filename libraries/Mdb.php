<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Mongodb Codeigniter Driver v1.0
 * Versions: CI 3.X , Mongodb 3.2+ (also can be used with CI 2.X) , PHP 5 and PHP 7
 * Author: el-ma
 */

class Mdb
{
    private $host;
    private $port;
    private $dbname;
    private $user;
    private $password;

    private $connString = null;

    private $conn = null;
    private $err = array();
    private $insertId;
    private $cursor;

    private $getOptions = array();
    private $where;
    private $lang=null;


    public function __construct($active_language=null){
        $CI =& get_instance();
        $CI->config->load('mongo');
        $settings = $CI->config->item('mongo');
        $CI->lang->load('mongo');
        $this->lang =& $CI->lang;

        if(isset($settings) && is_array($settings)){
            foreach($settings as $k=>$v){
              if(trim($v) !== '' || $v != null)
              {
                $this->{$k} = trim($v);
              }
            }
        }
        $this->connect();
    }

    public function connect()
    {
        try {
            if(!class_exists('MongoDB\Driver\Manager'))
            {
              throw new Exeption($this->lang->line('mongo_php_extension_not_installed'));
            }
            if ($this->connString === null)
            {
                $this->conn = new MongoDB\Driver\Manager($this->conn_str());
            } else {
                $this->conn = new MongoDB\Driver\Manager($this->connString);
            }
            return $this;
        } catch (Exception $e) {
            $this->err[] = ['line'=>$e->getLine(), 'msg'=>$e->getMessage(), 'method'=>__METHOD__, 'custom_message'=>$this->lang->line('mongo_connection_error')];
            $this->err_handler();
        }
    }

    public function conn_str()
    {
        $str = "mongodb://";
        if(isset($this->user) && isset($this->password))
        {
            $str .= $this->user . ":" . $this->password;
        }

        $str .= "@" . $this->host . ":" . $this->port;

        if(isset($this->dbname))
        {
            $str .= "/" . $this->dbname;
        }

        return (string) $str;
    }

    /**
     * it will be created if does not exists, otherwise is switched to another database
     * @param string $db
     * @return $this
     */
    public function select_db($db)
    {
        $this->dbname = $db;
        return $this;
    }

    /**
     * @param string $collection
     * @param array $data
     * $return TRUE/show error
     */
    public function insert($collection,$data)
    {
        try {
            if(!is_array($data) || count($data) == 0){
                throw new Exception($this->lang->line('mongo_invalid_data'));
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
            $this->err[] = ['line'=>$e->getLine(), 'msg'=>$e->getMessage(), 'method'=>__METHOD__, 'custom_message'=>$this->lang->line('mongo_insert_error')];
            $this->err_handler();
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
            $this->err[] = ['line'=>$e->getLine(), 'msg'=>$e->getMessage(), 'method'=>__METHOD__, 'custom_message'=>$this->lang->line('mongo_update_error')];
            $this->err_handler();
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
            $this->err[] = ['line'=>$e->getLine(), 'msg'=>$e->getMessage(), 'method'=>__METHOD__, 'custom_message'=>$this->lang->line('mongo_delete_error')];
            $this->err_handler();
        }
    }

    /**
    * Insert_batch || added by Bryup
    * @Return integer number of inserted rows(documents)
    */
    public function insert_batch($collection, $data)
    {
      try {
          if(!is_array($data) || count($data) == 0)
          {
              throw new Exception($this->lang->line('mongo_invalid_data'));
          }

          $bulk = new MongoDB\Driver\BulkWrite(['ordered' => true]);

          foreach ($data as $documents) {
            $bulk->insert($documents);
          }

          $result = $this->conn->executeBulkWrite($this->dbname.'.'.$collection,$bulk);

          return $result->getInsertedCount();

      } catch(Exception $e) {
          $this->err[] = ['line'=>$e->getLine(), 'msg'=>$e->getMessage(), 'method'=>__METHOD__, 'custom_message'=>$this->lang->line('mongo_insert_batch_error')];
          $this->err_handler();
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
              throw new Exception($this->lang->line('mongo_invalid_query_filter'));
            }

            if(!is_array($options)){
              throw new Exception($this->lang->line('mongo_invalid_query_options'));
            }

            $query = new MongoDB\Driver\Query($filter, $options);
            $this->cursor = $this->conn->executeQuery($this->dbname.'.'.$collection, $query)->toArray();
            return $this;
        }catch (Exception $e)
        {
            $this->err[] = ['line'=>$e->getLine(), 'msg'=>$e->getMessage(), 'method'=>__METHOD__, 'custom_message'=>$this->lang->line('mongo_query_error')];
            $this->err_handler();
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
            $this->err[] = ['line'=>$e->getLine(), 'msg'=>$e->getMessage(), 'method'=>__METHOD__, 'custom_message'=>$this->lang->line('mongo_query_error')];
            $this->err_handler();
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


    public function err_handler()
    {
        $msg = date('d-m-Y H:i:s') .":\n";
        foreach ($this->err as $error) {
            $msg .= " - ".implode(' | ', $error)." \n";
        }

        show_error($msg, 500, $heading = 'An Error Was Encountered');
    }

}
