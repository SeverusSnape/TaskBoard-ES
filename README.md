27% Translated
=================================

TaskBoard

Now in Spanish! - ¡Ahora en Español!

Acerca De
======
Un Foro, permitiendo a los Anons del mundo:

* Postear Temas pequeños, ej. "Hacer Poster para #OpSosis"
* Responder a los Temas con un mensaje o con un Archivo
* Influenciar Temas cuando reciben mas Participantes
* Buscar Temas por idioma, pais, tags, etc.
* Borrar temas usando una contraseña o un archivo llave
* Usar todas las funciones sin registro (Con Captchas)

Temas:

* Empiezan su Vida en la parte superior de "Temas Recientes"
* Empiezan a decender mientras se crean mas temas u otros reciben mayor participación
* Se Mueven a la sección de "Temas Activos" cuando llegan a una gran cantidad de actividad.
* Expiran después de 24 Horas sin actividad.

Cosas que necesitan ser programadas:

* Panel y Herramientas de Moderación/Admin
* Calificaciones
* Filtro de Spam
* Tema Sticky en Tags (Se mantienen en la parte superior de la sección del tag)

Requerimientos
======

* PHP
* SQLite
* HTTPd o cualquier otro servidor HTTP.

Instalación (Antigua)
======

Simplemente copia los contenidos de este Repo en el lugar que quieras de tu web server de elección.

Si es necesario, inicializa la DB navegando a: /index.php?q=/init

Si sigue existiendo un error, checkea la configuración de index.php y mira si concuerda con esto:

    // Settings
            $config_str = <<<SETTINGS
    [homepage]
    tasks_to_show = 10

    [tasks]
    lifespan = 1

    [database]
    dsn = sqlite:tasks.sq3
    username = 
    password =
    SETTINGS;

Instalar TaskBoard en un sistema Linux via SSH. Y opcionalmente esconderlo via Tor
======

En Consola (Se necesita Root, o sudo en su defecto si es necesario): 

Para Aptitude (si es necesario adapte los comandos de gestion de paqueteria a los de su distro, ej. yum)


    # apt-update 
    
    # apt-get install tor apache2 php5 mysql_server mysql_client php5-gd php5-mysql unzip wget
    
    $ wget https://github.com/corneyflorex/TaskBoard/zipball/master --no-check-certificate
    
    $ unzip master
    
    $ mv corneyflorex-TaskBoard-XXXXXXX/* 
    
    $ rm corneyflorex-TaskBoard-XXXXXXX/

Edite php.ini, (ejecute 'php --ini' si no lo puede encontrar) asegurese que contiene el equivalente de (no siempre puede tener la misma ruta, si no lo encuentra simplemente ejecute 'find | grep pdo_mysql.so' mot just 'find | grep pdo_mysql.so') extension=/usr/lib/php5/20090626/mysql.so extension=/usr/lib/php5/20090626/pdo_mysql.so extension=/usr/lib/php5/20090626/gd.so

Configuración MySQL para TaskBoard (en el prompt MySQL como mysqlroot) 


    mysql> CREATE DATABASE taskboard; 
    
    mysql> CREATE USER 'tbuser'@'%' IDENTIFIED BY 'SomE_paSs'; 
    
    mysql> GRANT ALL PRIVILEGES ON taskboard.* TO 'tbuser'@'%';

Edite settings.php con los datos de la BD, etc.

Cargue esta pagina en su navegador: http://[HOST]/?q=/init

Asegurese que: /etc/apache2/mods-enabled contiene 'php5.conf' y 'php5.load'

Por Hacer
======
+ Panel y Herramientas de Moderación/Admin
+ Mensajeria
+ Calificaciones
+ Stickys