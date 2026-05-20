<?php

class User {

    public $id;
    public $name;
    public $role;
    public $email;
    public $password;

    public function __construct($name, $email,$role , $password){
        $this->name = $name;
        $this->email = $email;
        $this->role = $role;
        $this->password = $password;
    }

}
?> 