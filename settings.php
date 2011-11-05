<?php	//Importar configuraciones("settings.php");
	// Configuracines
	// Edite este archivo a su gusto
	
	
	// Configuración General
	$__initEnable = true; // Desactivar despues de la Instalación (cambiar a 'false' mas bien que 'true')
	$__debug = false; // Modo Debug
	$__hiddenServer = false; // El Servidor Escondido no deberia usar rastreo de IPs como todos los usuarios aparecen como "locales" (por ejemplo: en Tor).
	
	// Que Tags deben estar por defecto.
	$__defaultTags = array("NorteAmerica", "SudAmerica", "MedioEste", "Africa", "Asia", "Europa", "Oceania");

	// SuperTrip Mods (Sistema de moderación temporal... hasta que tengamos un sistema decente,
	// Si no lo usarás, hazlo un array vacio escribiendo: array()
	// Se recomienda cambiar el tripcode por otro, ya que es como dejar una clave por defecto
	$__superModeratorByTrip = array(
									'VtCZ.WGmDw' => 'admin'
									);
	
	// Salt Unico
	// Por favor setea tu propio salt
	// $__salt = "LETRAS AL AZAR"
	if(!isset($__salt)){$__salt = sha1($_SERVER['DOCUMENT_ROOT'].$_SERVER['SERVER_SOFTWARE']);}
	
	// DB CONFIG
	// Rellenar estos datos con tu DB
	$settingMode = "sqlite";
	switch($settingMode){
		case "mysql":
			$dbType		= "mysql";
			$dbHost		= "localhost";
			$dbName		= "taskboard";
			$dbuser     = "root";
			$dbpass     = "";
			$dbConnection = "host=".$dbHost.";dbname=".$dbName;
			break;
		case "sqlite":
			$dbType		= "sqlite";
			$dbuser     = "";
			$dbpass     = "";
			$dbConnection = "tasks.sq3";
			break;
	}

	// ANUNCIOS DE ADMIN
	/* Anuncios para cada tag. "Home" es el tag para la frontpage */
	// Cambia los datos si deseas.
	$__tagPageArray = array(
							"home"	=> "Este es un preview de desarrollo de TaskBoard. <br/>
		Por favor ayudanos a mejorarlo en nuestro <a href='https://github.com/corneyflorex/TaskBoard'>repo dde github</a>"
							,
							"anonymous"		=> "Hey anons, este es solo un mensaje del Admin"
							);
	
	
	
	
	
	
	
	
	
	
	// Habia un problema usando parse_ini_string, cuando se usaba con MySQL
	// Basicamente el error estaba en "dsn = ' mysql:host=HOSTNAME;dbname=DBNAME' " 
	// NOTA: Esto esta bien, mientras nos des tus comentarios, asi que novatos no lo vuelvan a romper.
	// 			o deberiamos moverlo a settings.php para respaldarlo facilmente?
	$config = array(
					"homepage"	=>array(
										"tasks_to_show" => 10
										)
					,
					"tasks"		=>array(
										"lifespan" => 1
										)
					,
					"database"	=>array(
										"dsn" => $dbType.":".$dbConnection
										,
										"username" =>$dbuser 
										,
										"password" =>$dbpass
										)
					);
					
