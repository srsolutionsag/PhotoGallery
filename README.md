PhotoGallery
============

## Installation

Start at your ILIAS root directory

```bash
mkdir -p Customizing/global/plugins/Services/Repository/RepositoryObject/  
cd Customizing/global/plugins/Services/Repository/RepositoryObject/  
git clone https://github.com/studer-raimann/PhotoGallery.git  
```  

As ILIAS administrator go to "Administration->Plugins" and install/activate the plugin.


## File-Migration
When switching from an older version (before 4.0.0) to a newer one, a file migration is necessary.

The following commands can be entered in a command line at the ILIAS root directory.

Check for open migrations:
```bash
php setup/setup.php migrate
```

Start the PhotoGallery migrations:
```bash
php setup/setup.php migrate --run PhotoGallery.ilObjPhotoGalleryMigration
```

Confirm the migration by entering the name of the PhotoGallery migration:
```bash
ilObjPhotoGalleryMigration
```

You can also run the migrations without a confirmation request by appending `--yes` to the command:
```bash
php setup/setup.php migrate --run PhotoGallery.ilObjPhotoGalleryMigration --yes
```

One run of the PhotoGallery migration will migarte 1000 albums.

Repeat the migration until there are no more albums left to migrate (the command line will display the migration progress).

Alternatively you can change the number of albums moved in one run by specifying `--steps=...` in the run command (in this case 5000):
```bash
php setup/setup.php migrate --run PhotoGallery.ilObjPhotoGalleryMigration --steps=5000
```


### ILIAS Plugin SLA

Wir lieben und leben die Philosophie von Open Source Software! Die meisten unserer Entwicklungen, welche wir im
Kundenauftrag oder in Eigenleistung entwickeln, stellen wir öffentlich allen Interessierten kostenlos
unter https://github.com/studer-raimann zur Verfügung.

Setzen Sie eines unserer Plugins professionell ein? Sichern Sie sich mittels SLA die termingerechte Verfügbarkeit dieses
Plugins auch für die kommenden ILIAS Versionen. Informieren Sie sich hierzu
unter https://studer-raimann.ch/produkte/ilias-plugins/plugin-sla.

Bitte beachten Sie, dass wir nur Institutionen, welche ein SLA abschliessen Unterstützung und Release-Pflege
garantieren.

### Contact

info@studer-raimann.ch  
https://studer-raimann.ch  

