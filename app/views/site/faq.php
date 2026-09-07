<?php
/**
 * FAQ page with accessible accordion.
 * @var App\Libraries\Translator $t
 */
$items = range(1, 6);
?>
<section class="hero">
    <div class="container text-center">
        <span class="eyebrow"><?= e(__('site.nav.faq')) ?></span>
        <h1 class="display-6 fw-bold mt-2"><?= e(__('site.faq.title')) ?></h1>
        <p class="lead text-muted"><?= e(__('site.faq.subtitle')) ?></p>
    </div>
</section>

<section class="section">
    <div class="container" style="max-width:820px;">
        <div class="accordion" id="faqAccordion">
            <?php foreach ($items as $i): ?>
                <div class="accordion-item">
                    <h2 class="accordion-header" id="faqHead<?= $i ?>">
                        <button class="accordion-button <?= $i === 1 ? '' : 'collapsed' ?>" type="button"
                                data-bs-toggle="collapse" data-bs-target="#faq<?= $i ?>"
                                aria-expanded="<?= $i === 1 ? 'true' : 'false' ?>" aria-controls="faq<?= $i ?>">
                            <?= e(__('site.faq.q' . $i)) ?>
                        </button>
                    </h2>
                    <div id="faq<?= $i ?>" class="accordion-collapse collapse <?= $i === 1 ? 'show' : '' ?>"
                         aria-labelledby="faqHead<?= $i ?>" data-bs-parent="#faqAccordion">
                        <div class="accordion-body text-muted"><?= e(__('site.faq.a' . $i)) ?></div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
