<?php
namespace lessphp\dao;

class Validation{

    public static function validate($inputRules ,$args) ## completed
    {
       if($inputRules === NULL || $args === NULL) return -17;
        //$keys = ['required','integer','float','double','min','max'];
        $errors = [];
        $data = [];
        if(isset($args['id']))
            $data['id'] = $args['id'];
        foreach($inputRules as $key=>$stringRules)
        {
            self::verifyRule($args, $key, $stringRules, $data, $errors);
        }
        if(!empty($errors))
            return  ['errors'=>$errors];
        foreach($args as $key=>$value)
        {
            if($key[0] === '_')
                $data[$key] = $value;
        }
        print_r($data);
        return $data;
    }

    public static function verifyRule($args, $key, $stringRules, &$data, &$errors)
    {   
        $rules = explode('|', $stringRules);
            #$flag = 1; # for required input flag = 1
            foreach($rules as $rule)
            {
                if($rule === 'required')
                {
                    if(!isset($args[$key]) || $args[$key] === '')
                    {
                        $errors[] = ['field'=>$key, 'message'=>'required'];
                        break;
                    }
                    $data[$key] = $args[$key];
                }
                else if($rule === 'nullable')
                {    if(!isset($args[$key]))
                    {
                        $data[$key] =NULL;
                        break;
                    }
                    $data[$key] = $args[$key];
                }
                else if($rule === 'integer')
                {   $val = $args[$key] ?? '';
                    if(!is_numeric($val))
                    {
                        $errors[] = ['field'=>$key, 'message'=>"$key must be integer"];
                    }
                }
                else if($rule === 'float')
                {   $val = $args[$key] ?? '';
                    if(!is_int($val))
                    {
                        $errors[] = ['field'=>$key, 'message'=>"$key must be float"];
                    }
                }
                else if($rule === 'double')
                {   $val = $args[$key] ?? '';
                    if(!is_int($val))
                    {
                        $errors[] = ['field'=>$key, 'message'=>"$key must be double"];
                    }
                }
                else if(strpos($rule,'max')!== false)
                {
                    $val = explode(':',$rule);
                    if(strlen($args[$key]) > $val[1])
                    {
                        $errors[] = ['field'=>$key, 'message'=>"$key must be less than: $val[1]"];
                    }
                }
                else if(strpos($rule,'min')!== false)
                {
                    $val = explode(':',$rule);
                    if(strlen($args[$key]) < $val[1])
                    {
                        if(isset($errors[$key]))
                        {
                            $temp = explode(':',$errors["$key"]);
                            $errors[] = ['field'=>$key, 'message'=>"$key must be between ($val[1],$temp[1])"];
                        }
                        else
                            $errors[] = ['field'=>$key, 'message'=>"$key must be greeter than: $val[1]"];
                       
                    }
                }
                else if(is_numeric($rule))
                {
                    if(strlen($args[$key]) > intval($rule))
                    {
                        $errors[] = ['field'=>$key,'message'=>"$key must be less than: $rule"];
                    }
                }
            }
        return $errors;
    }
}