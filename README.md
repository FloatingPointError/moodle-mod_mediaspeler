# Mediaspeler (mod_mediaspeler)

Moodle-activiteit die audio uit een map in Nextcloud afspeelt als oefenreeks voor het muzieklab. De bestanden blijven in Nextcloud; Moodle bewaart alleen verwijzingen en geeft de audio door.

- **Moodle:** 5.2 (PHP 8.3 of 8.4)
- **Nextcloud:** 34, via WebDAV met één serviceaccount
- **Status:** fase 6. De activiteit leest een Nextcloud-map uit en toont de bestanden als oefenreeks in een eigen speler. De leerkracht stelt titel, uitleg en volgorde in; de leerling kiest zelf de volgorde. Activiteitsvoltooiing kan op "elk item beluisterd" (minstens 75% van elk). Items kunnen per groep of instrument getoond worden (fase 6).

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

De lijst wordt bijgewerkt vanuit Nextcloud door de geplande taak *Mediaspelers bijwerken vanuit Nextcloud* (elke 15 minuten, zolang de cron van Moodle draait) en telkens als een leerkracht de activiteit opent. Nieuwe bestanden komen achteraan de reeks; verwijderde bestanden verdwijnen uit de lijst. Eigen titels, uitleg en volgorde blijven bewaard, zolang het bestand dezelfde naam en map houdt. Na het verwijderen van een bestand wordt de voltooiing van de leerlingen opnieuw berekend.

## Oefenreeks en voltooiing (fase 5)

- **Items beheren:** open de activiteit als leerkracht en kies *Items beheren*. Per bestand stel je de titel, de uitleg en de positie in.
- **Speler:** één speler per activiteit, met de lijst eronder. Meerdere activiteiten op dezelfde pagina zitten elkaar niet in de weg.
- **Beluisterd:** een item telt als beluisterd zodra de leerling er minstens 75% van gehoord heeft. Alleen stukken die echt afgespeeld zijn tellen; vooruitspoelen telt niet.
- **Voltooiing:** zet bij de instellingen van de activiteit onder Activiteitsvoltooiing *Leerlingen moeten elk item beluisteren* aan. De leerkracht volgt dit in het rapport Activiteitsvoltooiing van de cursus.
- **Privacy:** de plugin bewaart per leerling welke items beluisterd zijn (tabel `mediaspeler_listened`). Export en verwijderen lopen via de privacy-API van Moodle.

## Per groep of instrument (fase 6)

- Maak in de cursus groepen aan, bijvoorbeeld één per instrument. Heeft de activiteit een groepering, dan kun je alleen groepen uit die groepering kiezen.
- Kies onder *Items beheren* per item bij **Voor groepen** de groepen die het item te zien krijgen. Leeg betekent: iedereen.
- Leerlingen zien en spelen alleen de items voor hun groepen. Ook afspelen en afvinken via een rechtstreekse link wordt geweigerd. Leerkrachten zien alle items, met de groepen erbij.
- Voltooiing telt alleen de items die voor de leerling bedoeld zijn. Wordt de leerling uit een groep gehaald of verandert de leerkracht de groepen, dan wordt de voltooiing opnieuw bekeken. Een voltooiing wordt nooit teruggedraaid, ook niet als er later een track bijkomt.
- Wordt een groep verwijderd, dan verdwijnt ze ook bij de items. Een item zonder overgebleven groepen is weer voor iedereen.

## JavaScript

De gebouwde versie van de speler staat in `amd/build`, dus Node.js is niet nodig om de plugin te gebruiken. Pas je `amd/src/player.js` aan, bouw dan opnieuw vanuit de hoofdmap van Moodle:

```bash
npx grunt amd --root=public/mod/mediaspeler
```

## Licentie

GNU GPL v3 of later, zoals Moodle zelf.
