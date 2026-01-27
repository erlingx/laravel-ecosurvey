# Kogebog for deployment af Laravel 12 projekt til Greengeeks shared host #

Greengeeks har git, composer, terminal men ikke node.js og npm (så npm run build køres lokalt og synkroniseres med GIT)

## Lokalt: ##
- kør ddev pint og ddev artisan test
- ddev npm run build (VIGTIG: Husk altid at køre dette efter frontend ændringer - ellers vil nye 
  komponenter/ændringer ikke virke på production!)
- Create and edit env.production
- git add, git tag v1.0.0, git commit, git push

## GreenGeeks: ##

Opret email adresse og sæt info i env.production (til at sende email notifikationer)

In C-panel:
- create a new domain (subdomain):
  https://ams200.greengeeks.net:2083/cpsess0619723521/frontend/jupiter/domains/index.html#/create
 - set document root in tools>domains>manage hvis ikke gjort ovenfor (Laravel: public)
- In terminal:
-  git add remote origin https://github.com/erlingx/laravel-organizer.git
-  git pull origin master
- Install dependencies (on production server):
    composer install --optimize-autoloader --no-dev
- php artisan key:generate
- php artisan migrate --force
- php artisan optimize:clear
- php artisan db:seed --class=ProductionSeeder

- I file manager i c-panel:
-  delete /docs folder and update .gitignore with /docs
  - opret .env 
  - paste indholdet fra lokale .env.produktion 
  - set permissions to 600 on .env file
  
## Cronjob ##
Sæt cronjob til at at køre queue der bruges af email/slack notifikationer
GreenGeeks: 
c-panel > advanced > cron jobs
- add new cron job
- common settings: once per one minute 
-  	cd /home/electr37/public_html/laravel-organizer.electrominds.dk && /usr/local/bin/php artisan queue:work database --stop-when-empty --max-time=50 >/dev/null 2>&1

If something breaks: Remove '>/dev/null 2>&1' temporarily to see error messages for debugging.
- save
- Tjek terminal om køen kører: `ps aux | grep "queue:work"`
- test ved at sende en notifikation



## Admin Access: ##
 Use ProductionSeeder to create admin user




