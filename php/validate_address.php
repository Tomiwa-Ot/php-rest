<?php
    
    require "./vendor/autoload.php";
    use Psr\Http\Message\ResponseInterface;
    use GuzzleHttp\Exception\RequestException;

    require 'credentials.php';

    $output = false;

    function validateAddress($address){
        global $secret;
        $client = new GuzzleHttp\Client([
            'headers' => [
                "Authorization" => "Bearer $secret",
                "Content-Type" => "application/json"
            ]
        ]);
        
        $promise = $client->getAsync("https://www.quidax.com/api/v1/btc/$address/validate_address");
        
        $promise->then(
            function (ResponseInterface $res) {
                global $output;
                $response = json_decode($res->getBody(), true);
                $output = $response['data']['valid'] ? true : false;
            },
            function (RequestException $e) {
                echo $e->getMessage() . "\n";
                echo $e->getRequest()->getMethod();
            }
        );

        $promise->wait();
      
    }
    
    if(isset($_POST['address'])){
        $address = $_POST['address'];
        validateAddress($address);
        if($output){
            echo json_encode(array(
                "status" => true
            ));
            http_response_code(200);
        }else{
            echo json_encode(array(
                "status" => false
            ));
            http_response_code(400);
        }
    }


?>
