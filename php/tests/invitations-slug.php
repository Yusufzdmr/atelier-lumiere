<?php

declare(strict_types=1);

use Atelier\Invitations;

/*
 * Invitations::slug() üç yerde davetiyeyle ilgisiz kullanılıyor (müşteri
 * kodu, admin liste sıralaması, OG görsel), bir yerde de InvitationsV2::
 * slug() üzerinden tüm canlı davetiyelerin URL'ini kuruyor — bu yüzden
 * Invitations.php küçülürken davranışı değişmemeli.
 */

assert_same('ayse-mehmet', Invitations::slug('Ayşe & Mehmet'), 'slug: Türkçe karakter ve boşluk');
assert_same('', Invitations::slug('   '), 'slug: sadece boşluk boş döner');
assert_same('test-123', Invitations::slug('  Test 123!!  '), 'slug: baştaki/sondaki boşluk ve özel karakter kırpılır');
assert_same('grossmutter', Invitations::slug('Großmutter'), 'slug: ß → ss');
