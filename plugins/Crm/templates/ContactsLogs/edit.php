<?php
use Cake\Routing\Router;

/**
 * This is admin_edit template file.
 */

$editForm = [
    'title_for_layout' =>
        h($contact->title) . ' :: ' .
        ($contactsLog->id ? __d('crm', 'Edit Log') : __d('crm', 'Add Log')),
    'form' => [
        'defaultHelper' => $this->Form,
        'pre' => '<div class="form">',
        'post' => '</div>',
        'lines' => [
            'form_start' => [
                'method' => 'create',
                'parameters' => ['model' => $contactsLog, ['idPrefix' => 'contacts-logs', 'type' => 'file']],
            ],
            'id' => [
                'method' => 'hidden',
                'parameters' => ['id'],
            ],
            'referer' => [
                'method' => 'hidden',
                'parameters' => ['referer', ['default' => Router::url($this->getRequest()->referer(), true)]],
            ],
            'contact_id' => [
                'method' => 'hidden',
                'parameters' => ['contact_id'],
            ],
            'user_id' => [
                'method' => 'hidden',
                'parameters' => ['user_id'],
            ],

            'descript' => [
                'method' => 'textarea',
                'parameters' => [
                    'descript',
                    [
                        'id' => 'contacts-logs-descript',
                    ],
                ],
            ],
            'spacer' => '<div>&nbsp;</div>',
            'submit' => [
                'method' => 'button',
                'parameters' => [
                    __d('crm', 'Save'),
                    ['type' => 'submit'],
                ],
            ],
            'form_end' => [
                'method' => 'end',
            ],
        ],
    ],
];
echo $this->Lil->form($editForm, 'Crm.ContactsLogs.edit');
?>
<script type="importmap">
{
  "imports": {
    "@tiptap/core": "https://esm.sh/@tiptap/core@2.27.2",
    "@tiptap/starter-kit": "https://esm.sh/@tiptap/starter-kit@2.27.2",
    "@tiptap/extension-underline": "https://esm.sh/@tiptap/extension-underline@2.27.2",
    "@tiptap/extension-subscript": "https://esm.sh/@tiptap/extension-subscript@2.27.2",
    "@tiptap/extension-superscript": "https://esm.sh/@tiptap/extension-superscript@2.27.2",
    "@tiptap/extension-table": "https://esm.sh/@tiptap/extension-table@2.27.2",
    "@tiptap/extension-table-row": "https://esm.sh/@tiptap/extension-table-row@2.27.2",
    "@tiptap/extension-table-header": "https://esm.sh/@tiptap/extension-table-header@2.27.2",
    "@tiptap/extension-table-cell": "https://esm.sh/@tiptap/extension-table-cell@2.27.2",
    "@tiptap/extension-image": "https://esm.sh/@tiptap/extension-image@2.27.2",
    "@tiptap/extension-placeholder": "https://esm.sh/@tiptap/extension-placeholder@2.27.2",
    "tiptap-markdown": "https://esm.sh/tiptap-markdown@0.8.10"
  }
}
</script>
<script type="module">
    import { Editor } from '@tiptap/core';
    import StarterKit from '@tiptap/starter-kit';
    import Underline from '@tiptap/extension-underline';
    import Subscript from '@tiptap/extension-subscript';
    import Superscript from '@tiptap/extension-superscript';
    import Table from '@tiptap/extension-table';
    import TableRow from '@tiptap/extension-table-row';
    import TableHeader from '@tiptap/extension-table-header';
    import TableCell from '@tiptap/extension-table-cell';
    import Image from '@tiptap/extension-image';
    import Placeholder from '@tiptap/extension-placeholder';
    import { Markdown } from 'tiptap-markdown';

    $(document).ready(function() {
        const $textarea = $('#contacts-logs-descript');
        if (!$textarea.length) return;

        $textarea.hide();

        const $wrapper = $('<div class="tiptap-editor-wrapper" style="width:700px;border:1px solid #ccc;"></div>');
        const $toolbar = $('<div class="tiptap-toolbar" style="padding:4px 8px;background:#f5f5f5;border-bottom:1px solid #ccc;display:flex;flex-wrap:wrap;gap:2px;"></div>');
        const $editor = $('<div style="min-height:200px;max-height:350px;overflow-y:auto;padding:8px;outline:none;"></div>');
        $wrapper.append($toolbar).append($editor);
        $wrapper.insertAfter($textarea);

        const editor = new Editor({
            element: $editor[0],
            content: $textarea.val() || '',
            extensions: [
                StarterKit,
                Underline,
                Subscript,
                Superscript,
                Table.configure({ resizable: true }),
                TableRow,
                TableHeader,
                TableCell,
                Image,
                Placeholder.configure({ placeholder: 'Enter text...' }),
                Markdown,
            ],
        });

        editor.on('update', ({ editor }) => {
            $textarea.val(editor.storage.markdown.getMarkdown());
        });

        // Build toolbar buttons
        const btnStyle = 'padding:4px 8px;cursor:pointer;border:1px solid #ccc;background:#fff;border-radius:3px;font-size:13px;';
        const buttons = [
            { cmd: 'toggleBold', icon: 'B', title: 'Bold' },
            { cmd: 'toggleItalic', icon: 'I', title: 'Italic' },
            { cmd: 'toggleUnderline', icon: 'U̲', title: 'Underline' },
            { cmd: 'toggleSubscript', icon: 'X₂', title: 'Subscript' },
            { cmd: 'toggleSuperscript', icon: 'X²', title: 'Superscript' },
            { sep: true },
            { cmd: 'toggleBulletList', icon: '•', title: 'Bullet List' },
            { cmd: 'toggleOrderedList', icon: '1.', title: 'Numbered List' },
            { sep: true },
            { cmd: 'undo', icon: '↩', title: 'Undo' },
            { cmd: 'redo', icon: '↪', title: 'Redo' },
            { sep: true },
            { cmd: 'insertTable', icon: '⊞', title: 'Insert Table' },
        ];

        buttons.forEach(b => {
            if (b.sep) {
                $toolbar.append($('<span style="width:1px;height:20px;background:#ccc;margin:0 4px;"></span>'));
                return;
            }
            const $btn = $('<span style="' + btnStyle + '"></span>')
                .html(b.icon)
                .attr('title', b.title)
                .on('click', function(e) {
                    e.preventDefault();
                    if (b.cmd === 'insertTable') {
                        editor.chain().focus().insertTable({ rows: 3, cols: 3, withHeaderRow: true }).run();
                    } else {
                        editor.chain().focus()[b.cmd]().run();
                    }
                });
            $toolbar.append($btn);
        });
    });
</script>