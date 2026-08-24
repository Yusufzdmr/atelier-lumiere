<?php
declare(strict_types=1);

use Atelier\InvitationsV2;

/*
 * Entwurf oder veroeffentlicht.
 *
 * Bis hierher gab es nur einen Zustand: es gibt sie, also steht sie im Netz.
 * Das reicht, solange jede Einladung sofort gilt - aber nicht, sobald eine
 * abgesagte Hochzeit vom Netz soll, ein falscher Link zurueckgezogen werden
 * muss, oder eines Tages die Bezahlung davorsteht (Spezifikation §14).
 *
 * Der Zustand steht in einer EIGENEN Tabelle und nicht in einer neuen Spalte:
 * schema.sql besteht ausschliesslich aus CREATE TABLE IF NOT EXISTS und ist
 * damit beliebig oft ausfuehrbar - ein ALTER TABLE waere die erste Zeile
 * darin, die beim zweiten Mal scheitert, und der Server hat keine
 * Migrationen.
 *
 * Und: KEINE Zeile heisst veroeffentlicht. Jede Einladung, die es heute gibt,
 * hat ihren Link laengst verteilt; ein Vorgabewert "Entwurf" haette sie alle
 * auf einen Schlag abgeschaltet.
 */

/* --- Nur zwei Woerter, alles andere ist ein Entwurf... --- */

assert_same('published', InvitationsV2::cleanStatus('published'), 'Zustand: veroeffentlicht bleibt');
assert_same('draft', InvitationsV2::cleanStatus('draft'), 'Zustand: Entwurf bleibt');

/*
 * ...nein: alles andere ist VEROEFFENTLICHT. Ein unbekanntes Wort - aus einer
 * aelteren Fassung, aus einem Tippfehler - darf keine Einladung abschalten,
 * die im Netz steht. Der Zweifel geht zugunsten des Gastes aus, der den Link
 * schon hat.
 */
assert_same('published', InvitationsV2::cleanStatus('geheim'), 'Zustand: Unbekanntes gilt als veroeffentlicht');
assert_same('published', InvitationsV2::cleanStatus(''), 'Zustand: leer gilt als veroeffentlicht');

/* --- Und die Frage, die der Renderer stellt --- */

assert_same(true, InvitationsV2::isPublic('published'), 'Zustand: veroeffentlicht ist oeffentlich');
assert_same(false, InvitationsV2::isPublic('draft'), 'Zustand: ein Entwurf ist es nicht');
assert_same(true, InvitationsV2::isPublic(''), 'Zustand: und ohne Angabe ist es oeffentlich');
