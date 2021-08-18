Please update readme if you change some structure (Both file and folder)

## install library for nodejs
```sh
$ npm install
```
## Build docker
```sh
$ docker build -t node-am .
$ docker run -d -p 7756:3067 --name node-am-app node-am
```

## Use pm2 from external host
```sh
$ docker exec -it <container-id> pm2 monit
$ docker exec -it <container-id> pm2 list	
$ docker exec -it <container-id> pm2 log <process-id>
$ docker exec -it <container-id> pm2 show
$ docker exec -it <container-id> pm2 reload all	
```
