<?php
    return [
        'formGroup' => '{{input}}{{label}}',
        'inputContainer' => '<div class="input-field {{type}}{{required}}">{{content}}</div>',
        'inputContainerError' => '<div class="input-field error {{type}}{{required}}">{{content}}<span class="helper-text">{{error}}</span></div>',
        'inputInlineContainer' => '<div class="input-field inline {{type}}{{required}}">{{content}}</div>',
        'submitContainer' => '<div class="input-field submit">{{content}}</div>',
        'inputSubmit' => '<button class="btn filled" type="submit">' . __('Submit') . '</button>',

        //'label' => '<label class="active"{{attrs}}>{{text}}</label>',
        'nestingLabel' => '<p>{{hidden}}<label{{attrs}}>{{input}}<span>{{text}}</span></label></p>',
        'textarea' => '<textarea name="{{name}}" class="materialize-textarea"{{attrs}}>{{value}}</textarea>',

        'durationWrapper' => '{{hidden}}{{hours}}{{minutes}}',

        'hidden' => '<input type="hidden" name="{{name}}"{{attrs}} />',

        'popup' => '<ul id="dropdown-{{name}}" class="dropdown-content {{class}}">{{content}}</ul>',
        'popup-item' => '<li{{active}}><a href="{{url}}"{{attrs}}>{{content}}</a></li>',
        'popup-item-plain' => '<li class="divider" tabindex="-1"></li>',

        'tablestart' => '<table class="index {{class}}"{{attrs}}>',
        'tableend' => '</table>',

        'tableheadstart' => '<thead{{attrs}}>',
        'tableheadrow' => '<tr class="{{class}}"{{attrs}}>',
        'tablebodyrow' => '<tr class="{{class}}"{{attrs}}>',
        'tablefootrow' => '<tr class="{{class}}"{{attrs}}>',

        'usersPicker' => '<div class="input-field userspicker">{{content}}</div>',

        'button' => '<button class="btn filled"{{attrs}}>{{text}}</button>',

        'linkedit' => '<a href="{{url}}" class="btn-small filled {{class}}" role="button"><i class="material-icons">edit</i></a>',
        'linkdelete' => '<a href="{{url}}" class="btn-small filled {{class}}" role="button" onclick="return confirm(\'{{confirmation}}\');"><i class="material-icons">delete</i></a>',
        'linkpopup' => '<a href="{{url}}" id="dropdown-{{name}}" class="dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"{{attrs}}>{{content}}</a>'
    ];
