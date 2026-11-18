SISTEMA DE SOLICITUDES DE ENCUESTA - SANTIAGO DE CALI
Desarrollo de Software II - Universidad del Valle
====================================================

REQUISITOS
- Docker Desktop (incluye Docker Compose)
- Git

EJECUCIÓN CON DOCKER
1. Copiar el archivo de entorno:
       cp .env.example .env
   (en Windows: copy .env.example .env)
2. Construir y levantar los contenedores:
       docker compose up -d --build
3. Abrir en el navegador:
       http://localhost:8080
   Chequeo de salud:
       http://localhost:8080/health   -> {"status":"ok","db":true}

La base de datos se crea automáticamente con database/schema.sql,
database/seed.sql y database/barrios.sql la primera vez que se levanta el
contenedor de MySQL. Si el puerto 8080 está ocupado, cambiar APP_PORT en .env.

USUARIOS DE PRUEBA (contraseña de todos: Cali2026*)
- admin@encuestas.test          Administrador
- ventanilla@encuestas.test     Ventanilla - Oficina principal (Comuna 17)
- ventanilla.c1@encuestas.test  Ventanilla - Punto de atención Comuna 1
- encuestador@encuestas.test    Encuestador
- calidad@encuestas.test        Calidad
- enrutador@encuestas.test      Enrutador

Para borrar la base y recrearla desde cero:
       docker compose down -v
       docker compose up -d --build

CARGA DE BARRIOS Y VEREDAS (división territorial de Cali)
Fuente oficial: IDESC - Alcaldía de Santiago de Cali, "Listado de barrios,
veredas y sectores" de la DIVIPOLA adoptada por el Acuerdo 0636 de 2026:
  https://idesc.cali.gov.co/download/divipola/listado_barrios_veredas_sectores.xlsx
El Excel está en database/fuentes/ junto con los CSV extraídos de él:
  - barrios_urbanos.csv   342 barrios en las 22 comunas
  - veredas_rurales.csv    95 veredas y 20 sectores de expansión en los
                           15 corregimientos
database/barrios.sql (457 registros) se genera con:
  docker compose run --rm -v "${PWD}/database:/var/www/html/database" app php database/tools/importar_barrios.php --barrios=database/fuentes/barrios_urbanos.csv --veredas=database/fuentes/veredas_rurales.csv --salida=database/barrios.sql
  (en CMD usar "%cd%/database" en lugar de "${PWD}/database")
Después de regenerarlo, recrear la base:
  docker compose down -v
  docker compose up -d --build

PRUEBAS
GitHub Actions las ejecuta en cada push (pestaña Actions del repositorio).
Para correrlas en el equipo hace falta PHP 8.4 y Composer instalados:
       composer install
       vendor/bin/phpunit --testdox

TECNOLOGÍAS Y VERSIONES
La página "Sistema" (perfil administrador, http://localhost:8080/sistema)
muestra las versiones exactas que están corriendo.
  Capa                  Tecnología                 Versión         Dónde se define
  Lenguaje              PHP                        8.4.x           Dockerfile (imagen php:8.4-apache)
  Servidor web          Apache HTTP Server         2.4.x           Dockerfile (imagen php:8.4-apache)
  Base de datos         MySQL                      8.4.x (LTS)     docker-compose.yml (imagen mysql:8.4)
  Acceso a datos        PDO + pdo_mysql            la de PHP       Dockerfile (docker-php-ext-install)
  Interfaz              Bootstrap                  5.3.3           config/app.php
  Mapas                 Leaflet + OpenStreetMap    1.9.4           config/app.php
  Pruebas               PHPUnit                    11.5 o 12       composer.json
  Dependencias          Composer                   2.x             Dockerfile (imagen composer:2)
  Contenedores          Docker + Docker Compose    Docker Desktop
  Integración continua  GitHub Actions             .github/workflows/ci.yml
  Control de versiones  Git + GitHub
  Gestión del proyecto  Jira
Para ver las versiones desde la terminal:
       docker compose exec app php -v
       docker compose exec app apache2 -v
       docker compose exec db mysql --version

CONEXIÓN A LA BASE DE DATOS
  .env                     contraseñas y puertos (no se sube a GitHub)
  docker-compose.yml       pasa DB_HOST, DB_PORT, DB_NAME, DB_USER y
                           DB_PASSWORD al contenedor de la aplicación
  config/database.php      ÚNICO lugar con los datos de conexión
  app/Core/Database.php    abre la conexión con PDO (utf8mb4, hora de Colombia)
  app/Models/*.php         hacen las consultas usando esa conexión
Dentro de Docker, MySQL se llama "db" y escucha en el puerto 3306.
Desde el equipo (por ejemplo, MySQL Workbench): localhost, puerto 3307,
usuario app, contraseña la de DB_PASSWORD en .env (por defecto app_secret).

ESTRUCTURA (patrón Modelo-Vista-Controlador)
  public/                  Punto de entrada (index.php), CSS y JavaScript
  app/
    Controllers/           C: reciben la petición, usan modelos y vistas
    Models/                M: consultas SQL (UsuarioModel, SolicitudModel,
                              CatalogoModel)
      Reglas/              M: reglas de negocio y validaciones (sin SQL)
    Views/                 V: plantillas HTML por módulo y layouts
    Core/                  Núcleo: enrutador, sesión, CSRF, autenticación,
                           menú por perfil, vistas y conexión a la base
    helpers.php            Funciones de ayuda para las vistas
  config/
    routes.php             Rutas y perfiles autorizados en cada una
    database.php           Datos de conexión a MySQL
    app.php                Nombre, versión y librerías del navegador
  database/                Esquema, usuarios de prueba, barrios y el importador
  tests/Unit/              Pruebas unitarias (PHPUnit)
  .github/workflows/       Pipeline de GitHub Actions
  Dockerfile, docker-compose.yml   Contenedores de la aplicación y de MySQL

REGLAS DE LAS SOLICITUDES
- Cada solicitud guarda el número de documento del solicitante. Una misma
  persona puede tener máximo 2 solicitudes activas (pendientes o listas);
  las rechazadas no cuentan. El límite está en
  app/Models/Reglas/SolicitudValidator.php (MAX_SOLICITUDES_POR_DOCUMENTO).
- Estados: toda solicitud nace "pendiente". En la bitácora, el enrutador la
  marca "lista" (puede ir a una hoja de ruta) o "rechazada" (con motivo
  obligatorio), y puede devolverla a "pendiente".
