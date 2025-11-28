<?php
    return [
        'formGroup' => '{{input}}{{label}}',
        'inputContainer' => '<div class="input-field {{type}}{{required}}">{{content}}</div>',
        'inputContainerError' => '<div class="input-field error {{type}}{{required}}">{{content}}<span class="helper-text">{{error}}</span></div>',
        'inputInlineContainer' => '<div class="input-field inline {{type}}{{required}}">{{content}}</div>',
        'submitContainer' => '<div class="input-field submit">{{content}}</div>',
        'inputSubmit' => '<button class="btn waves-effect waves-light" type="submit">' . __('Submit') . '</button>',

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

        'button' => '<button class="btn btn-default"{{attrs}}>{{text}}</button>',
        /*'input' => '<input type="{{type}}" name="{{name}}" class="form-control"{{attrs}}/>',
        'formStart' => '<form class="form-horizontal"{{attrs}}>',
        'inputSubmit' => '<input type="{{type}}" class="btn btn-primary"{{attrs}}/>',
        'inputContainer' => '<div class="form-group bmd-form-group {{type}}{{required}}">{{content}}</div>',
        'inputContainerError' => '<div class="form-group bmd-form-group has-error {{type}}{{required}}">{{content}}<span class="help-block">{{error}}</span></div>',
        'submitContainer' => '<div class="form-group">{{content}}</div>',

        //'file' => '<div class="custom-file"><input type="file" name="{{name}}" class="custom-file-input" {{attrs}}><label class="custom-file-label" for="customFile">Choose file</label></div',

        // this is for costum widget
        'hidden' => '<input type="hidden" name="{{name}}" class="hidden{{class}}"/>',

        'label' => '<label class="bmd-label-static"{{attrs}}>{{text}}</label>',
        'select' => '<select class="custom-select" data-style="btn" name="{{name}}"{{attrs}}>{{content}}</select>',

        'error' => '<div class="text-danger">{{content}}</div>',

        'navbar-menu' => '<ul class="navbar-nav {{class}}">{{items}}</ul>',
        'navbar-item' => '<li class="nav-item"><a href="{{url}}" class="nav-link btn btn-sm btn-outline-secondary {{class}}"{{attrs}}>{{content}}</a></li>',
        'navbar-submenu' => '<li class="nav-item dropdown"><a href="#" id="navbarDropdownMenu{{subid}}" class="nav-link dropdown-toggle btn btn-sm btn-outline-secondary {{class}}" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" {{attrs}}>{{content}}<b class="carret"></b></a><ul class="navbar-sub dropdown-menu" aria-labelledby="navbarDropdownMenu{{subid}}">{{subitems}}</ul></li>',

        'sidebar-menu' => '<ul class="nav {{class}}">{{items}}</ul>',
        'sidebar-item' => '<li class="nav-item"><a href="{{url}}" class="nav-link {{class}}"{{attrs}}><i class="material-icons">{{icon}}</i><p>{{content}}</p></a></li>',
        'sidebar-active' => '<li class="nav-item active"><a href="{{url}}" class="nav-link {{class}}"{{attrs}}><i class="material-icons">{{icon}}</i><p>{{content}}</p></a></li>',
        'sidebar-submenu' => '<li class="nav-item"><a href="#{{subid}}" class="nav-link" data-toggle="collapse" data-target="#{{subid}}"{{attrs}}><i class="material-icons">{{icon}}</i><p>{{content}}<b class="carret"></b></p></a><div class="collapse" id="{{subid}}"><ul class="nav nav-sub">{{subitems}}</ul></div></li>',
        'sidebar-expanded' => '<li class="nav-item"><a href="#{{subid}}" class="nav-link" data-toggle="collapse" data-target="#{{subid}}"{{attrs}}><i class="material-icons">{{icon}}</i><p>{{content}}<b class="carret"></b></p></a><div class="collapse show" id="{{subid}}"><ul class="nav nav-sub">{{subitems}}</ul></div></li>',

        */

        'linkedit' => '<a href="{{url}}" class="btn-small waves-effect waves-light waves-circle {{class}}" role="button"><i class="material-icons">edit</i></a>',
        'linkdelete' => '<a href="{{url}}" class="btn-small waves-effect waves-light waves-circle {{class}}" role="button" onclick="return confirm(\'{{confirmation}}\');"><i class="material-icons">delete</i></a>',
        'linkpopup' => '<a href="{{url}}" id="dropdown-{{name}}" class="dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"{{attrs}}>{{content}}</a>'
    ];
