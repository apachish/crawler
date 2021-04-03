<?php

namespace Application\helpers;



class Helper
{
    public static function post_request($url,$data)
    {
        $options = array(
            'http' => array(
                'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
                'method'  => 'POST',
                'content' => http_build_query($data),
            ),
        );
        $context  = stream_context_create($options);
        try{
            @$result = file_get_contents($url, false, $context);
            return $result;
        }catch (CException $x)
        {
            return false;
        }
    }
    public static function post_request_curl($url, $data=array(),$header=false)
    {
        if(is_string($data))
        {
            $postData = $data;
        }else{
            $postData = '';
            //create name value pairs separated by &
            foreach($data as $k => $v)
            {
                $postData .= $k . '='.$v.'&';
            }
            rtrim($postData, '&');
        }
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL,$url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER,true);
        curl_setopt($ch, CURLOPT_HEADER, $header);
        curl_setopt($ch, CURLOPT_POST, count($postData));
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
        curl_setopt($ch, CURLOPT_TIMEOUT, 60);
        $output=curl_exec($ch);
        curl_close($ch);
        return $output;
    }
    public static function soap_request_curl($xml_data,$URL)
    {
        $ch = curl_init($URL);

        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($ch, CURLOPT_TIMEOUT,        10);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1 );
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_POST,           1 );
        curl_setopt($ch, CURLOPT_HTTPHEADER,     array('Content-Type: text/xml; charset=utf-8'));
        curl_setopt($ch, CURLOPT_POSTFIELDS,     $xml_data);

        $output = curl_exec($ch);
        curl_close($ch);
        return $output;
    }
    public static function isJson($string)
    {
        json_decode($string);
        return (json_last_error() == JSON_ERROR_NONE);
    }
    public static function checkImageSize($imageFile,$maxDim=500)
    {
        list($width, $height, $type, $attr) = getimagesize( $imageFile );
        if ( $width > $maxDim || $height > $maxDim ) {
            $target_filename = $imageFile;
            $fn = $imageFile;
            $size = getimagesize( $fn );
            $ratio = $size[0]/$size[1]; // width/height
            if( $ratio > 1) {
                $width = $maxDim;
                $height = $maxDim/$ratio;
            } else {
                $width = $maxDim*$ratio;
                $height = $maxDim;
            }
            $src = imagecreatefromstring( file_get_contents( $fn ) );
            $dst = imagecreatetruecolor( $width, $height );
            imagecopyresampled( $dst, $src, 0, 0, 0, 0, $width, $height, $size[0], $size[1] );
            imagedestroy( $src );
            imagepng( $dst, $target_filename ); // adjust format as needed
            imagedestroy( $dst );
            return $dst;
        }
    }
    public static function is_empty($string, $if_true=true){
        if(!isset($string) || is_null($string)){
            return $if_true;
        }
        return false;
    }
    public static function sort_by_field(&$array,$field,$type='string',$order="ASC"){
        switch ($type){
            case 'string':
                usort($array,function($a,$b)use ($field){
                    return strcmp($a[$field], $b[$field]);
                });
                break;
            case 'date':
                if($order == 'DESC')
                    usort($array,function($a,$b)use ($field){
                            return strtotime($a[$field]) - strtotime($b[$field]);
                    });
                else
                    usort($array,function($a,$b)use ($field){
                        return strtotime($b[$field]) - strtotime($a[$field]) ;
                    });
                break;
            case 'digint':
                usort($array,function($a,$b)use ($field){
                    return $a[$field]-$b[$field];
                });
                break;
        }

    }

    public static function get_standard_date()
    {
        return date("Y-m-d H:i:s");
    }
    public static function format_size_units($bytes)
    {
        if ($bytes >= 1073741824)
        {
            $bytes = number_format($bytes / 1073741824, 2) . ' GB';
        }
        elseif ($bytes >= 1048576)
        {
            $bytes = number_format($bytes / 1048576, 2) . ' MB';
        }
        elseif ($bytes >= 1024)
        {
            $bytes = number_format($bytes / 1024, 2) . ' kB';
        }
        elseif ($bytes > 1)
        {
            $bytes = $bytes . ' bytes';
        }
        elseif ($bytes == 1)
        {
            $bytes = $bytes . ' byte';
        }
        else
        {
            $bytes = '0 bytes';
        }

        return $bytes;
    }

    /**get and parse request and manuplate them
     * @param $string
     * @param string $default_value
     * @return string
     */
    public static function req($string, $default_value='')
    {
        $command = explode('.',$string);

        $request = $_REQUEST;
        foreach ($command as $parm){
            if(isset($request[$parm])){
                $request = $request[$parm];
            }else{
                return $default_value;
            }
        }
        return $request;
    }

    /**
     * return user ip
     * @return string
     */
    public static function getIp()
    {
        // populate a local variable to avoid extra function calls.
        // NOTE: use of getenv is not as common as use of $_SERVER.
        //       because of this use of $_SERVER is recommended, but
        //       for consistency, I'll use getenv below
        $tmp = getenv("HTTP_CLIENT_IP");
        // you DON'T want the HTTP_CLIENT_ID to equal unknown. That said, I don't
        // believe it ever will (same for all below)
        if ($tmp && strcasecmp($tmp, "unknown")!=0 )
            return $tmp;

        $tmp = getenv("HTTP_X_FORWARDED_FOR");
        if ($tmp && strcasecmp($tmp, "unknown")!=0 )
            return $tmp;

        // no sense in testing SERVER after this.
        // $_SERVER[ 'REMOTE_ADDR' ] == gentenv( 'REMOTE_ADDR' );
        $tmp = getenv("REMOTE_ADDR");
        if ($tmp && strcasecmp($tmp, "unknown")!=0 )
            return $tmp;

        return ("unknown");
    }

    public static function getUserAgent(){
        return $_SERVER['HTTP_USER_AGENT'];
    }

    public static function has_uppercase_char($string) {
        if(preg_match("/[A-Z]/", $string)===0) {
            return false;
        }
        return true;
    }

    /**
     * check whether value is equal to obj[key] if is equal return true else return default value
     * @param $obj
     * @param $key
     * @param $value
     * @param bool $on_true
     * @param bool $on_false
     * @return bool
     */
    public static function eq($obj, $key, $value, $on_true = true, $on_false = false){
        $obj = static::g($obj,$key,$obj);
        if($obj==$value){
            return $on_true;
        }
        return $on_false;
    }

    /**get value of array
     * @param $request
     * @param $string
     * @param string $default_value
     * @return string
     */
    public static function g($request, $string, $default_value = '')
    {
        $command = explode('.', $string);

        if (is_array($command)) {
            foreach ($command as $parm) {
                if (isset($request[$parm])) {
                    $request = $request[$parm];
                } else {
                    return $default_value;
                }
            }
        } else {
            if (isset($request[$string])) {
                return $request[$string];
            } else {
                return $default_value;
            }
        }
        return $request;
    }

    /** convert string to http format
     * @param $url
     * @return string     
     */
    public static function get_url($url)
    {
        if (!preg_match("~^(?:f|ht)tps?://~i", $url)) {
            $url = "http://" . $url;
        }
        return $url;

    }

    public static function is_url($address)
    {
        if(strpos($address,'http://')===false)
            return false;
        return true;
    }

    public static function perfix_mobile($telephon){
        $pattern = '/(0|98|\+98)?(\w{10})/i';
        $replacement = '98${2}';
        return preg_replace($pattern, $replacement, $telephon);
    }
    public static function check_oprator_mobile($telephon){
        $pattern = '/(0|98|\+98)?(\w{3})(\w{7})/i';
        $replacement = '${2}';
        $pernumber = preg_replace($pattern, $replacement, $telephon);
        //0910 ، 0919 hamrah aval
        if($pernumber >= 910 && $pernumber<= 919){
            return true;
        }
        return false;
    }
    public static function isValidIranianNationalCode($input) {
        # check if input has 10 digits that all of them are not equal
        if (!preg_match("/^\d{10}$/", $input)) {
            return false;
        }

        $check = (int) $input[9];

        $sum = array_sum(array_map(function ($x) use ($input) {
                return ((int) $input[$x]) * (10 - $x);
            }, range(0, 8))) % 11;

        return ($sum < 2 && $check == $sum) || ($sum >= 2 && $check + $sum == 11);
    }
    public static function isValidMobileNumber($input) {
        # check if input has 10 digits that all of them are not equal
        if (!preg_match('/^(\+98|98|0)\d{10,10}$/', $input)) {
            return false;
        }
        return true;
    }
    public static function checkpatarntelephon($telephon){
        $pattern = '/(0|98|\+98)?(\w{10})/i';
        if(!preg_match($pattern,$telephon))
            return true;
        return false;
    }

    public static function is_strenght_password($password)
    {
        /**
         * check for strong password
         */
        if(!preg_match('/^(?=.*\d)(?=.*[A-Za-z])[0-9A-Za-z!@#$%]{8,30}$/', $password)) {
            return false;
        }
        return true;
    }

}