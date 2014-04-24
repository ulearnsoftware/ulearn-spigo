<?
session_start();

require_once("../lib/misc.lib.php");

$id_materia_periodo_lectivo=recoger_valor("id_materia_periodo_lectivo",$_GET,$_POST);
$username=recoger_valor("username",$_GET,$_POST);


   if(isset($_POST['Ingresar'])){
      header("Location: chatflash.php?id_materia_periodo_lectivo=$id_materia_periodo_lectivo&username=$username");
   }




$sContenido= "<form method=POST>".
               "<table>".
                  "<tr>".
                     "<td><input type='submit' name='Ingresar' value='Ingresar'>".
                        "<input type='hidden' name='id_materia_periodo_lectivo' value=$id_materia_periodo_lectivo>".
                        "<input type='hidden' name='username' value='username'></td>".
                  "</tr>".
               "</table>".
               "</form>";
?>
<html>
<body>
<?
   print $sContenido;
?>
</body>
</html>
