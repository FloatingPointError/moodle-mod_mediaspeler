# Mediaspeler (mod_mediaspeler)

Moodle-activiteit die audio uit een map in Nextcloud afspeelt als oefenreeks voor het muzieklab. De bestanden blijven in Nextcloud; Moodle bewaart alleen verwijzingen en geeft de audio door.

- **Moodle:** 5.2 (PHP 8.3 of 8.4)
- **Nextcloud:** 34, via WebDAV met één serviceaccount
- **Status:** fase 1, skelet. De activiteit kan aan een cursus worden toegevoegd; de koppeling met Nextcloud volgt in fase 3.

## Installeren op de test-VM

Sinds Moodle 5.1 staan plugins in de map `public`:

```bash
git clone git@github.com:FloatingPointError/moodle-mod_mediaspeler.git /var/www/moodle/public/mod/mediaspeler
cd /var/www/moodle
sudo -u www-data php8.3 admin/cli/upgrade.php
```

Of ga als beheerder naar Site-beheer → Meldingen.

## Licentie

GNU GPL v3 of later, zoals Moodle zelf.
