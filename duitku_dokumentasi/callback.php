<?php
$apiKey = 'XXXXXXXXXX7968XXXXXXXXXFB05332AF'; // API key anda
$merchantCode = isset($_POST['merchantCode']) ? $_POST['merchantCode'] : null; 
$amount = isset($_POST['amount']) ? $_POST['amount'] : null; 
$merchantOrderId = isset($_POST['merchantOrderId']) ? $_POST['merchantOrderId'] : null; 
$productDetail = isset($_POST['productDetail']) ? $_POST['productDetail'] : null; 
$additionalParam = isset($_POST['additionalParam']) ? $_POST['additionalParam'] : null; 
$paymentMethod = isset($_POST['paymentCode']) ? $_POST['paymentCode'] : null; 
$resultCode = isset($_POST['resultCode']) ? $_POST['resultCode'] : null; 
$merchantUserId = isset($_POST['merchantUserId']) ? $_POST['merchantUserId'] : null; 
$reference = isset($_POST['reference']) ? $_POST['reference'] : null; 
$signature = isset($_POST['signature']) ? $_POST['signature'] : null; 
$publisherOrderId = isset($_POST['publisherOrderId']) ? $_POST['publisherOrderId'] : null; 
$spUserHash = isset($_POST['spUserHash']) ? $_POST['spUserHash'] : null; 
$settlementDate = isset($_POST['settlementDate']) ? $_POST['settlementDate'] : null; 
$issuerCode = isset($_POST['issuerCode']) ? $_POST['issuerCode'] : null; 

//log callback untuk debug 
// file_put_contents('callback.txt', "* Callback *\r\n", FILE_APPEND | LOCK_EX);

if(!empty($merchantCode) && !empty($amount) && !empty($merchantOrderId) && !empty($signature))
{
    // Generate signature
    $stringToSign = $merchantCode . $amount . $merchantOrderId;
    $calcSignature = hash_hmac('sha256', $stringToSign, $apiKey);

    if($signature == $calcSignature)
    {
        //Callback tervalidasi
        //Silahkan rubah status transaksi anda disini
        // file_put_contents('callback.txt', "* Success *\r\n\r\n", FILE_APPEND | LOCK_EX);

    }
    else
    {
        // file_put_contents('callback.txt', "* Bad Signature *\r\n\r\n", FILE_APPEND | LOCK_EX);
        throw new Exception('Bad Signature')
    }
}
else
{
    // file_put_contents('callback.txt', "* Bad Parameter *\r\n\r\n", FILE_APPEND | LOCK_EX);
    throw new Exception('Bad Parameter')
}
?>
