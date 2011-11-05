26% Translated
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

Instalación
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

Por Hacer
======
+ Panel y Herramientas de Moderación/Admin
+ Mensajeria
+ Calificaciones
+ Stickys