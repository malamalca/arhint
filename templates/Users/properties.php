<?php
use Cake\Core\Configure;
use Cake\Routing\Router;

$userForm = [
    'title_for_layout' => __('My Properties'),
    'form' => [
        'defaultHelper' => $this->Form,
        'lines' => [
            'form_start' => [
                'method' => 'create',
                'parameters' => [$user, ['type' => 'file']]
            ],
            'id' => [
                'method' => 'hidden',
                'parameters' => ['id']
            ],
            'referer' => [
                'method' => 'hidden',
                'parameters' => ['referer', [
                    'default' => Router::url($this->getRequest()->referer(), true),
                ]]
            ],

            'fs_basics_start' => '<fieldset>',
            'lg_basics' => sprintf('<legend>%s</legend>', __('Basics')),

            'name' => [
                'method' => 'control',
                'parameters' => ['name', [
                    'type' => 'text',
                    'label' => __('Name') . ':',
                    'readonly' => true
                ]]
            ],
            'email' => [
                'method' => 'control',
                'parameters' => ['email', [
                    'type' => 'text',
                    'label' => __('Email') . ':',
                ]]
            ],
            'login_redirect' => [
                'method' => 'control',
                'parameters' => ['login_redirect', [
                    'type' => 'text',
                    'label' => __('Login Redirect') . ':',
                ]]
            ],
            'avatar' => [
                'method' => 'control',
                'parameters' => [
                    'avatar_file',
                    [
                        'type' => 'file',
                        'accept' => '.png',
                        'label' => [
                            'text' => __('Avatar') . ':',
                            'class' => 'active'
                        ],
                        'error' => __('Only png images smaller than 30kB allowed.')
                    ],
                ],
            ],
            'fs_basics_end' => '</fieldset>',

            'fs_emails_start' => '<fieldset>',
            'lg_emails' => sprintf('<legend>%s</legend>', __('Email Notifications')),
            'email_hourly' => [
                'method' => 'control',
                'parameters' => ['email_hourly', [
                    'type' => 'checkbox',
                    'label' => __('Receive Notifications Email'),
                ]],
            ],
            'fs_emails_end' => '</fieldset>',

            'fs_ai_start' => '<fieldset>',
            'lg_ai' => sprintf('<legend>%s</legend>', __('AI Settings')),

            'ai_provider' => [
                'method' => 'control',
                'parameters' => ['ai_provider', [
                    'type' => 'select',
                    'label' => __('Provider') . ':',
                    'options' => [
                        'none' => __('None'),
                        'openai' => __('OpenAI'),
                        'local' => __('Local'),
                    ],
                    'default' => $aiConfig['provider'] ?? 'none',
                    'empty' => '',
                    'id' => 'ai_provider',
                ]],
            ],

            // OpenAI fields (hidden by default)
            'ai_fields_openai_start' => '<div id="ai_fields_openai" style="display:none;">',
            'ai_api_key_openai' => [
                'method' => 'control',
                'parameters' => ['ai_api_key', [
                    'type' => 'text',
                    'label' => __('API Key') . ':',
                    'default' => $aiConfig['api_key'] ?? '',
                ]],
            ],
            'ai_fields_openai_end' => '</div>',

            // Local fields (hidden by default)
            'ai_fields_local_start' => '<div id="ai_fields_local" style="display:none;">',
            'ai_url' => [
                'method' => 'control',
                'parameters' => ['ai_url', [
                    'type' => 'text',
                    'label' => __('API URL') . ':',
                    'default' => $aiConfig['url'] ?? '',
                    'placeholder' => 'http://192.168.68.58:8080/v1/chat/completions',
                ]],
            ],
            'ai_model' => [
                'method' => 'control',
                'parameters' => ['ai_model', [
                    'type' => 'text',
                    'label' => __('Model') . ':',
                    'default' => $aiConfig['model'] ?? '',
                    'placeholder' => __('e.g. qwen'),
                ]],
            ],
            'ai_api_key_local' => [
                'method' => 'control',
                'parameters' => ['ai_api_key', [
                    'type' => 'text',
                    'label' => __('API Key') . ':',
                    'default' => $aiConfig['api_key'] ?? '',
                ]],
            ],
            'ai_native_tool_calls' => [
                'method' => 'control',
                'parameters' => ['ai_native_tool_calls', [
                    'type' => 'checkbox',
                    'label' => __('Native Tool Calls'),
                    'default' => $aiConfig['native_tool_calls'] ?? false,
                ]],
            ],
            'ai_fields_local_end' => '</div>',

            // JS to toggle visibility
            'ai_toggle_js' => '<script>
(function() {
    var provider = document.getElementById("ai_provider");
    var openaiFields = document.getElementById("ai_fields_openai");
    var localFields = document.getElementById("ai_fields_local");

    function toggleAiFields() {
        openaiFields.style.display = "none";
        localFields.style.display = "none";
        if (provider.value === "openai") {
            openaiFields.style.display = "block";
        } else if (provider.value === "local") {
            localFields.style.display = "block";
        }
    }

    provider.addEventListener("change", toggleAiFields);
    toggleAiFields(); // run on page load
})();
</script>',

            'fs_ai_end' => '</fieldset>',

            'fs_properties_start' => '<fieldset>',
            'lg_properties' => sprintf('<legend>%s</legend>', __('Properties')),

            'properties' => [
                'method' => 'control',
                'parameters' => ['properties', [
                    'type' => 'textarea',
                    'label' => __('Properties (JSON)') . ':',
                    'rows' => 6,
                    'error' => [
                        'validJson' => __('Must be valid JSON.'),
                    ],
                ]],
            ],
            'fs_properties_end' => '</fieldset>',

            'fs_login_start' => '<fieldset>',
            'lg_login' => sprintf('<legend>%s</legend>', __('Change Password')),
            'old-passwd' => [
                'method' => 'control',
                'parameters' => ['old_passwd', [
                    'type' => 'password',
                    'label' => __('Current Password') . ':',
                    'value' => '',
                    'error' => [
                        'empty' => __('Must not be empty.'),
                        'match' => __('Passwords do not match.')
                    ]
                ]]
            ],
            'passwd' => [
                'method' => 'control',
                'parameters' => ['passwd', [
                    'type' => 'password',
                    'label' => __('Password') . ':',
                    'value' => ''
                ]]
            ],
            'repeat-passwd' => [
                'method' => 'control',
                'parameters' => ['repeat_passwd', [
                    'type' => 'password',
                    'label' => __('Repeat Password') . ':',
                    'value' => '',
                    'error' => [
                        'empty' => __('Must not be empty.'),
                        'match' => __('Passwords do not match.')
                    ]
                ]]
            ],
            'fs_login_end' => '</fieldset>',

            'submit' => [
                'method' => 'button',
                'parameters' => [
                    __('Save'),
                    ['type' => 'submit'],
                ]
            ],
            'form_end' => [
                'method' => 'end',
                'parameters' => []
            ],
        ]
    ]
];

echo $this->Lil->form($userForm, 'User.properties');
