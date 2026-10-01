<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

class Flag extends Entity
{
    protected array $_accessible = [
        'flag_key' => true,
        'name' => true,
        'description' => true,
        'enabled' => true,
        'config_value' => true,
        'created' => true,
        'modified' => true,
    ];
}