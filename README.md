# CarShare - API
_Servidor API para la aplicacion de CarShare desarrollada en en curso academico 2020/2021 para el CIFP Virgen de Gracia (Puertollano)._

## Comienzo
_Las siguientes intrucciones te ayudaran a tener el proyecto funcionando en tu maquina local._

### Pre-requisitos
_Para el funcionamiento del proyecto debera de tener instalado en su equipo PHP si quiere lanzar la API en su propio equipo, aunque tambien podra
utilizar los ficheros .sh y .bat para desplegar el docker en su equipo._

### Instalación
_Para la instalacion de PHP para despliegue local:_
_Windows:_  
_Debera de realizar la instalacion manual del paquete o utilizar alguna de los famosos paquetes "todo en uno" que ya lo traen instalado, como XAMPP o WampServer._  
_Linux:_
```
sudo apt-get update
sudo apt-get upgrade
sudo apt-get install php
```
_Mac:_  
_Aunque las ultimas versiones de Mac ya traen PHP instalado podemos forzar su instalacion de la siguiente manera:_
```
/usr/bin/ruby -e "$(curl -fsSL https://raw.githubusercontent.com/Homebrew/install/master/install)"
brew update
brew install php
```
  
  
_Para la instalación de Docker para facilitar el despliegue:_  
_Windows:_  
Usaremos el instalador oficial desde la pagina de [Docker](https://hub.docker.com/editions/community/docker-ce-desktop-windows?tab=description).  
_Linux:_
```
sudo curl -L "https://github.com/docker/compose/releases/download/1.26.0/docker-compose-$(uname -s)-$(uname -m)" -o /usr/local/bin/docker-compose
sudo chmod +x /usr/local/bin/docker-compose
docker-compose --version
```
_Mac:_  
Para la instalación en Mac bastara con utilizar el isntalador desde la pagina iniciar de [Docker](https://hub.docker.com/editions/community/docker-ce-desktop-mac/)

### Despliegue con Docker
