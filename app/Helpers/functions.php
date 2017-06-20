<?php
if ( !function_exists( 'response_success' ) ) {
    function response_success ( $data = [] , $code = 0 , $msg = '操作成功' )
    {
        return response()->json( [
            'data' => $data ,
            'code' => $code ,
            'msg'  => $msg
        ] );
    }
}

if ( !function_exists( 'response_fail' ) ) {
    function response_fail ( $code = -1 , $msg = '操作失败' , $data = [] )
    {
        return response()->json( [
            'data' => $data ,
            'code' => $code ,
            'msg'  => $msg
        ] );
    }
}

//只保留小数点后6位
if ( !function_exists( 'six_floor' ) ) {
    function six_floor ( $v )
    {
        return floor( ( $v ) * 1000000 ) / 1000000;

    }
}
