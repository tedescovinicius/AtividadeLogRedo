<?php
$db_handle = pg_connect("host=localhost dbname=teste user=postgres password=123");
pg_query($db_handle, 'truncate table tabela');
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
    //print_r($linha);
    if($linha[0][1] == ","){
   //    print_r($linha);
        $coluna = $linha[0][0] ;
        $id =  $linha[0][2];
        $valor = explode('=' ,$linha[0])[1]; 
      //  print_r($coluna);
        if($coluna == 'A'){
               $result = pg_query_params($db_handle, "insert into tabela(id,a,b)  values($1,$2,$3)", array($id,$valor,null));
            if (!$result) {
                echo "An error occurred.\n";
                exit;
            }
        }else if($coluna == 'B'){
            $result = pg_query_params($db_handle, "insert into tabela(id,a,b)  values($1,$2,$3)", array($id,null,$valor));
            if (!$result) {
                echo "An error occurred.\n";
                exit;
            }
        }
    }
         
    if($linha[0] == "Start"){ //Checkpoint
        array_push($Checkpoint, str_replace(')','', str_replace('(','',$linha[2])));   //TO-DO resolver para caso onde check tem 2 variaveis
    }

    if($linha[0] == "End"){ //Checkpoint
        for($i = 0; count($to_redo) > $i ; $i++){
            if($Checkpoint[0] != $to_redo[$i]){
                $key = array_search($to_redo[$i], $abertas);
                unset($abertas[$key]);
                unset($to_redo[$i]);
                
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
  
    $altera = (explode(',',$linha[0]));

    if(count($altera) == 4){
        foreach($redu as $value){
            if(trim($altera[0]) == trim($value)){
               print_r($altera);
                if($altera[2] == 'A'){
                    $result = pg_query_params($db_handle, 'select A from tabela where id = $1 and B is null', array($altera[1]));
                    while ($row = pg_fetch_row($result)) {
                        if($row[0] != $altera[3]){
                            $result = pg_query_params($db_handle, 'update tabela SET A = $1 where id = $2 and B is null', array($altera[3],$altera[1]));
                        }
                    }  
                }else if($altera[2] == 'B'){
                    $result = pg_query_params($db_handle, 'select B from tabela where id = $1 and A is null', array($altera[1]));
                    while ($row = pg_fetch_row($result)) {
                        if($row[0] != $altera[3]){
                            $result = pg_query_params($db_handle, 'update tabela SET B = $1 where id = $2 and A is null', array($altera[3],$altera[1]));
                        }
                    }  
                }    
            }
        };    
    }
}   
fclose($arquivo);


foreach($dont_redu as $value){
    echo('n??o fez redu' .' '. $value);
}

foreach($redu as $value){
    echo('Fez redu' .' '. $value);
}


?>
