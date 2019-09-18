<?php

// Travis-CI's PHP version does not have support for CURL_HTTP_VERSION_2 constant
if( !defined('CURL_HTTP_VERSION_2') ){
    \define('CURL_HTTP_VERSION_2', 3);
}