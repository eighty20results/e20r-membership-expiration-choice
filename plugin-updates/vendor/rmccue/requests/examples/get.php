<?php

// First, include Requests
include('../library/Requests.php');

// Next, make sure Requests can load internal class
Requests::register_autoloader();

// Now let's make a request!
$request = Requests::get('http://httpbin.org/get', array('Accept' => 'application/json'));

// Check what we received
var_dump($request);