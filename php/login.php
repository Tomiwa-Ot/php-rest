<?php
    require 'db_connect.php';
    require 'credentials.php';

    require_once 'jwt/JWT.php';
    require_once 'jwt/JWK.php';
    require_once 'jwt/ExpiredException.php';
    require_once 'jwt/BeforeValidException.php';
    require_once 'jwt/SignatureInvalidException.php';

    use \Firebase\JWT\JWT;

    header("Context-Type:application/json");

    global $key;
    
    if(isset($_POST['email']) && isset($_POST['password'])){
        $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
        $password = $_POST['password'];
        
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
                    echo json_encode($json);
                    http_response_code(200);
                }else{
                    echo json_encode(array(
                        "status" => "failed"    
                    ));
                    http_response_code(404);
                }
            }else{
                echo json_encode(array(
                    "status" => "failed"    
                ));
                http_response_code(404);
            }
        }
    }else{
        http_response_code(400);
    }




?>
