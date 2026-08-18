<?php
declare(strict_types=1);

namespace Atelier\Controllers;

use Atelier\Admin;
use Atelier\I18n;
use Atelier\Integrations;
use Atelier\Security;
use Atelier\View;

/**
 * Admin parolasını panelden değiştirme.
 *
 * Parolanın kendisi Integrations::adminPasswordHash() ile veri tabanında
 * tutulur. Bu sayfa: mevcut parolayı doğrula, yeni parola × 2, hash'le ve
 * kaydet, session'ı yenile.
 */
final class AccessAdminController
{
    public function __construct(private readonly string $locale)
    {
        Admin::requireLogin($this->locale);
    }

    public function index(): void
    {
        $error = '';

        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
            Admin::checkCsrfOrFail();
            $error = $this->change();
            if ($error === '') {
                Admin::back($this->locale, '/zugang');
            }
        }

        View::page('admin/access', [
            'layout'  => 'admin/layout',
            'locale'  => $this->locale,
            'path'    => I18n::path('/admin/zugang', $this->locale),
            'current' => '/zugang',
            'meta'    => ['title' => 'Admin', 'noindex' => true],
            'csrf'    => Security::csrf(),
            'error'   => $error,
            'hasHash' => Integrations::adminPasswordHash() !== '',
        ]);
    }

    /** @return string boş = başarılı, aksi halde hata anahtarı */
    private function change(): string
    {
        $current = (string) ($_POST['current'] ?? '');
        $new     = (string) ($_POST['new'] ?? '');
        $confirm = (string) ($_POST['confirm'] ?? '');

        if (!Admin::verify($current)) {
            return 'current';
        }
        if (mb_strlen($new) < 8) {
            return 'short';
        }
        if ($new !== $confirm) {
            return 'mismatch';
        }

        $hash = password_hash($new, PASSWORD_DEFAULT);
        if (!is_string($hash) || $hash === '') {
            return 'hash';
        }

        Integrations::saveAdminPasswordHash($hash);

        // Sessiona yeni bir kimlik ver — eski cookie'yle oturum sürdürülemesin.
        session_regenerate_id(true);

        return '';
    }
}
