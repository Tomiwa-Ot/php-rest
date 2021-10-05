<?php
    require 'credentials.php';

    require_once 'jwt/JWT.php';
    require_once 'jwt/JWK.php';
    require_once 'jwt/ExpiredException.php';
    require_once 'jwt/BeforeValidException.php';
    require_once 'jwt/SignatureInvalidException.php';

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
                return $e->getMessage() . "\n";
                return $e->getRequest()->getMethod();
            }
        );

        $promise->wait();
      
    }

    if(isset($_SERVER['HTTP_AUTHORIZATION'])){
        try{
            $decoded = JWT::decode($_SERVER['HTTP_AUTHORIZATION'], $key, array('HS256'));
            if(isset($_POST['address'])){
                $address = $_POST['address'];
                validateAddress($address);
                if($output){
                    http_response_code(200);
                    return json_encode(array(
                        "status" => true
                    ));
                }else{
                    http_response_code(400);
                    return json_encode(array(
                        "status" => false
                    ));
                }
            }else{
                http_response_code(400);
                return json_encode(array(
                    "message" => "Bad Request"
                ));
            }
        }catch (Exception $e){
            http_response_code(401);
            return json_encode(array(
                "message" => "Access denied",
            ));
        }
    }else{
        http_response_code(401);
        return json_encode(array(
            "message" => "Access denied",
        ));
    }    

    

?>
