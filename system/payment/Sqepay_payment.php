<?php

// +----------------------------------------------------------------------
// | Fanwe 多语商城建站系统 (Build on ThinkPHP)
// +----------------------------------------------------------------------
// | Copyright (c) 2009 http://www.pz.com All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: jobin.lin(jobin.lin@gmail.com)
// +----------------------------------------------------------------------
// +----------------------------------------------------------------------
// | 双乾支付
// +----------------------------------------------------------------------
$payment_lang = array(
	'name'	=>	'双乾支付',
	'merno'	=>	'商户ID',
	'md5key'	=>	'Md5Key',
	'GO_TO_PAY'	=>	'前往双乾支付',
	'VALID_ERROR'	=>	'支付验证失败',
	'PAY_FAILED'	=>	'支付失败',
	'sqepay_gateway'	=>	'支持的银行',
	'sqepay_gateway_0'	=>	'纯网关支付',
	'sqepay_gateway_ICBC'	=>	'中国工商银行',
	'sqepay_gateway_ABC'	=>	'中国农业银行',
	'sqepay_gateway_BOCSH'	=>	'中国银行',
	'sqepay_gateway_CCB'	=>	'中国建设银行',
	'sqepay_gateway_CMB'	=>	'招商银行',
	'sqepay_gateway_SPDB'	=>	'上海浦东发展银行',
	'sqepay_gateway_GDB'	=>	'广东发展银行',
	'sqepay_gateway_BOCOM'	=>	'中国交通银行',
	'sqepay_gateway_PSBC'	=>	'中国邮政储蓄银行',
	'sqepay_gateway_CNCB'	=>	'中信银行',
	'sqepay_gateway_CMBC'	=>	'民生银行',
	'sqepay_gateway_CEB'	=>	'光大银行',
	'sqepay_gateway_HXB'	=>	'华夏银行',
	'sqepay_gateway_CIB'	=>	'兴业银行',
);

$config = array(
    'merno' => '', //商户ID
    'md5key' => '', //Md5Key
    'sqepay_gateway' => array(
    	'INPUT_TYPE'	=>	'3',
    	'VALUES'	=>	array(
    		'0',
	        'ICBC', //中国工商银行
	        'ABC', //中国农业银行
	        'BOCSH', //中国银行
	        'CCB', //中国建设银行
	        'CMB', //招商银行
	        'SPDB', //上海浦东发展银行
	        'GDB', //广东发展银行
	        'BOCOM', //交通银行
	        'PSBC', //中国邮政储蓄银行
	        'CNCB', //中信银行
	        'CMBC', //中国民生银行
	        'CEB', //光大银行
	        'HXB', //华夏银行
	        'CIB', //兴业银行
        ),
    ),
);

/* 模块的基本信息 */
if (isset($read_modules) && $read_modules == true){
    
    /* 会员数据整合插件的代码必须和文件名保持一致 */
    $module['class_name']    = 'Sqepay';

    /* 被整合的第三方程序的名称 */
    $module['name'] = $payment_lang['name'];
    
    /* 支付方式：1：在线支付；0：线下支付 */
    $module['online_pay'] = '1';
	
	 /* 配送 */
    $module['config'] = $config;
    
    $module['lang'] = $payment_lang;
	
    $module['reg_url'] = 'http://www.95epay.cn/PaySystem/merDownFile.action?DownID=3';
    
    return $module;
}

require_once(APP_ROOT_PATH.'system/libs/payment.php');
class Sqepay_payment implements payment {

    public function get_payment_code($payment_notice_id) {
        $payment_notice = $GLOBALS['db']->getRow("select * from ".DB_PREFIX."payment_notice where id = ".$payment_notice_id);
		$order = $GLOBALS['db']->getRow("select order_sn,bank_id from ".DB_PREFIX."deal_order where id = ".$payment_notice['order_id']);
		
		$payment_info = $GLOBALS['db']->getRow("select id,config,logo from ".DB_PREFIX."payment where id=".intval($payment_notice['payment_id']));
		$payment_info['config'] = unserialize($payment_info['config']);
       
        //订单号
		$BillNo = $order['order_sn'];
		// 总金额 
		$Amount = round($payment_notice['money'],2);
		
        $ReturnURL = get_domain().APP_ROOT.'/95epay_callback.php?act=response';
		$NotifyURL = get_domain().APP_ROOT.'/95epay_callback.php?act=notify';

        //支付方式
        $PayType = "CSPAY";
        $PaymentType = $order['bank_id'];
       
        $MerRemark = $payment_notice_id;
       
        /* 数字签名 */
        $MerNo = $payment_info['config']['merno'];
        $MD5key = $payment_info['config']['md5key'];
        
        $MD5info = $this->getSignature($MerNo, $BillNo, $Amount, $ReturnURL, $MD5key);

        /*交易参数*/
        $parameter = array(
            'MerNo'=>$MerNo,
            'MD5key'=>$MD5key,
            'Amount'=>$Amount,
            'BillNo'=>$BillNo,
            'MerNo'=>$MerNo,
            'ReturnURL'=>$ReturnURL,
			'NotifyURL'=>$NotifyURL,
            'MD5info'=>$MD5info,
            'SubMerNo'=>'',
            'PayType'=>$PayType,
            'PaymentType'=>$PaymentType,
            'MerRemark'=>$MerRemark,
            'products'=>''
        );
        
        $_SESSION['SqNoticeId'][$BillNo] = $payment_notice_id;
        
        $def_url = '<form style="text-align:center;" action="https://www.95epay.cn/sslpayment" style="margin:0px;padding:0px" method="post" >';

        foreach ($parameter AS $key => $val) {
            $def_url .= "<input type='hidden' name='$key' value='$val' />";
        }
        $def_url .= "<input type='submit' class='paybutton' value='前往双乾支付' />";
        $def_url .= "</form>";
        $def_url.="<br /><div style='text-align:center' class='red'>".$GLOBALS['lang']['PAY_TOTAL_PRICE'].":".format_price($Amount)."</div>";
        return $def_url;
    }
    
    public function response($request) {
		$return_res = array(
            'info' => '',
            'status' => false,
        );
        
        $BillNo   = $_POST["BillNo"];
        $Amount   = $_POST["Amount"];
        $Succeed  = $_POST["Succeed"];
        $MD5info  = $_POST["MD5info"];
        $Result   = $_POST["Result"];
		$payment_notice_sn   = intval($_POST["MerRemark"]);
       // $payment_notice_sn =  $_SESSION['SqNoticeId'][$BillNo];
        
        /*获取支付信息*/
        $payment = $GLOBALS['db']->getRow("select id,config from ".DB_PREFIX."payment where class_name='Sqepay'");  
    	$payment['config'] = unserialize($payment['config']);
		//$GLOBALS['db']->query("update ".DB_PREFIX."payment_notice set response_post='".$serialize($_POST)."' where id='".$payment_notice_sn."'");
    	
    	$MerNo = $payment['config']['merno'];
    	$MD5key = $payment['config']['md5key'];
		
    	
        /*比对连接加密字符串*/
		$md5sign = $this->getSignature_return($MerNo,$BillNo,$Amount,$Succeed,$MD5key);
		//$restr=$BillNo."@".$Amount."@".$Succeed."@".$MD5info."@".$Result."@".$payment_notice_sn;
		//echo $restr.'<br>'.$MD5info.'<br>'.$md5sign;
		//exit();
		
       if($MD5info == $md5sign && $Succeed=="88"){
	        $payment_notice = $GLOBALS['db']->getRow("select * from ".DB_PREFIX."payment_notice where id = '".$payment_notice_sn."'");
			$order_info = $GLOBALS['db']->getRow("select * from ".DB_PREFIX."deal_order where id = ".$payment_notice['order_id']);
			require_once APP_ROOT_PATH."system/libs/cart.php";
			$rs = payment_paid($payment_notice['id']);						
			if($rs)
			{
				$rs = order_paid($payment_notice['order_id']);				
				if($rs)
				{
					//开始更新相应的outer_notice_sn					
					$GLOBALS['db']->query("update ".DB_PREFIX."payment_notice set outer_notice_sn = '".$BillNo."' where id = ".$payment_notice['id']);
					if($order_info['type']==0)
						app_redirect(url("index","payment#done",array("id"=>$payment_notice['order_id']))); //支付成功
					else
						app_redirect(url("index","payment#incharge_done",array("id"=>$payment_notice['order_id']))); //支付成功
				}
				else 
				{
					if($order_info['pay_status'] == 2)
					{				
						if($order_info['type']==0)
							app_redirect(url("index","payment#done",array("id"=>$payment_notice['order_id']))); //支付成功
						else
							app_redirect(url("index","payment#incharge_done",array("id"=>$payment_notice['order_id']))); //支付成功
					}
					else
						app_redirect(url("index","payment#pay",array("id"=>$payment_notice['id'])));
				}
			}
			else
			{
				app_redirect(url("index","payment#pay",array("id"=>$payment_notice['id'])));
			}
        }
        else{
			
			$errstr=$GLOBALS['payment_lang']["VALID_ERROR"];
			
			if($Succeed=='-1') $errstr="订单号错误";
			if($Succeed=='0') $errstr="交易失败";
			if($Succeed=='5') $errstr="同一 ip重复交易";
			if($Succeed=='8') $errstr="同一 cookie重复交易";
			if($Succeed=='10') $errstr="不存在该商户号";
			if($Succeed=='11') $errstr="MD5key不存在";
			if($Succeed=='13') $errstr="MD5 验签失败";
			if($Succeed=='14') $errstr="交易网址未注册";
			if($Succeed=='15') $errstr="商户未开通败";
			if($Succeed=='16') $errstr="通道未开通";
			if($Succeed=='22') $errstr="返回地址未绑定";
			if($Succeed=='24') $errstr="交易流水号重复";
			if($Succeed=='25') $errstr="订单金额有误";
			if($Succeed=='26') $errstr="当天金额超过限制";
			if($Succeed=='30') $errstr="待处理";
			
			showErr($errstr);
        }
    }
    
     public function notify($request) {
		$return_res = array(
            'info' => '',
            'status' => false,
        );
		
        
        $BillNo   = $_POST["BillNo"];
        $Amount   = $_POST["Amount"];
        $Succeed  = $_POST["Succeed"];
        $MD5info  = $_POST["MD5info"];
        $Result   = $_POST["Result"];
		$payment_notice_sn   = intval($_POST["MerRemark"]);
		
		
		$restr=$BillNo."@".$Amount."@".$Succeed."@".$MD5info."@".$Result."@".$payment_notice_sn;
        //$payment_notice_sn =  $_SESSION['SqNoticeId'][$BillNo];
	
        /*获取支付信息*/
        $payment = $GLOBALS['db']->getRow("select id,config from ".DB_PREFIX."payment where class_name='Sqepay'");  
    	$payment['config'] = unserialize($payment['config']);
		//$GLOBALS['db']->query("update ".DB_PREFIX."payment set description='notify(@$restr)' where class_name='Sqepay'");
    	
    	$MerNo = $payment['config']['merno'];
    	$MD5key = $payment['config']['md5key'];
		
        /*比对连接加密字符串*/
		$md5sign = $this->getSignature_return($MerNo,$BillNo,$Amount,$Succeed,$MD5key);
		//echo $MerNo."@".$BillNo."@".$Amount."@".$Succeed."@".$MD5key.'<br>';
		//184655@20160516033424722@1.0@88@bR]szsOO
	    //$md5sign = $this->getSignature('184655','20160516033424722','1.0','88','bR]szsOO');

	  // echo '2D29038FA2F851CC4A0E163B82393F83<br>'.$md5sign;
	  // exit();
       if($MD5info == $md5sign && $Succeed=="88"){
			
			$payment_notice = $GLOBALS['db']->getRow("select * from ".DB_PREFIX."payment_notice where id = '".$payment_notice_sn."'");
			$order_info = $GLOBALS['db']->getRow("select * from ".DB_PREFIX."deal_order where id = ".$payment_notice['order_id']);
			require_once APP_ROOT_PATH."system/libs/cart.php";
			$rs = payment_paid($payment_notice['id']);						
			if($rs)
			{
				$rs = order_paid($payment_notice['order_id']);				
				if($rs)
				{
					//开始更新相应的outer_notice_sn					
					$GLOBALS['db']->query("update ".DB_PREFIX."payment_notice set outer_notice_sn = '".$BillNo."' where id = ".$payment_notice['id']);
					if($order_info['type']==0)
						app_redirect(url("index","payment#done",array("id"=>$payment_notice['order_id']))); //支付成功
					else
						app_redirect(url("index","payment#incharge_done",array("id"=>$payment_notice['order_id']))); //支付成功
				}
				else 
				{
					if($order_info['pay_status'] == 2)
					{				
						if($order_info['type']==0)
							app_redirect(url("index","payment#done",array("id"=>$payment_notice['order_id']))); //支付成功
						else
							app_redirect(url("index","payment#incharge_done",array("id"=>$payment_notice['order_id']))); //支付成功
					}
					else
						app_redirect(url("index","payment#pay",array("id"=>$payment_notice['id'])));
				}
			}
			else
			{
				app_redirect(url("index","payment#pay",array("id"=>$payment_notice['id'])));
			}
        }
        else{
        	showErr($GLOBALS['payment_lang']["VALID_ERROR"]);
        }
    }
    
     public function get_display_code() {
        $payment_item = $GLOBALS['db']->getRow("select * from ".DB_PREFIX."payment where class_name='Sqepay'");
		if($payment_item)
		{
			$payment_cfg = unserialize($payment_item['config']);

	        $html = "<style type='text/css'>.sqepay_types{float:left; display:block; background:url(".get_domain().APP_ROOT."/system/payment/Sqepay/banklist_hnapay.jpg); font-size:0px; width:150px; height:10px; text-align:left; padding:15px 0px;}";
	        $html .=".sqbk_type_0{background-position:15px -742px; }"; //中国建设银行
	        $html .=".sqbk_type_CCB{background-position:15px -72px; }"; //中国建设银行
	        $html .=".sqbk_type_CMB{background-position:15px -196px; }"; //招商银行
	        $html .=".sqbk_type_ICBC{background-position:15px 6px; }"; //中国工商银行
	        $html .=".sqbk_type_BOCSH{background-position:15px -113px; }"; //中国银行
	        $html .=".sqbk_type_ABC{background-position:15px -34px; }"; //中国农业银行
	        $html .=".sqbk_type_BOCOM{background-position:15px -158px; }"; //交通银行
	        $html .=".sqbk_type_CMBC{background-position:15px -230px; }"; //中国民生银行
	        $html .=".sqbk_type_HXB{background-position:15px -358px; }"; //华夏银行
	        $html .=".sqbk_type_CIB{background-position:15px -270px; }"; //兴业银行
	        $html .=".sqbk_type_SPDB{background-position:15px -312px; }"; //上海浦东发展银行
	        $html .=".sqbk_type_GDB{background-position:15px -475px; }"; //广东发展银行
	        $html .=".sqbk_type_CNCB{background-position:15px -396px; }"; //中信银行
	        $html .=".sqbk_type_CEB{background-position:15px -435px; }"; //光大银行
	        $html .=".sqbk_type_PSBC{background-position:15px -513px; }"; //中国邮政储蓄银行
	        $html .=".sqbk_type_SDB{background-position:15px -558px; }"; //深圳发展银行
	        $html .="</style>";
	        $html .="<script type='text/javascript'>function set_bank(bank_id)";
			$html .="{";
			$html .="$(\"input[name='bank_id']\").val(bank_id);";
			$html .="}</script>";
	
	       foreach ($payment_cfg['sqepay_gateway'] AS $key=>$val)
	        {
	        	if($key=="0")
	        	{
	        		$html  .= "<label class='sqepay_types sqbk_type_".$key."'><input type='radio' name='payment' value='".$payment_item['id']."' rel='".$key."' onclick='set_bank(\"\")' /></label>";
	        	}
	        	else
	           	 $html  .= "<label class='sqepay_types sqbk_type_".$key."'><input type='radio' name='payment' value='".$payment_item['id']."' rel='".$key."' onclick='set_bank(\"".$key."\")' /></label>";
	        }
	        $html .= "<input type='hidden' name='bank_id' />";
			return $html;
		}
		else{
			return '';
		}
    }
    
  function getSignature_return($MerNo, $BillNo, $Amount, $Succeed, $MD5key){
			$sign_params  = array(
        	'MerNo'         => $MerNo,
        	'BillNo'        => $BillNo, 
        	'Amount'        => $Amount,   
        	'Succeed'       => $Succeed
    			);
    
			$sign_str = "";
			ksort($sign_params);
			foreach ($sign_params as $key => $val) {              
                
                $sign_str .= sprintf("%s=%s&", $key, $val);
                               
            }
          // print $sign_str;print '<br/><br/><br/>';
  		return strtoupper(md5($sign_str. strtoupper(md5($MD5key))));   


	}   
	
	function getSignature($MerNo, $BillNo, $Amount, $ReturnURL, $MD5key){
		$_SESSION['MerNo'] = $MerNo;
	  $_SESSION['MD5key'] = $MD5key;
		$sign_params  = array(
			'MerNo'       => $MerNo,
			'BillNo'       => $BillNo, 
			'Amount'         => $Amount,   
			'ReturnURL'       => $ReturnURL
		);
	  $sign_str = "";
	  ksort($sign_params);
	  foreach ($sign_params as $key => $val) {
								   
					$sign_str .= sprintf("%s=%s&", $key, $val);                
					
				}
				//print $sign_str;print '<br/><br/><br/>';
	   return strtoupper(md5($sign_str. strtoupper(md5($MD5key))));   
	
		
	}
	
	     
//    function getSignature($MerNo, $BillNo, $Amount, $ReturnURL, $MD5key){
//		$_SESSION['MerNo'] = $MerNo;
//		$_SESSION['MD5key'] = $MD5key;
//		$sign_params  = array(
//	        'MerNo'       => $MerNo,
//	        'BillNo'       => $BillNo,
//	        'Amount'         => $Amount,
//	        'ReturnURL'       => $ReturnURL
//	    );
//	    $sign_str = "";
//	    ksort($sign_params);
//	    foreach ($sign_params as $key => $val) {
//	        $sign_str .= sprintf("%s=%s&", $key, $val);
//	    }
//	    return strtoupper(md5($sign_str. strtoupper(md5($MD5key))));
//	}
	
//	function getSignature_return($MerNo,$BillNo,$Amount,$responseCode,$MD5key){
//		$sign_params = array(
//			'MerNo'    => $MerNo,
//			'BillNo'   => $BillNo,
//			'Amount'   => $Amount,
//			'responseCode'   => $responseCode
//		);
//		$sign_str = "";
//		ksort($sign_params);
//		foreach ($sign_params as $key => $val) {
//	        $sign_str .= sprintf("%s=%s&", $key, $val);
//	    }
//	    return strtoupper(md5($sign_str. strtoupper(md5($MD5key))));
//	}
}
?>
