<?php
// +----------------------------------------------------------------------

// +----------------------------------------------------------------------
// | Copyright (c) 2011 http://www.pz.com All rights reserved.
// +----------------------------------------------------------------------
// | Author: @@@@@@@
// +----------------------------------------------------------------------

//引入数据库的系统配置及定义配置函数
require_once APP_ROOT_PATH."system/utils/es_cookie.php";
if(!function_exists("app_conf"))
{
	$sys_config = require APP_ROOT_PATH.'system/config.php';
	function app_conf($name)
	{
		return stripslashes($GLOBALS['sys_config'][$name]);
	}
}

class es_session 
{	
	static function id()
	{		
		return session_id();
	}
	static function start()
	{
		session_set_cookie_params(0,app_conf("COOKIE_PATH"),app_conf("DOMAIN_ROOT"));
		session_start();
	}
	
    // 判断session是否存在
    static function is_set($name) {    	
	    return isset($_SESSION[$name]);
    }

    // 获取某个session值
    static function get($name) {
		session_start();
        $value   = $_SESSION[$name];
        return $value;
    }

    // 设置某个session值
    static function set($name,$value) {   
       session_start();
	    $_SESSION[$name]  =   $value;
    }

    // 删除某个session值
    static function delete($name) {

        unset($_SESSION[$name]);
    }

    // 清空session
    static function clear() {

        session_destroy();
    }
    
    //关闭session的读写
    static function close()
    {

    	@session_write_close();
    }
    
    static function  is_expired()
    {

        if (isset($_SESSION[app_conf("AUTH_KEY")."expire"]) && $_SESSION[app_conf("AUTH_KEY")."expire"] < get_gmtime()) {
            return true;
        } else {        	
        	$_SESSION[app_conf("AUTH_KEY")."expire"] = get_gmtime()+(intval(app_conf("EXPIRED_TIME"))*60);
            return false;
        }
    }
}
?>