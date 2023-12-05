# Pendrej Reservations App

TBD

### How to set-up the project

#### Clone and access the code
```
git clone git@gitlab.fit.cvut.cz:BI-TWA/B231/team-pendrej.git
cd team-pendrej
```

### Add your user and group information (for correct permission settings)
Create a `.env.local` file with following content, this file is not committed:
```txt
USER_NAME=<your PC user name>
GROUP_NAME=< your PC group name>
```

#### Start-up Docker
```
docker compose up
docker exec -it pendrej-app composer install
```

#### Check status
Is application running? http://localhost:8080/
Is database (Adminer connection) running? http://localhost:8081/
- System: PostgreSQL
- Server: database
- User: app
- Password: app

### Useful commands
```
docker exec -it pendrej-app composer ...
docker exec -it pendrej-app symfony ... # access the Symfony binary
docker exec -it pendrej-app php bin/console make: ... # make commands for Symfony apps
```

### Authors

- Petr Kudrnovský - kudrnpe3
- Andrej Meliška - melisand
