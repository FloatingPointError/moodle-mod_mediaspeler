# Mediaspeler (mod_mediaspeler)

Moodle-activiteit die audio uit een map in Nextcloud afspeelt als oefenreeks voor het muzieklab. De bestanden blijven in Nextcloud; Moodle bewaart alleen verwijzingen en geeft de audio door.

- **Moodle:** 5.2 (PHP 8.3 of 8.4)
- **Nextcloud:** 34, via WebDAV met één serviceaccount
- **Status:** fase 4. De activiteit leest een Nextcloud-map uit en speelt de audiobestanden af via Moodle, met spoelen. De speler voor de oefenreeks volgt in fase 5.

## Installeren op de test-VM

Sinds Moodle 5.1 staan plugins in de map `public`:

```bash
git clone git@github.com:FloatingPointError/moodle-mod_mediaspeler.git /var/www/moodle/public/mod/mediaspeler
cd /var/www/moodle
sudo -u www-data php8.3 admin/cli/upgrade.php
```

Of ga als beheerder naar Site-beheer → Meldingen.

## Koppeling met Nextcloud instellen

Ga naar Site-beheer → Plugins → Activiteitsmodules → Mediaspeler en vul in:

| Instelling | Waarde op de test-VM |
| --- | --- |
| Adres van Nextcloud | `http://localhost:8080` |
| Serviceaccount | `moodle-mediaspeler` |
| App-wachtwoord | het app-wachtwoord uit Nextcloud |

Controleer de koppeling vanaf de commandoregel, vanuit `/var/www/moodle`:

```bash
sudo -u www-data php8.3 public/mod/mediaspeler/cli/test_connection.php --folder=Muzieklab-test
```

Het script toont de bestanden in de map, met "audio" bij de bestanden die de mediaspeler gebruikt.

Een leerkracht die de activiteit opent, werkt de lijst automatisch bij vanuit Nextcloud. Nieuwe bestanden komen achteraan de reeks; verwijderde bestanden verdwijnen uit de lijst.

## Licentie

GNU GPL v3 of later, zoals Moodle zelf.
