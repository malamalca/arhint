<?php

// Build lookup for children (used for toggle indicator and visibility)
$childrenOf = [];
foreach ($accounts as $a) {
    $pid = $a->parent_id ?? 0;
    $childrenOf[$pid][] = $a->id;
}

$searchVal = h($filter['search'] ?? '');

// CSS styles embedded in the pre block
$preHtml = '<style>';
$preHtml .= '.acc-indent{display:inline-block}';
$preHtml .= '.acc-toggle{cursor:pointer;user-select:none;display:inline-block;width:16px;text-align:center;color:#778;font-size:.8em}';
$preHtml .= '.acc-row-level-0.ui-row td:first-child{font-weight:bold}';
$preHtml .= '.acc-row-level-1.ui-row td:first-child{font-weight:600}';
$preHtml .= '.acc-search-bar{display:flex;align-items:center;gap:8px;margin-bottom:10px;flex-wrap:wrap}';
$preHtml .= '.acc-search-bar input[type=text]{height:auto;padding:4px 8px;border:1px solid #ccc;border-radius:3px;min-width:220px}';
$preHtml .= '</style>';

// Search form
$preHtml .= '<div class="acc-search-bar">';
$preHtml .= sprintf(
    '<form method="get" action="" style="display:flex;gap:6px;align-items:center">'
        . '<input type="text" name="search" value="%s" placeholder="%s"/>'
        . '<button type="submit" class="button small">%s</button>',
    $searchVal,
    __d('expenses', 'Search code or name…'),
    __d('expenses', 'Search'),
);
if (!empty($filter['search'])) {
    $preHtml .= $this->Html->link(__d('expenses', 'Clear'), ['action' => 'index'], ['class' => 'button small']);
}
$preHtml .= '</form>';

if ($treeMode) {
    $preHtml .= sprintf(
        '<button class="button small" id="btn-expand-all">%s</button>'
            . '<button class="button small" id="btn-collapse-all">%s</button>',
        __d('expenses', 'Expand all'),
        __d('expenses', 'Collapse all'),
    );
}
$preHtml .= '</div>';

$accountIndex = [
    'title_for_layout' => __d('expenses', 'Chart of Accounts'),
    'menu' => [
        'add' => [
            'title' => __d('expenses', 'Add'),
            'visible' => true,
            'url' => [
                'plugin' => 'Expenses',
                'controller' => 'Accounts',
                'action' => 'edit',
            ],
        ],
    ],
    'table' => [
        'parameters' => [
            'width' => '100%', 'cellspacing' => 0, 'cellpadding' => 0, 'id' => 'accounts-index-table',
        ],
        'pre' => $preHtml,
        'head' => ['rows' => [['columns' => [
            'code' => __d('expenses', 'Code'),
            'name' => __d('expenses', 'Name'),
            'actions' => '',
        ]]]],
    ],
];

foreach ($accounts as $item) {
    $hasChildren = !empty($childrenOf[$item->id]);
    $isCollapsed = $treeMode && $hasChildren && $item->level >= 1;
    $parentId = $item->parent_id ?? 0;
    $indent = $item->level * 20;

    $toggleHtml = $hasChildren && $treeMode
        ? sprintf(
            '<span class="acc-toggle" data-toggle="%s">%s</span>',
            $item->id,
            $isCollapsed ? '▶' : '▼',
        )
        : '<span style="display:inline-block;width:16px"></span>';

    $codeHtml = sprintf(
        '<span class="acc-indent" style="width:%dpx"></span>%s<strong>%s</strong>',
        $indent,
        $toggleHtml,
        h($item->code),
    );

    $rowParams = [
        'class' => 'acc-row-level-' . $item->level,
        'data-id' => $item->id,
        'data-parent' => $parentId,
        'data-level' => $item->level,
    ];
    if ($isCollapsed) {
        $rowParams['data-collapsed'] = '1';
    }
    if ($treeMode && $item->level >= 2) {
        $rowParams['style'] = 'display:none';
    }

    $actionsHtml = '';
    if ($this->getCurrentUser()->hasRole('root')) {
        $actionsHtml = $this->Lil->editLink($item->id);
        if (!$hasChildren) {
            $actionsHtml .= ' ' . $this->Lil->deleteLink($item->id);
        }
    }

    $accountIndex['table']['body']['rows'][] = [
        'parameters' => $rowParams,
        'columns' => [
            'code' => [
                'html' => $codeHtml,
            ],
            'name' => [
                'html' => h($item->name),
            ],
            'actions' => [
                'params' => ['class' => 'right-align nowrap'],
                'html' => $actionsHtml,
            ],
        ],
    ];
}

echo $this->Lil->index($accountIndex, 'Expenses.Accounts.index');
?>
<script type="text/javascript">
    (function () {
        var rows = Array.from(document.querySelectorAll("#accounts-index-table tbody tr"));
        var rowById = {}, childrenOf = {};
        rows.forEach(function (tr) {
            var id = tr.dataset.id, pid = tr.dataset.parent;
            rowById[id] = tr;
            if (pid !== "0") {
                if (!childrenOf[pid]) childrenOf[pid] = [];
                childrenOf[pid].push(id);
            }
        });

        function setSubtreeVisible(parentId, visible) {
            (childrenOf[parentId] || []).forEach(function (id) {
                var tr = rowById[id];
                if (!tr) return;
                if (visible) {
                    tr.style.display = "";
                    if (tr.dataset.collapsed !== "1") setSubtreeVisible(id, true);
                } else {
                    tr.style.display = "none";
                    setSubtreeVisible(id, false);
                }
            });
        }

        document.querySelectorAll(".acc-toggle").forEach(function (btn) {
            btn.addEventListener("click", function () {
                var id = btn.dataset.toggle, tr = rowById[id];
                if (!tr) return;
                var collapsed = tr.dataset.collapsed === "1";
                tr.dataset.collapsed = collapsed ? "0" : "1";
                btn.textContent = collapsed ? "\u25BC" : "\u25B6";
                setSubtreeVisible(id, collapsed);
            });
        });

        var btnExp = document.getElementById("btn-expand-all");
        if (btnExp) btnExp.addEventListener("click", function () {
            rows.forEach(function (tr) { tr.style.display = ""; tr.dataset.collapsed = "0"; });
            document.querySelectorAll(".acc-toggle").forEach(function (b) { b.textContent = "\u25BC"; });
        });

        var btnCol = document.getElementById("btn-collapse-all");
        if (btnCol) btnCol.addEventListener("click", function () {
            rows.forEach(function (tr) {
                tr.style.display = parseInt(tr.dataset.level) >= 1 ? "none" : "";
                if (childrenOf[tr.dataset.id]) tr.dataset.collapsed = "1";
            });
            document.querySelectorAll(".acc-toggle").forEach(function (b) { b.textContent = "\u25B6"; });
        });
    })();
</script>