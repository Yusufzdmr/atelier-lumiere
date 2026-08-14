<?php
declare(strict_types=1);

namespace Atelier;

/**
 * Anfragen aus dem Kontaktformular.
 *
 * Sie werden gespeichert UND per E-Mail geschickt: Die Datenbank ist der
 * verlässliche Teil (im Adminbereich sichtbar), die Mail der bequeme. Fällt
 * der Mailversand aus, ist die Anfrage trotzdem nicht verloren.
 */
final class Leads
{
    /** @param array<string,string> $data */
    public static function store(array $data): void
    {
        $lead = [
            'name'     => $data['name'] ?? '',
            'email'    => $data['email'] ?? '',
            'phone'    => $data['phone'] ?? '',
            'date'     => $data['date'] ?? '',
            'location' => $data['location'] ?? '',
            'guests'   => $data['guests'] ?? '',
            'service'  => $data['service'] ?? '',
            'message'  => $data['message'] ?? '',
            'locale'   => $data['locale'] ?? I18n::DEFAULT,
            'at'       => date('c'),
        ];

        Db::run('INSERT INTO leads (data) VALUES (?)', [Db::encode($lead)]);
        self::notify($lead);
    }

    /** @return list<array<string,mixed>> */
    public static function all(int $limit = 200): array
    {
        return Db::jsonList('SELECT data FROM leads ORDER BY at DESC LIMIT ' . max(1, $limit));
    }

    /** @param array<string,string> $lead */
    private static function notify(array $lead): void
    {
        $to = Config::str('mail_to');
        $from = Config::str('mail_from');
        if ($to === '' || $from === '') {
            return;
        }

        $subject = 'Neue Anfrage: ' . Security::singleLine($lead['name']);
        if ($lead['date'] !== '') {
            $subject .= ' (' . Security::singleLine($lead['date']) . ')';
        }

        $lines = [
            'Name:     ' . $lead['name'],
            'E-Mail:   ' . $lead['email'],
            'Telefon:  ' . $lead['phone'],
            'Datum:    ' . $lead['date'],
            'Ort:      ' . $lead['location'],
            'Gäste:    ' . $lead['guests'],
            'Wunsch:   ' . $lead['service'],
            'Sprache:  ' . $lead['locale'],
            '',
            $lead['message'],
        ];

        $headers = [
            'From: Atelier Lumière <' . Security::singleLine($from) . '>',
            'Content-Type: text/plain; charset=UTF-8',
        ];

        // Antworten sollen beim Paar landen, nicht bei der Website.
        if (filter_var($lead['email'], FILTER_VALIDATE_EMAIL)) {
            $headers[] = 'Reply-To: ' . Security::singleLine($lead['email']);
        }

        // Scheitert der Versand, bleibt die Anfrage in der Datenbank.
        @mail($to, '=?UTF-8?B?' . base64_encode($subject) . '?=', implode("\n", $lines), implode("\r\n", $headers));
    }
}
