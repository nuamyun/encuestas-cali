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

La base de datos se crea automáticamente con database/schema.sql y
database/seed.sql la primera vez que se levanta el contenedor de MySQL.

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

PRUEBAS (requiere PHP 8.4 y Composer en el equipo)
       composer install
       vendor/bin/phpunit --testdox

ESTRUCTURA
- public/            Punto de entrada (index.php) y archivos estáticos
- src/Core/          Núcleo: enrutador, vistas, base de datos, sesión, CSRF
- src/Controllers/   Controladores por módulo
- src/Views/         Vistas HTML
- config/routes.php  Rutas y perfiles autorizados
- database/          Script de creación de la base de datos
- tests/             Pruebas unitarias (PHPUnit)
- .github/workflows/ Pipeline de integración continua (GitHub Actions)

Este archivo se completa en la última etapa con los usuarios de prueba
y la descripción de cada módulo.
