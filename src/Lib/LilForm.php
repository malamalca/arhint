<?php
declare(strict_types=1);

namespace App\Lib;

/**
 * LilForm Helper class for passing forms by reference.
 *
 * @category Class
 * @package  Lil
 * @author   Arhim d.o.o. <info@arhim.si>
 * @license  http://www.gnu.org/copyleft/gpl.html GNU General Public License
 * @link     http://www.arhint.si
 */
class LilForm
{
    /**
     * @var array<string, mixed>
     */
    public ?array $menu = null;
    public ?string $title = null;
    /**
     * @var array<string, mixed>
     */
    public ?array $form = null;
    public ?string $pre = null;
    public ?string $post = null;
}
