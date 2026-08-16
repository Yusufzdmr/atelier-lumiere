<?php
declare(strict_types=1);

namespace Atelier;

/**
 * Ausgehende Post.
 *
 * Wenig, aber an drei Stellen dasselbe: Absender aus der config.php, Betreff
 * nach RFC 2047 kodiert (sonst steht „Ã¼“ im Posteingang), Text als UTF-8.
 * Fehlschläge sind absichtlich stumm – was verschickt werden sollte, steht
 * vorher schon in der Datenbank, und eine Fehlerseite hilft dem Absender an
 * dieser Stelle nicht.
 */
final class Mail
{
    /**
     * @param list<string> $lines Textzeilen der Nachricht
     */
    public static function send(string $to, string $subject, array $lines, string $replyTo = ''): bool
    {
        $to = Security::singleLine($to);
        $from = Security::singleLine(Config::str('mail_from'));

        if ($from === '' || !filter_var($to, FILTER_VALIDATE_EMAIL)) {
            return false;
        }

        $headers = [
            'From: Atelier Lumière <' . $from . '>',
            'Content-Type: text/plain; charset=UTF-8',
            'MIME-Version: 1.0',
        ];

        $replyTo = Security::singleLine($replyTo);
        if ($replyTo !== '' && filter_var($replyTo, FILTER_VALIDATE_EMAIL)) {
            $headers[] = 'Reply-To: ' . $replyTo;
        }

        return @mail(
            $to,
            '=?UTF-8?B?' . base64_encode(Security::singleLine($subject)) . '?=',
            implode("\n", $lines),
            implode("\r\n", $headers)
        );
    }

    /** Die Adresse des Betriebs – Kontaktformular, Galerieauswahl. */
    public static function toStudio(string $subject, array $lines, string $replyTo = ''): bool
    {
        return self::send(Config::str('mail_to'), $subject, $lines, $replyTo);
    }
}
