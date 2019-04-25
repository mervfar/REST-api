<?php
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;

$app = new \Slim\App;

$app->options('/{routes:.+}', function (Request $request, Response $response,array $args) {
    return $response;
});

$app->add(function ($req, $res, $next) {
    $response = $next($req, $res);
    return $response
            ->withHeader('Access-Control-Allow-Origin', '*')
            ->withHeader('Access-Control-Allow-Headers', 'X-Requested-With, Content-Type, Accept, Origin, Authorization')
            ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS');
});


// Login Service

$app->post('/api/customer/login', function(Request $request, Response $response,array $args){
    
	$username = $request->getParam('username');
    $password = $request->getParam('password');
   

$sql =  "SELECT musteri_id,adi FROM musteri WHERE kullanici_adi='$username' AND sifre='$password'";

    try{
      // Get DB Object
        $db = new db();
        // Connect
        $db = $db->connect();

        $stmt = $db->query($sql);
		
		
        $customer_login = $stmt->fetch(PDO::FETCH_OBJ);
        $db = null;
	echo json_encode($customer_login);
        
		

    } catch(PDOException $e){
       echo '{"error": {"text": '.$e->getMessage().'}';
    }
});

// Müşteri Hesap Cüzdanı

$app->post('/api/customer/history', function(Request $request, Response $response,array $args){
    
	$user = $request->getParam('musteri_id');
   

$sql =  "SELECT * FROM hesap_cuzdani WHERE musteri_id='$user'";

    try{
      // Get DB Object
        $db = new db();
        // Connect
        $db = $db->connect();

        $stmt = $db->query($sql);
		
		
        $customer_transactions = $stmt->fetchAll(PDO::FETCH_OBJ);
        $db = null;
	echo json_encode($customer_transactions);
        
		

    } catch(PDOException $e){
       echo '{"error": {"text": '.$e->getMessage().'}';
    }
});
// Müşteri Eft Üyeleri
$app->post('/api/customer/transactions', function(Request $request, Response $response,array $args){
    
	$user = $request->getParam('musteri_id');
   //$toplam= $username.$password;

$sql =  "SELECT musteri.adi,musteri.soyadi,musteri.hesap_no FROM musteri inner join musteri_iliskileri on musteri.musteri_id = musteri_iliskileri.iliskili_id where musteri_iliskileri.musteri_id='$user'";
//SELECT adi,soyadi FROM musteri inner join musteri_iliskileri on musteri.musteri_id = musteri_iliskileri.iliskili_id where musteri_iliskileri.musteri_id=

    try{
      // Get DB Object
        $db = new db();
        // Connect
        $db = $db->connect();

        $stmt = $db->query($sql);
		
		
        $customer_eft = $stmt->fetchAll(PDO::FETCH_OBJ);
        $db = null;
	echo json_encode($customer_eft);
        
		

    } catch(PDOException $e){
       echo '{"error": {"text": '.$e->getMessage().'}';
    }
});
// Get All Customers

$app->get('/api/customers', function(Request $request, Response $response){
    $sql = "SELECT * FROM musteri";

    try{
        // Get DB Object
        $db = new db();
        // Connect
        $db = $db->connect();

        $stmt = $db->query($sql);
        $customers = $stmt->fetchAll(PDO::FETCH_OBJ);
        $db = null;
        echo json_encode($customers);
    } catch(PDOException $e){
        echo '{"error": {"text": '.$e->getMessage().'}}';
    }
});

// Get Single Customer
$app->get('/api/customer/{id}', function(Request $request, Response $response,array $args){
    $id = $args['id'];

    $sql = "SELECT * FROM musteri WHERE musteri_id = $id";

    try{
        // Get DB Object
        $db = new db();
        // Connect
        $db = $db->connect();

        $stmt = $db->query($sql);
        $customer = $stmt->fetch(PDO::FETCH_OBJ);
        $db = null;
	echo json_encode($customer);
    } catch(PDOException $e){
        echo '{"error": {"text": '.$e->getMessage().'}';
    }
});

// Şikayet Post

$app->post('/api/customer/post/sikayet', function(Request $request, Response $response,array $args){
    
	$header = $request->getParam('header');
    $body = $request->getParam('body');
    $kimden= $request->getParam('who');


$sql = "INSERT INTO sikayet (baslik, tarih,sahibi,icerik)
            VALUES('$header', NOW(),'$kimden','$body')";
    try{
      // Get DB Object
        $db = new db();
        // Connect
        $db = $db->connect();

        $stmt = $db->query($sql);
		
		
        $stmt->fetchAll(PDO::FETCH_OBJ);
        $db = null;
	echo "true";
        
		

    } catch(PDOException $e){
       echo '{"error": {"text": '.$e->getMessage().'}'; // this is giving error,but i dont find how to fix it :D ALSO still works
    }
});

// EFT Service

$app->post('/api/customer/eft', function(Request $request, Response $response,array $args){
    
	$gonderen_id = $request->getParam('sender_id');
    $alici_hesap_no = $request->getParam('receiver_ac_no');
	$money=$request->getParam('money');
	
  
$sql1 =  "SELECT * FROM musteri WHERE musteri_id='$gonderen_id'";
$sql2 =  "SELECT * FROM musteri WHERE hesap_no='$alici_hesap_no'";


    try{
      // Get DB Object
        $db = new db();
        // Connect
        $db = $db->connect();
//////////////////////////////////
        $gonderen= $db->query($sql1);
		$alici=$db->query($sql2);
		
		$gonderen_all=json_decode(json_encode($gonderen->fetch(PDO::FETCH_OBJ)));
        $alici_all=json_decode(json_encode($alici->fetch(PDO::FETCH_OBJ)));
		
        $gonderen_bakiye=$gonderen_all->bakiye;
		$alici_bakiye=$alici_all->bakiye;
		
		$gonderen_adi_soyadi=($gonderen_all->adi)." ".($gonderen_all->soyadi);
		$alici_adi_soyadi=($alici_all->adi)." ".($alici_all->soyadi);
		
		$gonderen_hesap_no=$gonderen_all->hesap_no;
		$alici_musteri_id=$alici_all->musteri_id;
		
		
		$gonderen_bakiye_yeni=($gonderen_bakiye-$money);
		$alici_bakiye_yeni=($alici_bakiye+$money);
		
		$sql3 =  "UPDATE musteri SET bakiye ='$gonderen_bakiye_yeni' WHERE musteri_id='$gonderen_id'";
		$sql4 =  "UPDATE musteri SET bakiye ='$alici_bakiye_yeni' WHERE musteri_id='$alici_hesap_no'";
		
		$gonderen_update  = $db->query($sql3, PDO::FETCH_ASSOC);
		$alici_update  = $db->query($sql4, PDO::FETCH_ASSOC);
		$sql5 = "INSERT INTO hesap_cuzdani VALUES(
                        NULL,
                        NOW(),
                        'Şuradan Geldi: `$gonderen_adi_soyadi`, Hesap No: `$gonderen_hesap_no`',
                        '0',
                        '$money',
                        '$alici_musteri_id'
                    )";
					
$sql6 = "INSERT INTO hesap_cuzdani  VALUES(
                        NULL,
                        NOW(),
                        'Şuraya Gönderildi: `$alici_adi_soyadi`, Hesap No: `$alici_hesap_no`',
                        '$money',
                        '0',
                        '$gonderen_id'
                    )";
		
		$gonderen_cuzdan_update= $db->query($sql5, PDO::FETCH_ASSOC);
		$alici_cuzdan_update= $db->query($sql6, PDO::FETCH_ASSOC);
		/*
	
	echo ($gonderen_bakiye)+($alici_bakiye);
	echo "\n".($gonderen_bakiye_yeni);
	echo "\n".($alici_bakiye_yeni);
	echo "\n".($gonderen_adi_soyadi);
	echo "\n".($alici_adi_soyadi);
	*/
	echo "true";
	
        
		
$db = null;
    } catch(PDOException $e){
       echo '{"error": {"text": '.$e->getMessage().'}';
    }
});

