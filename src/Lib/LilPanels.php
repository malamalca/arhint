<?php
declare(strict_types=1);

namespace App\Lib;

use Cake\Datasource\EntityInterface;

class LilPanels
{
    /**
     * @var array<string, mixed>
     */
    public ?array $menu = null;
    public ?string $title = null;
    /**
     * @var array<string, mixed>
     */
    public ?array $actions = null;
    public ?string $pre = null;
    public ?string $post = null;
    public ?EntityInterface $entity = null;
    /**
     * @var array<string, mixed>|array<int, mixed>
     */
    public array $panels = [];
}
