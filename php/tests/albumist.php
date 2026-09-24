<?php

declare(strict_types=1);

use Atelier\Galleries;

/*
 * Bir galeri "hazır" sayılır ⇔ seçim var ve en az bir kare seçilmiş.
 * 2. madde (albüm modeli, kargo adresi) eklenene kadar tek ölçüt bu —
 * ZIP akışının zaten kullandığı ölçütle aynı.
 */

assert_same(
    false,
    Galleries::isReady(null),
    'Hazır: seçim hiç yoksa hazır değildir'
);

assert_same(
    false,
    Galleries::isReady(['picks' => []]),
    'Hazır: seçim var ama kare seçilmemişse hazır değildir'
);

assert_same(
    true,
    Galleries::isReady(['picks' => [0, 2]]),
    'Hazır: en az bir kare seçilmişse hazırdır'
);
