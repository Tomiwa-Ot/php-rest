<?php

    
    require 'jwt/JWT.php';
    require 'jwt/JWK.php';
    require 'jwt/ExpiredException.php';
    require 'jwt/BeforeValidException.php';
    require 'jwt/SignatureInvalidException.php';

    require "./vendor/autoload.php";
    
    require 'db_connect.php';
    require 'credentials.php';
    
    use Psr\Http\Message\ResponseInterface;
    use GuzzleHttp\Exception\RequestException;

    use \Firebase\JWT\JWT;



    $quid = "";
    $wallet = "";
    
    
    function registerOnQuidax($email, $firstname, $lastname){
        global $secret;
        $client = new GuzzleHttp\Client([
            'headers' => [
                "Authorization" => "Bearer $secret",
                "Content-Type" => "application/json"
            ]
        ]);
        
        $promise = $client->postAsync(
            "https://www.quidax.com/api/v1/users",
            array(
                'form_params' => array(
                    'email' => $email,
                    'first_name' => $firstname,
                    'last_name' => $lastname
                )
            )
        );
        
        $promise->then(
            function (ResponseInterface $res) {
                global $quid;
                $response = json_decode($res->getBody(), true);
                if($response['status'] == "success"){
                    $quid = $response['data']['id'];
                    createPaymentAddress($quid);
                }
            },
            function (RequestException $e) {
                return $e->getMessage() . "\n";
                return $e->getRequest()->getMethod();
            }
        );

        $promise->wait();
    }
    
 
    function createPaymentAddress($qid){
        global $secret;
        $client = new GuzzleHttp\Client([
            'headers' => [
                "Authorization" => "Bearer $secret",
                "Content-Type" => "application/json"
            ]
        ]);
        
        $promise = $client->postAsync("https://www.quidax.com/api/v1/users/$qid/wallets/btc/addresses");

       $promise->then(
            function (ResponseInterface $res) {
                
                global $quid, $status;
                $response = json_decode($res->getBody(), true);
                if($response['status'] == "success"){
                    fetchPaymentAddress($quid);
                }
            },
            function (RequestException $e) {
                return $e->getMessage() . "\n";
                return $e->getRequest()->getMethod();
            }
        );
       $promise->wait();
    }
    
    
    function fetchPaymentAddress($qid){
        global $secret;
        $client = new GuzzleHttp\Client([
            'headers' => [
                "Authorization" => "Bearer $secret",
                "Content-Type" => "application/json"
            ]
        ]);
        $promise = $client->getAsync("https://www.quidax.com/api/v1/users/$qid/wallets/btc/address");
        
        $promise->then(
            function (ResponseInterface $res) {
                global $wallet;
                $response = json_decode($res->getBody(), true);
                if($response['status'] == "success"){
                    $wallet = $response['data']['address'];
                }
            },
            function (RequestException $e) {
                return $e->getMessage() . "\n";
                return $e->getRequest()->getMethod();
            }
        );
       $promise->wait();

    }
    
    
    
    if(isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] == 'POST'){
        $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
        $firstname = filter_input(INPUT_POST, 'firstname', FILTER_SANITIZE_STRING);
        $lastname = filter_input(INPUT_POST, 'lastname', FILTER_SANITIZE_STRING);
        $password = filter_input(INPUT_POST, 'password');
        $password = password_hash($password, PASSWORD_DEFAULT);
        $pin = filter_input(INPUT_POST, 'pin');
        $pin = password_hash($pin, PASSWORD_DEFAULT);
        
        $q = "select * from users where email='$email'";
        $r = mysqli_query($con, $q);
        if($r){
            if(mysqli_num_rows($r) > 0){
                http_response_code(400);
                return json_encode(array(
                    "status" => "acccount exists"
                ));
            }else{
                registerOnQuidax($email, $firstname, $lastname);
                
                $query = "insert into users(firstname, lastname, email, password, quid, wallet, pin)  values('$firstname', '$lastname', '$email', '$password', '$quid', '$wallet', '$pin')";
                
                $result = mysqli_query($con, $query);
                if($result){
                    $payload = array(
                        "id" => $quid
                    );
                    $jwt = JWT::encode($payload, $key);
                    http_response_code(200);
                    return json_encode(array(
                        "status" => "success",
                        "id" => $quid,
                        "address" => $wallet,
                        "jwt" => $jwt
                    ));
                }
            }
        }
    }else{
        http_response_code(405);
      return "Method not allowed";
    }



?>
