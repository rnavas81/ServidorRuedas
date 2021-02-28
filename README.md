<p align="center">
  <img src="resRead/Banner.png" alt="banner"/>
</p>

# CarShare - API
_Servidor API para la aplicacion de CarShare desarrollada en en curso academico 2020/2021 para el CIFP Virgen de Gracia (Puertollano)._

### Tecnologias:
El proyecto ha sido realizado con:  
[![Generic badge](https://img.shields.io/badge/Tecnologia-Laravel-red.svg)](https://laravel.com)
[![Generic badge](https://img.shields.io/badge/Tecnologia-PHP8.0-blue.svg)](https://www.php.net)

## Comienzo
_Las siguientes intrucciones te ayudaran a tener el proyecto funcionando en tu maquina local._

### Pre-requisitos ⚙️
_El proyecto esta diseñado para su despliegue con Docker para que, al utilizar sus contendores el despliegue sea lo mas limpio y automatizado posible._  
Para la instalación de Docker y su correcto funcionamiento puede acceder a [Documentacion oficial de Docker](https://docs.docker.com/get-docker/). Ahi encontrar la instacion dependiende de su sistema operativo ademas de guias sobre el uso de Docker.

### Instalación:
Para la instalación tendremos dos opciones:

#### Git: 
La instalación de Git sera distinta dependiendo de nuestro sistema operativo: Windows y Mac cuentan con sus propios instaladores.  
Instalador de [Windows](http://git-scm.com/download/win)
Instalador de [Mac](http://git-scm.com/download/mac)  
Para la instalación en Linux solo deberemos usar el siguiente comando en la terminal:
```
apt-get install git
```
  
Una vez instalado en nuestro sistema simplemente tendremos que abrir una terminar en la carpeta donde queramos tener el proyecto y ejecutra el siguiente comando:
```
git clone https://github.com/rnavas81/ServidorRuedas.git
```
_A traves de los comandos de git podremos movernos los las ramas del repositorio._
```
git branch nombre_de_la_rama
```

#### Manual: 
Simplemente tendremos que ir a la parte superior de esta pagina y en el desplegable de *code* tendremos la opcion para descargar el proyecto en zip, despues solo nos quedara descomprimirlo en la carpeta que deseemos.
_Esto nos bajara el pryecto en la rama en la que nos situemos._

### Despliegue:
#### Instalacion de Docker:
Para ello tendremos que realizar la instalacion de Docker:  
Para Windows y Mac tendran sus propios instaladores que se encuentran en la [Documentacion oficial de Docker](https://docs.docker.com/get-docker/).  
Para Linux tendremos que realizar los siguientes comandos:
```
sudo apt-get update

sudo apt-get install apt-transport-https ca-certificates curl gnupg-agent software-properties-common

curl -fsSL https://download.docker.com/linux/ubuntu/gpg | sudo apt-key add -

sudo add-apt-repository "deb [arch=amd64] https://download.docker.com/linux/ubuntu $(lsb_release -cs) stable"

sudo apt-get update

sudo apt-get install docker-ce docker-ce-cli containerd.io
```
Despues añadiremos el usuario para no tener que hacer sudo
```
sudo usermod -aG docker $USER
```
Instalaremos Docker Compose para el despliegue automatizado
```
sudo apt install docker-compose
```

#### Ejecución de scripts:
Para el correcto despliegue automatizado el proyecto es acompañado por dos ficheros de scripts que deberemos de utilizar segun nuestro SO.  
Para evitar problemas de persmisos recomendamos ejecutar estos scripts con permisos de administrador.  
  
El conjunto de scripts levantara los Dockers necesarios para el despliegue del proyecto, ademas de que en la terminal iran pareciendo el proceso del depliegue ademas de las intrucciones.

### Contribuidores:
* [Rodrigo](https://github.com/rnavas81)
* [Jorge](https://github.com/IamUnder)
* [Alejandro](https://github.com/djmarpe)  
Si desea realizar alguna aportacion o corrección al codigo esta sera bien recibida atraves de un *pull resquest*

### Licencia:
Este proyecto está bajo licencia MIT.
[![Generic badge](https://img.shields.io/badge/Licencia-MIT-yellow.svg)](https://es.wikipedia.org/wiki/Licencia_MIT)

### Gracias a todos.
