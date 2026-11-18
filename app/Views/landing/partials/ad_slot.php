<?php
$slotNum = (int) ($slotNum ?? 0);
$ariaLabel = (string) ($ariaLabel ?? 'Iklan Sponsor AR Tilik');
$adsenseClient = trim((string) env('ADSENSE_CLIENT', 'ca-pub-9248253613564140'));
$defaultSlots = [
    1 => '4774338502',
    2 => '1341525808',
    3 => '7905712963',
];
$slotEnvKey = 'ADSENSE_SLOT_' . $slotNum;
$rawSlot = (string) env($slotEnvKey, $defaultSlots[$slotNum] ?? '');
$slotId = preg_replace('/\D+/', '', $rawSlot);

if ($slotNum > 0 && $slotId !== '' && $adsenseClient !== '') :
    $escClient = esc($adsenseClient, 'attr');
    $escSlot = esc($slotId, 'attr');
    $escAria = esc($ariaLabel, 'attr');
?>
    <aside class="artilik-ad-section google-anno-skip" aria-label="<?= $escAria ?>" data-ad-slot-wrapper="<?= $slotNum ?>">
        <div class="container">
            <div class="artilik-ad-card">
                <div class="artilik-ad-header">
                    <span class="artilik-ad-badge">Pariwara / Iklan Sponsor</span>
                </div>
                <div class="artilik-ad-slot-body">
                    <ins class="adsbygoogle"
                         style="display:block;width:100%;"
                         data-ad-client="<?= $escClient ?>"
                         data-ad-slot="<?= $escSlot ?>"
                         data-ad-format="auto"
                         data-full-width-responsive="true"></ins>
                </div>
            </div>
        </div>
    </aside>
<?php endif; ?>
