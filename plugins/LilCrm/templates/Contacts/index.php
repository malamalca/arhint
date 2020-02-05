<?php
use Cake\Core\Configure;
use Cake\Routing\Router;

$this->set('title_for_layout', __d('lil_crm', 'Contact List'));
$this->set('main_menu', [
    'add' => [
        'title' => __d('lil_crm', 'Add'),
        'visible' => true,
        'url' => [
            'plugin' => 'LilCrm',
            'controller' => 'Contacts',
            'action' => 'add',
            'kind' => $filter['kind'],
        ],
    ],
]);

$contactsIndex = [
    'title_for_layout' => __d('lil_crm', 'Contact List'),
    'menu' => [
        'add' => [
            'title' => __d('lil_crm', 'Add'),
            'visible' => true,
            'url' => [
                'plugin' => 'LilCrm',
                'controller' => 'Contacts',
                'action' => 'add',
                '?' => ['kind' => $filter['kind']],
            ],
        ],
    ],
    'table' => [
        'pre' => '<div class="index">' . PHP_EOL,
        'post' => '</div>',
        'parameters' => [
            'cellspacing' => 0,
            'cellpadding' => 0,
            'id' => 'ContactsIndexTable',
        ],
        'head' => [
            'rows' => [
                [
                    'columns' => [
                        'search' => [
                            'params' => ['colspan' => 2, 'class' => 'input-field'],
                            'html' => sprintf('<input placeholder="%s" id="SearchBox" />', __d('lil_crm', 'Search')),
                        ],
                        'pagination' => [
                            'params' => ['colspan' => 2, 'class' => 'right-align'],
                            'html' => '<ul class="paginator">' . $this->Paginator->numbers([
                                'first' => '<<',
                                'last' => '>>',
                                'modulus' => 3]) . '</ul>'],
                    ],
                ],
                [
                'columns' => [
                    'title' => [
                        'parameters' => ['class' => 'left-align'],
                        'html' => $filter['kind'] == 'T' ? __d('lil_crm', 'Name') : __d('lil_crm', 'Title'),
                    ],
                    'emails' => [
                        'parameters' => ['class' => 'left-align'],
                        'html' => __d('lil_crm', 'Email'),
                    ],
                    'phones' => [
                        'parameters' => ['class' => 'left-align'],
                        'html' => __d('lil_crm', 'Phone'),
                    ],
                    'syncable' => [
                        'parameters' => ['class' => 'center-align'],
                        'html' => __d('lil_crm', 'Syncable'),
                    ],
                ],
                ]],
        ],
    ],
];

$countries = Configure::read('LilCrm.countries');
foreach ($contacts as $contact) {
    $job = '';
    if (!empty($contact->company)) {
        $job .= '<div class="contact-employer">';
        if (!empty($contact->job)) {
            $job .= h($contact->job);
        } else {
            $job .= '<span class="light">' . __d('lil_crm', 'employed') . '</span>';
        }
        $job .= ' <span class="light">' . __d('lil_crm', 'at') . '</span> ';
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
            ['title' => Configure::read('LilCrm.phoneTypes.' . $email['kind'])]
        ) . '<br />';
    }

    $phones = '';
    foreach ($contact->contacts_phones as $phone) {
        $phones .= sprintf(
            '<span class="strong" title="%2$s">%1$s</span>',
            h($phone['no']),
            Configure::read('LilCrm.phoneTypes.' . $phone['kind'])
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
                'html' => $this->Html->link(
                    $contact->title,
                    ['action' => 'view', $contact->id],
                    ['class' => 'big', 'title' => $contact->title]
                ) . $job . $address . $descript,
            ],
            'emails' => [
                'parameters' => ['class' => 'nowrap'],
                'html' => $emails,
            ],
            'phones' => [
                'parameters' => ['class' => 'nowrap'],
                'html' => $phones,
            ],
            'syncable' => [
                'parameters' => ['class' => 'center-align'],
                'html' => $syncable,
            ],
        ],
    ];
}

echo $this->Lil->index($contactsIndex, 'LilCrm.Contacts.index');
?>
<script type="text/javascript">
    var toggleSyncableUrl = "<?php echo Router::url([
        'plugin' => 'LilCrm',
        'controller' => 'Contacts',
        'action' => 'set-syncable',
        '__id__', '__syncable__',
    ]); ?>";

    var ajaxSearchUrl = "<?php echo Router::url([
        'plugin' => 'LilCrm',
        'controller' => 'Contacts',
        'action' => 'index',
        '?' => ['kind' => $filter['kind'], 'term' => '__term__'],
    ]); ?>";

    $(document).ready(function() {
        $(".toggle-syncable").click(function() {
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
        });

        $("#SearchBox").on("input", function(e) {
            if ($(this).val().length > 1) {
                var rx_term = new RegExp("__term__", "i");
                $.get(ajaxSearchUrl.replace(rx_term, encodeURIComponent($(this).val())), function(response) {
                    let tBody = response.substring(response.indexOf("<tbody>")+7, response.indexOf("</tbody>"));
                    $("#ContactsIndexTable tbody").html(tBody);

                    let paginator = response.substring(
                        response.indexOf("<ul class=\"paginator\">")+22,
                        response.indexOf("</ul>", response.indexOf("<ul class=\"paginator\">"))
                    );
                    $("#ContactsIndexTable ul.paginator").html(paginator);
                });
            }
        });
    });
</script>
