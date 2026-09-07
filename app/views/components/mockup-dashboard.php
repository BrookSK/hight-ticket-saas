<?php
/**
 * Realistic dashboard mockup built with HTML/CSS (no stock images).
 * Represents a FUTURE feature visually; does not imply it is available.
 * @var App\Libraries\Translator $t
 */
?>
<div class="mockup" role="img" aria-label="<?= e(__('site.mockup.dashboard_alt')) ?>">
    <div class="mockup__bar">
        <span class="mockup__dot mockup__dot--r"></span>
        <span class="mockup__dot mockup__dot--y"></span>
        <span class="mockup__dot mockup__dot--g"></span>
    </div>
    <div class="mockup__body">
        <div class="row g-3 mb-3">
            <div class="col-4"><div class="mockup-stat"><div class="mockup-stat__value">128</div><div class="mockup-stat__label"><?= e(__('site.mockup.leads')) ?></div></div></div>
            <div class="col-4"><div class="mockup-stat"><div class="mockup-stat__value">37</div><div class="mockup-stat__label"><?= e(__('site.mockup.audits')) ?></div></div></div>
            <div class="col-4"><div class="mockup-stat"><div class="mockup-stat__value">12</div><div class="mockup-stat__label"><?= e(__('site.mockup.projects')) ?></div></div></div>
        </div>
        <div class="d-flex flex-column gap-2">
            <div class="mockup-bar-row"><span style="width:82%"></span></div>
            <div class="mockup-bar-row"><span style="width:64%"></span></div>
            <div class="mockup-bar-row"><span style="width:45%"></span></div>
            <div class="mockup-bar-row"><span style="width:73%"></span></div>
        </div>
    </div>
</div>
