<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * Dutch strings for mod_mediaspeler.
 *
 * @package    mod_mediaspeler
 * @copyright  2026 Michel Labruyère
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['autherror'] = 'Nextcloud weigerde de aanmelding. Controleer de gebruikersnaam en het app-wachtwoord van het serviceaccount.';
$string['completiondetail:listenall'] = 'Beluister elk item (minstens 75% van elk)';
$string['completionlistenall'] = 'Leerlingen moeten elk item beluisteren (minstens 75% van elk)';
$string['completionlistenall_help'] = 'Een item telt als beluisterd zodra de leerling er minstens 75% van gehoord heeft. Alleen stukken die echt afgespeeld zijn tellen: vooruitspoelen telt niet. De volgorde is vrij.';
$string['connectionerror'] = 'Nextcloud is niet bereikbaar: {$a}';
$string['foldernotfound'] = 'De map is niet gevonden in Nextcloud. Controleer of ze gedeeld is met het serviceaccount.';
$string['freeorder'] = 'Kies zelf welk item je afspeelt. De volgorde van de lijst is een voorstel.';
$string['httperror'] = 'Nextcloud antwoordde met HTTP-status {$a}.';
$string['invalidncurl'] = 'Het Nextcloud-adres "{$a}" is geen geldige URL.';
$string['invalidresponse'] = 'Nextcloud stuurde een antwoord dat niet gelezen kon worden.';
$string['itemdescription'] = 'Uitleg';
$string['itemposition'] = 'Positie';
$string['itemssaved'] = 'De items zijn opgeslagen.';
$string['itemtitle'] = 'Titel';
$string['listened'] = 'Beluisterd';
$string['listenedshare'] = 'Deel van dit item dat je gehoord hebt';
$string['manageitems'] = 'Items beheren';
$string['manageitems_intro'] = 'Stel per audiobestand de titel, de uitleg en de positie in. Nieuwe bestanden in de Nextcloud-map komen vanzelf achteraan.';
$string['mediaspeler:addinstance'] = 'Een nieuwe mediaspeler toevoegen';
$string['mediaspeler:manageitems'] = 'De items van een mediaspeler beheren';
$string['mediaspeler:view'] = 'Een mediaspeler bekijken en afspelen';
$string['modulename'] = 'Mediaspeler';
$string['modulename_help'] = 'De mediaspeler speelt audio uit een map in Nextcloud af als oefenreeks, zonder de bestanden naar Moodle te kopiëren.';
$string['modulenameplural'] = 'Mediaspelers';
$string['ncapppassword'] = 'App-wachtwoord';
$string['ncapppassword_desc'] = 'App-wachtwoord van het serviceaccount, aangemaakt in Nextcloud onder Persoonlijke instellingen → Beveiliging.';
$string['ncfolder'] = 'Map in Nextcloud';
$string['ncfolder_help'] = 'De map in Nextcloud, zoals het serviceaccount ze ziet. Bijvoorbeeld: Muzieklab-test';
$string['ncurl'] = 'Adres van Nextcloud';
$string['ncurl_desc'] = 'Adres waarmee Moodle Nextcloud bereikt. Draaien beide op dezelfde server: http://localhost:8080';
$string['ncusername'] = 'Serviceaccount';
$string['ncusername_desc'] = 'Gebruiker in Nextcloud die de gedeelde mappen leest namens Moodle.';
$string['nextcloudsettings'] = 'Nextcloud';
$string['nextcloudsettings_desc'] = 'Alle activiteiten lezen via dit ene serviceaccount uit Nextcloud. Deel de mappen met dit account, alleen-lezen.';
$string['noitems'] = 'Er staan nog geen audiobestanden in deze activiteit.';
$string['notconfigured'] = 'De koppeling met Nextcloud is nog niet ingesteld. Een beheerder doet dat onder Site-beheer → Plugins → Activiteitsmodules → Mediaspeler.';
$string['nowplaying'] = 'Nu geladen';
$string['pluginadministration'] = 'Beheer van de mediaspeler';
$string['pluginname'] = 'Mediaspeler';
$string['privacy:metadata:mediaspeler_listened'] = 'Welke items een gebruiker beluisterd heeft, voor de activiteitsvoltooiing.';
$string['privacy:metadata:mediaspeler_listened:itemid'] = 'Het item dat beluisterd werd.';
$string['privacy:metadata:mediaspeler_listened:timecreated'] = 'Wanneer de gebruiker genoeg van het item gehoord had.';
$string['privacy:metadata:mediaspeler_listened:userid'] = 'De gebruiker die luisterde.';
$string['privacy:metadata:nextcloud'] = 'De audio wordt uit Nextcloud gehaald met een gedeeld serviceaccount. Er gaan geen gegevens over de gebruiker naar Nextcloud.';
$string['sourcefolder'] = 'Map in Nextcloud: {$a}';
$string['syncdone'] = 'Bijgewerkt vanuit Nextcloud: {$a->added} toegevoegd, {$a->updated} gewijzigd, {$a->removed} verwijderd.';
$string['syncfailed'] = 'Bijwerken vanuit Nextcloud is mislukt. {$a}';
$string['task_syncitems'] = 'Mediaspelers bijwerken vanuit Nextcloud';
