<?php
    use Cake\Core\Configure;

    $this->set('pageTitle', Configure::read('App.title'));
    echo '<div class="row">' . $this->Lil->panels($panels) . '</div>';
?>
<?php $this->Html->script('aiChat.js', ['block' => 'script']) ?>
<style>
#DashboardAIAssistant {
    padding-left: 1rem;
    padding-right: 1rem;
}
#DashboardAIAssistant .ai-assistant-messages {
    max-height: 320px;
    overflow-y: auto;
    margin-bottom: .75rem;
    display: flex;
    flex-direction: column;
    gap: .5rem;
}
#DashboardAIAssistant .ai-message {
    display: flex;
    flex-direction: column;
    max-width: 90%;
}
#DashboardAIAssistant .ai-message--user {
    align-self: flex-end;
    align-items: flex-end;
}
#DashboardAIAssistant .ai-message--assistant,
#DashboardAIAssistant .ai-message--error {
    align-self: flex-start;
    align-items: flex-start;
}
#DashboardAIAssistant .ai-message__role {
    font-size: .7rem;
    font-weight: 600;
    text-transform: uppercase;
    opacity: .6;
    margin-bottom: 2px;
}
#DashboardAIAssistant .ai-message__body {
    background: rgba(0,0,0,.06);
    border-radius: 8px;
    padding: .4rem .7rem;
    white-space: normal;
    word-break: break-word;
}
#DashboardAIAssistant .ai-message__body p {
    margin: .2rem 0;
}
#DashboardAIAssistant .ai-message__body p:first-child { margin-top: 0; }
#DashboardAIAssistant .ai-message__body p:last-child  { margin-bottom: 0; }
#DashboardAIAssistant .ai-message__body ul,
#DashboardAIAssistant .ai-message__body ol {
    margin: .2rem 0 .2rem 1.2rem;
    padding: 0;
}
#DashboardAIAssistant .ai-message__body li {
    margin: .1rem 0;
}
#DashboardAIAssistant .ai-message__body pre {
    background: rgba(0,0,0,.08);
    border-radius: 4px;
    padding: .3rem .5rem;
    overflow-x: auto;
    font-size: .85em;
    margin: .3rem 0;
}
#DashboardAIAssistant .ai-message__body code {
    background: rgba(0,0,0,.08);
    border-radius: 3px;
    padding: .1em .3em;
    font-size: .9em;
}
#DashboardAIAssistant .ai-message__body pre code {
    background: none;
    padding: 0;
}
#DashboardAIAssistant .ai-message--user .ai-message__body {
    background: rgba(var(--primary-color-rgb, 63,81,181), .15);
}
#DashboardAIAssistant .ai-message--error .ai-message__body {
    background: rgba(200,0,0,.1);
    color: #c00;
}
#DashboardAIAssistant .ai-assistant-actions {
    margin-top: .5rem;
    display: flex;
    gap: .5rem;
    align-items: center;
}
#DashboardAIAssistant .ai-thinking {
    display: flex;
    gap: 4px;
    align-items: center;
    padding: .5rem .7rem;
}
#DashboardAIAssistant .ai-thinking span {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    background: currentColor;
    opacity: .4;
    animation: ai-bounce 1.2s infinite ease-in-out;
}
#DashboardAIAssistant .ai-thinking span:nth-child(1) { animation-delay: 0s; }
#DashboardAIAssistant .ai-thinking span:nth-child(2) { animation-delay: .2s; }
#DashboardAIAssistant .ai-thinking span:nth-child(3) { animation-delay: .4s; }
@keyframes ai-bounce {
    0%, 80%, 100% { transform: scale(.6); opacity: .4; }
    40%            { transform: scale(1);  opacity: 1;   }
}
#DashboardAIAssistant textarea {
    width: 100%;
    box-sizing: border-box;
    resize: vertical;
}
</style>