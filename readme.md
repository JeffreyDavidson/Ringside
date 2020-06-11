# Ringside

Ringside is created and maintained by Jeffrey Davidson and it is a Content Management System (CMS) for wrestling promoters to easily keep their roster updated as well as schedule new events.

## Prerequisites

Lando - https://github.com/lando/lando/releases


## Installation
For installing this application locally, clone this repository to your default project directory. After doing so, using any terminal application of your choice, change directories into the repository project.  

1. Run `composer install` to install all of the PHP packages needed for this application.
2. To start the application you will need to run the command `lando start`. This will start up your docker container and spit up the application and database containers for your application based on the lando.yaml file located in the root directory of the application.
3. You will need to then migrate the database and run the seeders but running `lando artisan migrate` then `lando artisan db:seed`.


**As of April 14, 2020 not all seed files are working as expected.**
