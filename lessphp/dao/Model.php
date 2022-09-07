<?php
namespace lessphp\dao;

require_once(dirname(__FILE__).'/DAO.php');
require_once(dirname(__FILE__).'/Validation.php');

class Model{

    public $_dao = NULL;
    private $_instance;
    private $_errors;


    public function __construct()
    {
        $class = get_called_class();
        $this->_dao = new DAO($class::$TABLE);
    }

    public function create($data, $checkValidation=true) # completed
    {
        try{
            if( $checkValidation === true && isset($this->validation))
            {   
                $validate = Validation::validate($this->validation, $data);
                $firstKey = array_key_first($validate);
                if($firstKey === 'errors')
                {
                    return json_encode($validate);
                }
                return $this->_dao->insert($validate, $this->fields);
            }
            else
                return $this->_dao->insert($data, $this->fields);

        }catch(Exception $e)
        {
            return $e->getMessage();
        }  
    }

    public function select($field=NULL)
    {   
        $this->_dao->select($field);
        return $this;
    }

    public function update($data, $checkValidation=true)
    {  
        try{
            $_data = $data;
            if( $checkValidation === true && isset($this->validation))
            {   
                $validate = Validation::validate($this->validation, $data);
                $firstKey = array_key_first($validate);
                if($firstKey === 'errors')
                {
                    return json_encode($validate);
                }
                $_data = $validate;
            }
            if(!isset($this->_dao->modelFields))
                $this->_dao->modelFields = $this->fields;
            if(isset($this->primaryKey))
                $this->_dao->keys = $this->primaryKey;
            return $this->_dao->update($_data);
            

        }catch(Exception $e)
        {
            return $e->getMessage();
        }  
    }

    public function delete($data=NULL)
    {
        if(isset($this->primaryKey))
            $this->_dao->keys = $this->primaryKey;
        return $this->_dao->delete($data);
    }

    public function save($data=NULL)
    {
        try{
        if(isset($this->primaryKey))
            $this->_dao->keys = $this->primaryKey;
        
            print_r($this->_errors);
        return $this->_dao->save($data);
        #return $this;
        }catch(Exception $e)
        {
            return $e->getMessage();
        }
    }

    public function where($field=NULL, $rule=NULL, $value=NULL)
    {
        
        $this->_dao->modelFields = $this->fields;
        $this->_dao->where($field,$rule,$value);
        return $this;
    }

    public function andWhere($field=NULL, $rule=NULL, $value=NULL)
    {
        $this->_dao->andWhere($field,$rule,$value);
        return $this;
    }

    public function find($args)
    {
        if(isset($this->primaryKey))
         {   $this->_dao->keys = $this->primaryKey;
            print_r($this->_dao->keys);
         }
        $this->_dao->find($args);
        return $this;
    }

    public function set($field ,$value)
    {
        try {
            if(isset($this->primaryKey))
            $this->_dao->keys = $this->primaryKey;
        $data = [];
        $error = [];
        $ruleString = $this->validation[$field];
        Validation::verifyRule([$field=>$value], $field, $ruleString, $data, $error);
        if(!empty($error))
        {    if(!isset($this->_errors))
                $this->_errors = ['errors'=>[]];
            $this->_errors['errors'] = $error;
        }
        $this->_dao->set($field ,$value);
        return $this;
        }catch(Exception $e)
        {
            return $e->getMessage();
        } 
    }

    public function get()
    {
        return $this->_dao->get();
    }

    public function limit($limit=10)
    {
        $this->_dao->limit($limit);
        return $this;
        
    }

    public function offset($offset=0)
    {
        $this->_dao->offset($offset);
        return $this;
    }
    
}