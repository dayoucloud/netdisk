<?php
include 'merchantProperties.php';
/*
 * @Description 易汇金支付产品通用接口范例 
 * @V1.0
 * @Author ma.chao
 */
 	
	#	产品通用接口测试请求地址
	$reqURL_onLine = "https://ehkpay.ehking.com/gateway/controller.action";   #生产地址
   # $reqURL_onLine = "http://qa.ehkpay.ehking.com/gateway/controller.action";  #QA测试地址
	# 业务类型
	# 支付请求，固定值"Buy" .	
	$p0_Cmd = "Buy";
		
	
#签名函数生成签名串
function getReqHmacString($p2_Order,$p3_Cur,$p4_Amt,$p5_Pid,$p6_Pcat,$p7_Pdesc,$p8_Url,$p9_MP,$pa_FrpId)
{
  global $p0_Cmd;
	include 'merchantProperties.php';
		
	#进行签名处理，一定按照文档中标明的签名顺序进行
  $sbOld = "";
  #加入业务类型
  $sbOld = $sbOld.$p0_Cmd;
  #加入商户编号
  $sbOld = $sbOld.$p1_MerId;
  #加入商户订单号
  $sbOld = $sbOld.$p2_Order; 
  #加入交易币种
  $sbOld = $sbOld.$p3_Cur;
  #加入支付金额
  $sbOld = $sbOld.$p4_Amt;
  #加入商品名称
  $sbOld = $sbOld.$p5_Pid;
  #加入商品分类
  $sbOld = $sbOld.$p6_Pcat;
  #加入商品描述
  $sbOld = $sbOld.$p7_Pdesc;
  #加入商户接收支付成功数据的地址
  $sbOld = $sbOld.$p8_Url;
  #加入商户扩展信息
  $sbOld = $sbOld.$p9_MP;
  #加入支付通道编码
  $sbOld = $sbOld.$pa_FrpId;
	logstr($p2_Order,$sbOld,HmacMd5($sbOld,$merchantKey));
  return HmacMd5($sbOld,$merchantKey);
  
} 

function getCallbackHmacString($p1_MerId,$r0_Cmd,$r1_Code,$r2_TrxId,$r3_Amt,$r4_Cur,$r5_Pid,$r6_Order,$r8_MP,$r9_BType,$ro_BankOrderId,$rp_PayDate)
{
  
	include 'merchantProperties.php';
  
	#取得加密前的字符串
	$sbOld = "";
	#加入商家ID
	$sbOld = $sbOld.$p1_MerId;
	#加入消息类型
	$sbOld = $sbOld.$r0_Cmd;
	#加入业务返回码
	$sbOld = $sbOld.$r1_Code;
	#加入交易ID
	$sbOld = $sbOld.$r2_TrxId;
	#加入交易金额
	$sbOld = $sbOld.$r3_Amt;
	#加入货币单位
	$sbOld = $sbOld.$r4_Cur;
	#加入产品Id
	$sbOld = $sbOld.$r5_Pid;
	#加入订单ID
	$sbOld = $sbOld.$r6_Order;
	#加入商家扩展信息
	$sbOld = $sbOld.$r8_MP;
	#加入交易结果返回类型
	$sbOld = $sbOld.$r9_BType;
	#银行订单号
	$sbOld = $sbOld.$ro_BankOrderId;
	#支付成功时间
	$sbOld = $sbOld.$rp_PayDate;

	logstr($r6_Order,$sbOld,HmacMd5($sbOld,$merchantKey));
	return HmacMd5($sbOld,$merchantKey);

}


#	取得返回串中的所有参数
function getCallBackValue(&$p1_MerId,&$r0_Cmd,&$r1_Code,&$r2_TrxId,&$r3_Amt,&$r4_Cur,&$r5_Pid,&$r6_Order,&$r8_MP,&$r9_BType,&$ro_BankOrderId,&$rp_PayDate,&$hmac)
{  
	$p1_MerId           = $_REQUEST['p1_MerId'];
	$r0_Cmd				= $_REQUEST['r0_Cmd'];
	$r1_Code			= $_REQUEST['r1_Code'];
	$r2_TrxId			= $_REQUEST['r2_TrxId'];
	$r3_Amt				= $_REQUEST['r3_Amt'];
	$r4_Cur				= $_REQUEST['r4_Cur'];
	$r5_Pid				= $_REQUEST['r5_Pid'];
	$r6_Order			= $_REQUEST['r6_Order'];
	$r8_MP				= $_REQUEST['r8_MP'];
	$r9_BType			= $_REQUEST['r9_BType']; 
	$ro_BankOrderId		= $_REQUEST['ro_BankOrderId'];
	$rp_PayDate			= $_REQUEST['rp_PayDate'];
	$hmac				= $_REQUEST['hmac'];
	
	return null;
}

function CheckHmac($p1_MerId,$r0_Cmd,$r1_Code,$r2_TrxId,$r3_Amt,$r4_Cur,$r5_Pid,$r6_Order,$r8_MP,$r9_BType,$ro_BankOrderId,$rp_PayDate,$hmac)
{
	if($hmac==getCallbackHmacString($p1_MerId,$r0_Cmd,$r1_Code,$r2_TrxId,$r3_Amt,$r4_Cur,$r5_Pid,$r6_Order,$r8_MP,$r9_BType,$ro_BankOrderId,$rp_PayDate))
		return true;
	else
		return false;
}
		
  
function HmacMd5($data,$key)
{
// RFC 2104 HMAC implementation for php.
// Creates an md5 HMAC.
// Eliminates the need to install mhash to compute a HMAC
// Hacked by Lance Rushing(NOTE: Hacked means written)

//需要配置环境支持iconv，否则中文参数不能正常处理
$key = iconv("GB2312","UTF-8",$key);
$data = iconv("GB2312","UTF-8",$data);

$b = 64; // byte length for md5
if (strlen($key) > $b) {
$key = pack("H*",md5($key));
}
$key = str_pad($key, $b, chr(0x00));
$ipad = str_pad('', $b, chr(0x36));
$opad = str_pad('', $b, chr(0x5c));
$k_ipad = $key ^ $ipad ;
$k_opad = $key ^ $opad;

return md5($k_opad . pack("H*",md5($k_ipad . $data)));
}

function logstr($orderid,$str,$hmac)
{
include 'merchantProperties.php';
$james=fopen($logName,"a+");
fwrite($james,"\r\n".date("Y-m-d H:i:s")."|orderid[".$orderid."]|str[".$str."]|hmac[".$hmac."]");
fclose($james);
}

?> 