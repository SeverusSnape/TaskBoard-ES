<?php
// Setear zona horaria por defecto a UTC
date_default_timezone_set('UTC'); 

// Arregla con el agotador problema 'get_magic_quotes_gpc' en algunos hostings compartidos
// Fuente: http://stackoverflow.com/questions/517008/how-to-turn-off-magic-quotes-on-shared-hosting
/*
// Esto parece que no procesaba _POST bien (ejemplo: Tambien se escapa \r\n. Donde /r/n es como linux/UNIX lo ve... 
// esto significa que magicquotes no tocara \r\n como no es "/", pero si podria una barra. Es una receta para un problema) 
if (get_magic_quotes_gpc() === 1)
{
    $_GET = json_decode(stripslashes(json_encode($_GET, JSON_HEX_APOS)), true);
    $_POST = json_decode(stripslashes(json_encode($_POST, JSON_HEX_APOS)), true);
    $_COOKIE = json_decode(stripslashes(json_encode($_COOKIE, JSON_HEX_APOS)), true);
    $_REQUEST = json_decode(stripslashes(json_encode($_REQUEST, JSON_HEX_APOS)), true);
}
*/
// ¿Este parece que funciona? (Honestamente, tan solo desactiva magic_quotes_gpc )
if ( in_array( strtolower( ini_get( 'magic_quotes_gpc' ) ), array( '1', 'on' ) ) )
{
    $_POST = array_map( 'stripslashes', $_POST );
    $_GET = array_map( 'stripslashes', $_GET );
    $_COOKIE = array_map( 'stripslashes', $_COOKIE );
}


// El sistema de sesiones ayudara a almacenar los archivos todavia no aprobados
// o imagenes, mientra el captcha es procesado
ini_set("session.use_cookies",0);
ini_set("session.use_only_cookies",0);
//ini_set("session.use_trans_sid",1);
session_start();

//Importar los archivos necesarios
require_once("settings.php");
require_once("LayoutEngine.php");
require_once("Database.php");
require_once("Taskboard.php");
require_once("anonregkit.php");

// Para documentos de renderizado
require_once("./phpmarkdown/markdown.php");
require_once("./htmlpurifier/library/HTMLPurifier.auto.php");
//require("./asciicaptcha/asciicaptcha.php");


//Abrir la conexion a la DB
Database::openDatabase('rw', $config['database']['dsn'], $config['database']['username'], $config['database']['password']);

//Obtener la pagina deseada
$uri = isset($_GET['q']) ? $_GET['q'] : '/';
$uri_parts = explode('/', trim($uri, '/'));

//Crear el objeto Taskboard
$board = new Taskboard();
$board->task_lifespan = $config['tasks']['lifespan'];

//Determinar nuestra tarea
switch($uri_parts[0]){
    /*
     * Cosas de Pruebas
     */
    case 'init':
        // Inserte datos de prueba si es necesario
        // activarlo con ?q=/init
		if (!$__initEnable) {echo 'Acceso Denegado. Comando Init Deshabilitado';exit;}
        $board->initDatabase();
        if($__debug)$board->createTask('23r34r', 'Mi Primer', 'Este podria ser mi segundo pero blah blah blah', array('first', 'misc'));
        if($__debug)$board->createTask('23r34r', 'Necesito un Poster', 'Necesito un poster de alpha+omega', array('graphics', 'first'));
        if($__debug)$board->createTask('23r34r', 'Hacedme musica', 'Por favor, necesito musica', array('music', 'misc'));
        if($__debug)$board->createTask('23r34r', 'algo', 'algo algo algo lago', array('misc'));
        if($__debug)$board->createTask('23r34r', 'Google', 'Sitio web llamado Google.com, sera un motor de busqueda', array('graphics', 'technical'));
        if($__debug)echo "Datos de prueba Insertados\n";
        $board->createTask('Anonymous', 'Bienvenido a TaskBoard', 'Este es el primer post de TaskBoard, ahora intenta probar las funciones buscar y enviar.', array('firstpost'));

        break;

    /*
     * Cosas Relacionadas con Las Tareas
     */
    case 'tasks':
        //Checkear si queremos una tarea
        if (isset($uri_parts[1])) {
            switch($uri_parts[1]){
                /*
                 *hacer una nueva tarea
                 */
                case 'new':
                    $mode = array('submitForm');
					
					/*
						Responder a, o clonar un post
					*/
						if( isset($_POST['respondtaskid']) ) {
							$responding_taskid =$_POST['respondtaskid'];
						}else if ( isset($_GET['respondtaskid']) ) {
							$responding_taskid = $_GET['respondtaskid'];
						}else{
							$responding_taskid = "";
						}
						if( $responding_taskid != "" ){
							if(!is_numeric($responding_taskid)){Echo "HAS FALLADO";exit;}
							//Obtener temas y sus comentarios
							$responding_to_task = $board->getTaskByID($responding_taskid);
							$responding_to_task = $responding_to_task[0];
						} else {
							$responding_to_task = null;
						}
						
                    break;

                /*
                 * Enviar y procesar el nuevo tema
                 */
                case 'submitnew':

					/*
						Obtener las ultimas fotos de $imageFileBinary
					*/
					$imageFileBinary = __getImageFile();
					if ($imageFileBinary == NULL) {
						if (empty($_SESSION['imageFileBinary'])) {
							$_SESSION['imageFileBinary'] = NULL;
						} 
						$imageFileBinary = $_SESSION['imageFileBinary'];
					} else {
						$_SESSION['imageFileBinary'] = $imageFileBinary;
					}
					
					/*
						Obtener el ultimo archivo llave y ponerlo en $keyFileBinary
					*/
					$keyFileBinary = __getKeyFile();
					if ($keyFileBinary == NULL) {
						if (empty($_SESSION['keyFileBinary'])) {
							$_SESSION['keyFileBinary'] = NULL;
						} 
						$keyFileBinary = $_SESSION['keyFileBinary'];
					} else {
						$_SESSION['keyFileBinary'] = $keyFileBinary;
					}
					
                    //Solo pasa si el mensaje y el titulo estan seteados
                    if(!isset($_POST['title'], $_POST['message']) || empty($_POST['title']) || empty($_POST['message'])){
                        echo "<b>Falta el Titulo o el Mensaje</b> \n";
						$missingfield = true;
                    } else {
						$missingfield = false;
					}
					
					
					// Checkear si el mensaje es nuevo y limpio (no es estupido, no es epic respost, no spam, no es corto)
					if( ! __postGateKeeper($_POST['message']) ){
                        echo "Tu post ha sido denegado por el portero. Acaso es muy corto? 
						Tiene muchos errores ortograficos? O fue planeado para ser estupido? \n";
						exit;
					};

					// Tambien debe pasar el captcha
					if( isset($_POST['security_code'])) {
						$first = false;
					} else {
						$_POST['security_code'] = "";
						$_SESSION['security_code'] = "";
						$first = true;
					}
					
				   if( ($missingfield == false) && ($_SESSION['security_code'] == $_POST['security_code'] && !empty($_SESSION['security_code'] ))  ) {
						echo 'Captcha Valido.';
						unset($_SESSION['security_code']);
				   } else {
						if ($first){
							echo 'Por favor ingrese este captcha para confirmar que es un humano y no un robot o una aspiradora';
						}else{
							echo 'Lo sentimos, el codigo no es correcto';
						}

						?>
						<br/>
						<br/>
						
						Modificar Texto:
							<FORM action='?<?php echo htmlspecialchars(SID); ?>&q=/tasks/submitnew' method='post' >
						Titulo*:<BR>		<INPUT type='text' name='title'value='<?php echo $_POST['title'];?>'><BR>	
						Mensaje*:<br />	<textarea class='' rows=5 name='message'><?php echo $_POST['message'];?></textarea><BR>			
						Tags:<BR><INPUT type='text' name='tags' value='<?php echo $_POST['tags'];?>'><BR>

							<input type="hidden" name="taskID" value="<?php //echo $_POST['taskID']; ?>"><br/>
							<INPUT type='hidden' name='keyfile' />
							<INPUT type='hidden' name='respondid' value="<?php echo $_POST['respondid'];?>" />
                            <INPUT type='hidden' name='password' value="<?php echo $_POST['password'];?>" >
							<b>CAPTCHA:</b> 
							<img src="./captcha/CaptchaSecurityImages.php?<?php echo htmlspecialchars(SID); ?>&width=100&height=40&characters=5" /><br />
							<label for="security_code">C&oacute;digo de Seguridad: </label><input id="security_code" name="security_code" type="text" /><br />
							<br />
							<input type="submit" value="Enviar" />	
						</form>
						<?php	
						exit;
					}


                    /*
					Extraer tag al array
					*/
					//preg_replace('/[^a-zA-Z0-9\s]/', '', $text) - Remuve los caracteres no alfanumericos
                    $s_tag = isset($_POST['tags']) ? preg_replace('/[^a-zA-Z0-9\s]/', '', $_POST['tags']) : "";
					// lo convierte en un array
					$s_tag_array_1 = explode(' ', $s_tag);
					//also extract any hashtags from the message itself
					$hashtagmatch = preg_match_all( '/#(\w+)/', $_POST['title']." ".$_POST['message'] , $pregmatch);
					if($hashtagmatch){
						 $s_tag_array_2 = $pregmatch[1];
					}else{
						$s_tag_array_2 = array();
					}
					//une s_tag_array_1 y s_tag_array_2 a s_tag_array
					$s_tag_array = array_merge( $s_tag_array_1 , $s_tag_array_2 );
					$s_tag_array = array_unique( $s_tag_array );
									
                    //inserta el pass
                    if( ( isset($_POST['password']) AND $_POST['password']!='' ) OR $keyFileBinary!=NULL){
                        $s_pass=__tripCode($_POST['password'].$keyFileBinary);
                    }else{// nuevo pass
						//$newpass = md5(mt_rand());
						if($__hiddenServer){
							$newpass = substr(md5(rand()),0,6);
						} else {
							$newpass = substr(md5($_SERVER['REMOTE_ADDR']),0,6);
						}
                        $s_pass=__tripCode($newpass);
                        echo      "<div style='z-index:100;background-color:white;color:black;'>Tu nueva contrase&ntilde;a es: '<bold>".$newpass."</bold>' Cuidala. </div>";
						echo		__prettyTripFormatter($s_pass);
                    }
                    
                    $newTaskID = $board->createTask($s_pass, $_POST['title'], $_POST['message'], $s_tag_array, $_POST['respondid'], $imageFileBinary);
                    echo "Post Enviado!<br/>";
					echo "Tags:".implode(" ",$s_tag_array)."<br/>";
					echo "<a href='?q=/view/".$newTaskID."'>Click pàra ir a el nuvo tema</a>";
					echo "<meta http-equiv='refresh' content='10; url=?q=/view/".$newTaskID."'> Refrescando en 10 segundos<br/>";
					exit;
                    break;
					
                /*
                 * envia y procesa el nuevo comentario
                 */
                case 'comment':
				
					/*
						toma el ultimo archivo llave y lo mete en $keyFileBinary
					*/
					$keyFileBinary = __getKeyFile();
					if ($keyFileBinary == NULL) {
						if (empty($_SESSION['keyFileBinary'])) {
							$_SESSION['keyFileBinary'] = NULL;
						} 
						$keyFileBinary = $_SESSION['keyFileBinary'];
					} else {
						$_SESSION['keyFileBinary'] = $keyFileBinary;
					}
				
                    //solo si el mensaje y el titulo estan seteados
                    if(!isset( $_POST['comment']) || empty($_POST['comment'])){
                        echo "Falta el Comentario \n";
						echo "<a href='?q=/view/".$uri_parts[2]."'>Click para volver</a>";
						exit;
                        break;
                    }
					
					// debe pasar el captcha
					if( isset($_POST['security_code'])) {
						$first = false;
					} else {
						$_POST['security_code'] = "";
						$_SESSION['security_code'] = "";
						$first = true;
					}
				   if( $_SESSION['security_code'] == $_POST['security_code'] && !empty($_SESSION['security_code'] ) ) {
						echo 'Captcha valido.';
						unset($_SESSION['security_code']);
				   } else {
						if ($first){
							echo 'Por favor ingrese este captcha para probar que eres un humano';
						}else{
							echo 'Captcha Invalido';
						}

						?>
						<br/>
						<br/>
						Moddificar Texto:
						<form name="add_comment" action="?<?php echo htmlspecialchars(SID); ?>&q=/tasks/comment/<?php echo $_POST['taskID']; ?>" method="post" >
							<textarea id="comment" name="comment"><?php echo $_POST['comment'];?></textarea>
							<input type="hidden" name="taskID" value="<?php echo $_POST['taskID']; ?>"><br/>
							<INPUT type='hidden' name='keyfile' />
                            <INPUT type='hidden' name='password' value="<?php echo $_POST['password'];?>" >
							<b>CAPTCHA:</b> 
							<img src="./captcha/CaptchaSecurityImages.php?<?php echo htmlspecialchars(SID); ?>&width=100&height=40&characters=5" /><br />
							<label for="security_code">Codigo de Seguridad: </label><input id="security_code" name="security_code" type="text" /><br />
							<br />
							<input type="submit" value="Enviar" />	
						</form>
						<?php	
						exit;
					}
					
					// checkea si es un buen mensaje, no tiene spam, etc.
					if( ! __postGateKeeper($_POST['comment']) ){
                        echo "Tu post fue rechazado por el portero. 
						Era pequeño? Tenia errores ortograficos? \n";
						exit;
					};

                    //inserte pass
                    if( ( isset($_POST['password']) AND $_POST['password']!='' ) OR $keyFileBinary!=NULL){
                        $s_pass=__tripCode($_POST['password'].$keyFileBinary);
						echo "<meta http-equiv='refresh' content='3; url=?q=/view/".$uri_parts[2]."'> Refrescando en 3 segundos";
                    }else{
						// nuevo pass                 
						//$newpass = md5(mt_rand());
						if($__hiddenServer){
							$newpass = substr(md5(rand()),0,6);
						} else {
							$newpass = substr(md5($_SERVER['REMOTE_ADDR']),0,6);
						}
                        $s_pass=__tripCode($newpass);
                        echo      "<div style='z-index:100;background-color:white;color:black;'>Tu nuevo pass es: '<bold>".$newpass."</bold>' Cuidalo </div>";
						echo		__prettyTripFormatter($s_pass);
                    }

					$board->createComment($s_pass, $uri_parts[2], $replyID=NULL, $_POST['comment'], 1);
                    echo "Post Enviado\n";
					echo "<a href='?q=/view/".$uri_parts[2]."'>Click para ir atras</a>";
					echo "<meta http-equiv='refresh' content='5; url=?q=/view/".$uri_parts[2]."'> Refrescando en 3 segundos<br/>";
					exit;
                    break;

                /*
                 * busca un tema
                 */
                case 'search':
                    // postear una busqueda
                    if(isset($_POST['tags'])){
						$tags_string = isset($_POST['tags']) ? preg_replace('/[^a-zA-Z0-9\s]/', '', $_POST['tags']) : "";
                        $tags = explode(' ', $tags_string);
                        header('Location: ?q=/tasks/search/'.implode(',', $tags));
                        //echo 'tags'.implode(',', $tags);
                        exit;
                    }
					
                    if(isset($uri_parts[2])){
                        $tags = explode(',', $uri_parts[2]);
                        $mode = array('tasksList');
                    } else {
                        $mode = array('tagSearch');
                    }
										
                    if(!empty($tags)){
                        $tasks = $board->getTasks($tags);
                    } else {
                        $tasks = array();
                    }
                    break;

                /*
                 * borrar tema
                 */
                case 'delete':
					
					$pass = $_POST['password'].__getKeyFile();
					if ($pass == ""){
						$pass = substr(md5($_SERVER['REMOTE_ADDR']),0,6);
					}

					if(!is_numeric($_POST['taskID'])){Echo "HAS FALLADO";exit;}
                    $s_array[0]=$_POST['taskID'];
					
                    $s_array[1]=__tripCode($pass);
					
					/*
						Mods
					*/
					if (array_key_exists($s_array[1],$__superModeratorByTrip)){
						$command = 'Borrar un Post';
						$board->delTaskBy($command,$s_array);
						break;
					}
					
					/*
					//borrado normal por pass
					*/
					var_dump($s_array);
                    //print_r($s_array);
                    $command = 'Borrar un solo tema con contrase&ntilde;a';
                    $board->delTaskBy($command,$s_array);
                    break;
					
            }
        }
        
        break;

    /*
     * obtiene imagen
     */
    case 'image':
		$taskid =$uri_parts[1];
		
        if(!is_numeric($uri_parts[1])){Echo "HAS FALLADO";exit;}
		
		// miniaturas
        if(isset($_GET['mode'])){
			if($_GET['mode']== 'thumbnail'){
				$tasks = $board->getTaskFileByID($taskid,'thumbnail');
			}
		}
		
		
		//muestra la imagen
        $tasks = $board->getTaskFileByID($taskid,'image');
        break;
		
    /*
     * busquedas y navegacion en temas
     */
    case 'view':
        $mode = array('tasksView');
		$taskid =$uri_parts[1];
		
        if(!is_numeric($uri_parts[1])){Echo "HAS FALLADO";exit;}
        
		//obtiene coments
        $tasks = $board->getTaskByID($taskid);
        $tagsused = $board->tagsByID($taskid);
        $comments = $board->getCommentsByTaskId($taskid);
        break;

    /*
     * Temas como hoja A4
     */
    case 'printview':
        $mode = array('tasksView');
		$taskid =$uri_parts[1];
		
        if(!is_numeric($uri_parts[1])){Echo "HAS FALLADO";exit;}
        
		//obtiene coments
        $tasks = $board->getTaskByID($taskid);

		//carga el layout
		require("printlayout.php");
		exit;
        break;
		
	case 'ajaxcomments':
		/*comentarios ajax*/
		$mode = array('tasksView');
		$taskid = $_POST['taskid'];
		
        //ultimo coment
        $comments = $board->getCommentsByTaskId($taskid);
		
		echo __commentDisplay($comments);
		
		exit;
		break;
		
	case 'ajaxtasks':
		/*mas ajax para temas*/
		$mode = array('tasksList');
		
		$tags = explode(',', $_POST['tags']);
		
        //ultimo coment
        $tasks = $board->getTasks($tags);
		
		//tag para la funcion clone
		if (!empty($tags)){
			$referraltag = $tags[0];
		} else {
			$referraltag = "";
		}
		
		echo __taskDisplay($tasks,$referraltag);
		
		exit;
		break;
		
	case 'embed':
		
		if (isset($_GET['tags'])){
		$tags = explode(',', $_GET['tags']);
		}else{
		$tags = array();
		}
		if (isset($uri_parts[1])){
		$tags = array_merge( explode(',', $uri_parts[1]) , $tags);
		}

        //obtiene ultimo comment
        $tasks = $board->getTasks($tags);
		
		?>
		<!DOCTYPE html>
		<html>
		<head>
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
		<link rel="stylesheet" href="css/embed.css" type="text/css" />
		</head>
		<body>
		<div id="newTask" class="greybox">
			<?php if (!empty($tags)){?>
				<a target="_blank" href="?q=/tasks/new&tag=<?php echo $tags[0];?>">Nuevo Post</a>
			<?php } else {?>
				<a target="_blank" href="?q=/tasks/new">Nuevo Post</a>
			<?php }?>
		</div>
		<div id="taskDIV" class="tasklist">
		<?php
		echo __taskDisplay($tasks);
		?>
		</div>
		</body>
		<?php
		
		exit;
		break;

	case 'rss':
		
		if (isset($_GET['tags'])){
		$tags = explode(',', $_GET['tags']);
		}else{
		$tags = array();
		}
		
		//XML headers
		$rssfeed = '<?xml version="1.0" encoding="ISO-8859-1"?>';
		$rssfeed .= "\n";
		$rssfeed .= '<rss version="2.0">';
		$rssfeed .= "\n";
		$rssfeed .= '<channel>';
		$rssfeed .= "\n";
		$rssfeed .= '<title>TaskBoard</title>';
		//$rssfeed .= "\n";
		//$rssfeed .= '<link></link>';
		$rssfeed .= "\n";
		$rssfeed .= '<description>Este es el Feed RSS de TaskBoard</description>';
		$rssfeed .= "\n";
		$rssfeed .= '<language>en-us</language>';
		//$rssfeed .= "\n";
		//$rssfeed .= '<copyright></copyright>';
		$rssfeed .= "\n\n\n";

		
        //ultimo coment
        $tasks = $board->getTasks($tags);

		foreach($tasks as $rowtask) {	
			// link dir
			$url = $_SERVER['REQUEST_URI']; //url actual
			$parts = explode('/',$url);
			$linkdir = $_SERVER['SERVER_NAME'];
			for ($i = 0; $i < count($parts) - 2; $i++) {
			 $linkdir .= $parts[$i] . "/";
			}
			//entrada RSS
			$rssfeed .= '<item>';
					$rssfeed .= "\n";
			$rssfeed .= '<title>(Trip:' . preg_replace('/[^a-zA-Z0-9\s]/', '', $rowtask['tripcode']).") - ".preg_replace('/[^a-zA-Z0-9\s]/', '', $rowtask['title'] ). '</title>';
					$rssfeed .= "\n";
			$rssfeed .= '<description>'.preg_replace('/[^a-zA-Z0-9\s]/', '',str_replace(array("\r\n", "\r", "\n", "\t"), ' ', htmlentities(stripslashes($rowtask['message']),null, 'utf-8')) ). '</description>';
					$rssfeed .= "\n";
			if(isset($_SERVER["SERVER_NAME"])){
				$rssfeed .= '<link>http://'.$linkdir.'?q=/view/'.$rowtask['task_id'].'</link>';
					$rssfeed .= "\n";
			}
			$rssfeed .= '<guid>' . md5($rowtask['message']) . '</guid>';
					$rssfeed .= "\n";
			$rssfeed .= '<pubDate>' . date("D, d M Y H:i:s O", $rowtask['created']) . '</pubDate>';
					$rssfeed .= "\n";
			$rssfeed .= '</item>';
					$rssfeed .= "\n\n";
		}
	 
		$rssfeed .= '</channel>';
		$rssfeed .= '</rss>';
	 
		echo $rssfeed;		

		exit;
		break;
		
    /*
     * obtener tags por defecto
     */
    default:
        
    /*
     * obtiene tags
     */
    case 'tags':
	
        // navega/busca tags
        $mode = array('tasksList');
        
        if (isset($uri_parts[1])) {
            $tags = explode(',', $uri_parts[1]);        
        } else if(isset($_POST['tags'])) {
            $tags = explode(' ', $_POST['tags']);    
        } else if(isset($_GET['tags'])) {
            $tags = explode(' ', $_POST['tags']);   			
        } else {
            $tags = array();                            
        }
		
		//nube de tags
		if(empty($tags)){
			$tagClouds = $board->tagsWeight(500);
		}
		
		$tagslist = implode(",",$tags);

        $tasks = $board->getTasks($tags);
        break;
        
}

//Crea layout
if(!isset($mode)) $mode = array(); //modo por defecto (tal vez deberia existir una pagina de error)
$top_tags = $board->topTags(10);
require("layout.php");
            

?>