<?php
    require 'credentials.php';

    require 'jwt/JWT.php';
    require 'jwt/JWK.php';
    require 'jwt/ExpiredException.php';
    require 'jwt/BeforeValidException.php';
    require 'jwt/SignatureInvalidException.php';

    require "./vendor/autoload.php";

    use Psr\Http\Message\ResponseInterface;
    use GuzzleHttp\Exception\RequestException;

    use \Firebase\JWT\JWT;

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

    if(isset($_SERVER['HTTP_AUTHORIZATION'])){
        try{
            $decoded = JWT::decode(filter_input(INPUT_SERVER, 'HTTP_AUTHORIZATION'), $key, array('HS256'));
            if(isset($_POST['address'])){
                $address = filter_input(INPUT_POST, 'address');
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
            }else{
                echo json_encode(array(
                    "message" => "Bad Request"
                ));
                http_response_code(400);
            }
        }catch (Exception $e){
            echo json_encode(array(
                "message" => "Access denied",
            ));
            http_response_code(401);
        }
    }else{
        echo json_encode(array(
            "message" => "Access denied",
        ));
        http_response_code(401);
    }    

    

?>
