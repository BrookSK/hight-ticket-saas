<?php
/**
 * Pipeline (Kanban). Drag-and-drop on desktop; a status <select> on each card
 * as the mobile-friendly alternative. Moves POST to /app/pipeline/{id}/move.
 * @var App\Libraries\Translator $t
 * @var array<string, array<int,array<string,mixed>>> $columns
 * @var list<string> $columnKeys
 */
$canMove = can('pipeline.update');
$scoreClass = static fn (?int $s): string => $s === null ? 'text-muted' : ($s >= 75 ? 'text-success' : ($s >= 60 ? 'text-warning' : 'text-danger'));
?>
<div class="d-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0"><?= e(__('crm.pipeline.title')) ?></h1>
    <a href="/app/leads" class="btn btn-outline-secondary btn-sm"><i class="bi bi-list-ul me-1"></i><?= e(__('crm.leads.title')) ?></a>
</div>

<div class="kanban" id="kanban" data-can-move="<?= $canMove ? '1' : '0' ?>">
    <?php foreach ($columnKeys as $status): ?>
        <div class="kanban__col" data-status="<?= e($status) ?>">
            <div class="kanban__head">
                <span><?= e(__('crm.status.' . $status)) ?></span>
                <span><?= count($columns[$status] ?? []) ?></span>
            </div>
            <div class="kanban__list" data-status="<?= e($status) ?>">
                <?php foreach ($columns[$status] ?? [] as $lead): ?>
                    <div class="kanban__card" draggable="<?= $canMove ? 'true' : 'false' ?>" data-id="<?= (int) $lead['id'] ?>">
                        <a href="/app/leads/<?= (int) $lead['id'] ?>" class="text-decoration-none fw-semibold d-block"><?= e($lead['company_name']) ?></a>
                        <div class="small text-muted"><?= e($lead['title'] ?? $lead['service_type'] ?? '') ?></div>
                        <div class="d-flex justify-content-between align-items-center mt-2">
                            <span class="small text-muted"><?= e(__('crm.temperature.' . $lead['temperature'])) ?></span>
                            <span class="kanban__score <?= $scoreClass($lead['latest_score'] !== null ? (int) $lead['latest_score'] : null) ?>">
                                <?= $lead['latest_score'] !== null ? (int) $lead['latest_score'] : '' ?>
                            </span>
                        </div>
                        <?php if ($canMove): ?>
                            <select class="form-select form-select-sm mt-2 d-lg-none kanban-mobile-move" data-id="<?= (int) $lead['id'] ?>">
                                <?php foreach ($columnKeys as $s): ?>
                                    <option value="<?= $s ?>" <?= $lead['status'] === $s ? 'selected' : '' ?>><?= e(__('crm.status.' . $s)) ?></option>
                                <?php endforeach; ?>
                            </select>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<?php if ($canMove): ?>
<script>
(function () {
    var token = document.querySelector('meta[name="csrf-token"]');
    token = token ? token.getAttribute('content') : '';
    function move(id, status) {
        return fetch('/app/pipeline/' + id + '/move', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-Token': token },
            body: JSON.stringify({ status: status, _token: token })
        }).then(function (r) { return r.json(); });
    }

    // Desktop drag-and-drop.
    var dragged = null;
    document.querySelectorAll('.kanban__card').forEach(function (card) {
        card.addEventListener('dragstart', function () { dragged = card; });
        card.addEventListener('dragend', function () { dragged = null; });
    });
    document.querySelectorAll('.kanban__list').forEach(function (list) {
        var col = list.closest('.kanban__col');
        list.addEventListener('dragover', function (e) { e.preventDefault(); col.classList.add('is-over'); });
        list.addEventListener('dragleave', function () { col.classList.remove('is-over'); });
        list.addEventListener('drop', function (e) {
            e.preventDefault();
            col.classList.remove('is-over');
            if (!dragged) { return; }
            var status = list.getAttribute('data-status');
            var id = dragged.getAttribute('data-id');
            list.appendChild(dragged);
            move(id, status).then(function (res) {
                if (res && res.success) { window.App && App.toast('success', res.message); }
                else { window.location.reload(); }
            }).catch(function () { window.location.reload(); });
        });
    });

    // Mobile select fallback.
    document.querySelectorAll('.kanban-mobile-move').forEach(function (sel) {
        sel.addEventListener('change', function () {
            move(sel.getAttribute('data-id'), sel.value).then(function () { window.location.reload(); });
        });
    });
})();
</script>
<?php endif; ?>
