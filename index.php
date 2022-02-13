<?php
$db_handle = pg_connect("host=localhost dbname=teste user=postgres password=123");
$arquivo = fopen ('log.txt', 'r');
$abertas = [];
$to_redo = [];
$Checkpoint = [];

if (!$db_handle) {
    echo "An error occurred.\n";
    exit;
}

while(!feof($arquivo)){
    $linha = fgets($arquivo, 1024);
    $linha = (str_replace('>','',str_replace('<','',explode(' ',$linha))));

    if($linha[0] == "Start"){ //Checkpoint
        array_push($Checkpoint, str_replace(')','', str_replace('(','',$linha[2])));   //TO-DO resolver para caso onde check tem 2 variaveis
    }

    if($linha[0] == "End"){ //Checkpoint
        for($i = 0; count($to_redo) > $i ; $i++){
            if($Checkpoint[0] != $to_redo[$i]){
                unset($to_redo[$i]);
                $key = array_search($to_redo[$i], $abertas);
                unset($abertas[$key]);
            };
        }
        $Checkpoint = [];
    }   

    if($linha[0] == 'commit'){
        if(in_array($linha[1], $abertas)){
            array_push($to_redo,$linha[1]);
        }
    }

    if($linha[0] == 'start'){
        array_push($abertas,$linha[1] );
    }
}
fclose($arquivo);

$dont_redu = array_diff($abertas, $to_redo);
$redu = $to_redo;

$arquivo = fopen ('log.txt', 'r');

while(!feof($arquivo)){ 
    $linha = fgets($arquivo, 1024);
    $linha = (str_replace('>','',str_replace('<','',explode(' ',$linha))));
    if(count($linha) == 1){
        $altera = (explode(',',$linha[0]));
        foreach($redu as $value){
            if($altera[0] == $value){
                $result = pg_prepare($db_handle, "update table table set $a = $valor where id = $id");
                $result = pg_execute($db_handle,  array($a = $altera[2],$valor =  $altera[3],$id = $altera[1]));
    
                if (!$result) {
                    echo "An error occurred.\n";
                    exit;
                }
            }
        }
    };

    
        

}   
fclose($arquivo);


foreach($dont_redu as $value){
   // echo('não fez redu' .' '. $value);
}

foreach($redu as $value){
   // echo('Fez redu' .' '. $value);
}


?>
