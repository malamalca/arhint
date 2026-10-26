<?php
/**
 * Default layout of HTML e-mails: branded header, content card and footer.
 *
 * Layout is table based with inline styles so it renders in e-mail clients that ignore or strip
 * <style> blocks. The stylesheet in `webroot/css/email.css` is still included for the content
 * (reports with tables), therefore paddings and the heading size of the layout cells are marked
 * `!important` to win over the generic `td` and `h1` rules in that file.
 *
 * @var \App\View\AppView $this
 * @var string|null $emailTitle Heading of the message (its subject).
 */
use Cake\Core\Configure;
use Cake\Routing\Router;

$appTitle = (string)Configure::read('App.title', 'ARHIM Intranet');
// The `title` block would default to the template path ("Email/html"), so the mailer passes the subject.
$pageTitle = trim(strip_tags((string)($emailTitle ?? '')));
$homeUrl = Router::url('/', true);
$homeLabel = (string)(parse_url($homeUrl, PHP_URL_HOST) ?: $homeUrl);
?>
<!DOCTYPE html>
<html lang="<?= h(substr((string)Configure::read('App.defaultLocale', 'sl'), 0, 2)) ?>" xmlns:o="urn:schemas-microsoft-com:office:office">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="x-apple-disable-message-reformatting">
    <title><?= h($pageTitle !== '' ? $pageTitle . ' — ' . $appTitle : $appTitle) ?></title>
    <!--[if mso]>
    <xml><o:OfficeDocumentSettings><o:PixelsPerInch>96</o:PixelsPerInch></o:OfficeDocumentSettings></xml>
    <![endif]-->
    <style>
        body { margin: 0; padding: 0; -webkit-text-size-adjust: 100%; -ms-text-size-adjust: 100%; }
        table { mso-table-lspace: 0pt; mso-table-rspace: 0pt; }
        .email-content p { margin: 0 0 16px; }
        .email-content a { color: #006495; text-decoration: underline; }
        .email-content img { max-width: 100%; height: auto; border: 0; }
        @media screen and (max-width: 640px) {
            .email-outer { padding: 16px 8px !important; }
            .email-padding { padding-left: 20px !important; padding-right: 20px !important; }
        }
<?= file_get_contents(WWW_ROOT . 'css' . DS . 'email.css') ?>
    </style>
</head>
<?php // phpcs:disable Generic.Files.LineLength -- inline-styled email markup ?>
<body bgcolor="#f3f6f9" style="margin: 0; padding: 0; width: 100%; background-color: #f3f6f9; color: #1c2a33;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" bgcolor="#f3f6f9" style="width: 100%; background-color: #f3f6f9;">
        <tr>
            <td class="email-outer" align="center" style="padding: 32px 16px !important;">
                <!--[if mso]><table role="presentation" width="640" cellpadding="0" cellspacing="0" border="0"><tr><td><![endif]-->
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="width: 100%; max-width: 640px;">
                    <tr>
                        <td class="email-padding" bgcolor="#004b71" style="padding: 22px 32px !important; background-color: #004b71; border-radius: 12px 12px 0 0; font-family: Arial, Helvetica, sans-serif; font-size: 20px; line-height: 26px; font-weight: bold;">
                            <a href="<?= h($homeUrl) ?>" style="color: #ffffff; text-decoration: none;"><?= h($appTitle) ?></a>
                        </td>
                    </tr>
                    <tr>
                        <td height="4" bgcolor="#0f7eb8" style="height: 4px; background-color: #0f7eb8; font-size: 0; line-height: 0; padding: 0 !important;">&nbsp;</td>
                    </tr>
                    <tr>
                        <td class="email-padding email-content" bgcolor="#ffffff" style="padding: 28px 32px 20px !important; background-color: #ffffff; border: 1px solid #d9e2ea; border-top: 0; border-radius: 0 0 12px 12px; color: #1c2a33; font-family: Arial, Helvetica, sans-serif; font-size: 15px; line-height: 24px; overflow-wrap: anywhere; word-wrap: break-word;">
                            <?php if ($pageTitle !== '') : ?>
                            <h1 style="margin: 0 0 20px; color: #004b71; font-family: Arial, Helvetica, sans-serif; font-size: 24px !important; line-height: 30px; font-weight: bold;"><?= h($pageTitle) ?></h1>
                            <?php endif; ?>
                            <?= $this->fetch('content') ?>
                        </td>
                    </tr>
                    <tr>
                        <td class="email-padding" align="center" style="padding: 20px 32px 0 !important; color: #5b6b77; font-family: Arial, Helvetica, sans-serif; font-size: 12px; line-height: 18px;">
                            <p style="margin: 0 0 4px; font-weight: bold;"><?= h($appTitle) ?></p>
                            <p style="margin: 0;"><a href="<?= h($homeUrl) ?>" style="color: #006495; text-decoration: underline;"><?= h($homeLabel) ?></a></p>
                        </td>
                    </tr>
                </table>
                <!--[if mso]></td></tr></table><![endif]-->
            </td>
        </tr>
    </table>
</body>
</html>
