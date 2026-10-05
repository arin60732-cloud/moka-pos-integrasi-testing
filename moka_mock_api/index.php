<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

$f=__DIR__."/data.json";
if(!file_exists($f)) file_put_contents($f,json_encode([
"products"=>[
["id"=>"P001","name"=>"Kopi Arabica","stock"=>20,"price"=>25000],
["id"=>"P002","name"=>"Teh Manis","stock"=>15,"price"=>12000],
["id"=>"P003","name"=>"Roti Cokelat","stock"=>10,"price"=>15000]
],"transactions"=>[]
],JSON_PRETTY_PRINT));

function out($s,$d){http_response_code($s);echo json_encode($d,JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE);exit;}
function db(){global $f;return json_decode(file_get_contents($f),true);}
function save($d){global $f;file_put_contents($f,json_encode($d,JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE));}
function parts(){
    $p = parse_url($_SERVER["REQUEST_URI"], PHP_URL_PATH);
    $script = str_replace("\\", "/", $_SERVER["SCRIPT_NAME"]);

    if (strpos($p, $script) === 0) {
        $p = substr($p, strlen($script));
    } else {
        $dir = rtrim(str_replace("\\", "/", dirname($script)), "/");
        if ($dir && strpos($p, $dir) === 0) {
            $p = substr($p, strlen($dir));
        }
    }

    return array_values(array_filter(explode("/", trim($p, "/"))));
}
$st=$_GET["mock_status"]??"";
if($st==="429")out(429,["status"=>"error","message"=>"Too Many Requests (simulasi)."]);
if($st==="503")out(503,["status"=>"error","message"=>"Service Unavailable (simulasi)."]);
if($st==="slow")sleep(3);

$ps=parts();$m=$_SERVER["REQUEST_METHOD"];$d=db();
if(!$ps)out(200,["status"=>"success","service"=>"Moka POS Mock API","note"=>"API simulasi untuk praktikum."]);
if(($ps[0]??"")!=="api")out(404,["status"=>"error","message"=>"Endpoint tidak ditemukan."]);

if(($ps[1]??"")==="health")out(200,["status"=>"success","service"=>"moka-pos-mock-api"]);
if(($ps[1]??"")==="products" && $m==="GET"){
 if(isset($ps[2]))foreach($d["products"] as $p)if($p["id"]===$ps[2])out(200,["status"=>"success","data"=>$p]);
 if(isset($ps[2]))out(404,["status"=>"error","message"=>"Product not found."]);
 out(200,["status"=>"success","data"=>$d["products"]]);
}
if(($ps[1]??"")==="transactions" && $m==="GET")out(200,["status"=>"success","data"=>$d["transactions"]]);

if(($ps[1]??"")==="sync" && $m==="POST"){
 $h=function_exists("getallheaders")?getallheaders():[];
 $a=$h["Authorization"]??$h["authorization"]??"";
 if($a!=="Bearer demo-token-123")out(401,["status"=>"error","message"=>"Unauthorized: token tidak valid."]);
 $x=json_decode(file_get_contents("php://input"),true);
 if(!is_array($x))out(400,["status"=>"error","message"=>"Body harus JSON."]);
 foreach(["transaction_id","product_id","quantity"] as $k)if(!isset($x[$k])||$x[$k]==="")out(400,["status"=>"error","message"=>"Field wajib belum diisi.","field"=>$k]);
 if(!is_string($x["transaction_id"])||!is_string($x["product_id"])||!is_numeric($x["quantity"]))out(400,["status"=>"error","message"=>"Tipe data tidak valid."]);
 $q=(int)$x["quantity"];
 foreach($d["transactions"] as $t)if($t["transaction_id"]===$x["transaction_id"])out(409,["status"=>"error","message"=>"Duplicate transaction."]);
 $i=null;foreach($d["products"] as $n=>$p)if($p["id"]===$x["product_id"]){$i=$n;break;}
 if($i===null)out(404,["status"=>"error","message"=>"Product not found."]);
 if($q>$d["products"][$i]["stock"])out(409,["status"=>"error","message"=>"Insufficient stock.","available_stock"=>$d["products"][$i]["stock"]]);
 $d["products"][$i]["stock"]-=$q;
 $t=["transaction_id"=>$x["transaction_id"],"product_id"=>$x["product_id"],"quantity"=>$q,"total"=>$q*$d["products"][$i]["price"],"status"=>"synchronized"];
 $d["transactions"][]=$t;save($d);
 out(200,["status"=>"success","message"=>"Transaction synchronized.","data"=>$t,"remaining_stock"=>$d["products"][$i]["stock"]]);
}
out(404,["status"=>"error","message"=>"Endpoint tidak ditemukan."]);
?>