<?php

    require "./vendor/autoload.php";
    use Psr\Http\Message\ResponseInterface;
    use GuzzleHttp\Exception\RequestException;
    require 'db_connect.php';
    require 'credentials.php';
    
    header("Context-Type:application/json");
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
                echo $e->getMessage() . "\n";
                echo $e->getRequest()->getMethod();
            }
        );

        $promise->wait();
    }
    
 
    function createPaymentAddress($id){
        global $secret;
        $client = new GuzzleHttp\Client([
            'headers' => [
                "Authorization" => "Bearer $secret",
                "Content-Type" => "application/json"
            ]
        ]);
        
        $promise = $client->postAsync("https://www.quidax.com/api/v1/users/$id/wallets/btc/addresses");

       $promise->then(
            function (ResponseInterface $res) {
                
                global $quid, $status;
                $response = json_decode($res->getBody(), true);
                if($response['status'] == "success"){
                    fetchPaymentAddress($quid);
                }
            },
            function (RequestException $e) {
                echo $e->getMessage() . "\n";
                echo $e->getRequest()->getMethod();
            }
        );
       $promise->wait();
    }
    
    
    function fetchPaymentAddress($id){
        global $secret;
        sleep(2);
        $client = new GuzzleHttp\Client([
            'headers' => [
                "Authorization" => "Bearer $secret",
                "Content-Type" => "application/json"
            ]
        ]);
        $promise = $client->getAsync("https://www.quidax.com/api/v1/users/$id/wallets/btc/address");
        
        $promise->then(
            function (ResponseInterface $res) {
                global $wallet;
                $response = json_decode($res->getBody(), true);
                if($response['status'] == "success"){
                    $wallet = $response['data']['address'];
                }
            },
            function (RequestException $e) {
                echo $e->getMessage() . "\n";
                echo $e->getRequest()->getMethod();
            }
        );
       $promise->wait();

    }
    
    
    
    if($_SERVER['REQUEST_METHOD'] == 'POST'){
        $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
        $firstname = filter_var($_POST['firstname'], FILTER_SANITIZE_STRING);
        $lastname = filter_var($_POST['lastname'], FILTER_SANITIZE_STRING);
        $password = $_POST['password'];
        $password = password_hash($password, PASSWORD_DEFAULT);
        $pin = $_POST['pin'];
        $pin = password_hash($pin, PASSWORD_DEFAULT);
        
        $q = "select * from pos_users where email='$email'";
        $r = mysqli_query($con, $q);
        if($r){
            if(mysqli_num_rows($r) > 0){
                echo json_encode(array(
                    "status" => "acccount exists"
                ));
              http_response_code(400);
            }else{
                registerOnQuidax($email, $firstname, $lastname);
                
                $query = "insert into users(firstname, lastname, email, password, quid, wallet, pin)  values('$firstname', '$lastname', '$email', '$password', '$quid', '$wallet', '$pin')";
                
                $result = mysqli_query($con, $query);
                if($result){
                    echo json_encode(array(
                        "status" => "success",
                        "id" => $quid,
                        "address" => $wallet
                    ));
                  http_response_code(200);
                }
            }
        }
    }else{
      echo "Method not allowed";
      http_response_code(405);
    }



?>