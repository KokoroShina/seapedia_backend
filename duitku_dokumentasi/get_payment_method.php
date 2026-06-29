<?php

    // Set kode merchant anda 
    $merchantCode = "DXXXX"; 
    // Set merchant key anda 
    $apiKey = "DXXXXCX80TZJ85Q70QCI";
    // catatan: environtment untuk sandbox dan passport berbeda 

    $datetime = date('Y-m-d H:i:s');  
    $paymentAmount = 10000;

    $stringToSign = $merchantCode . $paymentAmount . $datetime;

    // Generate HMAC SHA256 (output hex lowercase)
    $signature = hash_hmac('sha256', $stringToSign, $apiKey);

    $params = array(
        'merchantcode' => $merchantCode,
        'amount' => $paymentAmount,
        'datetime' => $datetime,
        'signature' => $signature
    );

    $params_string = json_encode($params);

    $url = 'https://sandbox.duitku.com/webapi/api/merchant/paymentmethod/getpaymentmethod'; 

    $ch = curl_init();

    curl_setopt($ch, CURLOPT_URL, $url); 
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");                                                                     
    curl_setopt($ch, CURLOPT_POSTFIELDS, $params_string);                                                                  
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);                                                                      
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(                                                                          
        'Content-Type: application/json',                                                                                
        'Content-Length: ' . strlen($params_string))                                                                       
    );   
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);

    //execute post
    $request = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    if($httpCode == 200)
    {
        $results = json_decode($request, true);
        print_r($results, false);
    }
    else{
        $request = json_decode($request);
        $error_message = "Server Error " . $httpCode ." ". $request->Message;
        echo $error_message;
    }
?>
<!-- 
PARAMETER RESPON GATEAWAY
{
    "paymentFee": [        
        {
            "paymentMethod": "VA",
            "paymentName": "MAYBANK VA",
            "paymentImage": "https://images.duitku.com/hotlink-ok/VA.PNG",
            "totalFee": "0"
        },
        {
            "paymentMethod": "BT",
            "paymentName": "PERMATA VA",
            "paymentImage": "https://images.duitku.com/hotlink-ok/PERMATA.PNG",
            "totalFee": "0"
        },
    ],
    "responseCode": "00",
    "responseMessage": "SUCCESS"
} -->
