<?php
    require 'db_connect.php';
    require 'credentials.php';

    require 'jwt/JWT.php';
    require 'jwt/JWK.php';
    require 'jwt/ExpiredException.php';
    require 'jwt/BeforeValidException.php';
    require 'jwt/SignatureInvalidException.php';

    use \Firebase\JWT\JWT;

    
    if(isset($_POST['email']) && isset($_POST['password'])){
        $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
        $password = filter_input(INPUT_POST, 'password');
        
        $query = "select * from users where email='$email'";
        $result = mysqli_query($con, $query);
        if($result){
            if(mysqli_num_rows($result) > 0){
                $response = mysqli_fetch_array($result);
                if(password_verify($password, $response['password'])){
                    $payload = array(
                        "id" => $response['quid']
                    );
                    $jwt = JWT::encode($payload, $key);
                    $json = array(
                        "status" => "success",
                        "id" => $response['quid'],
                        "firstname" => $response['firstname'],
                        "lastname" => $response['lastname'],
                        "email" => $response['email'],
                        "wallet" => $response['wallet'],
                        "jwt" => $jwt
                    );
                    http_response_code(200);
                    return json_encode($json);
                }else{
                    http_response_code(404);
                    return json_encode(array(
                        "status" => "failed"    
                    ));
                }
            }else{
                http_response_code(404);
                return json_encode(array(
                    "status" => "failed"    
                ));
            }
        }
    }else{
        http_response_code(400);
    }




?>
