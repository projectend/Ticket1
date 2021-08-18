<?php
class MyCurlComponent extends Component {
	    
	public function MyCurl($url,$data,$authen)
	{      
 
        $headr  =  array();
        $headr[] = 'Content-type: application/json; charset=utf-8';
        $headr[] = 'Authorization:'.$authen;
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_HTTPHEADER,$headr);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 900);                
        $response = curl_exec($ch);
        $info = curl_getinfo($ch);

        if($info['http_code']==200)
        {
            return $response;
        }
        else
        {
            return $this->httpHeaderResult($info['http_code']);
            exit(0);
        }
                
                
	}
    function httpHeaderResult($code)
    {
                $result = array(
                0 => Configure::read('URL_NodeAM')." Connect ECONNREFUSED",
                200 => "OK",
                201 => "Created",
                202 => "Accepted",
                203 => "Non-Authoritative Information",
                204 => "No Content",
                205 => "Reset Content",
                206 => "Partial Content",

        
                300 => "Multiple Choices",
                301 => "Moved Permanently",
                302 => "Found",
                303 => "See Other",
                304 => "Not Modified",
                305 => "Use Proxy",
                306 => "(Unused)",
                307 => "Temporary Redirect",


                400 => "Bad Request",
                401 => "Unauthorized",
                402 => "Payment Required",
                403 => "Forbidden",
                404 => "Not Found",
                405 => "Method Not Allowed",
                406 => "Not Acceptable",
                407 => "Proxy Authentication Required",
                408 => "Request Timeout",
                409 => "Conflict",
                410 => "Gone",
                411 => "Length Required",
                412 => "Precondition Failed",
                413 => "Request Entity Too Large",
                414 => "Request-URI Too Long",
                415 => "Unsupported Media Type",
                416 => "Requested Range Not Satisfiable",
                417 => "Expectation Failed",


                500 => "Internal Server Error",
                501 => "Not Implemented",
                502 => "Bad Gateway",
                503 => "Service Unavailable",
                504 => "Gateway Timeout",
                505 => "HTTP Version Not Supported");
                
                return array('response_code'=>$code,'response_message'=>$result[$code]);        
                
    }
    public function tokenAPI($username,$password)
    {
        $token    = base64_encode($username.":".$password);
        return $token;
    }

    public function MyCurlStore($url,$data,$authen)
    {
        $headr  =  array();
        $headr[] = 'Content-type: application/json; charset=utf-8';
        $headr[] = 'Authorization:'.$authen;
        $headr[] = 'api_key:'.Configure::read('URL_NodeAM_key');
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_HTTPHEADER,$headr);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 900);                
        $response = curl_exec($ch);
        $info = curl_getinfo($ch);

        if($info['http_code']==200)
        {
            return $response;
        }
        else
        {
            return json_encode($this->httpHeaderResult($info['http_code']));
            exit(0);
        }
    }

    public function check_alert_back($data,$data_send)
    {
        if(isset($data['response_code'])){
            $message_e = $data['response_message'];
            $url = str_replace("app/webroot/index.php","",'http://'.$_SERVER["HTTP_HOST"].$_SERVER['PHP_SELF']);
            echo '<script> 
                    $("#content").append(`<div class="modal fade" id="myModal" role="dialog">
                    <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                        <h4 class="modal-title">Connect API Error</h4>
                        </div>
                        <div class="modal-body">
                        <p>Error Code '.$data['response_code'].' : '.$message_e.'.</p>
                        </div>
                    </div>
                    
                    </div>
                </div>`)
            
                $("#myModal").modal({backdrop: "static", keyboard: false})  
                setTimeout(function(){  
                    $("#myModal").modal("hide") 
                    window.location.href = "'.$url.'"
                }, 5000);
                
                </script>';
            exit();
        }

        if(isset($data['code'])){
            $message_e = $data['message'];
            $url = str_replace("app/webroot/index.php","",'http://'.$_SERVER["HTTP_HOST"].$_SERVER['PHP_SELF']);
            echo '<script> 
                    $("#content").append(`<div class="modal fade" id="myModal" role="dialog">
                    <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                        <h4 class="modal-title">API Error</h4>
                        </div>
                        <div class="modal-body">
                        <p>Error Code '.$data['code'].' : '.$message_e.'.</p>
                        </div>
                    </div>
                    
                    </div>
                </div>`)
                $("#myModal").modal({backdrop: "static", keyboard: false})  
                setTimeout(function(){  
                    $("#myModal").modal("hide") 
                    window.location.href = "'.$url.'"
                }, 5000);
                
                </script>';
            exit();
        }
    }

    public function check_data($data,$data_send)
    {

        if(isset($data['response_code'])){
            return array('code' => $data['response_code'],'message' => $data['response_message'],'data'=>'');
        }

        if(isset($data['code'])){
            return array('code' => $data['code'],'message' => $data['message'],'data'=>$data_send);
            
        }

        return $data;
    }
}