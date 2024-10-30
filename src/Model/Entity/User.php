<?php
declare(strict_types=1);

namespace App\Model\Entity;

use App\AppPluginsEnum;
use Authentication\IdentityInterface as AuthenticationIdentity;
use Authentication\PasswordHasher\DefaultPasswordHasher;
use Authorization\AuthorizationServiceInterface;
use Authorization\IdentityInterface as AuthorizationIdentity;
use Authorization\Policy\ResultInterface;
use Cake\ORM\Entity;

/**
 * User Entity
 *
 * @property string $id
 * @property string|null $company_id
 * @property string $name
 * @property string $username
 * @property string|null $passwd
 * @property string $email
 * @property string|null $reset_key
 * @property int $access
 * @property int $privileges
 * @property bool $active
 * @property string|null $login_redirect
 * @property string|null $avatar
 * @property string|null $properties
 * @property \Cake\I18n\DateTime|null $created
 * @property \Cake\I18n\DateTime|null $modified
 *
 * @property \App\Model\Entity\Company $company
 */
class User extends Entity implements AuthorizationIdentity, AuthenticationIdentity
{
    /**
     * Fields that can be mass assigned using newEntity() or patchEntity().
     *
     * Note that when '*' is set to true, this allows all unspecified fields to
     * be mass assigned. For security purposes, it is advised to set '*' to false
     * (or remove it), and explicitly make individual fields accessible as needed.
     *
     * @var array<string, bool>
     */
    protected array $_accessible = [
        'company_id' => true,
        'name' => true,
        'username' => true,
        'passwd' => true,
        'email' => true,
        'reset_key' => true,
        'privileges' => true,
        'access' => true,
        'active' => true,
        'avatar' => true,
        'login_redirect' => true,
        'created' => true,
        'modified' => true,
        'company' => true,
    ];

    /**
     * Fields that are excluded from JSON versions of the entity.
     *
     * @var array<int, string>
     */
    protected array $_hidden = [
        'passwd',
    ];

    protected AuthorizationServiceInterface $authorization;

    /**
     * Entity to string magic method
     *
     * @return string
     */
    public function __toString(): string
    {
        return (string)$this->name;
    }

    /**
     * Set password method.
     *
     * @param string $password Users password.
     * @return string
     */
    protected function _setPasswd(string $password): ?string
    {
        $ret = (new DefaultPasswordHasher())->hash($password);

        return $ret;
    }

    /**
     * Authentication\IdentityInterface method
     *
     * @return string
     */
    public function getIdentifier(): string
    {
        return $this->id;
    }

    /**
     * Authentication\IdentityInterface method
     * ArrayAccess|array
     *
     * @return self
     */
    public function getOriginalData(): User
    {
        return $this;
    }

    /**
     * Setter to be used by the middleware.
     *
     * @param \Authorization\AuthorizationServiceInterface $service Service interface
     * @return self
     */
    public function setAuthorization(AuthorizationServiceInterface $service): self
    {
        $this->authorization = $service;

        return $this;
    }

    /**
     * Authorization\IdentityInterface method
     */
    public function can(string $action, mixed $resource): bool
    {
        return $this->authorization->can($this, $action, $resource);
    }

    /**
     * Authorization\IdentityInterface method
     */
    public function canResult(string $action, mixed $resource): ResultInterface
    {
        return $this->authorization->canResult($this, $action, $resource);
    }

    /**
     * Authorization\IdentityInterface method
     */
    public function applyScope(string $action, mixed $resource, mixed ...$optionalArgs): mixed
    {
        return $this->authorization->applyScope($this, $action, $resource);
    }

    /**
     * Checks user's role.
     *
     * @param string $role User role.
     * @return bool
     */
    public function hasRole(string $role): bool
    {
        if ($role == 'root') {
            return $this->privileges <= 2;
        }
        if ($role == 'admin') {
            return $this->privileges <= 5;
        }
        if ($role == 'editor') {
            return $this->privileges <= 10;
        }

        return false;
    }

    /**
     * Checks user's access to plugins
     *
     * @param App\AppPluginsEnum $plugin Plugin
     * @return bool
     */
    public function hasAccess(AppPluginsEnum $plugin): bool
    {
        if ($this->privileges <= 5) {
            return true;
        }

        return ($this->access & (1 << $plugin->getOrdinal())) > 0;
    }

    /**
     * Returns users plugins
     *
     * @param string $pluginName Plugin name
     * @return bool
     */
    public function canUsePlugin(string $pluginName): bool
    {
        return true;
    }

    /**
     * Returns users avatar as image
     *
     * @return string|bool
     */
    public function getAvatarImage(): string|bool
    {
        $ret = false;
        $avatarSize = 90;

        if (!empty($this->avatar)) {
            $im = imagecreatefromstring(base64_decode($this->avatar));
            if (!$im) {
                return false;
            }
            $width = imagesx($im);
            $height = imagesy($im);

            if ($width > $height) {
                $newHeight = $avatarSize;
                $newWidth = (int)floor($width * $newHeight / $height);
                $cropX = (int)ceil(($width - $height) / 2);
                $cropY = 0;
            } else {
                $newWidth = $avatarSize;
                $newHeight = (int)floor($height * $newWidth / $width);
                $cropX = 0;
                $cropY = (int)ceil(($height - $width) / 2);
            }

            $newImage = imagecreatetruecolor($avatarSize, $avatarSize);
            if (!$newImage) {
                return false;
            }
            imagealphablending($newImage, false);
            imagesavealpha($newImage, true);
            $transparent = (int)imagecolorallocatealpha($newImage, 255, 255, 255, 127);
            imagefilledrectangle($newImage, 0, 0, $avatarSize, $avatarSize, $transparent);
            imagecopyresampled($newImage, $im, 0, 0, $cropX, $cropY, $newWidth, $newHeight, $width, $height);
            imagedestroy($im);

            ob_start();
            imagepng($newImage);
            $ret = ob_get_contents();
            ob_end_clean();
            imagedestroy($newImage);
        }

        return $ret;
    }

    /**
     * Properties field accessor
     *
     * @param string $key Properties key
     * @param mixed $default Default return value
     * @return mixed
     * @psalm-suppress PossiblyUnusedMethod
     */
    public function getProperty(string $key, mixed $default = null): mixed
    {
        if ($this->properties !== null) {
            $properties = json_decode($this->properties);
            if (isset($properties->$key)) {
                return $properties->$key;
            }
        }

        return $default;
    }
}
