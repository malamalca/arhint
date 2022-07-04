<?php
use Cake\Core\Configure;
use Cake\Routing\Router;

$this->set('title_for_layout', __d('crm', 'Contact List'));
$this->set('main_menu', [
    'add' => [
        'title' => __d('crm', 'Add'),
        'visible' => true,
        'url' => [
            'plugin' => 'Crm',
            'controller' => 'Contacts',
            'action' => 'edit',
            'kind' => $filter['kind'],
        ],
    ],
]);

$contactsIndex = [
    'title_for_layout' => __d('crm', 'Contact List'),
    'menu' => [
        'add' => [
            'title' => __d('crm', 'Add'),
            'visible' => true,
            'url' => [
                'plugin' => 'Crm',
                'controller' => 'Contacts',
                'action' => 'edit',
                '?' => ['kind' => $filter['kind']],
            ],
        ],
    ],
    'table' => [
        'pre' => $this->Arhint->searchPanel($this->getRequest()->getQuery('search', '')),
        'parameters' => [
            'cellspacing' => 0,
            'cellpadding' => 0,
            'id' => 'ContactsIndexTable',
        ],
        'head' => [
            'rows' => [
                [
                'columns' => [
                    'title' => [
                        'parameters' => ['class' => 'left-align'],
                        'html' => $filter['kind'] == 'T' ? __d('crm', 'Name') : __d('crm', 'Title'),
                    ],
                    'emails' => [
                        'parameters' => ['class' => 'left-align hide-on-small-only'],
                        'html' => __d('crm', 'Email'),
                    ],
                    'phones' => [
                        'parameters' => ['class' => 'left-align hide-on-small-only'],
                        'html' => __d('crm', 'Phone'),
                    ],
                    'syncable' => [
                        'parameters' => ['class' => 'center-align hide-on-small-only'],
                        'html' => __d('crm', 'Syncable'),
                    ],
                ],
                ]],
        ],
        'foot' => [
            'rows' => [
                [
                    'columns' => [
                        'pagination' => [
                            'params' => ['colspan' => 2, 'class' => 'left-align hide-on-small-only'],
                            'html' => '<ul class="paginator">' . $this->Paginator->numbers([
                                'first' => '<<',
                                'last' => '>>',
                                'modulus' => 3]) . '</ul>',
                        ],
                        'empty' => [
                            'params' => ['colspan' => 2],
                            'html' => '',
                        ],
                    ],
                ],
            ],
        ],
    ],
];

$countries = Configure::read('Crm.countries');
foreach ($contacts as $contact) {
    $job = '';

    if (!empty($contact->company)) {
        $job .= '<div class="contact-employer">';
        if (!empty($contact->job)) {
            $job .= h($contact->job);
        } else {
            $job .= '<span class="light">' . __d('crm', 'employed') . '</span>';
        }
        $job .= ' <span class="light">' . __d('crm', 'at') . '</span> ';
        $job .= $this->Html->link(
            $contact->company->title,
            [
                'action' => 'view',
                $contact->company->id,
                'kind' => 'C',
            ],
            ['title' => h($contact->company->title)]
        );
        $job .= '</div>';
    } elseif (!empty($contact['Contact']['job'])) {
        $job .= sprintf('<div>%s</div>', h($contact->job));
    }

    $address = '';
    if (!empty($contact->primary_address)) {
        $address = sprintf('<div class="light small">%s</div>', implode(', ', array_filter([
            $contact->primary_address->street,
            implode(' ', array_filter([
                $contact->primary_address->zip,
                $contact->primary_address->city,
            ])),
            h($countries[$contact->primary_address->country_code] ?? $contact->primary_address->country_code),
        ])));
    }

    $descript = '';
    if (!empty($contact->descript)) {
        $descript = sprintf('<div class="contact-descript">%s</div>', h($contact->descript));
    }

    $emails = '';
    foreach ($contact->contacts_emails as $email) {
        $emails .= $this->Html->link(
            $email->email,
            'mailto:' . h($email->email),
            ['title' => Configure::read('Crm.phoneTypes.' . $email['kind'])]
        ) . '<br />';
    }

    $phones = '';
    foreach ($contact->contacts_phones as $phone) {
        $phones .= sprintf(
            '<span class="strong" title="%2$s">%1$s</span>',
            h($phone['no']),
            Configure::read('Crm.phoneTypes.' . $phone['kind'])
        ) . '<br />';
    }

    $syncable = sprintf(
        '<div class="switch"><label><input type="checkbox" id="cb%1$s" class="toggle-syncable"%2$s%3$s />' .
        '<span class="lever"></span></label></div>',
        $contact->id,
        $contact->kind == 'T' ? '' : ' disabled="disabled"',
        $contact->kind == 'T' && $contact->syncable ? ' checked="checked"' : ''
    );

    $contactsIndex['table']['body']['rows'][] = [
        'contact' => $contact,
        'columns' => [
            'title' => [
                'parameters' => ['class' => 'contact-name'],
                'html' =>
                sprintf('<div class="hide-on-med-and-up" style="display: block; float: right">%s</div>', $phones) .
                $this->Html->link(
                    empty($contact->title) ? 'N/A' : $contact->title,
                    ['action' => 'view', $contact->id],
                    ['class' => 'big contact-name', 'title' => $contact->title]
                ) . $job . $address . $descript,
            ],
            'emails' => [
                'parameters' => ['class' => 'nowrap hide-on-small-only'],
                'html' => $emails,
            ],
            'phones' => [
                'parameters' => ['class' => 'nowrap hide-on-small-only'],
                'html' => $phones,
            ],
            'syncable' => [
                'parameters' => ['class' => 'center-align hide-on-small-only'],
                'html' => $syncable,
            ],
        ],
    ];
}

echo $this->Lil->index($contactsIndex, 'Crm.Contacts.index');
?>
<script type="text/javascript">
    var toggleSyncableUrl = "<?php echo Router::url([
        'plugin' => 'Crm',
        'controller' => 'Contacts',
        'action' => 'set-syncable',
        '__id__', '__syncable__',
    ]); ?>";

    var searchUrl = "<?php echo Router::url([
        'plugin' => 'Crm',
        'controller' => 'Contacts',
        'action' => 'index',
        '?' => ['kind' => $filter['kind'], 'term' => '__term__'],
    ]); ?>";

    var searchTimer = null;

    function onToggleSyncable() {
        var rx_id = new RegExp("__id__", "i");
        var rx_syncable = new RegExp("__syncable__", "i");
        var cb = this;

        // prevent another click before ajax request completes
        $(cb).prop("disabled", true);

        // do the ajax request
        var jqxhr = $.get(
            toggleSyncableUrl
                .replace(rx_id, $(cb).prop("id").substr(2))
                .replace(rx_syncable, $(cb).prop("checked") === true ? 1 : 0)
        )
        .fail(function() {
            // toggle checkbox back on ajax failure
            $(cb).prop("checked", function (i, value) { return !value });
        })
        .always(function() {
            // reenable cb
            $(cb).prop("disabled", false);
        });
    }

    function searchContacts()
    {
        var rx_term = new RegExp("__term__", "i");
        $.get(searchUrl.replace(rx_term, encodeURIComponent($(".search-panel input").val())), function(response) {
            let tBody = response
                .substring(response.indexOf("<table class=\"index"), response.indexOf("</table>")+8);
            $("#ContactsIndexTable").html(tBody);
            $(".toggle-syncable").click(onToggleSyncable);
        });
    }

    $(document).ready(function() {
        $(".toggle-syncable").click(onToggleSyncable);

        $(".search-panel input").on("input", function(e) {
            if ($(this).val().length > 1) {
                if (searchTimer) {
                    window.clearTimeout(searchTimer);
                    searchTimer = null;
                }
                searchTimer = window.setTimeout(searchContacts, 500);
            }
        }).focus();
    });
</script>
