<?php
/**
 * Prepare-contact form: choose channel/contact/template, preview and save
 * (human-in-the-loop). Submits via fetch and shows toasts.
 * @var App\Libraries\Translator $t
 * @var array<string,mixed> $lead
 * @var array<int,array<string,mixed>> $contacts
 * @var array<int,array<string,mixed>> $templates
 * @var array<int,array<string,mixed>> $sequences
 * @var array<int,string> $variables
 * @var list<string> $channels
 */
?>
<div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-4">
    <div>
        <h1 class="h3 mb-1"><?= e(__('outreach.prepare.title')) ?></h1>
        <p class="text-muted mb-0"><?= e((string) ($lead['company_name'] ?? '')) ?></p>
    </div>
    <a href="/app/leads/<?= (int) $lead['id'] ?>" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i><?= e(__('common.actions.back')) ?></a>
</div>

<div class="row g-3">
    <div class="col-12 col-lg-7">
        <div class="card-surface p-3">
            <form id="prepareForm">
                <?= csrf_field() ?>
                <input type="hidden" name="lead_id" value="<?= (int) $lead['id'] ?>">

                <div class="mb-3">
                    <label class="form-label" for="channel"><?= e(__('outreach.channel')) ?></label>
                    <select class="form-select" id="channel" name="channel">
                        <?php foreach ($channels as $ch): ?><option value="<?= e($ch) ?>"><?= e(__('outreach.channels.' . $ch)) ?></option><?php endforeach; ?>
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label" for="contact_id"><?= e(__('outreach.prepare.contact')) ?></label>
                    <select class="form-select" id="contact_id" name="contact_id">
                        <option value=""><?= e(__('outreach.prepare.no_contact')) ?></option>
                        <?php foreach ($contacts as $c): ?>
                            <option value="<?= (int) $c['id'] ?>"><?= e(trim(($c['first_name'] ?? '') . ' ' . ($c['last_name'] ?? ''))) ?> — <?= e((string) ($c['whatsapp'] ?? $c['phone'] ?? $c['email'] ?? '')) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label" for="template_id"><?= e(__('outreach.prepare.template')) ?></label>
                    <select class="form-select" id="template_id" name="template_id">
                        <option value=""><?= e(__('outreach.prepare.no_template')) ?></option>
                        <?php foreach ($templates as $tpl): ?>
                            <option value="<?= (int) $tpl['id'] ?>" data-body="<?= e((string) $tpl['body']) ?>"><?= e((string) $tpl['name']) ?> (<?= e(__('outreach.channels.' . $tpl['channel'])) ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label" for="body"><?= e(__('outreach.prepare.message')) ?></label>
                    <textarea class="form-control" id="body" name="body" rows="6" placeholder="<?= e(__('outreach.prepare.message_placeholder')) ?>"></textarea>
                    <div class="form-text"><?= e(__('outreach.prepare.variables_hint')) ?>: <?php foreach ($variables as $v): ?><code>{{<?= e($v) ?>}}</code> <?php endforeach; ?></div>
                </div>

                <div class="d-flex gap-2">
                    <button type="button" id="previewBtn" class="btn btn-outline-secondary"><i class="bi bi-eye me-1"></i><?= e(__('outreach.prepare.preview')) ?></button>
                    <button type="submit" class="btn btn-brand"><i class="bi bi-save me-1"></i><?= e(__('outreach.prepare.save')) ?></button>
                </div>
            </form>
        </div>
    </div>

    <div class="col-12 col-lg-5">
        <div class="card-surface p-3 mb-3">
            <h2 class="h6 mb-2"><?= e(__('outreach.prepare.preview')) ?></h2>
            <pre id="previewBox" class="p-2 bg-light rounded small mb-0" style="white-space:pre-wrap;min-height:6rem;"></pre>
        </div>

        <?php if ($sequences !== []): ?>
        <div class="card-surface p-3">
            <h2 class="h6 mb-2"><?= e(__('outreach.sequences.enroll_title')) ?></h2>
            <form method="post" action="/app/outreach/enroll">
                <?= csrf_field() ?>
                <input type="hidden" name="lead_id" value="<?= (int) $lead['id'] ?>">
                <div class="input-group input-group-sm">
                    <select class="form-select" name="sequence_id">
                        <?php foreach ($sequences as $seq): ?><option value="<?= (int) $seq['id'] ?>"><?= e((string) $seq['name']) ?></option><?php endforeach; ?>
                    </select>
                    <button class="btn btn-outline-primary" type="submit"><?= e(__('outreach.sequences.enroll')) ?></button>
                </div>
            </form>
        </div>
        <?php endif; ?>
    </div>
</div>

<script>
(function () {
    const form = document.getElementById('prepareForm');
    const tpl = document.getElementById('template_id');
    const body = document.getElementById('body');
    const previewBox = document.getElementById('previewBox');

    tpl.addEventListener('change', function () {
        const opt = tpl.options[tpl.selectedIndex];
        if (opt && opt.dataset.body) { body.value = opt.dataset.body; }
    });

    async function submit(previewOnly) {
        const data = new FormData(form);
        if (previewOnly) { data.append('preview_only', '1'); }
        try {
            const res = await fetch('/app/outreach/messages', { method: 'POST', body: data, headers: { 'Accept': 'application/json' } });
            const json = await res.json();
            if (!res.ok || !json.success) {
                window.App.toast('error', json.message || '<?= e(__('errors.generic')) ?>');
                return;
            }
            if (previewOnly) {
                previewBox.textContent = (json.data && json.data.preview) || '';
            } else {
                window.App.toast('success', json.message || '<?= e(__('outreach.prepare.saved')) ?>');
                setTimeout(() => window.location = '/app/outreach/outbox', 800);
            }
        } catch (e) {
            window.App.toast('error', '<?= e(__('errors.generic')) ?>');
        }
    }

    document.getElementById('previewBtn').addEventListener('click', () => submit(true));
    form.addEventListener('submit', function (e) { e.preventDefault(); submit(false); });
})();
</script>
