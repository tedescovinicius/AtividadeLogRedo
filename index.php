<?php
$db_handle = pg_connect("host=localhost dbname=teste user=postgres password=123");
$arquivo = fopen ('log.txt', 'r');
$abertas = [];
$to_redo = [];
$Checkpoint = [];

while(!feof($arquivo)){
    $linha = fgets($arquivo, 1024);
    $linha = (str_replace('>','',str_replace('<','',explode(' ',$linha))));
    if($linha[0] == "Start"){ //Checkpoint
        array_push($Checkpoint, str_replace( ')','', str_replace('(','',$linha[2])));
    }
    if($linha[0] == "End"){ //Checkpoint
        for($i = 0; count($to_redo) > $i ; $i++){
            if($Checkpoint[0] != $to_redo[$i]){
                unset($to_redo[$i]);
            };
        }
        unset($Checkpoint[0]);
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
print_r($to_redo);
//rint_r($Checkpoint);
if (!$db_handle) {
    echo "An error occurred.\n";
    exit;
}

$result = pg_query($db_handle, "SELECT * FROM tabela");
  if (!$result) {
    echo "An error occurred.\n";
    exit;
  }
  while ($row = pg_fetch_row($result)) {
        //print_r($row);
  }

?>