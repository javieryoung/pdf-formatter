#/bin/bash


#upload files
scp -r -i /home/javier/Documents/workspaces/nerd/nerd-production.pem ubuntu@18.228.72.231:/var/www/html/pdf-formatter/public/formatted ../files-formatted
