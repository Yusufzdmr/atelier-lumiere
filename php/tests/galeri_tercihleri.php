<?php

declare(strict_types=1);

use Atelier\Galleries;

/*
 * Galerinin ikinci sekmesi: stil seçimi + anket. "Görüldü" işareti
 * saveSelection'daki pick-sayısı hilesi yerine doğrudan zaman damgası
 * karşılaştırıyor - kayıt her seferinde tamamen değiştiği için (yeni style +
 * yeni answers), "en son ne zaman değişti" zaten "at" alanında duruyor.
 */

assert_same(
    true,
    Galleries::isPreferencesUnseen(['at' => '2026-09-20T10:00:00+00:00']),
    'Tercihler: hiç görülmemiş kayıt görülmemiş sayılır'
);

assert_same(
    false,
    Galleries::isPreferencesUnseen(['at' => '2026-09-20T10:00:00+00:00', 'seenAt' => '2026-09-20T11:00:00+00:00']),
    'Tercihler: görüldükten sonra değişmemişse görülmüş sayılır'
);

assert_same(
    true,
    Galleries::isPreferencesUnseen(['at' => '2026-09-21T09:00:00+00:00', 'seenAt' => '2026-09-20T11:00:00+00:00']),
    'Tercihler: görüldükten sonra yeniden gönderilmişse tekrar görülmemiş sayılır'
);
