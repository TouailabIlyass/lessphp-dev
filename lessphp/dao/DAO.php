<?php
namespace lessphp\dao;
require_once(dirname(__FILE__).'/../../connection.php');

class DAO{

    private $query = null;
    private $whereArgs = [];
    public $keys = [];
    private $whereStmt = null;
    private $className = null;
    private $joinClause = null;
    private $limit = 'LIMIT 10';
    private $offset = 'OFFSET 0';
    private $flag = NULL;
    private const INSERT = 'INSERT';
    private const SELECT = 'SELECT';
    private const UPDATE = 'UPDATE';
    private const DELETE = 'DELETE';

    public function __construct($className)
    {
        $this->className = $className;
    }

    private function init(){
        $this->query = null;
        $this->whereArgs = [];
        $this->keys = [];
        $this->whereStmt = null;
        $this->joinClause = null;
        $this->limit = 'LIMIT 10';
        $this->offset = 'OFFSET 0';
        $this->flag = NULL;
    }

    private function executeQuery($query ,$data)
    {  
        $pdo = NULL;
        $stmt = NULL;
        try{ 
            $pdo = \DB\ConnectionDB::getConnection();
            $stmt = $pdo->prepare($query);
            $result = $stmt->execute($data);
            if($this->flag === self::SELECT)
            {   $stmt->setFetchMode(\PDO::FETCH_OBJ|\PDO::FETCH_PROPS_LATE);
                unset($pdo);
                return $stmt->fetchAll();
            }
            unset($stmt);
            unset($pdo);
            return $result;
        }catch(Exception $e)
        {   
            unset($stmt);
            unset($pdo);
            return $e->getMessage();
        }
    }

    public function insert($data, $modelFields)
    {   
        $this->flag = self::INSERT;
        if($data === NULL) return -2;
		try{
            $query = self::makeInsertQuery($modelFields);
            return $this->executeQuery($query ,$data);
        }catch(Exception $e)
        {
            return $e->getMessage();
        }
    }

    public function where($field=NULL, $rule=NULL, $value=NULL)
    {   
        try{
            return $this->makeWhere($field, $rule, $value);
        }catch(Exception $e)
        {
            return $e->getMessage();
        }
    }

    public function AndWhere($field=NULL ,$rule=NULL, $value=NULL)
    {
        try{
            return $this->makeWhere($field, $rule, $value);
        }catch(Exception $e)
        {
            return $e->getMessage();
        }
    }

    public function OrWhere($field=NULL ,$rule=NULL, $value=NULL)
    {
        try{
            return $this->makeWhere($field, $rule, $value, 'OR');
        }catch(Exception $e)
        {
            return $e->getMessage();
        }
    }

    public function get()
    {  
        try{
            if($this->query === null)
            $this->query = "{$this->className}.* ";
            if($this->flag === NULL)
                $this->flag = self::SELECT;
            $result = $this->executeQuery("select $this->query from {$this->className} {$this->joinClause} $this->whereStmt {$this->limit} {$this->offset}" ,$this->whereArgs);
            $this->init();
            return $result;
        }catch(Exception $e)
        {
            return $e->getMessage();
        }  
    } 

    public function join($table, $column, $condition, $originColumn)
    {
       if($table !== NULL && $column !== NULL && $condition !== NULL && $originColumn !== NULL)
       {
           $this->joinClause = " , $table";
           if($this->whereStmt === NULL)
             $this->whereStmt = " where $column $condition $originColumn";
            else
             $this->whereStmt .= " and ($column $condition $originColumn)";  
       }
       return $this;
    }

    public function truncate()
    {

    }

    public function update($data)
    {
        try{  
            if ($data === NULL) return $this;
            $query = $this->makeUpdateQuery($this->modelFields);
            $this->keysExtraction($this->keys, $data);
            $query .= $this->whereStmt;
            return $this->executeQuery($query ,$data);
        }catch(Exception $e)
        {
            return $e->getMessage();
        }
    }

    public function arrayMerge(&$arr1, $arr2)
    {
        foreach($arr2 as $key=>$value)
        {
            $arr1[$key] = $value;
        }
    }

    public function delete($data=NULL)
    {
        try{
            $this->keysExtraction($this->keys, $data);
            $query = "delete from $this->className $this->whereStmt";
            return $this->executeQuery($query ,$this->whereArgs);
        }catch(Exception $e)
        {
            return $e->getMessage();
        }
    }

    private function makeWhere($field, $rule, $value, $operation='and')
    {
        try{
            if($field !== NULL && $rule !== NULL && $value !== NULL){
                $key = $field;
                if(strpos($key,'.') !== false)
                {
                    $key = str_replace('.','_',$key);
                }
                if($this->whereStmt === NULL)
                    $this->whereStmt = " where $field $rule :_{$field}";
                else
                    $this->whereStmt .= " $operation $field $rule :_$key";
                $this->whereArgs["_$key"] = $value;
            }
        return $this;
        }catch(Exception $e)
        {
            return $e->getMessage();
        }
    }

    public function whereNULL($field=NULL)
    {
        return $this->isNullCondition($field);
    }
    
    public function whereNotNULL($field=NULL)
    {
       return $this->isNullCondition($field, 'NOT');
    }

    private function isNullCondition($field, $condition=NULL)
    {
        if($field !== NULL){
            if($this->whereStmt !== null)
                {
                    $this->whereStmt .= " and $field is $condition NULL";
                }
            else 
                {
                    $this->whereStmt = " where $field is $condition NULL";
                }
        }
       return $this;
    }

    public function select($field='')
    {
        if($field !== '')
        {  
            if($this->query === null)
                $this->query = " $field";
            else
                $this->query .= " ,$field";  
        }
        return $this;
    }

    public function addSelect($field='')
    {
        return $this->select($field);
    }

    public function find($data)
    {
        $this->keysExtraction($this->keys, $data);
    }

    public function set($field ,$value=NULL)
    {
        if($field !== NULL)
        {
            if($this->query !== NULL)
            {
                $this->query .= " ,$field= :_{$field}";
            }
            else{
                $this->query = " $field= :_{$field}";
                $this->fieldsToUpdate = [];
            }
            $this->fieldsToUpdate["_{$field}"] = $value;
        }
        return $this;
    }

    public function save($data=NULL)
    {
        try{
            $this->keysExtraction($this->keys, $data);
            $this->arrayMerge($this->whereArgs, $this->fieldsToUpdate);
            unset($this->fieldsToUpdate);
            return $this->executeQuery("update $this->className set $this->query $this->whereStmt", $this->whereArgs);
        }catch(Exception $e)
        {
            return $e->getMessage();
        }
    }

    private function keysExtraction($keys, &$data=NULL)
    {
        if(count($this->whereArgs) !== 0 ){
            if ($data === NULL) $data = [];
            $this->arrayMerge($data, $this->whereArgs);
        }
        else
        {   if(count($keys) === 0)
            {
                $keys = 'id';
                $this->where($keys, '=', $data[$keys] ?? $data[0]) ;
                $this->arrayMerge($data, $this->whereArgs);
                unset($data[$keys]);
            }
            else if(is_array($keys)){
                for($i=0;$i<count($keys);$i++)
                {
                    $this->where($keys[$i], '=', $data[$keys[$i]] ?? $data[$i]);
                }
                $this->arrayMerge($data, $this->whereArgs);
            }
        }
    }

    private function makeUpdateQuery($modelFields)
    {
        $fields = "update $this->className set ";
        foreach($modelFields as $field)
        {
            $fields .= "$field= :{$field},";
        }
        if($fields !== '')
        {
            $fields = substr($fields, 0, -1);
        }
        return $fields;
    }

    private function makeInsertQuery($modelFields)
    {
        $fields = "insert into $this->className(";
        $values = "values(";
        foreach($modelFields as $field)
        {
            $fields .= "$field,";
            $values .= ":{$field},";
        }
        if($fields !== '')
        {
            $fields = substr($fields, 0, -1).") ";
            $values = substr($values, 0, -1).")";
        }
        return "$fields $values";
    }

    public function limit($limit=10)
    {
        $this->limit = "LIMIT {$limit}";
    }

    public function offset($offset=0)
    {
        $this->offset = "OFFSET {$offset}";
    }
}
